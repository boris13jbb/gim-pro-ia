<?php
require_once __DIR__ . '/XmlBuilderSri.php';
require_once __DIR__ . '/XmlSignerSri.php';
require_once __DIR__ . '/SriClient.php';
require_once __DIR__ . '/../../models/Configuracion.php';
require_once __DIR__ . '/../../models/ComprobanteElectronico.php';

/**
 * Servicio de Facturación SRI (Ecuador) de alto nivel.
 * 
 * Flujo:
 *   1. Calcular base imponible e IVA (15% por defecto).
 *   2. Reservar secuencial y persistir en base de datos.
 *   3. Generar XML SRI con clave de acceso de 49 dígitos.
 *   4. Firmar digitalmente con el certificado PKCS#12 (.p12).
 *   5. Enviar al servicio de Recepción del SRI.
 *   6. Si es aceptado, enviar al servicio de Autorización del SRI.
 *   7. Registrar logs y actualizar estado en base de datos.
 */
class FacturadorSri {

    private $cfg;
    private $modelo;
    private $rutaXml;

    public function __construct() {
        $cfgModel = new Configuracion();
        $this->cfg = $cfgModel->obtenerDatos();
        $this->modelo = new ComprobanteElectronico();
        
        $this->rutaXml = realpath(__DIR__ . '/../../../public/sri/xml') . DIRECTORY_SEPARATOR;
        if (!$this->rutaXml || !is_dir($this->rutaXml)) {
            @mkdir(__DIR__ . '/../../../public/sri/xml', 0775, true);
            $this->rutaXml = __DIR__ . '/../../../public/sri/xml/';
        }
    }

    /**
     * Emite un comprobante para Ecuador (Factura o Nota de Crédito).
     * 
     * @param array $datos Cabecera y detalle del comprobante
     * @return array Result list ['ok'=>bool, 'comprobante_id'=>int, 'codigo'=>string, 'descripcion'=>string]
     */
    public function emitir(array $datos) {
        // 1) Cálculos impositivos para Ecuador (IVA)
        list($cab, $items) = $this->prepararCalculo($datos);

        // 2) Reservar secuencial correlativo
        if (empty($cab['serie'])) {
            // En Ecuador, establecimiento + punto_emision (6 dígitos, ej: '001001')
            $estab = str_pad($this->cfg['sri_establecimiento'] ?? '001', 3, '0', STR_PAD_LEFT);
            $pto = str_pad($this->cfg['sri_punto_emision'] ?? '001', 3, '0', STR_PAD_LEFT);
            $cab['serie'] = $estab . $pto;
        }
        $cab['correlativo'] = $this->modelo->siguienteCorrelativo($cab['tipo_doc'], $cab['serie']);

        // Generar clave de acceso inicial antes de guardar
        $ruc = preg_replace('/[^0-9]/', '', $cab['emisor_ruc']);
        $ambiente = $this->cfg['sri_ambiente'] ?? '1';
        $fechaClave = date('dmY', strtotime($cab['fecha_emision']));
        $secuencial = str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT);
        
        $cab['clave_acceso'] = XmlBuilderSri::generarClaveAcceso(
            $fechaClave, 
            $cab['tipo_doc'], 
            $ruc, 
            $ambiente, 
            substr($cab['serie'], 0, 3), 
            substr($cab['serie'], 3, 3), 
            $secuencial
        );

        // 3) Persistir en base de datos
        $id = $this->modelo->guardar($cab, $items);

        // Mezclar datos adicionales de emisor
        $cabFull = array_merge($cab, [
            'emisor_nombre_comercial' => $this->cfg['nombre_comercial'] ?? $this->cfg['nombre_sistema'],
            'emisor_direccion'        => $this->cfg['direccion']    ?? 'Dirección Matriz',
            'iva_tasa'                => $this->cfg['iva_tasa']      ?? 15.00,
            'sri_ambiente'            => $this->cfg['sri_ambiente']  ?? '1',
            'sri_establecimiento'     => substr($cab['serie'], 0, 3),
            'sri_punto_emision'       => substr($cab['serie'], 3, 3),
        ]);

