<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-card {
            border-radius: 16px;
            color: white;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        }

        .kpi-title { font-size: 0.85rem; font-weight: 500; opacity: 0.9; }
        .kpi-value { font-size: 1.8rem; font-weight: 700; margin: 0; line-height: 1.2; }
        .kpi-subtitle { font-size: 0.75rem; opacity: 0.8; }
        
        .kpi-bars-bg {
            position: absolute;
            bottom: 10px; right: 10px;
            height: 40px; width: 80px;
            background: url('data:image/svg+xml;utf8,<svg width="80" height="40" viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg"><rect x="0" y="20" width="4" height="20" fill="white" fill-opacity="0.3" rx="2"/><rect x="8" y="10" width="4" height="30" fill="white" fill-opacity="0.4" rx="2"/><rect x="16" y="25" width="4" height="15" fill="white" fill-opacity="0.2" rx="2"/><rect x="24" y="5" width="4" height="35" fill="white" fill-opacity="0.5" rx="2"/><rect x="32" y="15" width="4" height="25" fill="white" fill-opacity="0.3" rx="2"/><rect x="40" y="28" width="4" height="12" fill="white" fill-opacity="0.2" rx="2"/><rect x="48" y="8" width="4" height="32" fill="white" fill-opacity="0.6" rx="2"/><rect x="56" y="18" width="4" height="22" fill="white" fill-opacity="0.4" rx="2"/><rect x="64" y="22" width="4" height="18" fill="white" fill-opacity="0.3" rx="2"/><rect x="72" y="12" width="4" height="28" fill="white" fill-opacity="0.5" rx="2"/></svg>') no-repeat right bottom;
        }

        .card-socios { background: var(--grad-purple); }
        .card-ingresos { background: var(--grad-pink); }
        .card-gastos { background: var(--grad-orange); }
        .card-utilidad { background: var(--grad-teal); }

        .chart-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* Custom Checkbox for Data Filter */
        .custom-checkbox .form-check-input {
            width: 1.2em; height: 1.2em;
            margin-top: 0.1em;
            border: 2px solid #E5E7EB;
        }
        .custom-checkbox .form-check-input:checked {
            background-color: var(--gym-primary); border-color: var(--gym-primary);
        }
        
        /* Table overrides */
        .table-budget th {
            text-transform: none; font-size: 0.8rem; font-weight: 600;
            color: var(--gym-text-dark); border-bottom: 1px solid #F3F4F6 !important;
            background: transparent !important; padding: 1rem 0.5rem;
        }
        .table-budget td {
            font-size: 0.85rem; padding: 1rem 0.5rem; border-bottom: 1px solid #F3F4F6;
            vertical-align: middle;
        }
        
        .badge-completed { background-color: #E0F2FE; color: #0EA5E9; padding: 0.4em 0.8em; border-radius: 6px; font-weight: 600; }
        .badge-pending { background-color: #FEF3C7; color: #D97706; padding: 0.4em 0.8em; border-radius: 6px; font-weight: 600; }

        .icon-box {
            width: 36px; height: 36px; border-radius: 8px;
            display: inline-flex; align-items: center; justify-content: center;
            color: white; font-size: 1rem;
        }

        /* Quick Access card hover */
        .quick-access-card:hover .card {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(99,102,241,0.15) !important;
        }
        .quick-access-card .card { cursor: pointer; }
    </style>
</head>

<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <!-- Wrapper div is opened in navbar.php -->

        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin'): ?>

            <!-- ── QUICK ACCESS ROW ──────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h6 class="fw-bold text-dark mb-3 fs-5"><i class="fas fa-bolt text-warning me-2"></i>Acceso Rápido</h6>
                    <div class="row g-3">

                        <?php
                        $quickLinks = [
                            ['href'=>'/socios/crear',         'icon'=>'fa-user-plus',          'label'=>'Nuevo Socio',       'grad'=>'linear-gradient(135deg,#8B5CF6,#6D28D9)'],
                            ['href'=>'/suscripciones/crear',  'icon'=>'fa-file-invoice-dollar', 'label'=>'Suscripción',       'grad'=>'linear-gradient(135deg,#EC4899,#BE185D)'],
                            ['href'=>'/caja/index',             'icon'=>'fa-cash-register',       'label'=>'Caja',              'grad'=>'linear-gradient(135deg,#F97316,#D97706)'],
                            ['href'=>'/pos/index',              'icon'=>'fa-shopping-cart',       'label'=>'Punto de Venta',    'grad'=>'linear-gradient(135deg,#14B8A6,#0D9488)'],
                            ['href'=>'/asistencia/index',       'icon'=>'fa-clock',               'label'=>'Asistencia',        'grad'=>'linear-gradient(135deg,#6366F1,#4338CA)'],
                            ['href'=>'/notificaciones/index',   'icon'=>'fa-whatsapp fab',        'label'=>'Notificaciones',    'grad'=>'linear-gradient(135deg,#22C55E,#15803D)'],
                            ['href'=>'/reportes/index',         'icon'=>'fa-chart-line',          'label'=>'Reportes',          'grad'=>'linear-gradient(135deg,#0EA5E9,#0284C7)'],
                            ['href'=>'/usuarios/index',         'icon'=>'fa-user-shield',         'label'=>'Usuarios',          'grad'=>'linear-gradient(135deg,#F59E0B,#D97706)'],
                        ];
                        foreach($quickLinks as $ql):
                            $iconClass = strpos($ql['icon'], ' fab') !== false
                                ? 'fab ' . explode(' ', $ql['icon'])[0]
                                : 'fas ' . $ql['icon'];
                        ?>
                        <div class="col-6 col-sm-4 col-md-3 col-xl-1-5">
                            <a href="<?= $ql['href'] ?>" class="text-decoration-none quick-access-card d-block">
                                <div class="card border-0 shadow-sm text-center p-3 h-100" style="border-radius:16px; transition: transform .18s, box-shadow .18s;">
                                    <div class="mx-auto mb-2 d-flex align-items-center justify-content-center"
                                         style="width:52px;height:52px;border-radius:14px;background:<?= $ql['grad'] ?>;">
                                        <i class="<?= $iconClass ?> text-white fs-5"></i>
                                    </div>
                                    <span class="fw-semibold text-dark" style="font-size:.8rem;"><?= $ql['label'] ?></span>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>
            </div>
            <!-- ── END QUICK ACCESS ─────────────────────────── -->

            <!-- ── KPI CARDS ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi-card card-socios">
                        <div class="d-flex justify-content-between">
                            <span class="kpi-title">Socios Activos</span>
                            <i class="fas fa-ellipsis-h text-white-50"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-end mb-1">
                                <h3 class="kpi-value me-2" id="kpi-socios-activos"><?= $totalSocios ?></h3>
                                <span class="kpi-subtitle text-white-50">+5% Mes</span>
                            </div>
                            <div class="kpi-bars-bg"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi-card card-ingresos">
                        <div class="d-flex justify-content-between">
                            <span class="kpi-title">Ingresos Mes</span>
                            <i class="fas fa-ellipsis-h text-white-50"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-end mb-1">
                                <h3 class="kpi-value me-2"><?= number_format($ingresosMes, 0) ?></h3>
                                <span class="kpi-subtitle text-white-50 text-truncate">+<?= $config['moneda'] ?> Ventas</span>
                            </div>
                            <div class="kpi-bars-bg"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi-card card-gastos">
                        <div class="d-flex justify-content-between">
                            <span class="kpi-title">Gastos Mes</span>
                            <i class="fas fa-ellipsis-h text-white-50"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-end mb-1">
                                <h3 class="kpi-value me-2"><?= number_format($gastosMes ?? 0, 0) ?></h3>
                                <span class="kpi-subtitle text-white-50">Salidas</span>
                            </div>
                            <div class="kpi-bars-bg"></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="kpi-card card-utilidad">
                        <div class="d-flex justify-content-between">
                            <span class="kpi-title">Utilidad Neta</span>
                            <i class="fas fa-ellipsis-h text-white-50"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-end mb-1">
                                <h3 class="kpi-value me-2"><?= number_format($utilidad ?? 0, 0) ?></h3>
                                <span class="kpi-subtitle text-white-50">Rentabilidad</span>
                            </div>
                            <div class="kpi-bars-bg"></div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ── END KPI CARDS ─────────────────────────────── -->

            <!-- ── KPIs SRI — Facturación Electrónica ──────── -->
            <?php if (!empty($sriKpis)): ?>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold text-dark mb-0">
                            <i class="fas fa-receipt text-primary"></i> Facturación Electrónica SRI —
                            <?php
                                $_meses = ['enero','febrero','marzo','abril','mayo','junio',
                                           'julio','agosto','septiembre','octubre','noviembre','diciembre'];
                                echo ucfirst($_meses[(int)date('n') - 1]) . ' ' . date('Y');
                            ?>
                        </h6>
                        <a href="/facturacionelectronica/index" class="btn btn-sm btn-outline-primary">
                            Ver bandeja <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <p class="small text-muted mb-1"><i class="fas fa-check-circle text-success"></i> Autorizados</p>
                            <h4 class="fw-bold mb-0 text-success"><?= (int)($sriKpis['aceptados'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <p class="small text-muted mb-1"><i class="fas fa-clock text-secondary"></i> Pendientes</p>
                            <h4 class="fw-bold mb-0 text-secondary"><?= (int)($sriKpis['pendientes'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <p class="small text-muted mb-1"><i class="fas fa-times-circle text-danger"></i> Rechazados</p>
                            <h4 class="fw-bold mb-0 text-danger"><?= (int)($sriKpis['rechazados'] ?? 0) ?></h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-3">
                            <p class="small text-muted mb-1"><i class="fas fa-percent text-warning"></i> IVA cobrado</p>
                            <h5 class="fw-bold mb-0"><?= $config['moneda'] ?> <?= number_format($sriKpis['igv_mes'] ?? 0, 2) ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg,#6366F1,#7c3aed); color:#fff;">
                        <div class="card-body p-3">
                            <p class="small mb-1 text-white-50"><i class="fas fa-coins"></i> Total facturado (autorizado)</p>
                            <h3 class="fw-bold mb-0"><?= $config['moneda'] ?> <?= number_format($sriKpis['total_mes'] ?? 0, 2) ?></h3>
                            <div class="d-flex gap-3 small text-white-50 mt-2">
                                <span><i class="fas fa-file-invoice-dollar"></i> <?= (int)($sriKpis['facturas'] ?? 0) ?> facturas</span>
                                <span><i class="fas fa-undo"></i> <?= (int)($sriKpis['ncreditos'] ?? 0) ?> NC</span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!empty($sriDiario)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <p class="small text-muted mb-2"><i class="fas fa-chart-line"></i> Emisión diaria (autorizados)</p>
                            <!-- Contenedor con altura fija para evitar el bug de crecimiento infinito de Chart.js -->
                            <div style="position: relative; height: 220px; width: 100%;">
                                <canvas id="chartSri"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <!-- ── /KPIs SRI ────────────────────────────────── -->

            <!-- ── CHARTS ROW ─────────────────────────────────── -->
            <div class="row g-3 mb-4">
                <!-- Bar Chart: Progreso Financiero -->
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm h-100 p-2">
                        <div class="card-header d-flex justify-content-between align-items-start pb-0">
                            <div>
                                <h6 class="fw-bold text-dark fs-5 mb-1">Progreso Financiero</h6>
                                <p class="text-muted small">Evolución de ventas mensuales</p>
                            </div>
                            <select class="form-select form-select-sm w-auto border-0 text-muted fw-semibold bg-light shadow-none">
                                <option>Este Año</option>
                            </select>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper">
                                <canvas id="ventasChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doughnut Chart: Detalle de Ventas -->
                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm h-100 p-2">
                        <div class="card-header d-flex justify-content-between align-items-center pb-0">
                            <h6 class="fw-bold text-dark fs-5 mb-0">Detalle de Ventas</h6>
                            <i class="fas fa-ellipsis-h text-muted"></i>
                        </div>
                        <div class="card-body pt-2 d-flex flex-column align-items-center justify-content-center">
                            <div class="d-flex justify-content-between w-100 px-3 mb-2 opacity-50 small">
                                <span><i class="fas fa-chevron-left"></i></span>
                                <span class="fw-bold text-dark"><?= date('Y') ?></span>
                                <span><i class="fas fa-chevron-right"></i></span>
                            </div>
                            <div class="chart-wrapper" style="height: 180px; position: relative;">
                                <canvas id="planesChart"></canvas>
                                <div class="position-absolute top-50 start-50 translate-middle text-center" style="margin-top: 5px;">
                                    <span class="text-muted d-block small" style="font-size: 0.65rem;">Total</span>
                                    <span class="fw-bold text-primary fs-5"><?= $config['moneda'] ?><?= number_format($ingresosMes, 0) ?></span>
                                </div>
                            </div>
                            <!-- Top Planes -->
                            <div class="w-100 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold fs-6 mb-0">Top Planes</h6>
                                    <select class="form-select form-select-sm w-auto border-0 text-muted py-0 shadow-none" style="font-size: 0.75rem;">
                                        <option>Mes</option>
                                    </select>
                                </div>
                                <?php 
                                $totalVentas = array_sum($planesData ?? []);
                                $topPlanes = [];
                                if (!empty($planesLabels) && !empty($planesData)) {
                                    foreach($planesLabels as $i => $label) {
                                        $topPlanes[] = [
                                            'label' => $label,
                                            'data' => $planesData[$i],
                                            'percent' => $totalVentas > 0 ? round(($planesData[$i] / $totalVentas) * 100) : 0
                                        ];
                                    }
                                    usort($topPlanes, function($a, $b) { return $b['data'] <=> $a['data']; });
                                    $topPlanes = array_slice($topPlanes, 0, 2);
                                }
                                $iconGradients = [
                                    'linear-gradient(135deg, #8B5CF6, #EC4899)', 
                                    'linear-gradient(135deg, #3B82F6, #14B8A6)'
                                ];
                                $icons = ['fa-star', 'fa-cube'];
                                foreach($topPlanes as $index => $plan): 
                                ?>
                                <div class="d-flex align-items-center <?= $index === 0 ? 'mb-3' : '' ?>">
                                    <div class="icon-box text-white p-2 text-center me-3" style="background: <?= $iconGradients[$index] ?? $iconGradients[0] ?>; border-radius: 8px;">
                                        <i class="fas <?= $icons[$index] ?? 'fa-check' ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 fw-bold fs-6 text-dark text-truncate" style="max-width: 120px;"><?= htmlspecialchars($plan['label']) ?></h6>
                                        <span class="text-muted" style="font-size: 0.7rem;">Top Ventas</span>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-muted d-block" style="font-size: 0.7rem;">Porcentaje</span>
                                        <span class="fw-bold text-success" style="font-size: 0.85rem;"><i class="fas fa-arrow-up fa-sm"></i> <?= $plan['percent'] ?>%</span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if(empty($topPlanes)): ?>
                                     <div class="text-center text-muted small py-2">No hay datos</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ── END CHARTS ROW ─────────────────────────────── -->

            <!-- ── TABLE + MINI CHART ROW ────────────────────── -->
            <div class="row g-3">
                <!-- Próximos Vencimientos -->
                <div class="col-12 col-xl-8">
                    <div class="card shadow-sm h-100 p-2">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center pb-0">
                            <div>
                                <h6 class="fw-bold text-dark fs-5 mb-1">Próximos Vencimientos</h6>
                                <p class="text-muted small">Socios por renovar en 7 días</p>
                            </div>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm w-auto border text-muted shadow-none bg-light rounded-pill px-3">
                                    <option>Todos</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-budget mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-2">Socio</th>
                                            <th>Tipo de Plan</th>
                                            <th>Progreso</th>
                                            <th class="text-end pe-2">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-vencimientos-body">
                                        <?php if (empty($vencimientos)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">No hay vencimientos próximos</td></tr>
                                        <?php else: ?>
                                            <?php 
                                            $icons = ['fa-rocket text-white bg-warning', 'fa-fire text-white bg-danger', 'fa-bolt text-white bg-primary'];
                                            foreach (array_slice($vencimientos, 0, 4) as $index => $v): 
                                                $iconClass = $icons[$index % count($icons)];
                                            ?>
                                            <tr>
                                                <td class="ps-2">
                                                    <div class="d-flex align-items-center">
                                                        <div class="icon-box d-flex align-items-center justify-content-center rounded-circle me-3 <?= $iconClass ?>" style="width: 32px; height: 32px;">
                                                            <i class="fas <?= explode(' ', $iconClass)[0] ?>"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0 fw-bold text-dark fs-6"><?= $v['socio'] ?></h6>
                                                            <span class="text-muted" style="font-size: 0.7rem;">Fin: <?= date('d M Y', strtotime($v['fecha_fin'])) ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-dark"><?= $v['plan'] ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 bg-light" style="height: 6px;">
                                                            <div class="progress-bar bg-danger rounded-pill" style="width: 80%"></div>
                                                        </div>
                                                        <span class="ms-2 text-danger fw-bold" style="font-size: 0.75rem;">Vence</span>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-2">
                                                    <span class="badge-pending">Por Vencer</span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Planes -->
                <div class="col-12 col-xl-4">
                    <div class="card shadow-sm h-100 p-2">
                        <div class="card-header d-flex justify-content-between align-items-center pb-0">
                            <h6 class="fw-bold text-dark fs-5 mb-0">Resumen de Planes</h6>
                            <i class="fas fa-ellipsis-h text-muted"></i>
                        </div>
                        <div class="card-body pt-3">
                            <div class="chart-wrapper" style="height: 250px;">
                                <canvas id="miniDataChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ── END TABLE + MINI CHART ─────────────────────── -->

            <script>
                // Common Chart Options
                Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
                Chart.defaults.color = '#A3AED0';
                Chart.defaults.elements.bar.borderRadius = 8;
                Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(43, 54, 116, 0.9)';
                Chart.defaults.plugins.tooltip.padding = 12;

                // 1. Company Progress (Bar Chart)
                const ctxVentas = document.getElementById('ventasChart').getContext('2d');
                
                // Color mapping to match the image gradient bars (Purple, Orange, Pink, Teal...)
                const barColors = [
                    ctxVentas.createLinearGradient(0,0,0,400),
                    ctxVentas.createLinearGradient(0,0,0,400),
                    ctxVentas.createLinearGradient(0,0,0,400),
                    ctxVentas.createLinearGradient(0,0,0,400),
                    ctxVentas.createLinearGradient(0,0,0,400)
                ];
                barColors[0].addColorStop(0, '#8B5CF6'); barColors[0].addColorStop(1, '#6D28D9');
                barColors[1].addColorStop(0, '#F97316'); barColors[1].addColorStop(1, '#D97706');
                barColors[2].addColorStop(0, '#EC4899'); barColors[2].addColorStop(1, '#BE185D');
                barColors[3].addColorStop(0, '#6366F1'); barColors[3].addColorStop(1, '#4338CA');
                barColors[4].addColorStop(0, '#F59E0B'); barColors[4].addColorStop(1, '#B45309');

                new Chart(ctxVentas, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Ventas',
                            data: <?= json_encode($data) ?>,
                            backgroundColor: barColors,
                            barThickness: 24,
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { grid: { color: '#F4F7FE', drawBorder: false }, border: { display: false } },
                            x: { grid: { display: false }, border: { display: false } }
                        }
                    }
                });

                // 2. Sales Detail (Doughnut)
                const ctxPlanes = document.getElementById('planesChart').getContext('2d');
                new Chart(ctxPlanes, {
                    type: 'doughnut',
                    data: {
                        labels: <?= json_encode($planesLabels) ?>,
                        datasets: [{
                            data: <?= json_encode($planesData) ?>,
                            backgroundColor: ['#6366F1', '#EC4899', '#F97316', '#2DD4BF', '#FBBF24'],
                            borderWidth: 0,
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '80%',
                        plugins: { legend: { display: false } }
                    }
                });

                // 3. Balance Sheet (Area Curve)
                const elBalance = document.getElementById('balanceChart');
                if (elBalance) {
                const ctxBalance = elBalance.getContext('2d');
                let gradArea = ctxBalance.createLinearGradient(0,0,0,200);
                gradArea.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
                gradArea.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
                
                // Mock balance data using $data array for shape
                let balanceData = <?= json_encode($data) ?>.map(v => v * 1500); 

                new Chart(ctxBalance, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($labels) ?>,
                        datasets: [{
                            label: 'Balance',
                            data: balanceData,
                            borderColor: '#6366F1',
                            borderWidth: 3,
                            backgroundColor: gradArea,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6366F1',
                            pointBorderWidth: 2,
                            pointRadius: 3
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { display: true, grid: { color: '#F4F7FE', drawBorder: false }, ticks: { font: {size: 10} } },
                            x: { display: true, grid: { display: false } }
                        }
                    }
                });
                }

                // 4. Resumen de Planes (Bar Chart)
                const ctxMini = document.getElementById('miniDataChart').getContext('2d');
                new Chart(ctxMini, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(!empty($planesLabels) ? array_slice($planesLabels, 0, 4) : ['N/A']) ?>,
                        datasets: [{
                            data: <?= json_encode(!empty($planesData) ? array_slice($planesData, 0, 4) : [0]) ?>,
                            backgroundColor: ['#6366F1', '#EC4899', '#F97316', '#E0E7FF'],
                            barThickness: 30,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' Ventas';
                                }
                            }
                        }},
                        scales: {
                            x: { grid: { display: false }, border: { display: false } },
                            y: { display: false }
                        }
                    }
                });

                // ── FILTROS DEL DASHBOARD ────────────────────────────────────────────
                let filtroTimer = null;

                function aplicarFiltros() {
                    const busqueda = document.getElementById('filtro-busqueda')?.value?.trim() ?? '';
                    const checks   = document.querySelectorAll('.filtro-plan-check:checked');
                    const nombres  = Array.from(checks).map(c => c.value);

                    const body = new URLSearchParams();
                    body.append('busqueda', busqueda);
                    nombres.forEach(n => body.append('plan_ids[]', n));

                    // Spinner visual
                    const tbody = document.getElementById('tabla-vencimientos-body');
                    if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3"><span class="spinner-border spinner-border-sm text-primary"></span></td></tr>';

                    fetch('/home/filtrar', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: body.toString()
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (tbody) tbody.innerHTML = data.rows_html ?? '';
                        const kpi = document.getElementById('kpi-socios-activos');
                        if (kpi) kpi.textContent = data.total_socios ?? 0;
                    })
                    .catch(() => {
                        if (tbody) tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Error al cargar datos</td></tr>';
                    });
                }

                // Búsqueda con debounce
                const inputBusqueda = document.getElementById('filtro-busqueda');
                if (inputBusqueda) {
                    inputBusqueda.addEventListener('input', () => {
                        clearTimeout(filtroTimer);
                        filtroTimer = setTimeout(aplicarFiltros, 380);
                    });
                }

                // Checkboxes de planes
                document.querySelectorAll('.filtro-plan-check').forEach(chk => {
                    chk.addEventListener('change', aplicarFiltros);
                });

                // Limpiar filtros
                const btnLimpiar = document.getElementById('btn-limpiar-filtros');
                if (btnLimpiar) {
                    btnLimpiar.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (inputBusqueda) inputBusqueda.value = '';
                        document.querySelectorAll('.filtro-plan-check').forEach(c => c.checked = true);
                        aplicarFiltros();
                    });
                }

                // Ver más planes
                const btnVerMas = document.getElementById('btn-ver-mas-planes');
                if (btnVerMas) {
                    btnVerMas.addEventListener('click', function(e) {
                        e.preventDefault();
                        document.querySelectorAll('.filtro-plan-extra').forEach(el => el.classList.remove('d-none'));
                        this.remove();
                    });
                }
                // ────────────────────────────────────────────────────────────────────

                // ── Gráfico SRI (emisión diaria del mes actual) ──
                const elSri = document.getElementById('chartSri');
                if (elSri) {
                    const sLabels   = <?= json_encode($sriLabels ?? []) ?>;
                    const sCantidad = <?= json_encode($sriCantidad ?? []) ?>;
                    const sMontos   = <?= json_encode($sriMontos ?? []) ?>;

                    new Chart(elSri.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels: sLabels,
                            datasets: [
                                {
                                    type: 'bar',
                                    label: 'Comprobantes',
                                    data: sCantidad,
                                    backgroundColor: 'rgba(99,102,241,0.75)',
                                    borderColor: '#6366F1',
                                    borderWidth: 1,
                                    borderRadius: 6,
                                    yAxisID: 'y'
                                },
                                {
                                    type: 'line',
                                    label: 'Monto (<?= $config['moneda'] ?>)',
                                    data: sMontos,
                                    borderColor: '#10B981',
                                    backgroundColor: 'rgba(16,185,129,0.15)',
                                    fill: true,
                                    tension: 0.35,
                                    pointBackgroundColor: '#10B981',
                                    pointRadius: 3,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            scales: {
                                x: {
                                    title: { display: true, text: 'Día del mes' },
                                    grid:  { display: false }
                                },
                                y:  {
                                    type: 'linear', position: 'left',
                                    beginAtZero: true,
                                    ticks: { stepSize: 1, precision: 0 },
                                    title: { display: true, text: '# CPE' }
                                },
                                y1: {
                                    type: 'linear', position: 'right',
                                    beginAtZero: true,
                                    grid: { drawOnChartArea: false },
                                    title: { display: true, text: 'Monto (<?= $config['moneda'] ?>)' }
                                }
                            },
                            plugins: {
                                legend: { position: 'bottom' },
                                tooltip: {
                                    callbacks: {
                                        title: (items) => 'Día ' + items[0].label,
                                    }
                                }
                            }
                        }
                    });
                }

            </script>

        <?php else: ?>
            // Normal User View
            <div class="row align-items-center vh-100 px-4">
                <div class="col-12 text-center p-5 bg-white shadow-sm" style="border-radius: 20px;">
                    <i class="fas fa-user-circle fa-4x text-primary mb-4"></i>
                    <h1 class="fw-bold text-dark">¡Bienvenido, <?= $_SESSION['user_name'] ?? 'Usuario' ?>!</h1>
                    <p class="text-muted">Tu perfil actual: <?= ucfirst($_SESSION['user_rol'] ?? 'Invitado') ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- Close wrapper -->
    <?php require_once '../app/views/inc/footer.php'; ?>
    
</body>
</html>
