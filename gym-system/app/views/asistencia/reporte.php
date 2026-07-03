<?php
if (!isset($_SESSION['user_id'])) { header('Location: /auth/index'); exit; }
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Asistencias - <?= htmlspecialchars($config['nombre_sistema'] ?? 'Gym System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <style>
        .kpi-card { border-radius: 18px; padding: 1.4rem 1.8rem; color: white; position: relative; overflow: hidden; }
        .kpi-card::after { content:''; position:absolute; right:-20px; top:-20px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.12); }
        .rank-bar { height: 8px; border-radius: 4px; background: linear-gradient(90deg, #6366F1, #8B5CF6); }
    </style>
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-clipboard-list text-primary me-2"></i>Reporte de Asistencias</h4>
            <p class="text-muted mb-0">Historial de ingresos al gimnasio filtrable por período y socio.</p>
        </div>
        <a href="/asistencia/exportarPDF?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?><?= $socio_id ? '&socio_id=' . $socio_id : '' ?>" 
           class="btn btn-danger rounded-pill fw-bold px-4" target="_blank">
            <i class="fas fa-file-pdf me-2"></i>Exportar PDF
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #6366F1, #4F46E5);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Total de Visitas</p>
                <h3 class="fw-black m-0"><?= $totalVisitas ?></h3>
                <i class="fas fa-door-open position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #10B981, #059669);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Promedio Diario</p>
                <h3 class="fw-black m-0"><?= $promDiario ?></h3>
                <i class="fas fa-chart-bar position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Socio Más Frecuente</p>
                <h5 class="fw-black m-0"><?= htmlspecialchars($liderr) ?></h5>
                <i class="fas fa-medal position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Filters + Table -->
        <div class="col-12 col-xl-8">
            <div class="module-container mb-4">
                <form method="GET" action="/asistencia/reporte" class="row g-3 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold text-dark small">Desde</label>
                        <input type="date" name="desde" class="form-control rounded-3" value="<?= htmlspecialchars($desde) ?>">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold text-dark small">Hasta</label>
                        <input type="date" name="hasta" class="form-control rounded-3" value="<?= htmlspecialchars($hasta) ?>">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold text-dark small">Socio (opcional)</label>
                        <select name="socio_id" class="form-select rounded-3">
                            <option value="">Todos los socios</option>
                            <?php foreach ($socios as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($socio_id == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button class="btn btn-primary rounded-3 fw-bold flex-fill"><i class="fas fa-search me-1"></i>Filtrar</button>
                        <a href="/asistencia/reporte" class="btn btn-light rounded-3 fw-bold"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>

            <div class="module-container">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><?= $totalVisitas ?> registros</h6>
                    <input type="text" id="searchReg" class="form-control form-control-sm rounded-pill" style="max-width:200px;" placeholder="🔍 Buscar...">
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="regTable">
                        <thead class="table-light">
                            <tr>
                                <th class="fw-bold text-muted small text-uppercase">Fecha y Hora</th>
                                <th class="fw-bold text-muted small text-uppercase">Socio</th>
                                <th class="fw-bold text-muted small text-uppercase">DNI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($registros)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-5">
                                <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>Sin registros en el período.
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($registros as $r): ?>
                            <tr>
                                <td>
                                    <span class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($r['fecha_hora'])) ?></span>
                                    <small class="text-muted d-block"><?= date('H:i', strtotime($r['fecha_hora'])) ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:32px;height:32px;flex-shrink:0;">
                                            <i class="fas fa-user text-primary" style="font-size:.75rem;"></i>
                                        </div>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($r['nombre']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted fw-semibold"><?= $r['dni'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ranking -->
        <div class="col-12 col-xl-4">
            <div class="module-container">
                <h6 class="fw-bold text-dark mb-4"><i class="fas fa-trophy text-warning me-2"></i>Top 5 Más Frecuentes</h6>
                <?php if (empty($ranking)): ?>
                <p class="text-muted text-center py-4">Sin datos en el período.</p>
                <?php else: ?>
                <?php $maxVisitas = $ranking[0]['visitas'] ?? 1; ?>
                <?php foreach ($ranking as $i => $r): ?>
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-black text-primary" style="min-width:20px;"><?= $i + 1 ?></span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($r['nombre']) ?></span>
                        </div>
                        <span class="badge bg-primary rounded-pill px-3"><?= $r['visitas'] ?> visitas</span>
                    </div>
                    <div class="bg-light rounded-pill" style="height:8px;">
                        <div class="rank-bar" style="width:<?= round(($r['visitas'] / $maxVisitas) * 100) ?>%;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchReg').addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#regTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
