<?php
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
// CAMBIO 1: Cargamos la nueva librería de QR
require_once '../app/lib/fpdf/qrcode.php'; 
require_once '../app/config/Database.php';
require_once '../app/models/Configuracion.php';
require_once '../app/models/Socio.php';

class CarnetController {
    
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
        $socioModel = new Socio();
        $socio = $socioModel->obtenerPorId($id);
        if (!$socio) { die("Socio no encontrado"); }

        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();

        // CAMBIO 2: Usamos la clase PDF_QR
        // Tamaño Tarjeta: 85mm x 55mm
        $pdf = new PDF_QR('L', 'mm', [85, 55]);
        $pdf->SetMargins(2, 2, 2);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // --- MARCO PRINCIPAL ---
        $pdf->SetLineWidth(0.3);
        $pdf->SetDrawColor(200, 200, 200); // Lighter, softer border
        $pdf->Rect(2, 2, 81, 51); // Inner bounds softly padded
        
        // --- FRANJA DECORATIVA SUPERIOR ---
        $pdf->SetFillColor(99, 102, 241); // Primary Theme Color
        $pdf->Rect(2, 2, 81, 4, 'F'); // Top colored strip

        // --- ENCABEZADO ---
        if (!empty($config['logo'])) {
            $ruta_logo = '../public/img/' . $config['logo'];
            if (file_exists($ruta_logo)) {
                $pdf->Image($ruta_logo, 4, 8, 12); 
            }
        }

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetXY(16, 9);
        $pdf->Cell(66, 6, $this->texto($config['nombre_sistema']), 0, 1, 'C');
        
        $pdf->SetFont('Arial', '', 7);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetXY(16, 14);
        $pdf->Cell(66, 4, 'CARNET DIGITAL DE SOCIO', 0, 1, 'C');

        // --- LÍNEA DIVISORIA ENCABEZADO ---
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->Line(4, 20, 81, 20);

        // --- SECCIÓN IZQUIERDA: FOTO ---
        $y_elementos = 23;
        $tamano_foto = 22; 

        $pdf->SetDrawColor(220, 220, 220); // Soft box around photo
        $pdf->SetLineWidth(0.2);

        if (!empty($socio['foto'])) {
            $ruta_foto = '../public/img/socios/' . $socio['foto'];
            if (file_exists($ruta_foto)) {
                $pdf->Image($ruta_foto, 5, $y_elementos, $tamano_foto, $tamano_foto);
                $pdf->Rect(5, $y_elementos, $tamano_foto, $tamano_foto);
            }
        } else {
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect(5, $y_elementos, $tamano_foto, $tamano_foto, 'DF');
            $pdf->SetXY(5, $y_elementos + 9);
            $pdf->SetFont('Arial', '', 6);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell($tamano_foto, 4, 'SIN FOTO', 0, 0, 'C');
        }

        // --- SECCIÓN CENTRAL: DATOS ---
        $x_datos = 30;
        
        $pdf->SetXY($x_datos, $y_elementos + 2);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(30, 4, 'SOCIO:', 0, 1);
        
        $pdf->SetXY($x_datos, $y_elementos + 6);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(50, 50, 50);
        $nombre_corto = substr($socio['nombre'], 0, 18);
        $pdf->Cell(30, 4, $this->texto($nombre_corto), 0, 1);

        $pdf->SetXY($x_datos, $y_elementos + 12);
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetTextColor(120, 120, 120);
        $pdf->Cell(30, 4, 'DNI / ID:', 0, 1);

        $pdf->SetXY($x_datos, $y_elementos + 16);
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(60, 60, 60);
        $pdf->Cell(30, 4, $socio['dni'], 0, 1);

        // --- SECCIÓN DERECHA: CÓDIGO QR ---
        $x_qr = 58;
        $t_qr = 22;
        $pdf->SetDrawColor(220, 220, 220); // Soft box for QR
        $pdf->Rect($x_qr, $y_elementos, $t_qr, $t_qr);
        $pdf->ImageQR($socio['dni'], $x_qr + 0.5, $y_elementos + 0.5, $t_qr - 1, $t_qr - 1);

        // LÍNEA DIVISORIA INFERIOR
        $pdf->SetDrawColor(230, 230, 230);
        $pdf->Line(4, 48, 81, 48);

        // Pie de página pequeño
        $pdf->SetXY(0, 49.5);
        $pdf->SetFont('Arial', 'I', 6);
        $pdf->SetTextColor(160, 160, 160);
        $pdf->Cell(85, 3, 'ID Interno de Sistema: ' . $socio['id'] . ' - Valide en Recepcion', 0, 0, 'C');

        $pdf->Output('I', 'Carnet_QR_'.$socio['dni'].'.pdf');
    }
}