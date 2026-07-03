<?php
class XmlBuilderSri {

    /**
     * Genera la Clave de Acceso SRI de 49 dígitos.
     * 
     * @param string $fecha ddmmyyyy
     * @param string $tipoDoc '01'=Factura, '04'=Nota de Crédito
     * @param string $ruc 13 dígitos
     * @param string $ambiente '1'=Pruebas, '2'=Producción
     * @param string $estab 3 dígitos (ej: '001')
     * @param string $ptoEmi 3 dígitos (ej: '001')
     * @param string $secuencial 9 dígitos (ej: '000000001')
     * @param string $codigoNumerico 8 dígitos (opcional)
     * @return string 49 dígitos
     */
    public static function generarClaveAcceso($fecha, $tipoDoc, $ruc, $ambiente, $estab, $ptoEmi, $secuencial, $codigoNumerico = null) {
        $fecha = str_replace(['-', '/'], '', $fecha); // Asegurar ddmmyyyy
        if (strlen($fecha) !== 8) {
            $fecha = date('dmY', strtotime($fecha));
        }

        // Si no viene código numérico, usar el secuencial truncado/rellenado
        if (!$codigoNumerico) {
            $codigoNumerico = str_pad(substr($secuencial, -8), 8, '0', STR_PAD_LEFT);
        }
        $codigoNumerico = str_pad(preg_replace('/[^0-9]/', '', $codigoNumerico), 8, '0', STR_PAD_LEFT);

        $clave = $fecha 
               . str_pad($tipoDoc, 2, '0', STR_PAD_LEFT) 
               . str_pad($ruc, 13, '0', STR_PAD_LEFT) 
               . $ambiente 
               . str_pad($estab, 3, '0', STR_PAD_LEFT) 
               . str_pad($ptoEmi, 3, '0', STR_PAD_LEFT) 
               . str_pad($secuencial, 9, '0', STR_PAD_LEFT) 
               . $codigoNumerico 
               . '1'; // Tipo emisión: 1 = Normal

        $digito = self::calcularDigitoVerificador($clave);
        return $clave . $digito;
    }

    /** Algoritmo Módulo 11 para el dígito verificador. */
    public static function calcularDigitoVerificador($clave48) {
        $pivote = 2;
        $suma = 0;
        for ($i = strlen($clave48) - 1; $i >= 0; $i--) {
            $suma += (int)$clave48[$i] * $pivote;
            $pivote++;
            if ($pivote > 7) {
                $pivote = 2;
            }
        }
        $resto = $suma % 11;
        $digito = 11 - $resto;
        if ($digito == 11) {
            $digito = 0;
        } elseif ($digito == 10) {
            $digito = 1;
        }
        return $digito;
    }

    /**
     * Construye el XML de la Factura (comprobante tipo 01).
     */
    public static function factura(array $cab, array $items) {
        $ruc = preg_replace('/[^0-9]/', '', $cab['emisor_ruc']);
        $ambiente = $cab['sri_ambiente'] ?? '1';
        $estab = str_pad($cab['sri_establecimiento'] ?? '001', 3, '0', STR_PAD_LEFT);
        $ptoEmi = str_pad($cab['sri_punto_emision'] ?? '001', 3, '0', STR_PAD_LEFT);
        $secuencial = str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT);
        
        $fechaEmisionObj = new DateTime($cab['fecha_emision']);
        $fechaEmiSri = $fechaEmisionObj->format('d/m/Y'); // dd/mm/yyyy
        $fechaEmiClave = $fechaEmisionObj->format('dmY');  // ddmmyyyy

        $claveAcceso = self::generarClaveAcceso($fechaEmiClave, '01', $ruc, $ambiente, $estab, $ptoEmi, $secuencial);

        // Identificación comprador
        $clienteDoc = preg_replace('/[^0-9a-zA-Z]/', '', $cab['cliente_num_doc']);
        $tipoIdentificacion = '06'; // Pasaporte por defecto
        if ($clienteDoc === '9999999999999') {
            $tipoIdentificacion = '07'; // Consumidor Final
        } elseif (strlen($clienteDoc) === 10) {
            $tipoIdentificacion = '05'; // Cédula
        } elseif (strlen($clienteDoc) === 13) {
            $tipoIdentificacion = '04'; // RUC
        }

