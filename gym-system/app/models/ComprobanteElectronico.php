<?php
class ComprobanteElectronico {
    private $conn;
    private $tabla   = 'comprobantes_electronicos';
    private $detalle = 'comprobantes_detalle';
    private $series  = 'sri_series'; // Cambiado de sunat_series a sri_series

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function getConnection() { return $this->conn; }

    /** Reserva el siguiente correlativo de la serie indicada. */
    public function siguienteCorrelativo($tipoDoc, $serie) {
        $this->conn->beginTransaction();
        try {
            $stmt = $this->conn->prepare("SELECT correlativo FROM {$this->series}
                                          WHERE tipo_doc=:t AND serie=:s
                                          FOR UPDATE");
            $stmt->execute([':t'=>$tipoDoc, ':s'=>$serie]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->conn->rollBack();
                throw new Exception("Serie no configurada: $tipoDoc - $serie");
            }
            $nuevo = ((int)$row['correlativo']) + 1;
            $upd = $this->conn->prepare("UPDATE {$this->series} SET correlativo=:n
                                         WHERE tipo_doc=:t AND serie=:s");
            $upd->execute([':n'=>$nuevo, ':t'=>$tipoDoc, ':s'=>$serie]);
            $this->conn->commit();
            return $nuevo;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) $this->conn->rollBack();
            throw $e;
        }
    }

    /** Devuelve la serie por defecto para un tipo doc (la primera activa). */
    public function serieDefault($tipoDoc) {
        $stmt = $this->conn->prepare("SELECT serie FROM {$this->series}
                                      WHERE tipo_doc=:t AND estado='activo'
                                      ORDER BY id ASC LIMIT 1");
        $stmt->execute([':t'=>$tipoDoc]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['serie'] : null;
    }

    /** Inserta cabecera + detalle. Devuelve el id insertado. */
    public function guardar(array $cab, array $items) {
        $sql = "INSERT INTO {$this->tabla}
            (origen_tipo, origen_id, tipo_doc, serie, correlativo, clave_acceso, fecha_emision, hora_emision,
             emisor_ruc, emisor_razon,
             cliente_tipo_doc, cliente_num_doc, cliente_razon, cliente_direccion, cliente_email,
             moneda, gravadas, inafectas, exoneradas, gratuitas, descuentos, igv, total, total_letras,
             forma_pago, metodo_pago,
             ref_tipo_doc, ref_serie, ref_correlativo, motivo_codigo, motivo_descripcion,
             estado_sunat, estado_sri, usuario_id)
            VALUES
            (:origen_tipo,:origen_id,:tipo_doc,:serie,:correlativo,:clave_acceso,:fecha_emision,:hora_emision,
             :emisor_ruc,:emisor_razon,
             :cliente_tipo_doc,:cliente_num_doc,:cliente_razon,:cliente_direccion,:cliente_email,
             :moneda,:gravadas,:inafectas,:exoneradas,:gratuitas,:descuentos,:igv,:total,:total_letras,
             :forma_pago,:metodo_pago,
             :ref_tipo_doc,:ref_serie,:ref_correlativo,:motivo_codigo,:motivo_descripcion,
             'pendiente','pendiente',:usuario_id)";
             
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            ':origen_tipo'  => $cab['origen_tipo'],
            ':origen_id'    => $cab['origen_id'] ?? null,
            ':tipo_doc'     => $cab['tipo_doc'],
            ':serie'        => $cab['serie'],
            ':correlativo'  => $cab['correlativo'],
            ':clave_acceso' => $cab['clave_acceso'] ?? null,
            ':fecha_emision'=> $cab['fecha_emision'],
            ':hora_emision' => $cab['hora_emision'] ?? null,
            ':emisor_ruc'   => $cab['emisor_ruc'],
            ':emisor_razon' => $cab['emisor_razon'],
            ':cliente_tipo_doc' => $cab['cliente_tipo_doc'],
            ':cliente_num_doc'  => $cab['cliente_num_doc'] ?? null,
            ':cliente_razon'    => $cab['cliente_razon'],
            ':cliente_direccion'=> $cab['cliente_direccion'] ?? null,
            ':cliente_email'    => $cab['cliente_email'] ?? null,
            ':moneda'      => $cab['moneda'] ?? '$',
            ':gravadas'    => $cab['gravadas'] ?? 0,
            ':inafectas'   => $cab['inafectas'] ?? 0,
            ':exoneradas'  => $cab['exoneradas'] ?? 0,
            ':gratuitas'   => $cab['gratuitas'] ?? 0,
            ':descuentos'  => $cab['descuentos'] ?? 0,
            ':igv'         => $cab['igv'] ?? 0,
            ':total'       => $cab['total'],
            ':total_letras'=> $cab['total_letras'] ?? null,
            ':forma_pago'  => $cab['forma_pago'] ?? 'Contado',
            ':metodo_pago' => $cab['metodo_pago'] ?? 'efectivo',
            ':ref_tipo_doc'=> $cab['ref_tipo_doc'] ?? null,
            ':ref_serie'   => $cab['ref_serie'] ?? null,
            ':ref_correlativo' => $cab['ref_correlativo'] ?? null,
            ':motivo_codigo'   => $cab['motivo_codigo'] ?? null,
            ':motivo_descripcion' => $cab['motivo_descripcion'] ?? null,
            ':usuario_id'  => $cab['usuario_id'] ?? null,
        ]);
        $id = $this->conn->lastInsertId();

        $sqlD = "INSERT INTO {$this->detalle}
            (comprobante_id, linea, codigo, descripcion, unidad, cantidad,
             valor_unitario, precio_unitario, subtotal, igv_linea, total_linea, tipo_afectacion)
            VALUES
            (:cid,:linea,:codigo,:descripcion,:unidad,:cantidad,
             :valor_unitario,:precio_unitario,:subtotal,:igv_linea,:total_linea,:tipo_afectacion)";
        $sd = $this->conn->prepare($sqlD);
        foreach ($items as $it) {
            $sd->execute([
                ':cid' => $id,
                ':linea' => $it['linea'],
                ':codigo' => $it['codigo'] ?? null,
                ':descripcion' => $it['descripcion'],
                ':unidad' => $it['unidad'] ?? 'NIU',
                ':cantidad' => $it['cantidad'],
                ':valor_unitario' => $it['valor_unitario'],
                ':precio_unitario'=> $it['precio_unitario'],
                ':subtotal' => $it['subtotal'],
                ':igv_linea' => $it['igv_linea'],
                ':total_linea' => $it['total_linea'],
                ':tipo_afectacion' => $it['tipo_afectacion'] ?? '10',
            ]);
        }
        return $id;
    }

    public function actualizarEnvio($id, $estado, $codigo, $descripcion, $xmlFirmado, $hash, $cdrZip = null, $error = null) {
        // En Ecuador, estado mapea a estado_sri y guardamos en sri_authorization_xml
        $stmt = $this->conn->prepare("UPDATE {$this->tabla}
            SET estado_sri=:e, estado_sunat=:esunat, cdr_codigo=:c, cdr_descripcion=:d,
                xml_firmado=:x, sri_authorization_xml=:x2, xml_hash=:h, mensaje_error=:m
            WHERE id=:id");
        
        // Mapear estado_sri a estado_sunat para compatibilidad de vistas anteriores
        $esunat = 'pendiente';
        if ($estado === 'autorizado') $esunat = 'aceptado';
        elseif ($estado === 'no_autorizado' || $estado === 'devuelta') $esunat = 'rechazado';
        elseif ($estado === 'anulado') $esunat = 'anulado';
        elseif ($estado === 'error') $esunat = 'error';
        elseif ($estado === 'recibida') $esunat = 'enviando';

        $stmt->bindValue(':e', $estado);
        $stmt->bindValue(':esunat', $esunat);
        $stmt->bindValue(':c', $codigo);
        $stmt->bindValue(':d', $descripcion);
        $stmt->bindValue(':x', $xmlFirmado);
        $stmt->bindValue(':x2', $xmlFirmado);
        $stmt->bindValue(':h', $hash);
        $stmt->bindValue(':m', $error);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->tabla} WHERE id=:id LIMIT 1");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalle($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->detalle} WHERE comprobante_id=:id ORDER BY linea ASC");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar($filtros = []) {
        $sql = "SELECT * FROM {$this->tabla} WHERE 1=1";
        $p = [];
        if (!empty($filtros['desde'])) { $sql .= " AND fecha_emision >= :desde"; $p[':desde'] = $filtros['desde']; }
        if (!empty($filtros['hasta'])) { $sql .= " AND fecha_emision <= :hasta"; $p[':hasta'] = $filtros['hasta']; }
        if (!empty($filtros['tipo_doc']))  { $sql .= " AND tipo_doc=:t"; $p[':t'] = $filtros['tipo_doc']; }
        if (!empty($filtros['estado']))    { $sql .= " AND estado_sri=:est"; $p[':est'] = $filtros['estado']; }
        $sql .= " ORDER BY id DESC LIMIT 300";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function log($comprobanteId, $accion, $req, $resp, $codigo = null, $mensaje = null) {
        $stmt = $this->conn->prepare("INSERT INTO sri_log
            (comprobante_id, accion, request_xml, response_xml, codigo, mensaje)
            VALUES (:cid,:ac,:rq,:rs,:co,:ms)");
        $stmt->execute([
            ':cid'=>$comprobanteId, ':ac'=>$accion, ':rq'=>$req,
            ':rs'=>$resp, ':co'=>$codigo, ':ms'=>$mensaje
        ]);
    }
}
