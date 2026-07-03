<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Progreso del Socio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <style>
        .nav-pills-custom .nav-link {
            color: #495057;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 50rem;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-pills-custom .nav-link:hover {
            background-color: #f8f9fa;
        }
        .nav-pills-custom .nav-link.active {
            color: #fff;
            background-color: #6366F1; /* Primary theme color */
            border-color: #6366F1;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
        }
    </style>

    <div class="container-fluid px-4 mt-4 mb-5">
        
        <!-- Header Profile Card -->
        <div class="card module-container border-0 shadow-sm mb-4">
            <div class="card-body d-flex flex-wrap align-items-center p-4">
                <div class="me-4 position-relative">
                    <?php if(!empty($socio['foto'])): ?>
                        <img src="/img/socios/<?= $socio['foto'] ?>" class="rounded-circle shadow-sm border border-2 border-white" width="90" height="90" style="object-fit:cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary bg-opacity-10 text-secondary d-flex align-items-center justify-content-center" style="width: 90px; height: 90px;">
                            <i class="fas fa-user-circle fa-4x text-opacity-50"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Info -->
                <div class="flex-grow-1 mb-3 mb-md-0">
                    <h2 class="fw-bold text-dark mb-1"><?= $socio['nombre'] ?></h2>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-light border text-secondary px-3 py-2 rounded-pill"><i class="fas fa-id-card me-1 text-primary"></i> <?= $socio['dni'] ?></span>
                        <span class="badge bg-light border text-secondary px-3 py-2 rounded-pill"><i class="fas fa-phone me-1 text-success"></i> <?= $socio['telefono'] ?></span>
                    </div>
                </div>
                <!-- Actions -->
                <div class="ms-md-auto d-flex gap-2">
                    <a href="/socios/index" class="btn btn-light border rounded-pill px-4 fw-bold text-secondary shadow-sm transition-hover">Volver</a>
                    <a href="/socios/editar/<?= $socio['id'] ?>" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm transition-hover"><i class="fas fa-edit me-1"></i> Editar</a>
                </div>
            </div>
        </div>

        <!-- Custom Pill Tabs -->
        <ul class="nav nav-pills nav-pills-custom mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="medidas-tab" data-bs-toggle="pill" data-bs-target="#medidas" type="button" role="tab" aria-selected="true">
                    <i class="fas fa-weight me-2"></i> Medidas y Gráficos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rutina-tab" data-bs-toggle="pill" data-bs-target="#rutina" type="button" role="tab" aria-selected="false">
                    <i class="fas fa-dumbbell me-2"></i> Rutina de Entrenamiento
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            
            <div class="tab-pane fade show active" id="medidas">
                <div class="row g-4">
                    <div class="col-xl-4 col-lg-5">
                        <div class="card module-container border-0 shadow-sm h-100">
                            <div class="card-header bg-white border-0 pt-4 pb-2">
                                <h5 class="fw-bold text-dark m-0"><i class="fas fa-plus-circle text-primary me-2"></i> Registrar Nueva Medida</h5>
                            </div>
                            <div class="card-body px-4 pb-4">
                                <form action="/progreso/guardar_medida" method="POST" class="needs-validation">
                                    <input type="hidden" name="socio_id" value="<?= $socio['id'] ?>">
                                    
                                    <div class="form-floating mb-3">
                                        <input type="date" name="fecha" id="fecha" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                                        <label for="fecha">Fecha del Registro</label>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="form-floating">
                                                <input type="number" step="0.01" name="peso" id="peso" class="form-control rounded-3 border-primary border-opacity-50" placeholder="0.0" required>
                                                <label for="peso" class="text-primary fw-bold">Peso (Kg) *</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-floating">
                                                <input type="number" step="0.01" name="grasa" id="grasa" class="form-control rounded-3" placeholder="0.0">
                                                <label for="grasa">% de Grasa</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-floating">
                                                <input type="number" step="0.01" name="cintura" id="cintura" class="form-control rounded-3" placeholder="0.0">
                                                <label for="cintura">Cintura (cm)</label>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-floating">
                                                <input type="number" step="0.01" name="brazo" id="brazo" class="form-control rounded-3" placeholder="0.0">
                                                <label for="brazo">Brazo (cm)</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button class="btn btn-primary w-100 mt-4 rounded-pill fw-bold py-2 shadow-sm transition-hover">
                                        <i class="fas fa-save me-1"></i> Guardar Progreso
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8 col-lg-7">
                        
                        <!-- Multi-Metric Chart Card -->
                        <div class="card module-container border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold text-dark m-0"><i class="fas fa-chart-line text-primary me-2"></i>Evolución de Métricas</h5>
                                <div class="d-flex gap-2 flex-wrap" id="chartToggles">
                                    <button class="btn btn-sm btn-primary rounded-pill toggle-metric active" data-idx="0">Peso kg</button>
                                    <button class="btn btn-sm btn-outline-warning rounded-pill toggle-metric" data-idx="1">% Grasa</button>
                                    <button class="btn btn-sm btn-outline-success rounded-pill toggle-metric" data-idx="2">Cintura cm</button>
                                    <button class="btn btn-sm btn-outline-info rounded-pill toggle-metric" data-idx="3">Brazo cm</button>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <?php if (count($medidas) < 2): ?>
                                <p class="text-muted text-center py-3"><i class="fas fa-info-circle me-2"></i>Agrega al menos 2 medidas para ver el gráfico de evolución.</p>
                                <?php else: ?>
                                <canvas id="multiChart" style="max-height: 260px;"></canvas>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Summary Delta Cards -->
                        <?php if (count($medidas) >= 2):
                            $primera = reset($medidas);
                            $ultima  = end($medidas);
                            $metrics = [
                                ['label' => 'Peso', 'unit' => 'kg', 'key' => 'peso', 'icon' => 'fa-weight', 'color' => 'primary'],
                                ['label' => '% Grasa', 'unit' => '%', 'key' => 'grasa', 'icon' => 'fa-fire', 'color' => 'warning'],
                                ['label' => 'Cintura', 'unit' => 'cm', 'key' => 'cintura', 'icon' => 'fa-circle', 'color' => 'success'],
                                ['label' => 'Brazo', 'unit' => 'cm', 'key' => 'brazo', 'icon' => 'fa-hand-rock', 'color' => 'info'],
                            ];
                        ?>
                        <div class="row g-3 mb-4">
                        <?php foreach ($metrics as $m): ?>
                            <?php
                                $ini = floatval($primera[$m['key']]);
                                $fin = floatval($ultima[$m['key']]);
                                $diff = $fin - $ini;
                                $sign = $diff >= 0 ? '+' : '';
                                $isGood = ($m['key'] === 'grasa' || $m['key'] === 'cintura') ? $diff <= 0 : $diff >= 0;
                                $diffColor = $diff == 0 ? 'secondary' : ($isGood ? 'success' : 'danger');
                            ?>
                            <div class="col-6">
                                <div class="rounded-3 border p-3 bg-white">
                                    <p class="text-<?= $m['color'] ?> fw-bold small mb-1"><i class="fas <?= $m['icon'] ?> me-1"></i><?= $m['label'] ?></p>
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <small class="text-muted d-block">Inicial: <strong><?= $ini > 0 ? $ini . ' ' . $m['unit'] : '—' ?></strong></small>
                                            <small class="text-muted d-block">Actual: <strong><?= $fin > 0 ? $fin . ' ' . $m['unit'] : '—' ?></strong></small>
                                        </div>
                                        <?php if ($ini > 0 && $fin > 0): ?>
                                        <span class="badge bg-<?= $diffColor ?> rounded-pill px-3 py-2 fw-bold"><?= $sign . number_format($diff, 1) ?> <?= $m['unit'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- History Table Card -->
                        <div class="card module-container border-0 shadow-sm">
                            <div class="card-header bg-white border-0 pt-4 pb-2">
                                <h5 class="fw-bold text-dark m-0"><i class="fas fa-history text-secondary me-2"></i> Historial de Registros</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle table-data border-0 mb-0">
                                        <thead>
                                            <tr>
                                                <th class="border-0 bg-transparent text-muted ps-4">Fecha</th>
                                                <th class="border-0 bg-transparent text-muted text-center">Peso</th>
                                                <th class="border-0 bg-transparent text-muted text-center">Grasa</th>
                                                <th class="border-0 bg-transparent text-muted text-center">Cintura</th>
                                                <th class="border-0 bg-transparent text-muted text-center">Brazo</th>
                                                <th class="border-0 bg-transparent text-muted text-end pe-4">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($medidas as $m): ?>
                                            <tr>
                                                <td class="ps-4 text-dark fw-semibold"><?= date('d/m/Y', strtotime($m['fecha'])) ?></td>
                                                <td class="text-center text-primary fw-bold bg-primary bg-opacity-10 rounded-pill px-2 d-inline-block mt-2"><?= $m['peso'] ?> kg</td>
                                                <td class="text-center text-muted"><?= $m['grasa'] ? $m['grasa'].' %' : '-' ?></td>
                                                <td class="text-center text-muted"><?= $m['cintura'] ? $m['cintura'].' cm' : '-' ?></td>
                                                <td class="text-center text-muted"><?= $m['brazo'] ? $m['brazo'].' cm' : '-' ?></td>
                                                <td class="text-end pe-4">
                                                    <a href="/progreso/eliminar_medida/<?= $m['id'] ?>/<?= $socio['id'] ?>" class="btn btn-sm btn-outline-danger border-0 rounded-circle transition-hover" title="Eliminar Medida" onclick="return confirm('¿Seguro de borrar esta medida?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="rutina">
                <form action="/progreso/guardar_rutina" method="POST" class="needs-validation">
                    <input type="hidden" name="socio_id" value="<?= $socio['id'] ?>">
                    
                    <div class="row g-4 mb-4">
                        <?php 
                            $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
                            $campos = ['dia1', 'dia2', 'dia3', 'dia4', 'dia5', 'dia6'];
                            $colores = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
                        ?>
                        
                        <?php for($i=0; $i<6; $i++): $clr = $colores[$i]; ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card module-container border-0 shadow-sm h-100 transition-hover" style="border-top: 4px solid var(--bs-<?= $clr ?>) !important;">
                                <div class="card-header bg-white border-0 pt-3 pb-0 text-center">
                                    <span class="badge bg-<?= $clr ?> bg-opacity-10 text-<?= $clr ?> rounded-pill px-3 py-2 fw-bold w-50"><?= $dias[$i] ?></span>
                                </div>
                                <div class="card-body p-3">
                                    <textarea name="<?= $campos[$i] ?>" class="form-control rounded-3 border-light bg-light focus-ring" rows="5" placeholder="Ej: 4x12 Press Banca..."><?= $rutina[$campos[$i]] ?? '' ?></textarea>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="card module-container border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <div class="form-floating">
                                <input type="text" name="observaciones" id="obs" class="form-control rounded-3" value="<?= $rutina['observaciones'] ?? '' ?>" placeholder="Ej: Enfocarse en la técnica...">
                                <label for="obs" class="fw-bold"><i class="fas fa-comment-medical text-primary me-2"></i> Observaciones Generales (Meta, Lesiones, Recomendaciones):</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 py-3 shadow-sm fw-bold transition-hover">
                            <i class="fas fa-save me-2"></i> GUARDAR RUTINA SEMANAL
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <script>
        <?php if (count($medidas) >= 2): ?>
        const chartLabels  = <?= json_encode($labels) ?>;
        const chartDatasets = [
            { label: 'Peso (kg)',     data: <?= json_encode($dataPeso) ?>,    borderColor: '#6366F1', backgroundColor: 'rgba(99,102,241,0.08)',  fill: true,  tension: 0.4 },
            { label: '% Grasa',      data: <?= json_encode($dataGrasa) ?>,   borderColor: '#F59E0B', backgroundColor: 'rgba(245,158,11,0.08)',   fill: false, tension: 0.4, hidden: true },
            { label: 'Cintura (cm)', data: <?= json_encode($dataCintura) ?>, borderColor: '#10B981', backgroundColor: 'rgba(16,185,129,0.08)',  fill: false, tension: 0.4, hidden: true },
            { label: 'Brazo (cm)',   data: <?= json_encode($dataBrazo) ?>,   borderColor: '#06B6D4', backgroundColor: 'rgba(6,182,212,0.08)',    fill: false, tension: 0.4, hidden: true },
        ];

        const multiChart = new Chart(document.getElementById('multiChart').getContext('2d'), {
            type: 'line',
            data: { labels: chartLabels, datasets: chartDatasets },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: false } }
            }
        });

        document.querySelectorAll('.toggle-metric').forEach(btn => {
            btn.addEventListener('click', function() {
                const idx = parseInt(this.dataset.idx);
                const meta = multiChart.data.datasets[idx];
                meta.hidden = !meta.hidden;
                this.classList.toggle('active');
                // Swap btn style between solid and outline
                const colors = ['primary', 'warning', 'success', 'info'];
                const c = colors[idx];
                if (meta.hidden) {
                    this.className = `btn btn-sm btn-outline-${c} rounded-pill toggle-metric`;
                } else {
                    this.className = `btn btn-sm btn-${c} rounded-pill toggle-metric active`;
                }
                multiChart.update();
            });
        });
        <?php endif; ?>

        if(window.location.href.indexOf("tab=rutina") > -1) {
            var tabTrigger = new bootstrap.Tab(document.getElementById('rutina-tab'));
            tabTrigger.show();
        }
    </script>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>