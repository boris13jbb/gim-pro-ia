<?php
// 1. Cargar el motor base de FPDF (está en la misma carpeta)
require_once('fpdf.php');

// 2. Cargar la librería de generación de QR
// Usamos __DIR__ para asegurar que busque la carpeta "hermana" phpqrcode
// La ruta final será: app/lib/phpqrcode/qrlib.php
require_once __DIR__ . '/../phpqrcode/qrlib.php';

class PDF_QR extends FPDF {
    function __construct($orientation='P', $unit='mm', $format='A4') {
        parent::__construct($orientation, $unit, $format);
    }

    function ImageQR($text, $x, $y, $w=0, $h=0) {
        // 1. Definir una ruta temporal para guardar la imagen del QR
        // sys_get_temp_dir() obtiene la carpeta temporal de Windows/Linux
        $temp_file = sys_get_temp_dir() . '/qr_' . md5($text) . '.png';
        
        // 2. Generar el Código QR usando la librería externa
        // QR_ECLEVEL_L = Nivel de corrección bajo (suficiente y rápido)
        // 3 = Tamaño del pixel
        // 1 = Margen blanco alrededor
        QRcode::png($text, $temp_file, QR_ECLEVEL_L, 3, 1);

        // 3. Insertar la imagen generada en el PDF
        $this->Image($temp_file, $x, $y, $w, $h);

        // 4. Borrar el archivo temporal para no llenar basura en el disco
        if(file_exists($temp_file)) {
            unlink($temp_file);
        }
    }
}
?>