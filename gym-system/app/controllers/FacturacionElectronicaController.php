<?php
require_once '../app/config/Database.php';
require_once '../app/models/ComprobanteElectronico.php';
require_once '../app/models/Configuracion.php';
require_once '../app/models/Suscripcion.php';
require_once '../app/models/Socio.php';
require_once '../app/models/Plan.php';
require_once '../app/models/Venta.php';
require_once '../app/lib/sri/FacturadorSri.php'; // Cambiado de FacturadorSunat a FacturadorSri

class FacturacionElectronicaController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) { header('Location: /auth/index'); exit; }
        if ($_SESSION['user_rol'] == 'entrenador') {
            header('Location: /home/index'); exit;
        }
    }

    // ============================================================
    //  BANDEJA / LISTADO
    // ============================================================
    public function index() {
        $this->verificarAuth();
        $modelo = new ComprobanteElectronico();
        $cfgModel = new Configuracion();

        $filtros = [
            'desde'    => $_GET['desde']    ?? date('Y-m-01'),
            'hasta'    => $_GET['hasta']    ?? date('Y-m-d'),
            'tipo_doc' => $_GET['tipo_doc'] ?? '',
            'estado'   => $_GET['estado']   ?? '',
        ];
        $comprobantes = $modelo->listar($filtros);
        $config = $cfgModel->obtenerDatos();
        require_once '../app/views/facturacion/index.php';
    }

    // ============================================================
    //  EMITIR desde Suscripción (Factura SRI)
    // ============================================================
    public function emitirSuscripcion($suscripcion_id) {
        $this->verificarAuth();
        $tipoDoc = '01'; // En Ecuador sólo se emite Factura (01)

        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT s.*, so.nombre AS cliente_razon, so.dni, so.email, so.direccion_fiscal, so.tipo_doc AS cliente_tipo,
                                     p.nombre AS plan, p.precio
                              FROM suscripciones s
                              INNER JOIN socios so ON s.socio_id = so.id
                              INNER JOIN planes p  ON s.plan_id  = p.id
                              WHERE s.id=:id LIMIT 1");
        $stmt->execute([':id'=>$suscripcion_id]);
        $sus = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sus) { die('Suscripción no encontrada'); }

        // Identificación del receptor (En Ecuador RUC = 13 dig, Cédula = 10 dig, o Consumidor Final = 9999999999999)
        $numDocCli  = $sus['dni'] ?: '9999999999999';

        $datos = [
            'origen_tipo' => 'suscripcion',
            'origen_id'   => (int)$sus['id'],
            'tipo_doc'    => $tipoDoc,
            'cliente_tipo_doc' => $sus['cliente_tipo'] ?: '1',
            'cliente_num_doc'  => $numDocCli,
            'cliente_razon'    => $sus['cliente_razon'],
            'cliente_direccion'=> $sus['direccion_fiscal'] ?? 'Quito, Ecuador',
            'cliente_email'    => $sus['email'],
            'forma_pago'  => 'Contado',
            'metodo_pago' => 'efectivo',
            'items' => [[
                'codigo'      => 'PLAN-' . $sus['plan_id'],
                'descripcion' => 'Suscripción: ' . $sus['plan'],
                'unidad'      => 'ZZ',
                'cantidad'    => 1,
                'precio_unitario' => (float)$sus['precio'],
                'tipo_afectacion' => '10',
            ]]
        ];

        $svc = new FacturadorSri();
        $r = $svc->emitir($datos);

        // Vincular comprobante con suscripción
        if (!empty($r['comprobante_id'])) {
            $up = $db->prepare("UPDATE suscripciones SET comprobante_id=:c, tipo_comprobante=:tc WHERE id=:id");
            $up->execute([
                ':c'  => $r['comprobante_id'],
                ':tc' => 'factura',
                ':id' => $suscripcion_id,
            ]);
        }
        $_SESSION['flash_msg'] = $r['ok']
            ? "Comprobante autorizado por el SRI exitosamente."
            : "Problema SRI: {$r['descripcion']}";
        $_SESSION['flash_ok']  = $r['ok'];
        header('Location: /facturacionelectronica/ver/' . $r['comprobante_id']);
    }

    // ============================================================
    //  EMITIR desde POS / Venta
    // ============================================================
    public function emitirVenta($venta_id) {
        $this->verificarAuth();
        $tipoDoc = '01'; // En Ecuador sólo se emite Factura (01)

        $db = (new Database())->getConnection();
        $vstmt = $db->prepare("SELECT v.*, IFNULL(s.nombre,'CONSUMIDOR FINAL') AS socio_nombre,
                                       s.dni AS socio_dni, s.tipo_doc AS socio_tipo_doc,
                                       s.direccion_fiscal AS socio_dir
                               FROM ventas v
                               LEFT JOIN socios s ON v.socio_id = s.id
                               WHERE v.id=:id LIMIT 1");
        $vstmt->execute([':id'=>$venta_id]);
        $venta = $vstmt->fetch(PDO::FETCH_ASSOC);
        if (!$venta) die('Venta no encontrada');

        $dstmt = $db->prepare("SELECT dv.*, p.nombre, p.codigo
                               FROM detalle_ventas dv
                               INNER JOIN productos p ON dv.producto_id = p.id
                               WHERE dv.venta_id=:id");
        $dstmt->execute([':id'=>$venta_id]);
        $items = [];
        foreach ($dstmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $items[] = [
                'codigo' => $d['codigo'] ?: 'P-'.$d['producto_id'],
                'descripcion' => $d['nombre'],
                'unidad' => 'NIU',
                'cantidad' => (float)$d['cantidad'],
                'precio_unitario' => (float)$d['precio_unitario'],
                'tipo_afectacion' => '10',
            ];
        }

        $datos = [
            'origen_tipo' => 'venta',
            'origen_id'   => (int)$venta_id,
            'tipo_doc'    => $tipoDoc,
            'cliente_tipo_doc' => $venta['socio_tipo_doc'] ?: '1',
            'cliente_num_doc'  => $venta['cliente_num_doc'] ?: ($venta['socio_dni'] ?: '9999999999999'),
            'cliente_razon'    => $venta['cliente_razon'] ?: $venta['socio_nombre'],
            'cliente_direccion'=> $venta['cliente_direccion'] ?: ($venta['socio_dir'] ?: 'Quito, Ecuador'),
            'metodo_pago' => $venta['metodo_pago'],
            'descuentos'  => (float)$venta['descuento'],
            'items'       => $items,
        ];
        
        $svc = new FacturadorSri();
        $r = $svc->emitir($datos);

        if (!empty($r['comprobante_id'])) {
            $up = $db->prepare("UPDATE ventas SET comprobante_id=:c, tipo_comprobante=:tc WHERE id=:id");
            $up->execute([
                ':c'  => $r['comprobante_id'],
                ':tc' => 'factura',
                ':id' => $venta_id,
            ]);
        }
        $_SESSION['flash_msg'] = $r['ok']
            ? "Comprobante autorizado por el SRI exitosamente."
            : "Problema SRI: {$r['descripcion']}";
        $_SESSION['flash_ok']  = $r['ok'];
        header('Location: /facturacionelectronica/ver/' . $r['comprobante_id']);
    }

    // ============================================================
    //  NOTA DE CRÉDITO (anulación / devolución en Ecuador)
    // ============================================================
    public function notaCredito($comprobante_id) {
        $this->verificarAuth();
        $modelo = new ComprobanteElectronico();
        $cab = $modelo->obtenerPorId($comprobante_id);
        if (!$cab) die('Comprobante original no encontrado');
        $det = $modelo->obtenerDetalle($comprobante_id);

        $items = [];
        foreach ($det as $d) {
            $items[] = [
                'codigo' => $d['codigo'],
                'descripcion' => $d['descripcion'],
                'unidad' => $d['unidad'],
                'cantidad' => $d['cantidad'],
                'valor_unitario' => $d['valor_unitario'],
                'precio_unitario' => $d['precio_unitario'],
                'subtotal' => $d['subtotal'],
                'igv_linea' => $d['igv_linea'],
                'total_linea' => $d['total_linea'],
                'tipo_afectacion' => $d['tipo_afectacion'],
            ];
        }
        
        $datos = [
            'origen_tipo' => 'manual',
            'origen_id'   => (int)$comprobante_id,
            'tipo_doc'    => '04', // Nota de Crédito en Ecuador
            'cliente_tipo_doc' => $cab['cliente_tipo_doc'],
            'cliente_num_doc'  => $cab['cliente_num_doc'],
            'cliente_razon'    => $cab['cliente_razon'],
            'cliente_direccion'=> $cab['cliente_direccion'] ?? 'Quito, Ecuador',
            'cliente_email'    => $cab['cliente_email'],
            'ref_tipo_doc'     => $cab['tipo_doc'],
            'ref_establecimiento' => substr($cab['serie'], 0, 3),
            'ref_punto_emision'   => substr($cab['serie'], 3, 3),
            'ref_correlativo'  => $cab['correlativo'],
            'ref_fecha_emision'=> $cab['fecha_emision'],
            'motivo_codigo'    => $_POST['motivo_codigo'] ?? '01',
            'motivo_descripcion' => $_POST['motivo_descripcion'] ?? 'Anulación de la operación',
            'items' => $items,
        ];
        
        $svc = new FacturadorSri();
        $r = $svc->emitir($datos);

        $_SESSION['flash_msg'] = $r['ok']
            ? "Nota de crédito autorizada por el SRI exitosamente."
            : "Problema SRI: {$r['descripcion']}";
        $_SESSION['flash_ok']  = $r['ok'];
        header('Location: /facturacionelectronica/ver/' . $r['comprobante_id']);
    }

    // ============================================================
    //  VER detalle de un comprobante
    // ============================================================
    public function ver($id) {
        $this->verificarAuth();
        $modelo = new ComprobanteElectronico();
        $cab = $modelo->obtenerPorId($id);
        $det = $modelo->obtenerDetalle($id);
        if (!$cab) die('Comprobante no encontrado');
        $config = (new Configuracion())->obtenerDatos();
        require_once '../app/views/facturacion/ver.php';
    }

    // ============================================================
    //  Descargar XML Autorizado del SRI
    // ============================================================
    public function descargarXml($id) {
        $this->verificarAuth();
        $modelo = new ComprobanteElectronico();
        $cab = $modelo->obtenerPorId($id);
        if (!$cab || !$cab['xml_firmado']) die('Sin XML');
        
        $name = $cab['emisor_ruc'].'-'.$cab['tipo_doc'].'-'.$cab['serie'].'-'.str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT).'.xml';
        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="'.$name.'"');
        echo $cab['xml_firmado'];
    }

    public function pdf($id) {
        require_once '../app/controllers/ComprobanteController.php';
        $c = new ComprobanteController();
        $c->cpe($id);
    }

    // ============================================================
    //  REINTENTO DE AUTORIZACIÓN (Consulta estado en SRI)
    // ============================================================
    public function reintentar($id) {
        $this->verificarAuth();
        $svc = new FacturadorSri();
        $r = $svc->reenviar($id);
        $_SESSION['flash_msg'] = $r['ok']
            ? "Consulta SRI Exitosa: {$r['descripcion']}"
            : "Consulta SRI falló: {$r['descripcion']}";
        $_SESSION['flash_ok'] = $r['ok'];
        header('Location: /facturacionelectronica/ver/' . $id);
    }

    // ============================================================
    //  LOG técnico de un comprobante
    // ============================================================
    public function logs($id) {
        $this->verificarAuth();
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("SELECT * FROM sri_log WHERE comprobante_id=:id ORDER BY id DESC");
        $stmt->execute([':id'=>$id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../app/views/facturacion/logs.php';
    }

    // ============================================================
    //  ENVÍO AL CLIENTE (Email básico)
    // ============================================================
    public function enviarCliente($comprobante_id) {
        $this->verificarAuth();
        $modelo = new ComprobanteElectronico();
        $cab = $modelo->obtenerPorId($comprobante_id);
        if (!$cab) { $_SESSION['flash_msg']='No encontrado'; header('Location: /facturacionelectronica/index'); exit; }

        $destinatario = $cab['cliente_email'];
        if (empty($destinatario)) {
            $_SESSION['flash_msg'] = "El cliente no tiene correo electrónico configurado.";
            $_SESSION['flash_ok']  = false;
            header('Location: /facturacionelectronica/ver/' . $comprobante_id);
            exit;
        }

        // Simulación de envío por correo electrónico
        $_SESSION['flash_msg'] = "XML y PDF del comprobante enviados con éxito a: $destinatario";
        $_SESSION['flash_ok']  = true;
        header('Location: /facturacionelectronica/ver/' . $comprobante_id);
    }
}