        // 4) Generar XML sin firmar
        try {
            if ($cab['tipo_doc'] === '01') {
                $resXml = XmlBuilderSri::factura($cabFull, $items);
            } elseif ($cab['tipo_doc'] === '04') {
                $resXml = XmlBuilderSri::notaCredito($cabFull, $items);
            } else {
                throw new Exception("Tipo de comprobante no soportado para Ecuador (sólo 01 Factura y 04 Nota de Crédito).");
            }
            $xmlSinFirmar = $resXml['xml'];
            $claveAcceso = $resXml['clave'];
        } catch (Exception $e) {
            $this->modelo->actualizarEnvio($id, 'error', '', '', '', '', null, 'Error XML: ' . $e->getMessage());
            return ['ok' => false, 'comprobante_id' => $id, 'codigo' => '', 'descripcion' => $e->getMessage()];
        }

        // 5) Firmar XML con el certificado P12
        $certPath = $this->resolverRutaCertificado();
        $xmlFirmado = '';
        $hash = '';
        
        if ($certPath && file_exists($certPath)) {
            try {
                $signer = new XmlSignerSri($certPath, $this->cfg['sri_certificado_clave'] ?? '');
                $xmlFirmado = $signer->firmar($xmlSinFirmar);
                $hash = XmlSignerSri::calcularHash($xmlFirmado);
            } catch (Exception $e) {
                // Si la firma falla pero estamos en pruebas, simular firma para no bloquear al usuario
                if ($ambiente === '1') {
                    $xmlFirmado = $xmlSinFirmar; // Simular que el XML original es el firmado
                    $hash = md5($xmlSinFirmar);
                } else {
                    $this->modelo->actualizarEnvio($id, 'error', '', '', $xmlSinFirmar, '', null, 'Error firma: ' . $e->getMessage());
                    return ['ok' => false, 'comprobante_id' => $id, 'codigo' => '', 'descripcion' => $e->getMessage()];
                }
            }
        } else {
            // Certificado no configurado, simular firma para pruebas
            if ($ambiente === '1') {
                $xmlFirmado = $xmlSinFirmar;
                $hash = md5($xmlSinFirmar);
            } else {
                $this->modelo->actualizarEnvio($id, 'error', '', '', $xmlSinFirmar, '', null, 'Falta subir certificado digital P12.');
                return ['ok' => false, 'comprobante_id' => $id, 'codigo' => '', 'descripcion' => 'Certificado digital .p12 no cargado en configuración.'];
            }
        }

        // Guardar archivo XML firmado en el servidor local
        $nombreArchivo = $ruc . '-' . $cab['tipo_doc'] . '-' . $cab['serie'] . '-' . $secuencial;
        file_put_contents($this->rutaXml . $nombreArchivo . '.xml', $xmlFirmado);

        // 6) Transmisión al SRI
        $sri = new SriClient($ambiente);

        // A. Envío (Recepción)
        $resRecepcion = $sri->enviarComprobante($xmlFirmado);
        $this->modelo->log($id, 'validarComprobante', $xmlFirmado, $resRecepcion['rawResponse'], $resRecepcion['estado'], implode(' | ', $resRecepcion['mensajes']));

        if (!$resRecepcion['ok']) {
            $msgError = implode(' | ', $resRecepcion['mensajes']);
            $this->modelo->actualizarEnvio($id, 'devuelta', '', '', $xmlFirmado, $hash, null, $msgError);
            return ['ok' => false, 'comprobante_id' => $id, 'codigo' => 'DEVUELTA', 'descripcion' => $msgError];
        }

        // B. Autorización inmediata
        $resAutorizacion = $sri->autorizarComprobante($claveAcceso);
        $this->modelo->log($id, 'autorizacionComprobante', $claveAcceso, $resAutorizacion['rawResponse'], $resAutorizacion['estado'], implode(' | ', $resAutorizacion['mensajes']));

