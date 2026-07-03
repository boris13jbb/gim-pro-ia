<?php
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Financieros - <?= htmlspecialchars($config['nombre_sistema']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .kpi-card {
            background: white;
            border-radius: 20px;
            padding: 1.6rem 1.8rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: 1px solid rgba(0,0,0,0.04);
            transition: all 0.3s;
        }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        .kpi-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .kpi-value { font-size: 2rem; font-weight: 900; letter-spacing: -1px; line-height: 1; }
        .kpi-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #9CA3AF; margin-top: 0.3rem; }
        .chart-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .badge-ingreso { background: #D1FAE5; color: #065F46; }
        .badge-gasto   { background: #FEE2E2; color: #991B1B; }
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
    </style>
</head>
<body>
    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-4 mb-5 fade-in">

        <!-- Page Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark m-0">
                    <i class="fas fa-chart-line me-2 text-primary"></i> Reportes Financieros
                </h3>
                <p class="text-muted small mt-1 mb-0">
                    Período: <strong><?= date('d/m/Y', strtotime($desde)) ?></strong> al <strong><?= date('d/m/Y', strtotime($hasta)) ?></strong>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button onclick="ExportHelper.toExcel('tblMovimientos', 'Reporte_Financiero_<?= $desde ?>_<?= $hasta ?>', 'Reporte Financiero - <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?>')" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fas fa-file-excel me-2"></i> Excel
                </button>
                <button onclick="ExportHelper.toPDF('tblMovimientos', 'Reporte_Financiero_<?= $desde ?>_<?= $hasta ?>', 'Reporte Financiero — <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?>')" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fas fa-file-pdf me-2"></i> PDF
                </button>
                <button onclick="ExportHelper.print('tblMovimientos', 'Reporte Financiero — <?= date('d/m/Y', strtotime($desde)) ?> al <?= date('d/m/Y', strtotime($hasta)) ?>')" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fas fa-print me-2"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Filter Bar -->
        <form method="GET" action="/reportes/index">
            <div class="filter-bar mb-4 d-flex flex-wrap gap-3 align-items-center">
                <i class="fas fa-filter text-primary"></i>
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold text-muted small text-nowrap">Desde:</label>
                    <input type="date" name="desde" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $desde ?>">
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-semibold text-muted small text-nowrap">Hasta:</label>
                    <input type="date" name="hasta" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= $hasta ?>">
                </div>
                <!-- Shortcuts -->
                <div class="d-flex gap-2 ms-auto">
                    <?php
                    $shortcuts = [
                        'Este mes'  => [date('Y-m-01'), date('Y-m-d')],
                        'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month'))],
                        'Este año'  => [date('Y-01-01'), date('Y-m-d')],
                    ];
                    foreach ($shortcuts as $label => $rango):
                    ?>
                        <a href="/reportes/index?desde=<?= $rango[0] ?>&hasta=<?= $rango[1] ?>" class="btn btn-sm border-0 fw-bold
                            <?= ($desde == $rango[0] && $hasta == $rango[1]) ? 'btn-primary' : 'bg-light text-muted' ?>
                            rounded-pill px-3">
                            <?= $label ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                    <i class="fas fa-search me-1"></i> Filtrar
                </button>
            </div>
        </form>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <!-- Ingresos Membresías -->
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(99,102,241,0.1);">
                        <i class="fas fa-id-card text-primary"></i>
                    </div>
                    <div class="kpi-value text-primary"><?= $config['moneda'] ?><?= number_format($ingresosMem, 0) ?></div>
                    <div class="kpi-label">Ingresos Membresías</div>
                </div>
            </div>
            <!-- Ventas POS -->
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(16,185,129,0.1);">
                        <i class="fas fa-shopping-cart" style="color: #10B981;"></i>
                    </div>
                    <div class="kpi-value" style="color: #10B981;"><?= $config['moneda'] ?><?= number_format($ingresosPOS, 0) ?></div>
                    <div class="kpi-label">Ventas POS</div>
                </div>
            </div>
            <!-- Gastos -->
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon" style="background: rgba(239,68,68,0.1);">
                        <i class="fas fa-money-bill-wave" style="color: #EF4444;"></i>
                    </div>
                    <div class="kpi-value" style="color: #EF4444;"><?= $config['moneda'] ?><?= number_format($gastos, 0) ?></div>
                    <div class="kpi-label">Total Gastos</div>
                </div>
            </div>
            <!-- Utilidad Neta -->
            <div class="col-6 col-md-3">
                <div class="kpi-card" style="<?= $utilidad >= 0 ? 'background: linear-gradient(135deg, #059669, #34D399);' : 'background: linear-gradient(135deg, #DC2626, #F87171);' ?>">
                    <div class="kpi-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-chart-line text-white"></i>
                    </div>
                    <div class="kpi-value text-white"><?= $config['moneda'] ?><?= number_format(abs($utilidad), 0) ?></div>
                    <div class="kpi-label" style="color: rgba(255,255,255,0.8);"><?= $utilidad >= 0 ? 'Utilidad Neta ✓' : 'Pérdida Neta ✗' ?></div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <!-- Ingresos por Mes -->
            <div class="col-md-8">
                <div class="chart-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark m-0"><i class="fas fa-chart-bar me-2 text-primary"></i> Ingresos por Mes</h6>
                    </div>
                    <canvas id="chartIngresos" height="90"></canvas>
                </div>
            </div>
            <!-- Métodos de Pago POS -->
            <div class="col-md-4">
                <div class="chart-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold text-dark m-0"><i class="fas fa-credit-card me-2 text-primary"></i> Métodos de Pago (POS)</h6>
                    </div>
                    <?php if (empty($porMetodoPago)): ?>
                        <div class="text-center text-muted py-5 opacity-50">
                            <i class="fas fa-chart-pie fa-3x mb-3 d-block"></i>
                            Sin ventas POS en este período
                        </div>
                    <?php else: ?>
                        <canvas id="chartMetodos" height="180"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Movements Table -->
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="fas fa-list-ul me-2 text-primary"></i>
                    Detalle de Movimientos
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= count($movimientos) ?></span>
                </span>
                <span class="text-muted small"><?= date('d/m/Y', strtotime($desde)) ?> — <?= date('d/m/Y', strtotime($hasta)) ?></span>
            </div>
            <div class="p-4 pt-3">
                <div class="table-responsive">
                    <table id="tblMovimientos" class="table table-hover align-middle border-0" data-no-datatable style="width:100%;">
                        <thead>
                            <tr>
                                <th class="border-0 bg-transparent text-muted">Fecha</th>
                                <th class="border-0 bg-transparent text-muted">Descripción</th>
                                <th class="border-0 bg-transparent text-muted text-end">Monto</th>
                                <th class="border-0 bg-transparent text-muted text-center">Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($movimientos)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block opacity-30"></i>
                                    Sin movimientos en este período
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td class="text-muted small fw-semibold"><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($m['descripcion']) ?></td>
                                <td class="text-end fw-bold <?= ($m['tipo'] === 'gasto') ? 'text-danger' : 'text-success' ?>">
                                    <?= ($m['tipo'] === 'gasto') ? '-' : '+' ?><?= $config['moneda'] ?><?= number_format($m['monto'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3 py-2 fw-bold badge-<?= $m['tipo'] ?>">
                                        <?= ucfirst($m['tipo']) ?>
                                    </span>
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

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
    // ── Gráfico Ingresos por Mes
    const porMes = <?= json_encode($porMes) ?>;
    const labels = porMes.map(d => { const [y, m] = d.mes.split('-'); return new Date(y, m-1).toLocaleString('es', {month:'short', year:'numeric'}); });
    const totales = porMes.map(d => parseFloat(d.total));

    new Chart(document.getElementById('chartIngresos'), {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Ingresos (<?= $config['moneda'] ?>)',
                data: totales,
                backgroundColor: 'rgba(99,102,241,0.15)',
                borderColor: 'rgba(99,102,241,1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => '<?= $config['moneda'] ?>' + v.toLocaleString() } },
                x: { grid: { display: false } }
            }
        }
    });

    // ── Gráfico Métodos de Pago
    const metodosData = <?= json_encode($porMetodoPago) ?>;
    if (metodosData.length > 0) {
        new Chart(document.getElementById('chartMetodos'), {
            type: 'doughnut',
            data: {
                labels: metodosData.map(d => d.metodo_pago.charAt(0).toUpperCase() + d.metodo_pago.slice(1)),
                datasets: [{
                    data: metodosData.map(d => parseFloat(d.total)),
                    backgroundColor: ['#6366F1','#10B981','#F59E0B','#EF4444'],
                    borderWidth: 0,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 15, font: { weight: 'bold' } } },
                    tooltip: { callbacks: { label: ctx => ' <?= $config['moneda'] ?>' + parseFloat(ctx.parsed).toFixed(2) } }
                }
            }
        });
    }
    </script>
</body>
</html>
