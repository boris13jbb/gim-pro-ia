<?php
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
require_once '../app/lib/fpdf/fpdf.php';
require_once '../app/config/Database.php';
require_once '../app/models/Configuracion.php';

class TicketController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    private function t($str) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str ?? '');
    }

    private function obtenerVenta($id) {
        $db = new Database();
        $conn = $db->getConnection();

        // Cabecera de venta
        $qv = "SELECT v.*, 
                      u.nombre as cajero,
                      IFNULL(so.nombre, 'Público General') as cliente
               FROM ventas v
               INNER JOIN cajas c ON v.caja_id = c.id
               INNER JOIN usuarios u ON c.usuario_id = u.id
               LEFT JOIN socios so ON v.socio_id = so.id
               WHERE v.id = :id LIMIT 1";
        $st = $conn->prepare($qv);
        $st->bindParam(':id', $id);
        $st->execute();
        $venta = $st->fetch(PDO::FETCH_ASSOC);

        // Líneas de detalle
        $qd = "SELECT dv.*, p.nombre as producto
               FROM detalle_ventas dv
               INNER JOIN productos p ON dv.producto_id = p.id
               WHERE dv.venta_id = :id";
        $std = $conn->prepare($qd);
        $std->bindParam(':id', $id);
        $std->execute();
        $detalle = $std->fetchAll(PDO::FETCH_ASSOC);

        return [$venta, $detalle];
    }

    // ── Vista HTML del ticket (para imprimir desde el navegador)
    public function ver($id) {
        $this->verificarAuth();
        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();

        [$venta, $detalle] = $this->obtenerVenta($id);
        if (!$venta) {
            header('Location: /pos/index');
            exit;
        }

        require_once '../app/views/pos/ticket.php';
    }

    // ── PDF tipo ticket térmico (80mm)
    public function generar($id) {
        $this->verificarAuth();
        $configModel = new Configuracion();
        $empresa = $configModel->obtenerDatos();
        $moneda = $empresa['moneda'];

        [$venta, $detalle] = $this->obtenerVenta($id);
        if (!$venta) { die("Venta no encontrada."); }

        // 80mm ≈ 80mm de ancho, altura variable
        $pdf = new FPDF('P', 'mm', [80, 200]);
        $pdf->SetMargins(4, 4, 4);
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 4);

        // Logo (pequeño, centrado)
        if (!empty($empresa['logo'])) {
            $logo = '../public/img/' . $empresa['logo'];
            if (file_exists($logo)) {
                $pdf->Image($logo, 28, 4, 24);
                $pdf->Ln(20);
            }
        }

        // Nombre empresa
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(72, 5, $this->t($empresa['nombre_sistema']), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(72, 4, $this->t($empresa['direccion'] ?? ''), 0, 1, 'C');
        $pdf->Cell(72, 4, $this->t('Tel: ' . ($empresa['telefono'] ?? '')), 0, 1, 'C');

        // Separador
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(72, 3, str_repeat('-', 52), 0, 1, 'C');

        // Datos de venta
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(36, 4, 'Ticket N°: ' . str_pad($venta['id'], 6, '0', STR_PAD_LEFT), 0, 0);
        $pdf->Cell(36, 4, date('d/m/Y H:i', strtotime($venta['fecha'])), 0, 1, 'R');
        $pdf->SetFont('Arial', '', 7);
        $pdf->Cell(72, 3, 'Cajero: ' . $this->t($venta['cajero']), 0, 1);
        $pdf->Cell(72, 3, 'Cliente: ' . $this->t($venta['cliente']), 0, 1);
        $pdf->Cell(72, 3, str_repeat('-', 52), 0, 1, 'C');

        // Cabecera tabla productos
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->Cell(34, 4, 'Producto', 0, 0);
        $pdf->Cell(10, 4, 'Cant', 0, 0, 'C');
        $pdf->Cell(14, 4, 'P.Unit', 0, 0, 'R');
        $pdf->Cell(14, 4, 'Subtotal', 0, 1, 'R');
        $pdf->Cell(72, 2, str_repeat('-', 52), 0, 1, 'C');

        // Filas
        $pdf->SetFont('Arial', '', 7);
        foreach ($detalle as $d) {
            $nombre = mb_strimwidth($d['producto'], 0, 22, '..');
            $pdf->Cell(34, 5, $this->t($nombre), 0, 0);
            $pdf->Cell(10, 5, $d['cantidad'], 0, 0, 'C');
            $pdf->Cell(14, 5, $moneda . number_format($d['precio_unitario'], 2), 0, 0, 'R');
            $pdf->Cell(14, 5, $moneda . number_format($d['subtotal'], 2), 0, 1, 'R');
        }

        // Total
        $pdf->Cell(72, 2, str_repeat('-', 52), 0, 1, 'C');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(48, 6, 'TOTAL:', 0, 0);
        $pdf->Cell(24, 6, $moneda . number_format($venta['total'], 2), 0, 1, 'R');
        $pdf->SetFont('Arial', '', 7);
        $metodo = ucfirst($venta['metodo_pago'] ?? 'Efectivo');
        $pdf->Cell(72, 4, 'Pago: ' . $metodo, 0, 1, 'C');
        $pdf->Cell(72, 2, str_repeat('-', 52), 0, 1, 'C');

        // Pie
        $pdf->SetFont('Arial', 'I', 6);
        $pdf->Cell(72, 4, $this->t('¡Gracias por tu preferencia!'), 0, 1, 'C');
        $pdf->Cell(72, 3, $this->t($empresa['nombre_sistema'] . ' - ' . date('Y')), 0, 1, 'C');

        $pdf->Output('I', 'Ticket_' . str_pad($id, 6, '0', STR_PAD_LEFT) . '.pdf');
    }
}
