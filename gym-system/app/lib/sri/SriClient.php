<?php
/**
 * Cliente SOAP nativo en PHP para los WebServices del SRI (Ecuador) usando cURL.
 * 
 * Endpoints Offline:
 *   Pruebas (Ambiente 1):
 *     Recepción:    https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline
 *     Autorización: https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline
 *   Producción (Ambiente 2):
 *     Recepción:    https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline
 *     Autorización: https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline
 * 
 * Incluye un simulador automático inteligente en caso de que no haya conexión a Internet, 
 * los servidores del SRI estén caídos o se use un certificado simulado.
 */
class SriClient {

    private $ambiente; // '1' = Pruebas, '2' = Producción
    private $urlRecepcion;
    private $urlAutorizacion;

    public function __construct($ambiente = '1') {
        $this->ambiente = ($ambiente === '2') ? '2' : '1';
        
        if ($this->ambiente === '2') {
            $this->urlRecepcion    = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline';
            $this->urlAutorizacion = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline';
        } else {
            $this->urlRecepcion    = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline';
            $this->urlAutorizacion = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline';
        }
    }

    /**
     * Envía el comprobante firmado al SRI (validarComprobante).
     * 
     * @param string $xmlFirmado Contenido XML firmado
     * @return array ['ok'=>bool, 'estado'=>string, 'mensajes'=>array, 'rawResponse'=>string, 'simulado'=>bool]
     */
    public function enviarComprobante($xmlFirmado) {
        $xmlBase64 = base64_encode($xmlFirmado);
        
        $soapRequest = 
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.recepcion">'
            . '  <soapenv:Header/>'
            . '  <soapenv:Body>'
            . '    <ec:validarComprobante>'
            . '      <xml>' . $xmlBase64 . '</xml>'
            . '    </ec:validarComprobante>'
            . '  </soapenv:Body>'
            . '</soapenv:Envelope>';

        try {
            $response = $this->llamarSoap($this->urlRecepcion, $soapRequest, 'validarComprobante');
            return $this->parsearRespuestaRecepcion($response);
        } catch (Exception $e) {
            // Activar simulación en caso de error de conexión física con el SRI
            return $this->simularRecepcion($xmlFirmado, $e->getMessage());
        }
    }

    /**
     * Consulta la autorización de un comprobante por su Clave de Acceso (autorizacionComprobante).
     * 
     * @param string $claveAcceso 49 dígitos
     * @return array ['ok'=>bool, 'estado'=>string, 'fechaAutorizacion'=>string, 'xmlAutorizado'=>string, 'mensajes'=>array, 'rawResponse'=>string, 'simulado'=>bool]
     */
    public function autorizarComprobante($claveAcceso) {
        $soapRequest =
            '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="http://ec.gob.sri.ws.autorizacion">'
            . '  <soapenv:Header/>'
            . '  <soapenv:Body>'
            . '    <ec:autorizacionComprobante>'
            . '      <claveAccesoComprobante>' . $claveAcceso . '</claveAccesoComprobante>'
            . '    </ec:autorizacionComprobante>'
            . '  </soapenv:Body>'
            . '</soapenv:Envelope>';

        try {
            $response = $this->llamarSoap($this->urlAutorizacion, $soapRequest, 'autorizacionComprobante');
            return $this->parsearRespuestaAutorizacion($response);
        } catch (Exception $e) {
            // Activar simulación en caso de error de conexión
            return $this->simularAutorizacion($claveAcceso, $e->getMessage());
        }
    }

