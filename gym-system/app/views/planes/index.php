<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 fade-in mt-5 mb-5">
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-4 d-flex justify-content-between align-items-center mb-2 flex-wrap gap-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-dumbbell text-primary"></i>
                    </div>
                    Planes de Membresía
                </h3>
                <div class="d-flex gap-2 flex-wrap">
                    <button onclick="ExportHelper?.toExcel('tblPlanes', 'Planes', 'Reporte de Planes')" class="btn btn-success btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button onclick="ExportHelper?.toPDF('tblPlanes', 'Planes', 'Reporte de Planes')" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button onclick="ExportHelper?.print('tblPlanes', 'Reporte de Planes')" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                    <a href="/planes/crear" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center">
                        <i class="fas fa-plus me-2"></i> Nuevo Plan
                    </a>
                </div>
            </div>
            
            <?php if(isset($_GET['success'])): ?>
                <div class="px-4 mt-3">
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Operación realizada con éxito.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="p-4 pt-0 mt-2">
                <div class="table-responsive">
                    <table id="tblPlanes" class="table table-hover align-middle table-data border-0">
                        <thead>
                            <tr>
                                <th class="border-0 bg-transparent text-muted">Nombre</th>
                                <th class="border-0 bg-transparent text-muted">Precio</th>
                                <th class="border-0 bg-transparent text-muted">Duración (Días)</th>
                                <th class="border-0 bg-transparent text-muted">Descripción</th>
                                <th class="border-0 bg-transparent text-muted">Estado</th>
                                <th class="border-0 bg-transparent text-muted text-end">Acciones</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php foreach ($planes as $plan): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm border border-primary border-opacity-25" style="width: 40px; height: 40px;">
                                        <i class="fas fa-id-card"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= $plan['nombre'] ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="fw-bold text-success">
                                <span class="text-muted fa-sm me-1"><?= $config['moneda'] ?></span>
                                <?= number_format($plan['precio'], 2) ?>
                            </td>
                            
                            <td>
                                <span class="badge bg-light text-dark border shadow-sm px-2 py-1"><i class="fas fa-clock text-warning me-1"></i><?= $plan['duracion_dias'] ?> días</span>
                            </td>
                            <td>
                                <div class="text-truncate text-muted" style="max-width: 200px;" title="<?= htmlspecialchars($plan['descripcion']) ?>">
                                    <?= empty($plan['descripcion']) ? '<span class="fst-italic">Sin descripción</span>' : $plan['descripcion'] ?>
                                </div>
                            </td>
                            <td>
                                <?php if($plan['estado'] == 'activo'): ?>
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/planes/editar/<?= $plan['id'] ?>" class="btn btn-light text-warning btn-sm rounded-circle d-flex align-items-center justify-content-center border" 
                                       style="width: 35px; height: 35px; transition: all 0.2s;" title="Editar Plan"
                                       onmouseover="this.classList.replace('btn-light', 'btn-warning'); this.classList.replace('text-warning', 'text-white');" 
                                       onmouseout="this.classList.replace('btn-warning', 'btn-light'); this.classList.replace('text-white', 'text-warning');">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if($plan['estado'] == 'activo'): ?>
                                        <a href="/planes/cambiarEstado/<?= $plan['id'] ?>/inactivo" 
                                           class="btn btn-light text-danger btn-sm btn-confirm rounded-circle d-flex align-items-center justify-content-center border"
                                           style="width: 35px; height: 35px; transition: all 0.2s;" title="Desactivar Plan"
                                           data-title="¿Desactivar este plan?"
                                           onmouseover="this.classList.replace('btn-light', 'btn-danger'); this.classList.replace('text-danger', 'text-white');" 
                                           onmouseout="this.classList.replace('btn-danger', 'btn-light'); this.classList.replace('text-white', 'text-danger');">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="/planes/cambiarEstado/<?= $plan['id'] ?>/activo" 
                                           class="btn btn-light text-success btn-sm btn-confirm rounded-circle d-flex align-items-center justify-content-center border"
                                           style="width: 35px; height: 35px; transition: all 0.2s;" title="Reactivar Plan"
                                           data-title="¿Reactivar este plan?"
                                           onmouseover="this.classList.replace('btn-light', 'btn-success'); this.classList.replace('text-success', 'text-white');" 
                                           onmouseout="this.classList.replace('btn-success', 'btn-light'); this.classList.replace('text-white', 'text-success');">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../app/views/inc/footer.php'; ?>
    
    <!-- Add ExportHelper script dependency since we've added export buttons if not in footer -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>
    <script src="/js/ExportHelper.js?v=<?= time() ?>"></script>
</body>
</html>