        if ($resAutorizacion['ok']) {
            // Comprobante AUTORIZADO
            $xmlAutorizado = $resAutorizacion['xmlAutorizado'];
            $estadoDB = 'autorizado';
            $msg = 'Autorizado por el SRI';
            
            // Guardar XML autorizado final
            file_put_contents($this->rutaXml . $nombreArchivo . '.xml', $xmlAutorizado);

            $this->modelo->actualizarEnvio($id, $estadoDB, 'AUTORIZADO', $msg, $xmlAutorizado, $hash, null, null);
            return ['ok' => true, 'comprobante_id' => $id, 'codigo' => 'AUTORIZADO', 'descripcion' => $msg];
        } else {
            // Falló la autorización o está en procesamiento
            $msgError = implode(' | ', $resAutorizacion['mensajes']);
            $estadoDB = ($resAutorizacion['estado'] === 'PROCESAMIENTO') ? 'recibida' : 'no_autorizado';
            
            $this->modelo->actualizarEnvio($id, $estadoDB, $resAutorizacion['estado'], $msgError, $xmlFirmado, $hash, null, $msgError);
            return ['ok' => false, 'comprobante_id' => $id, 'codigo' => $resAutorizacion['estado'], 'descripcion' => $msgError];
        }
    }

    /**
     * Consulta el estado en el SRI de un comprobante que quedó en procesamiento o error de red.
     */
    public function reenviar($comprobanteId) {
        $cab = $this->modelo->obtenerPorId($comprobanteId);
        if (!$cab) return ['ok' => false, 'codigo' => '', 'descripcion' => 'Comprobante no encontrado.'];

        $ambiente = $this->cfg['sri_ambiente'] ?? '1';
        $sri = new SriClient($ambiente);
        $claveAcceso = $cab['clave_acceso'];

        $resAutorizacion = $sri->autorizarComprobante($claveAcceso);
        $this->modelo->log($comprobanteId, 'consulta-autorizacionComprobante', $claveAcceso, $resAutorizacion['rawResponse'], $resAutorizacion['estado'], implode(' | ', $resAutorizacion['mensajes']));

        if ($resAutorizacion['ok']) {
            $xmlAutorizado = $resAutorizacion['xmlAutorizado'];
            $this->modelo->actualizarEnvio($comprobanteId, 'autorizado', 'AUTORIZADO', 'Autorizado por el SRI', $xmlAutorizado, $cab['xml_hash'], null, null);
            
            // Sobrescribir archivo local con el XML oficial del SRI
            $nombreArchivo = preg_replace('/[^0-9]/', '', $cab['emisor_ruc']) . '-' . $cab['tipo_doc'] . '-' . $cab['serie'] . '-' . str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT);
            file_put_contents($this->rutaXml . $nombreArchivo . '.xml', $xmlAutorizado);

            return ['ok' => true, 'codigo' => 'AUTORIZADO', 'descripcion' => 'Autorizado exitosamente por el SRI.'];
        } else {
            $msgError = implode(' | ', $resAutorizacion['mensajes']);
            $estadoDB = ($resAutorizacion['estado'] === 'PROCESAMIENTO') ? 'recibida' : 'no_autorizado';
            $this->modelo->actualizarEnvio($comprobanteId, $estadoDB, $resAutorizacion['estado'], $msgError, $cab['xml_firmado'], $cab['xml_hash'], null, $msgError);
            return ['ok' => false, 'codigo' => $resAutorizacion['estado'], 'descripcion' => $msgError];
        }
    }

    /**
     * Calcula base imponible e IVA de los productos según el sistema ecuatoriano.
     */
    private function prepararCalculo(array $d) {
        $ivaTasa = (float)($this->cfg['iva_tasa'] ?? 15.00);
        $incluyeIva = (bool)($this->cfg['incluye_iva'] ?? true);

        $gravadas = 0; $iva = 0; $totalLineas = 0;
        $items = [];
        $linea = 1;
        
        foreach ($d['items'] as $it) {
            $cant = (float)$it['cantidad'];
            $precio = (float)$it['precio_unitario']; // Valor final al usuario
            
            if ($incluyeIva) {
                $valorUnit = round($precio / (1 + $ivaTasa / 100), 4);
                $precioUnit = round($precio, 4);
            } else {
                $valorUnit = round($precio, 4);
                $precioUnit = round($precio * (1 + $ivaTasa / 100), 4);
            }
            
            $subtotal = round($cant * $valorUnit, 2);
            $ivaLinea = round($subtotal * $ivaTasa / 100, 2);
            $totalLinea = round($subtotal + $ivaLinea, 2);

            $items[] = [
                'linea' => $linea++,
                'codigo' => $it['codigo'] ?? null,
                'descripcion' => $it['descripcion'],
                'unidad' => $it['unidad'] ?? 'NIU',
                'cantidad' => $cant,
                'valor_unitario'  => $valorUnit,
                'precio_unitario' => $precioUnit,
                'subtotal'   => $subtotal,
                'igv_linea'  => $ivaLinea, // Se guarda en igv_linea de la BD para compatibilidad
                'total_linea'=> $totalLinea,
                'tipo_afectacion' => $it['tipo_afectacion'] ?? '10',
            ];

            $gravadas    += $subtotal;
            $iva         += $ivaLinea;
            $totalLineas += $totalLinea;
        }

        $gravadas = round($gravadas, 2);
        $iva      = round($iva, 2);
        $total    = round($totalLineas, 2);

        $cab = [
            'origen_tipo' => $d['origen_tipo'] ?? 'manual',
            'origen_id'   => $d['origen_id']   ?? null,
            'tipo_doc'    => $d['tipo_doc'],
            'serie'       => $d['serie'] ?? null,
            'fecha_emision' => $d['fecha_emision'] ?? date('Y-m-d'),
            'emisor_ruc'    => $this->cfg['ruc'],
            'emisor_razon'  => $this->cfg['razon_social'] ?? $this->cfg['nombre_sistema'],
            'cliente_tipo_doc' => $d['cliente_tipo_doc'] ?? '1',
            'cliente_num_doc'  => $d['cliente_num_doc'] ?? '9999999999999',
            'cliente_razon'    => $d['cliente_razon'] ?? 'CONSUMIDOR FINAL',
            'cliente_direccion'=> $d['cliente_direccion'] ?? null,
            'cliente_email'    => $d['cliente_email'] ?? null,
            'moneda'    => '$',
            'gravadas'  => $gravadas,
            'inafectas' => 0,
            'exoneradas'=> 0,
            'gratuitas' => 0,
            'descuentos'=> $d['descuentos'] ?? 0,
            'igv'       => $iva, // Se guarda en igv de la BD para compatibilidad
            'total'     => $total,
            'total_letras' => $this->totalALetras($total),
            'forma_pago'   => $d['forma_pago']  ?? 'Contado',
            'metodo_pago'  => $d['metodo_pago'] ?? 'efectivo',
            'ref_tipo_doc' => $d['ref_tipo_doc'] ?? null,
            'ref_serie'    => $d['ref_serie']    ?? null,
            'ref_correlativo' => $d['ref_correlativo'] ?? null,
            'motivo_codigo'   => $d['motivo_codigo']    ?? null,
            'motivo_descripcion' => $d['motivo_descripcion'] ?? null,
            'usuario_id' => $_SESSION['user_id'] ?? null,
        ];

        return [$cab, $items];
    }

    private function resolverRutaCertificado() {
        $rel = $this->cfg['sri_certificado_p12'] ?? '';
        if (empty($rel)) return null;
        $abs = __DIR__ . '/../../../public/cert/' . $rel;
        return file_exists($abs) ? $abs : null;
    }

    private function totalALetras($monto) {
        if (file_exists(__DIR__ . '/NumeroLetras.php')) {
            require_once __DIR__ . '/NumeroLetras.php';
            return NumeroLetras::convertir($monto, 'DOLARES');
        }
        return "SON " . number_format($monto, 2) . " DOLARES";
    }
}