        $razonSocialCli = htmlspecialchars($cab['cliente_razon'], ENT_XML1, 'UTF-8');
        $dirMatriz = htmlspecialchars($cab['emisor_direccion'] ?? 'Matriz Gym', ENT_XML1, 'UTF-8');
        $razonSocialEmisor = htmlspecialchars($cab['emisor_razon'], ENT_XML1, 'UTF-8');
        $nomComercialEmisor = htmlspecialchars($cab['emisor_nombre_comercial'] ?? $cab['emisor_razon'], ENT_XML1, 'UTF-8');

        // IVA Tasa
        $ivaTasa = (float)($cab['iva_tasa'] ?? 15.00);
        $baseImponible = number_format($cab['gravadas'], 2, '.', '');
        $valorIva = number_format($cab['igv'], 2, '.', '');
        $totalSinImpuestos = number_format($cab['gravadas'], 2, '.', '');
        $importeTotal = number_format($cab['total'], 2, '.', '');
        $descuentoTotal = number_format($cab['descuentos'] ?? 0.00, 2, '.', '');

        // Forma de pago del SRI (01 = Sin utilización del sistema financiero, 20 = Otros con utilización del sistema financiero)
        $formaPago = ($cab['metodo_pago'] === 'efectivo') ? '01' : '20';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<factura id="comprobante" version="1.1.0">' . "\n";
        $xml .= '  <infoTributaria>' . "\n";
        $xml .= '    <ambiente>' . $ambiente . '</ambiente>' . "\n";
        $xml .= '    <tipoEmision>1</tipoEmision>' . "\n";
        $xml .= '    <razonSocial>' . $razonSocialEmisor . '</razonSocial>' . "\n";
        $xml .= '    <nombreComercial>' . $nomComercialEmisor . '</nombreComercial>' . "\n";
        $xml .= '    <ruc>' . $ruc . '</ruc>' . "\n";
        $xml .= '    <claveAcceso>' . $claveAcceso . '</claveAcceso>' . "\n";
        $xml .= '    <codDoc>01</codDoc>' . "\n";
        $xml .= '    <estab>' . $estab . '</estab>' . "\n";
        $xml .= '    <ptoEmi>' . $ptoEmi . '</ptoEmi>' . "\n";
        $xml .= '    <secuencial>' . $secuencial . '</secuencial>' . "\n";
        $xml .= '    <dirMatriz>' . $dirMatriz . '</dirMatriz>' . "\n";
        $xml .= '  </infoTributaria>' . "\n";
        
        $xml .= '  <infoFactura>' . "\n";
        $xml .= '    <fechaEmision>' . $fechaEmiSri . '</fechaEmision>' . "\n";
        $xml .= '    <dirEstablecimiento>' . $dirMatriz . '</dirEstablecimiento>' . "\n";
        $xml .= '    <obligadoContabilidad>NO</obligadoContabilidad>' . "\n";
        $xml .= '    <tipoIdentificacionComprador>' . $tipoIdentificacion . '</tipoIdentificacionComprador>' . "\n";
        $xml .= '    <razonSocialComprador>' . $razonSocialCli . '</razonSocialComprador>' . "\n";
        $xml .= '    <identificacionComprador>' . $clienteDoc . '</identificacionComprador>' . "\n";
        $xml .= '    <totalSinImpuestos>' . $totalSinImpuestos . '</totalSinImpuestos>' . "\n";
        $xml .= '    <totalDescuento>' . $descuentoTotal . '</totalDescuento>' . "\n";
        $xml .= '    <totalConImpuestos>' . "\n";
        $xml .= '      <totalImpuesto>' . "\n";
        $xml .= '        <codigo>2</codigo>' . "\n"; // Código de IVA: siempre 2
        // Código porcentaje: 4 = 15% (vigente), 2 = 12%
        $codigoPorcentaje = ($ivaTasa >= 14.0) ? '4' : '2';
        $xml .= '        <codigoPorcentaje>' . $codigoPorcentaje . '</codigoPorcentaje>' . "\n";
        $xml .= '        <baseImponible>' . $baseImponible . '</baseImponible>' . "\n";
        $xml .= '        <tarifa>' . number_format($ivaTasa, 2, '.', '') . '</tarifa>' . "\n";
        $xml .= '        <valor>' . $valorIva . '</valor>' . "\n";
        $xml .= '      </totalImpuesto>' . "\n";
        $xml .= '    </totalConImpuestos>' . "\n";
        $xml .= '    <propina>0.00</propina>' . "\n";
        $xml .= '    <importeTotal>' . $importeTotal . '</importeTotal>' . "\n";
        $xml .= '    <moneda>DOLAR</moneda>' . "\n";
        $xml .= '    <pagos>' . "\n";
        $xml .= '      <pago>' . "\n";
        $xml .= '        <formaPago>' . $formaPago . '</formaPago>' . "\n";
        $xml .= '        <total>' . $importeTotal . '</total>' . "\n";
        $xml .= '      </pago>' . "\n";
        $xml .= '    </pagos>' . "\n";
        $xml .= '  </infoFactura>' . "\n";

