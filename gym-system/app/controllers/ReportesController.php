<?php
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
require_once '../app/lib/fpdf/fpdf.php';
require_once '../app/config/Database.php';
require_once '../app/models/Reporte.php';
require_once '../app/models/Configuracion.php';

class ReportesController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
        if ($_SESSION['user_rol'] !== 'admin') {
            header('Location: /home/index');
            exit;
        }
    }

    private function texto($str) {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
    }

    public function index() {
        $this->verificarAuth();

        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();

        // Período por defecto: mes actual
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $reporte = new Reporte();

        $ingresosMem  = $reporte->ingresosSuscripciones($desde, $hasta);
        $ingresosPOS  = $reporte->ingresosPOS($desde, $hasta);
        $gastos       = $reporte->totalGastos($desde, $hasta);
        $utilidad     = ($ingresosMem + $ingresosPOS) - $gastos;
        $sociosActivos = $reporte->sociosActivos();

        $porMes           = $reporte->ingresosPorMes($desde, $hasta);
        $porMetodoPago    = $reporte->ventasPorMetodoPago($desde, $hasta);
        $nuevosSocios     = $reporte->sociosNuevosPorMes($desde, $hasta);
        $movimientos      = $reporte->movimientosDetalle($desde, $hasta);

        require_once '../app/views/reportes/index.php';
    }

    // ── Exportar Excel
    public function exportarExcel() {
        $this->verificarAuth();

        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();
        $moneda = $config['moneda'];

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $reporte = new Reporte();
        $movimientos = $reporte->movimientosDetalle($desde, $hasta);

        $filename = "Reporte_Finanzas_" . str_replace('-', '', $desde) . "_" . str_replace('-', '', $hasta) . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
        echo "<table border='1'>";
        echo "<tr style='background:#6366F1;color:white;font-weight:bold;'>
                <th>Fecha</th><th>Descripción</th><th>Monto ($moneda)</th><th>Tipo</th>
              </tr>";
        foreach ($movimientos as $m) {
            $color = ($m['tipo'] === 'gasto') ? '#FEE2E2' : '#F0FDF4';
            echo "<tr style='background:$color;'>";
            echo "<td>" . date('d/m/Y', strtotime($m['fecha'])) . "</td>";
            echo "<td>" . htmlspecialchars($m['descripcion']) . "</td>";
            echo "<td>$moneda " . number_format($m['monto'], 2) . "</td>";
            echo "<td>" . ucfirst($m['tipo']) . "</td>";
            echo "</tr>";
        }

        // Totales
        $ing = $reporte->ingresosSuscripciones($desde, $hasta) + $reporte->ingresosPOS($desde, $hasta);
        $gas = $reporte->totalGastos($desde, $hasta);
        echo "<tr style='font-weight:bold;background:#E0E0E0;'><td colspan='2'>TOTAL INGRESOS</td><td>$moneda " . number_format($ing, 2) . "</td><td></td></tr>";
        echo "<tr style='font-weight:bold;background:#E0E0E0;'><td colspan='2'>TOTAL GASTOS</td><td>$moneda " . number_format($gas, 2) . "</td><td></td></tr>";
        echo "<tr style='font-weight:bold;background:#BBF7D0;'><td colspan='2'>UTILIDAD NETA</td><td>$moneda " . number_format($ing - $gas, 2) . "</td><td></td></tr>";
        echo "</table>";
        exit;
    }

    // ── Exportar PDF
    public function exportarPDF() {
        $this->verificarAuth();

        $configModel = new Configuracion();
        $empresa = $configModel->obtenerDatos();
        $moneda = $empresa['moneda'];

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $reporte = new Reporte();
        $ingresosMem  = $reporte->ingresosSuscripciones($desde, $hasta);
        $ingresosPOS  = $reporte->ingresosPOS($desde, $hasta);
        $gastos       = $reporte->totalGastos($desde, $hasta);
        $utilidad     = ($ingresosMem + $ingresosPOS) - $gastos;
        $movimientos  = $reporte->movimientosDetalle($desde, $hasta);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        // --- Encabezado ---
        if (!empty($empresa['logo'])) {
            $ruta = '../public/img/' . $empresa['logo'];
            if (file_exists($ruta)) $pdf->Image($ruta, 10, 10, 20);
        }
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, $this->texto($empresa['nombre_sistema']), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, $this->texto('Reporte Financiero: ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta))), 0, 1, 'C');
        $pdf->Ln(8);

        // --- KPI Summary ---
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(99, 102, 241);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(190, 9, $this->texto('  Resumen del Período'), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(95, 8, $this->texto('  Ingresos Membresías: ' . $moneda . ' ' . number_format($ingresosMem, 2)), 1, 0);
        $pdf->Cell(95, 8, $this->texto('  Ventas POS: ' . $moneda . ' ' . number_format($ingresosPOS, 2)), 1, 1);
        $pdf->Cell(95, 8, $this->texto('  Total Gastos: ' . $moneda . ' ' . number_format($gastos, 2)), 1, 0);
        $utilColor = $utilidad >= 0 ? [16, 185, 129] : [239, 68, 68];
        $pdf->SetFillColor($utilColor[0], $utilColor[1], $utilColor[2]);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(95, 8, $this->texto('  Utilidad Neta: ' . $moneda . ' ' . number_format($utilidad, 2)), 1, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(220, 230, 241);
        $pdf->Ln(5);

        // --- Tabla de Movimientos ---
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetFillColor(99, 102, 241);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(190, 9, $this->texto('  Detalle de Movimientos'), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(235, 235, 245);
        $pdf->Cell(25, 7, 'Fecha', 1, 0, 'C', true);
        $pdf->Cell(110, 7, $this->texto('Descripción'), 1, 0, 'L', true);
        $pdf->Cell(30, 7, 'Monto', 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'Tipo', 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        foreach ($movimientos as $m) {
            if ($m['tipo'] === 'gasto') {
                $pdf->SetFillColor(254, 226, 226);
            } else {
                $pdf->SetFillColor(240, 253, 244);
            }
            $fill = true;
            $pdf->Cell(25, 7, date('d/m/Y', strtotime($m['fecha'])), 1, 0, 'C', $fill);
            $desc = $this->texto(mb_strimwidth($m['descripcion'], 0, 60, '...'));
            $pdf->Cell(110, 7, $desc, 1, 0, 'L', $fill);
            $pdf->Cell(30, 7, $moneda . ' ' . number_format($m['monto'], 2), 1, 0, 'R', $fill);
            $pdf->Cell(25, 7, $this->texto(ucfirst($m['tipo'])), 1, 1, 'C', $fill);
        }

        $pdf->Ln(3);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell(0, 5, $this->texto('Reporte generado el ' . date('d/m/Y H:i') . ' por ' . $empresa['nombre_sistema']), 0, 1, 'C');

        $pdf->Output('I', 'Reporte_' . $desde . '_' . $hasta . '.pdf');
    }
}
