<?php
require_once '../app/models/Dashboard.php';

class HomeController {
    
    public function index() {
        // --- Seguridad ---
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }

        $dashboardModel = new Dashboard();

        // 1. Obtener KPIs Financieros
        $totalSocios = $dashboardModel->contarSociosActivos();
        $ingresosMes = $dashboardModel->ingresosEsteMes();
        $gastosMes   = $dashboardModel->egresosEsteMes(); // Nuevo
        
        // CÁLCULO DE UTILIDAD NETA
        $utilidad = $ingresosMes - $gastosMes;

        // 2. Obtener lista de Vencimientos
        $vencimientos = $dashboardModel->obtenerVencimientosCercanos();

        // 3. Datos Gráfico Barras
        $datosGrafico = $dashboardModel->ventasUltimosMeses();
        $labels = [];
        $data = [];
        foreach($datosGrafico as $g) {
            $labels[] = $g['mes'];
            $data[] = $g['cantidad'];
        }

        // 4. Datos Gráfico Pastel
        $datosPlanes = $dashboardModel->ventasPorPlan();
        $planesLabels = [];
        $planesData = [];
        foreach($datosPlanes as $p) {
            $planesLabels[] = $p['nombre'];
            $planesData[] = $p['cantidad'];
        }

        // 5. KPIs SRI (facturación electrónica)
        $sriKpis   = $dashboardModel->kpisSriMes();
        $sriDiario = $dashboardModel->emisionDiariaSri();

        // Mapear data por día para llenar todos los días del mes (incluso sin emisión)
        $emisionPorDia = [];
        foreach ($sriDiario as $d) {
            $emisionPorDia[$d['dia']] = [
                'cant'  => (int)$d['cant'],
                'monto' => (float)$d['monto'],
            ];
        }
        $sriLabels = $sriCantidad = $sriMontos = [];
        $inicioMes = strtotime(date('Y-m-01'));
        $hoy       = strtotime(date('Y-m-d'));
        for ($t = $inicioMes; $t <= $hoy; $t = strtotime('+1 day', $t)) {
            $diaFmt = date('Y-m-d', $t);
            $sriLabels[]   = date('d', $t);
            $sriCantidad[] = $emisionPorDia[$diaFmt]['cant']  ?? 0;
            $sriMontos[]   = $emisionPorDia[$diaFmt]['monto'] ?? 0;
        }

        require_once '../app/views/home/index.php';
    }

    // Endpoint AJAX para filtros del dashboard
    public function filtrar() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }

        header('Content-Type: application/json');

        $planNombres = isset($_POST['plan_ids']) && is_array($_POST['plan_ids'])
                    ? array_map('trim', $_POST['plan_ids'])
                    : [];
        $busqueda = trim($_POST['busqueda'] ?? '');

        $dashboardModel = new Dashboard();

        $vencimientos  = $dashboardModel->filtrarVencimientos($planNombres, $busqueda);
        $totalSocios   = empty($planNombres) && empty($busqueda)
                         ? $dashboardModel->contarSociosActivos()
                         : $dashboardModel->contarSociosActivosFiltrado($planNombres);

        // Build rows HTML
        $icons = ['fa-rocket bg-warning', 'fa-fire bg-danger', 'fa-bolt bg-primary'];
        $rowsHtml = '';
        if (empty($vencimientos)) {
            $rowsHtml = '<tr><td colspan="4" class="text-center text-muted py-4">No hay vencimientos próximos</td></tr>';
        } else {
            foreach (array_slice($vencimientos, 0, 4) as $index => $v) {
                $iconClass = $icons[$index % count($icons)];
                $fecha     = date('d M Y', strtotime($v['fecha_fin']));
                $rowsHtml .= "
                <tr>
                    <td class='ps-2'>
                        <div class='d-flex align-items-center'>
                            <div class='icon-box d-flex align-items-center justify-content-center rounded-circle me-3 text-white {$iconClass}' style='width:32px;height:32px;'>
                                <i class='fas " . explode(' ', $iconClass)[0] . "'></i>
                            </div>
                            <div>
                                <h6 class='mb-0 fw-bold text-dark fs-6'>" . htmlspecialchars($v['socio']) . "</h6>
                                <span class='text-muted' style='font-size:0.7rem;'>Fin: {$fecha}</span>
                            </div>
                        </div>
                    </td>
                    <td class='fw-semibold text-dark'>" . htmlspecialchars($v['plan']) . "</td>
                    <td>
                        <div class='d-flex align-items-center'>
                            <div class='progress flex-grow-1 bg-light' style='height:6px;'>
                                <div class='progress-bar bg-danger rounded-pill' style='width:80%'></div>
                            </div>
                            <span class='ms-2 text-danger fw-bold' style='font-size:0.75rem;'>Vence</span>
                        </div>
                    </td>
                    <td class='text-end pe-2'><span class='badge-pending'>Por Vencer</span></td>
                </tr>";
            }
        }

        echo json_encode([
            'total_socios' => $totalSocios,
            'rows_html'    => $rowsHtml,
        ]);
        exit;
    }
}