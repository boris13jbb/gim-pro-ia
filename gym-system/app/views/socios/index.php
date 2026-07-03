<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Socios - <?= $config['nombre_sistema'] ?? 'Gym System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="module-container">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div class="d-flex flex-wrap gap-2">
                    <button onclick="ExportHelper.toExcel('tblSocios', 'Lista_Socios', 'Lista de Socios')" class="btn btn-success btn-sm rounded-pill fw-bold px-3 shadow-sm transition-hover">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button onclick="ExportHelper.toPDF('tblSocios', 'Lista_Socios', 'Lista de Socios')" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 shadow-sm transition-hover">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button onclick="ExportHelper.print('tblSocios', 'Lista de Socios')" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 shadow-sm transition-hover">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                </div>
                <?php if($_SESSION['user_rol'] != 'entrenador'): ?>
                    <div class="d-flex align-items-center mt-3 mt-md-0">
                        <h4 class="fw-bold text-dark m-0 me-3 d-none d-md-block">Directorio de Socios</h4>
                        <a href="/socios/crear" class="btn btn-primary shadow-sm fw-bold rounded-pill px-4 transition-hover">
                            <i class="fas fa-user-plus me-2"></i> Nuevo Socio
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Search & Filter Bar -->
            <div class="row g-2 mb-4 align-items-center">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill border" style="border-right:none;">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="searchSocio" class="form-control border-start-0 rounded-end-pill" placeholder="Buscar por nombre, DNI o email..." style="border-left:none;">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select id="filterEstado" class="form-select rounded-pill">
                        <option value="">Todos los estados</option>
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="pendiente">Pendiente</option>
                    </select>
                </div>
                <div class="col-6 col-md-4 text-end">
                    <span class="badge bg-primary rounded-pill px-3 py-2 fw-semibold" id="socioCount"></span>
                </div>
            </div>
            
            <table id="tblSocios" class="table table-hover align-middle table-data border-0">
                <thead>
                    <tr>
                        <th class="border-0 bg-transparent text-muted"><i class="fas fa-image me-1"></i> Foto</th>
                        <th class="border-0 bg-transparent text-muted">Nombre</th>
                        <th class="border-0 bg-transparent text-muted">DNI</th>
                        <th class="border-0 bg-transparent text-muted">Email</th>
                        <th class="border-0 bg-transparent text-muted">Estado</th>
                        <th class="border-0 bg-transparent text-muted">Acciones</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php foreach ($socios as $socio): ?>
                        <tr>
                            <td class="text-center" style="width: 80px;">
                                <?php if(!empty($socio['foto'])): ?>
                                    <img src="/img/socios/<?= $socio['foto'] ?>?v=<?= time() ?>" 
                                         alt="Foto" 
                                         class="rounded-circle border border-2 border-primary"
                                         width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td><strong><?= $socio['nombre'] ?></strong></td>
                            <td><?= $socio['dni'] ?></td>
                            <td><?= $socio['email'] ?></td>
                            
                            <td>
                                <?php if($socio['estado'] == 'activo'): ?>
                                    <span class="badge badge-soft-success rounded-pill px-3 py-2"><i class="fas fa-check-circle me-1"></i> Activo</span>
                                <?php elseif($socio['estado'] == 'inactivo'): ?>
                                    <span class="badge badge-soft-danger rounded-pill px-3 py-2"><i class="fas fa-times-circle me-1"></i> Inactivo</span>
                                <?php else: ?>
                                    <span class="badge badge-soft-warning rounded-pill px-3 py-2"><i class="fas fa-clock me-1"></i> Pendiente</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <?php if($_SESSION['user_rol'] != 'entrenador'): ?>
                                        <a href="/carnet/generar/<?= $socio['id'] ?>" 
                                           target="_blank" 
                                           class="btn btn-info btn-sm text-white rounded-circle shadow-sm transition-hover"
                                           style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                           title="Generar Carnet Digital">
                                            <i class="fas fa-id-card"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="/progreso/ver/<?= $socio['id'] ?>" 
                                       class="btn btn-primary btn-sm rounded-circle shadow-sm transition-hover" 
                                       style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                       title="Ver Medidas y Rutina">
                                        <i class="fas fa-chart-line"></i>
                                    </a>

                                    <?php if($_SESSION['user_rol'] != 'entrenador'): ?>
                                        <a href="/socios/editar/<?= $socio['id'] ?>" 
                                           class="btn btn-warning btn-sm text-dark rounded-circle shadow-sm transition-hover" 
                                           style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if(isset($_SESSION['user_rol']) && $_SESSION['user_rol'] == 'admin'): ?>
                                        
                                        <?php if($socio['estado'] != 'inactivo'): ?>
                                            <a href="/socios/cambiarEstado/<?= $socio['id'] ?>/inactivo" 
                                               class="btn btn-danger btn-sm btn-confirm rounded-circle shadow-sm transition-hover" 
                                               style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                               data-title="¿Desactivar a <?= $socio['nombre'] ?>?"
                                               title="Dar de Baja">
                                                <i class="fas fa-user-times"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/socios/cambiarEstado/<?= $socio['id'] ?>/activo" 
                                               class="btn btn-success btn-sm btn-confirm rounded-circle shadow-sm transition-hover" 
                                               style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                               data-title="¿Reactivar a <?= $socio['nombre'] ?>?"
                                               title="Reactivar">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
            </table>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
    <script>
        (function() {
            const searchInput  = document.getElementById('searchSocio');
            const filterSelect = document.getElementById('filterEstado');
            const counter      = document.getElementById('socioCount');
            const rows         = Array.from(document.querySelectorAll('#tblSocios tbody tr'));

            function updateCounter(visible) {
                counter.textContent = visible + ' socio' + (visible !== 1 ? 's' : '') + ' encontrado' + (visible !== 1 ? 's' : '');
            }

            function applyFilters() {
                const term   = searchInput.value.toLowerCase();
                const estado = filterSelect.value.toLowerCase();
                let visible  = 0;
                rows.forEach(row => {
                    const text    = row.textContent.toLowerCase();
                    const badge   = row.querySelector('.badge');
                    const badgeTxt = badge ? badge.textContent.toLowerCase().trim() : '';
                    const matchT  = !term   || text.includes(term);
                    const matchE  = !estado || badgeTxt.includes(estado);
                    row.style.display = (matchT && matchE) ? '' : 'none';
                    if (matchT && matchE) visible++;
                });
                updateCounter(visible);
            }

            searchInput.addEventListener('input', applyFilters);
            filterSelect.addEventListener('change', applyFilters);
            updateCounter(rows.length);
        })();
    </script>
</body>
</html>