        $xml .= '  <detalles>' . "\n";
        foreach ($items as $it) {
            $codPr = htmlspecialchars($it['codigo'] ?? 'S/C', ENT_XML1, 'UTF-8');
            $descPr = htmlspecialchars($it['descripcion'], ENT_XML1, 'UTF-8');
            $cant = number_format($it['cantidad'], 2, '.', '');
            $valUnit = number_format($it['valor_unitario'], 4, '.', '');
            $subt = number_format($it['subtotal'], 2, '.', '');
            $ivaLin = number_format($it['igv_linea'], 2, '.', '');
            
            $xml .= '    <detalle>' . "\n";
            $xml .= '      <codigoPrincipal>' . $codPr . '</codigoPrincipal>' . "\n";
            $xml .= '      <descripcion>' . $descPr . '</descripcion>' . "\n";
            $xml .= '      <cantidad>' . $cant . '</cantidad>' . "\n";
            $xml .= '      <precioUnitario>' . $valUnit . '</precioUnitario>' . "\n";
            $xml .= '      <descuento>0.00</descuento>' . "\n";
            $xml .= '      <precioTotalSinImpuesto>' . $subt . '</precioTotalSinImpuesto>' . "\n";
            $xml .= '      <impuestos>' . "\n";
            $xml .= '        <impuesto>' . "\n";
            $xml .= '          <codigo>2</codigo>' . "\n";
            $xml .= '          <codigoPorcentaje>' . $codigoPorcentaje . '</codigoPorcentaje>' . "\n";
            $xml .= '          <tarifa>' . number_format($ivaTasa, 2, '.', '') . '</tarifa>' . "\n";
            $xml .= '          <baseImponible>' . $subt . '</baseImponible>' . "\n";
            $xml .= '          <valor>' . $ivaLin . '</valor>' . "\n";
            $xml .= '        </impuesto>' . "\n";
            $xml .= '      </impuestos>' . "\n";
            $xml .= '    </detalle>' . "\n";
        }
        $xml .= '  </detalles>' . "\n";
        $xml .= '</factura>';

