<?php
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
require_once '../app/lib/fpdf/fpdf.php';
require_once '../app/config/Database.php';
require_once '../app/models/Configuracion.php';

class ComprobanteController {
    
    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    private function texto($str) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
    }

    public function generar($id) {
        $this->verificarAuth();
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT s.*, so.nombre as socio, so.dni, so.email, p.nombre as plan, p.precio 
                  FROM suscripciones s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE s.id = :id LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$datos) { die("Error: El comprobante no existe."); }

        $configModel = new Configuracion();
        $empresa = $configModel->obtenerDatos();
        
        // --- VARIABLE MONEDA ---
        $simbolo = $empresa['moneda']; 

        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();

        // Barra superior decorativa
        $pdf->SetFillColor(99, 102, 241); // Indigo color
        $pdf->Rect(0, 0, 210, 6, 'F');
        $pdf->Ln(5);

        // Logo y Encabezado
        $hay_logo = false;
        if (!empty($empresa['logo'])) {
            $ruta_logo = '../public/img/' . $empresa['logo'];
            if (file_exists($ruta_logo)) {
                $pdf->Image($ruta_logo, 10, 12, 25); 
                $hay_logo = true;
            }
        }

        $pdf->SetFont('Arial','B',20);
        $pdf->SetTextColor(33, 37, 41);
        if($hay_logo) { $pdf->Cell(28); } 
        $pdf->Cell(0, 8, $this->texto($empresa['nombre_sistema']), 0, 1, 'L');
        
        $pdf->SetFont('Arial','B',10);
        $pdf->SetTextColor(99, 102, 241);
        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, 'COMPROBANTE DE PAGO', 0, 1, 'L');
        
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(100, 100, 100);
        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, $this->texto('RUC: ' . $empresa['ruc'] . '  |  Tel: ' . $empresa['telefono']), 0, 1, 'L');

        if($hay_logo) { $pdf->Cell(28); }
        $pdf->Cell(0, 5, $this->texto($empresa['direccion']), 0, 1, 'L');
        
        $pdf->SetTextColor(0, 0, 0); // Reset text color
        
        $pdf->Ln(12);

        // Cliente Box
        $pdf->SetFillColor(250, 250, 252);
        $pdf->SetDrawColor(220, 220, 225);
        $pdf->Rect(10, $pdf->GetY(), 190, 26, 'DF'); // Draw box
        
        $pdf->Ln(3); // Padding inside box
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  Socio:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $this->texto($datos['socio']), 0, 0);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30, 6, 'Comprobante:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(40, 6, str_pad($datos['id'], 6, '0', STR_PAD_LEFT), 0, 1);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  DNI:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $datos['dni'], 0, 0);
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(30, 6, 'Emision:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(40, 6, date('d/m/Y'), 0, 1);
        
        $pdf->SetFont('Arial','B',10);
        $pdf->Cell(25, 6, '  Email:', 0, 0);
        $pdf->SetFont('Arial','',10);
        $pdf->Cell(95, 6, $this->texto($datos['email']), 0, 1);
        
        $pdf->Ln(10); // Spacing before table

        // Detalle
        $pdf->SetFont('Arial','B',11);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(190, 8, $this->texto('   DETALLE DE LA SUSCRIPCION'), 0, 1, 'L');
        
        // Headers with lighter gray and subtle borders
        $pdf->SetFillColor(245, 245, 248); 
        $pdf->SetDrawColor(200, 200, 200); 
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetTextColor(100, 100, 100);

        $pdf->Cell(25, 9, ' ID PLAN', 'B', 0, 'C', true);
        $pdf->Cell(75, 9, $this->texto(' DESCRIPCION'), 'B', 0, 'L', true);
        $pdf->Cell(50, 9, ' PERIODO', 'B', 0, 'C', true);
        $pdf->Cell(40, 9, ' IMPORTE', 'B', 1, 'R', true);

        // Body with bottom border
        $pdf->SetFont('Arial','',10);
        $pdf->SetTextColor(33, 37, 41);
        $pdf->Cell(25, 10, $datos['plan_id'], 'B', 0, 'C');
        $pdf->Cell(75, 10, $this->texto(" " . $datos['plan']), 'B', 0, 'L');
        $fecha_txt = date('d/m/Y', strtotime($datos['fecha_inicio'])) . " - " . date('d/m/Y', strtotime($datos['fecha_fin']));
        $pdf->Cell(50, 10, $fecha_txt, 'B', 0, 'C');
        
        // --- USO DE MONEDA DINÁMICA ---
        $pdf->Cell(40, 10, $simbolo . ' ' . number_format($datos['precio'], 2) . ' ', 'B', 1, 'R');

        // Totales
        $pdf->Ln(2);
        $pdf->SetFont('Arial','B',11);
        $pdf->Cell(150, 10, 'TOTAL PAGADO: ', 0, 0, 'R');
        $pdf->SetTextColor(99, 102, 241); // Indigo color for emphasis
        $pdf->Cell(40, 10, $simbolo . ' ' . number_format($datos['precio'], 2) . ' ', 0, 1, 'R');
        $pdf->SetTextColor(0, 0, 0); // Reset text color

        $pdf->Ln(30);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(65, $pdf->GetY(), 145, $pdf->GetY());
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, $this->texto('Firma Conformidad'), 0, 1, 'C');

        $pdf->Ln(15);
        $pdf->SetFont('Arial','I',8);
        $pdf->Cell(0, 5, $this->texto('Gracias por su preferencia - Documento generado el ' . date('d/m/Y H:i')), 0, 1, 'C');
        $pdf->Cell(0, 5, $this->texto('Cualquier duda, contáctenos en ' . $empresa['email']), 0, 1, 'C');

        $pdf->Output('I', 'Comprobante_'.$datos['id'].'.pdf');
    }

    // =========================================================
    //  PDF de Comprobante de Pago Electrónico (CPE) con QR SUNAT
    //  Acceso: /comprobante/cpe/{id}
    // =========================================================
    public function cpe($id) {
        $this->verificarAuth();
        require_once '../app/models/ComprobanteElectronico.php';
        require_once '../app/lib/phpqrcode/phpqrcode.php';

        $modelo = new ComprobanteElectronico();
        $cab = $modelo->obtenerPorId($id);
        if (!$cab) die('Comprobante no encontrado');
        $det = $modelo->obtenerDetalle($id);

        $configModel = new Configuracion();
        $empresa = $configModel->obtenerDatos();

        // Tipos de comprobante (descriptivos)
        $nombresTipo = [
            '01' => 'FACTURA ELECTRONICA',
            '03' => 'BOLETA DE VENTA ELECTRONICA',
            '07' => 'NOTA DE CREDITO ELECTRONICA',
            '08' => 'NOTA DE DEBITO ELECTRONICA'
        ];
        $tituloDoc = $nombresTipo[$cab['tipo_doc']] ?? 'COMPROBANTE ELECTRONICO';
        $numero = $cab['serie'] . '-' . str_pad($cab['correlativo'], 8, '0', STR_PAD_LEFT);

        // Cadena QR SUNAT: RUC|TIPO|SERIE|NUMERO|IGV|TOTAL|FECHA|TIPODOC_CLI|NUMDOC_CLI|HASH
        $qrCadena = implode('|', [
            $empresa['ruc'],
            $cab['tipo_doc'],
            $cab['serie'],
            $cab['correlativo'],
            number_format($cab['igv'], 2, '.', ''),
            number_format($cab['total'], 2, '.', ''),
            $cab['fecha_emision'],
            $cab['cliente_tipo_doc'],
            $cab['cliente_num_doc'],
            $cab['xml_hash'] ?? '',
        ]);
        $qrTmp = sys_get_temp_dir() . '/qr_cpe_' . $id . '.png';
        QRcode::png($qrCadena, $qrTmp, QR_ECLEVEL_M, 3);

        // ============ PDF ============
        $pdf = new FPDF('P','mm','A4');
        $pdf->AddPage();

        // Barra
        $pdf->SetFillColor(99,102,241);
        $pdf->Rect(0,0,210,6,'F');
        $pdf->Ln(5);

        // Logo + Emisor
        if (!empty($empresa['logo']) && file_exists('../public/img/'.$empresa['logo'])) {
            $pdf->Image('../public/img/'.$empresa['logo'], 10, 12, 25);
            $pdf->Cell(28);
        }
        $pdf->SetFont('Arial','B',16);
        $pdf->Cell(0,7,$this->texto($empresa['razon_social'] ?? $empresa['nombre_sistema']),0,1,'L');
        $pdf->SetFont('Arial','',9);
        if (!empty($empresa['logo']) && file_exists('../public/img/'.$empresa['logo'])) $pdf->Cell(28);
        $pdf->Cell(0,4,$this->texto('RUC: '.$empresa['ruc'].'   Tel: '.$empresa['telefono']),0,1,'L');
        if (!empty($empresa['logo']) && file_exists('../public/img/'.$empresa['logo'])) $pdf->Cell(28);
        $pdf->Cell(0,4,$this->texto($empresa['direccion']),0,1,'L');

        // Recuadro derecho con el tipo de documento
        $pdf->SetXY(135,12);
        $pdf->SetDrawColor(99,102,241);
        $pdf->SetLineWidth(0.4);
        $pdf->Rect(135,12,65,28);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetTextColor(99,102,241);
        $pdf->Cell(65,6,'RUC: '.$empresa['ruc'],0,2,'C');
        $pdf->Cell(65,6,$this->texto($tituloDoc),0,2,'C');
        $pdf->SetTextColor(33,37,41);
        $pdf->SetFont('Arial','B',13);
        $pdf->Cell(65,8,$numero,0,2,'C');

        // Datos cliente
        $pdf->SetXY(10,46);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetFillColor(245,245,248);
        $pdf->Cell(190,7,'  '.$this->texto('DATOS DEL CLIENTE'),0,1,'L',true);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(35,6,'  Cliente:',0,0);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(105,6,$this->texto($cab['cliente_razon']),0,0);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(20,6,'Fecha:',0,0);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(30,6,date('d/m/Y',strtotime($cab['fecha_emision'])),0,1);

        $tiposNombre = ['1'=>'DNI','6'=>'RUC','4'=>'CE','7'=>'PAS','0'=>'-'];
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(35,6,'  '.($tiposNombre[$cab['cliente_tipo_doc']] ?? 'Doc').':',0,0);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(105,6,$cab['cliente_num_doc'],0,0);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(20,6,'Moneda:',0,0);
        $pdf->SetFont('Arial','',9);
        $pdf->Cell(30,6,$cab['moneda'],0,1);
        if (!empty($cab['cliente_direccion'])) {
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(35,6,'  Direccion:',0,0);
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(155,6,$this->texto($cab['cliente_direccion']),0,1);
        }

        // Referencia (notas)
        if (!empty($cab['ref_serie'])) {
            $pdf->Ln(1);
            $pdf->SetFont('Arial','B',9);
            $pdf->Cell(35,6,'  Modifica a:',0,0);
            $pdf->SetFont('Arial','',9);
            $pdf->Cell(60,6,$cab['ref_serie'].'-'.str_pad($cab['ref_correlativo'],8,'0',STR_PAD_LEFT),0,0);
            $pdf->Cell(25,6,'Motivo:',0,0);
            $pdf->Cell(70,6,$this->texto($cab['motivo_descripcion']),0,1);
        }

        // Tabla detalle
        $pdf->Ln(3);
        $pdf->SetFont('Arial','B',8);
        $pdf->SetFillColor(99,102,241);
        $pdf->SetTextColor(255,255,255);
        $pdf->Cell(15,7,'CANT',1,0,'C',true);
        $pdf->Cell(20,7,'UNID',1,0,'C',true);
        $pdf->Cell(85,7,$this->texto('DESCRIPCION'),1,0,'C',true);
        $pdf->Cell(25,7,'V.UNIT',1,0,'C',true);
        $pdf->Cell(20,7,'IGV',1,0,'C',true);
        $pdf->Cell(25,7,'TOTAL',1,1,'C',true);

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(33,37,41);
        foreach ($det as $d) {
            $pdf->Cell(15,6,number_format($d['cantidad'],2),'LR',0,'C');
            $pdf->Cell(20,6,$d['unidad'],'R',0,'C');
            $pdf->Cell(85,6,$this->texto(substr($d['descripcion'],0,55)),'R',0,'L');
            $pdf->Cell(25,6,number_format($d['valor_unitario'],4),'R',0,'R');
            $pdf->Cell(20,6,number_format($d['igv_linea'],2),'R',0,'R');
            $pdf->Cell(25,6,number_format($d['total_linea'],2),'R',1,'R');
        }
        $pdf->Cell(190,0,'','T',1);

        // Totales
        $pdf->Ln(2);
        $pdf->SetFont('Arial','',9);
        $labelW = 150; $valW = 40;
        $pdf->Cell($labelW,6,'OP. GRAVADA:',0,0,'R');
        $pdf->Cell($valW,6,$cab['moneda'].' '.number_format($cab['gravadas'],2),0,1,'R');
        $pdf->Cell($labelW,6,'IGV ('.($empresa['igv_tasa'] ?? '18').'%):',0,0,'R');
        $pdf->Cell($valW,6,$cab['moneda'].' '.number_format($cab['igv'],2),0,1,'R');
        if ($cab['descuentos'] > 0) {
            $pdf->Cell($labelW,6,'DESCUENTO:',0,0,'R');
            $pdf->Cell($valW,6,'-'.$cab['moneda'].' '.number_format($cab['descuentos'],2),0,1,'R');
        }
        $pdf->SetFont('Arial','B',11);
        $pdf->SetTextColor(99,102,241);
        $pdf->Cell($labelW,8,'IMPORTE TOTAL:',0,0,'R');
        $pdf->Cell($valW,8,$cab['moneda'].' '.number_format($cab['total'],2),0,1,'R');
        $pdf->SetTextColor(0,0,0);

        // Total en letras
        $pdf->Ln(2);
        $pdf->SetFont('Arial','I',9);
        $pdf->MultiCell(0,5,$this->texto('SON: '.$cab['total_letras']),0,'L');

        // QR + estado SUNAT
        $pdf->Ln(4);
        $yQR = $pdf->GetY();
        if (file_exists($qrTmp)) {
            $pdf->Image($qrTmp, 10, $yQR, 35, 35);
        }
        $pdf->SetXY(50, $yQR);
        $pdf->SetFont('Arial','B',9);
        $pdf->Cell(0,5,$this->texto('Representación impresa del Comprobante Electrónico'),0,1);
        $pdf->SetX(50);
        $pdf->SetFont('Arial','',8);
        $pdf->Cell(0,4,$this->texto('Consulte este documento en https://e-consultaruc.sunat.gob.pe'),0,1);

        $pdf->SetX(50);
        $estadoTxt = strtoupper($cab['estado_sunat']);
        $pdf->SetFont('Arial','B',9);
        $color = ($cab['estado_sunat'] === 'aceptado') ? [16,185,129] : [239,68,68];
        $pdf->SetTextColor($color[0],$color[1],$color[2]);
        $pdf->Cell(0,5,'Estado SUNAT: '.$estadoTxt.'   ('.$cab['cdr_codigo'].' - '.$this->texto($cab['cdr_descripcion']).')',0,1);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetX(50);
        $pdf->SetFont('Arial','',7);
        $pdf->Cell(0,4,'Hash: '.$cab['xml_hash'],0,1);

        @unlink($qrTmp);
        $pdf->Output('I', $empresa['ruc'].'-'.$cab['tipo_doc'].'-'.$cab['serie'].'-'.$cab['correlativo'].'.pdf');
    }
}