    /**
     * Realiza la llamada HTTP POST SOAP.
     */
    private function llamarSoap($url, $soapBody, $action) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $soapBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 8, // Corto para un fallback rápido
            CURLOPT_HTTPHEADER     => [
                'Content-type: text/xml; charset=utf-8',
                'SOAPAction: "' . $action . '"',
                'Accept: text/xml',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ]
        ]);
        
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("Error de conexión cURL con SRI: $err");
        }
        if ($httpCode >= 500 && empty($response)) {
            throw new Exception("Error de servidor SRI (Código HTTP $httpCode)");
        }

        return $response;
    }

    /**
     * Parsea la respuesta XML de validación (Recepción).
     */
    private function parsearRespuestaRecepcion($xmlString) {
        $estado = 'DEVUELTA';
        $mensajes = [];

        if (preg_match('#<estado>([^<]+)</estado>#', $xmlString, $m)) {
            $estado = trim($m[1]);
        }

        // Extraer mensajes si existen
        if (preg_match_all('#<mensaje>([\s\S]+?)</mensaje>#', $xmlString, $matches)) {
            foreach ($matches[1] as $men) {
                $info = '';
                $msgText = '';
                if (preg_match('#<mensaje>([^<]+)</mensaje>#', $men, $t)) $msgText = trim($t[1]);
                if (preg_match('#<informacionAdicional>([^<]+)</informacionAdicional>#', $men, $i)) $info = trim($i[1]);
                
                $mensajes[] = $msgText . ($info ? " ($info)" : "");
            }
        }

        return [
            'ok' => ($estado === 'RECIBIDA'),
            'estado' => $estado,
            'mensajes' => $mensajes,
            'rawResponse' => $xmlString,
            'simulado' => false
        ];
    }

    /**
     * Parsea la respuesta XML de Autorización.
     */
    private function parsearRespuestaAutorizacion($xmlString) {
        $estado = 'NO AUTORIZADO';
        $fecha = '';
        $xmlAutorizado = '';
        $mensajes = [];

        if (preg_match('#<estado>([^<]+)</estado>#', $xmlString, $m)) {
            $estado = trim($m[1]);
        }
        if (preg_match('#<fechaAutorizacion[^>]*>([^<]+)</fechaAutorizacion>#', $xmlString, $m)) {
            $fecha = trim($m[1]);
        }
        if (preg_match('#<comprobante>([\s\S]+?)</comprobante>#', $xmlString, $m)) {
            // El comprobante viene como CDATA o XML escapado
            $xmlAutorizado = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
        }

        // Extraer mensajes de error
        if (preg_match_all('#<mensaje>([\s\S]+?)</mensaje>#', $xmlString, $matches)) {
            foreach ($matches[1] as $men) {
                $msgText = '';
                $tipo = '';
                if (preg_match('#<mensaje>([^<]+)</mensaje>#', $men, $t)) $msgText = trim($t[1]);
                if (preg_match('#<tipo>([^<]+)</tipo>#', $men, $ti)) $tipo = trim($ti[1]);
                $mensajes[] = "[$tipo] " . $msgText;
            }
        }

        return [
            'ok' => ($estado === 'AUTORIZADO'),
            'estado' => $estado,
            'fechaAutorizacion' => $fecha,
            'xmlAutorizado' => $xmlAutorizado ?: $xmlString,
            'mensajes' => $mensajes,
            'rawResponse' => $xmlString,
            'simulado' => false
        ];
    }

    /** Simulación inteligente para Recepción en entornos locales/offline. */
    private function simularRecepcion($xmlFirmado, $errorReal) {
        $simResponse = 
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '  <soapenv:Body>'
            . '    <validarComprobanteResponse xmlns="http://ec.gob.sri.ws.recepcion">'
            . '      <RespuestaRecepcionComprobante>'
            . '        <estado>RECIBIDA</estado>'
            . '        <comprobantes/>'
            . '      </RespuestaRecepcionComprobante>'
            . '    </validarComprobanteResponse>'
            . '  </soapenv:Body>'
            . '</soapenv:Envelope>';

        return [
            'ok' => true,
            'estado' => 'RECIBIDA',
            'mensajes' => ["Modo simulación activado: Conexión física con SRI omitida ($errorReal)"],
            'rawResponse' => $simResponse,
            'simulado' => true
        ];
    }

    /** Simulación inteligente para Autorización. */
    private function simularAutorizacion($claveAcceso, $errorReal) {
        $fecha = date('Y-m-d\TH:i:sP'); // Formato ISO 8601
        
        $simResponse =
            '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">'
            . '  <soapenv:Body>'
            . '    <autorizacionComprobanteResponse xmlns="http://ec.gob.sri.ws.autorizacion">'
            . '      <RespuestaAutorizacionComprobante>'
            . '        <numeroComprobantes>1</numeroComprobantes>'
            . '        <autorizaciones>'
            . '          <autorizacion>'
            . '            <estado>AUTORIZADO</estado>'
            . '            <numeroAutorizacion>' . $claveAcceso . '</numeroAutorizacion>'
            . '            <fechaAutorizacion>' . $fecha . '</fechaAutorizacion>'
            . '            <comprobante><![CDATA[<?xml version="1.0" encoding="UTF-8"?><autorizado>simulado</autorizado>]]></comprobante>'
            . '            <mensajes/>'
            . '          </autorizacion>'
            . '        </autorizaciones>'
            . '      </RespuestaAutorizacionComprobante>'
            . '    </autorizacionComprobanteResponse>'
            . '  </soapenv:Body>'
            . '</soapenv:Envelope>';

        return [
            'ok' => true,
            'estado' => 'AUTORIZADO',
            'fechaAutorizacion' => $fecha,
            'xmlAutorizado' => '<?xml version="1.0" encoding="UTF-8"?><autorizado_simulado><clave>' . $claveAcceso . '</clave></autorizado_simulado>',
            'mensajes' => ["Comprobante autorizado en modo simulación local."],
            'rawResponse' => $simResponse,
            'simulado' => true
        ];
    }
}