        return [
            'xml' => $xml,
            'clave' => $claveAcceso
        ];
    }

    /**
     * Construye el XML de la Nota de Crédito (comprobante tipo 04).
     */
    public static function notaCredito(array $cab, array $items) {
        $ruc = preg_replace('/[^0-9]/', '', $cab['emisor_ruc']);
        $ambiente = $cab['sri_ambiente'] ?? '1';
        $estab = str_pad($cab['sri_establecimiento'] ?? '001', 3, '0', STR_PAD_LEFT);
        $ptoEmi = str_pad($cab['sri_punto_emision'] ?? '001', 3, '0', STR_PAD_LEFT);
        $secuencial = str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT);

        $fechaEmisionObj = new DateTime($cab['fecha_emision']);
        $fechaEmiSri = $fechaEmisionObj->format('d/m/Y');
        $fechaEmiClave = $fechaEmisionObj->format('dmY');

        $claveAcceso = self::generarClaveAcceso($fechaEmiClave, '04', $ruc, $ambiente, $estab, $ptoEmi, $secuencial);

        // Identificación comprador
        $clienteDoc = preg_replace('/[^0-9a-zA-Z]/', '', $cab['cliente_num_doc']);
        $tipoIdentificacion = '06';
        if ($clienteDoc === '9999999999999') {
            $tipoIdentificacion = '07';
        } elseif (strlen($clienteDoc) === 10) {
            $tipoIdentificacion = '05';
        } elseif (strlen($clienteDoc) === 13) {
            $tipoIdentificacion = '04';
        }

        $razonSocialCli = htmlspecialchars($cab['cliente_razon'], ENT_XML1, 'UTF-8');
        $dirMatriz = htmlspecialchars($cab['emisor_direccion'] ?? 'Matriz Gym', ENT_XML1, 'UTF-8');
        $razonSocialEmisor = htmlspecialchars($cab['emisor_razon'], ENT_XML1, 'UTF-8');
        $nomComercialEmisor = htmlspecialchars($cab['emisor_nombre_comercial'] ?? $cab['emisor_razon'], ENT_XML1, 'UTF-8');

        // IVA Tasa
        $ivaTasa = (float)($cab['iva_tasa'] ?? 15.00);
        $baseImponible = number_format($cab['gravadas'], 2, '.', '');
        $valorIva = number_format($cab['igv'], 2, '.', '');
        $totalSinImpuestos = number_format($cab['gravadas'], 2, '.', '');
        $importeTotal = number_format($cab['total'], 2, '.', '');

        // Formato fecha de la factura de referencia
        $refFechaObj = new DateTime($cab['ref_fecha_emision'] ?? date('Y-m-d'));
        $refFechaSri = $refFechaObj->format('d/m/Y');

        // Formatear el secuencial de referencia a 15 caracteres: estab (3) + ptoEmi (3) + secuencial (9)
        $refEstab = str_pad($cab['ref_establecimiento'] ?? '001', 3, '0', STR_PAD_LEFT);
        $refPto = str_pad($cab['ref_punto_emision'] ?? '001', 3, '0', STR_PAD_LEFT);
        $refSec = str_pad($cab['ref_correlativo'], 9, '0', STR_PAD_LEFT);
        $numDocModificado = $refEstab . '-' . $refPto . '-' . $refSec;

        $motivo = htmlspecialchars($cab['motivo_descripcion'] ?? 'Anulación de la operación', ENT_XML1, 'UTF-8');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<notaCredito id="comprobante" version="1.0.0">' . "\n";
        $xml .= '  <infoTributaria>' . "\n";
        $xml .= '    <ambiente>' . $ambiente . '</ambiente>' . "\n";
        $xml .= '    <tipoEmision>1</tipoEmision>' . "\n";
        $xml .= '    <razonSocial>' . $razonSocialEmisor . '</razonSocial>' . "\n";
        $xml .= '    <nombreComercial>' . $nomComercialEmisor . '</nombreComercial>' . "\n";
        $xml .= '    <ruc>' . $ruc . '</ruc>' . "\n";
        $xml .= '    <claveAcceso>' . $claveAcceso . '</claveAcceso>' . "\n";
        $xml .= '    <codDoc>04</codDoc>' . "\n";
        $xml .= '    <estab>' . $estab . '</estab>' . "\n";
        $xml .= '    <ptoEmi>' . $ptoEmi . '</ptoEmi>' . "\n";
        $xml .= '    <secuencial>' . $secuencial . '</secuencial>' . "\n";
        $xml .= '    <dirMatriz>' . $dirMatriz . '</dirMatriz>' . "\n";
        $xml .= '  </infoTributaria>' . "\n";

        $xml .= '  <infoNotaCredito>' . "\n";
        $xml .= '    <fechaEmision>' . $fechaEmiSri . '</fechaEmision>' . "\n";
        $xml .= '    <dirEstablecimiento>' . $dirMatriz . '</dirEstablecimiento>' . "\n";
        $xml .= '    <tipoIdentificacionComprador>' . $tipoIdentificacion . '</tipoIdentificacionComprador>' . "\n";
        $xml .= '    <razonSocialComprador>' . $razonSocialCli . '</razonSocialComprador>' . "\n";
        $xml .= '    <identificacionComprador>' . $clienteDoc . '</identificacionComprador>' . "\n";
        $xml .= '    <obligadoContabilidad>NO</obligadoContabilidad>' . "\n";
        $xml .= '    <codDocModificado>01</codDocModificado>' . "\n"; // Modifica a una Factura
        $xml .= '    <numDocModificado>' . $numDocModificado . '</numDocModificado>' . "\n";
        $xml .= '    <fechaEmisionDocSustento>' . $refFechaSri . '</fechaEmisionDocSustento>' . "\n";
        $xml .= '    <totalSinImpuestos>' . $totalSinImpuestos . '</totalSinImpuestos>' . "\n";
        $xml .= '    <valorModificacion>' . $importeTotal . '</valorModificacion>' . "\n";
        $xml .= '    <moneda>DOLAR</moneda>' . "\n";
        $xml .= '    <totalConImpuestos>' . "\n";
        $xml .= '      <totalImpuesto>' . "\n";
        $xml .= '        <codigo>2</codigo>' . "\n";
        $codigoPorcentaje = ($ivaTasa >= 14.0) ? '4' : '2';
        $xml .= '        <codigoPorcentaje>' . $codigoPorcentaje . '</codigoPorcentaje>' . "\n";
        $xml .= '        <baseImponible>' . $baseImponible . '</baseImponible>' . "\n";
        $xml .= '        <valor>' . $valorIva . '</valor>' . "\n";
        $xml .= '      </totalImpuesto>' . "\n";
        $xml .= '    </totalConImpuestos>' . "\n";
        $xml .= '    <motivo>' . $motivo . '</motivo>' . "\n";
        $xml .= '  </infoNotaCredito>' . "\n";

        $xml .= '  <detalles>' . "\n";
        foreach ($items as $it) {
            $codPr = htmlspecialchars($it['codigo'] ?? 'S/C', ENT_XML1, 'UTF-8');
            $descPr = htmlspecialchars($it['descripcion'], ENT_XML1, 'UTF-8');
            $cant = number_format($it['cantidad'], 2, '.', '');
            $valUnit = number_format($it['valor_unitario'], 4, '.', '');
            $subt = number_format($it['subtotal'], 2, '.', '');
            $ivaLin = number_format($it['igv_linea'], 2, '.', '');

            $xml .= '    <detalle>' . "\n";
            $xml .= '      <codigoInterno>' . $codPr . '</codigoInterno>' . "\n";
            $xml .= '      <descripcion>' . $descPr . '</descripcion>' . "\n";
            $xml .= '      <cantidad>' . $cant . '</cantidad>' . "\n";
            $xml .= '      <precioUnitario>' . $valUnit . '</precioUnitario>' . "\n";
            $xml .= '      <descuento>0.00</descuento>' . "\n";
            $xml .= '      <precioTotalSinImpuesto>' . $subt . '</precioTotalSinImpuesto>' . "\n";
            $xml .= '      <impuestos>' . "\n";
            $xml .= '        <impuesto>' . "\n";
            $xml .= '          <codigo>2</codigo>' . "\n";
            $xml .= '          <codigoPorcentaje>' . $codigoPorcentaje . '</codigoPorcentaje>' . "\n";
            $xml .= '          <tarifa>' . number_format($ivaTasa, 2, '.', '') . '</tarifa>' . "\n";
            $xml .= '          <baseImponible>' . $subt . '</baseImponible>' . "\n";
            $xml .= '          <valor>' . $ivaLin . '</valor>' . "\n";
            $xml .= '        </impuesto>' . "\n";
            $xml .= '      </impuestos>' . "\n";
            $xml .= '    </detalle>' . "\n";
        }
        $xml .= '  </detalles>' . "\n";
        $xml .= '</notaCredito>';

        return [
            'xml' => $xml,
            'clave' => $claveAcceso
        ];
    }
}
