<?php
$title = "Categorías - Iron Gym";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Link al tema principal -->
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 fade-in mt-5 mb-5">
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-4 d-flex justify-content-between align-items-center mb-2" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-tags text-primary"></i>
                    </div>
                    Gestión de Categorías
                </h3>
                <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                    <i class="fas fa-plus me-2"></i> Nueva Categoría
                </button>
            </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> Operación realizada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="p-4 pt-0 mt-2">
            <div class="table-responsive">
                <table class="table table-hover align-middle table-data w-100 border-0">
                    <thead>
                        <tr>
                            <th class="border-0 bg-transparent text-muted">ID</th>
                            <th class="border-0 bg-transparent text-muted">Nombre de Categoría</th>
                            <th class="border-0 bg-transparent text-muted">Estado</th>
                            <th class="border-0 bg-transparent text-muted text-end">Acciones</th>
                        </tr>
                    </thead>
                            <tbody>
                                <?php foreach ($categorias as $cat): ?>
                                <tr>
                                    <td class="text-muted fw-bold">#<?= $cat['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm border" style="width: 35px; height: 35px;">
                                                <i class="fas fa-tag text-muted"></i>
                                            </div>
                                            <div class="fw-bold text-dark"><?= $cat['nombre'] ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($cat['estado'] == 'activo'): ?>
                                            <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-light text-warning btn-sm rounded-circle d-flex align-items-center justify-content-center border edit-btn" 
                                                    style="width: 35px; height: 35px; transition: all 0.2s;"
                                                    data-id="<?= $cat['id'] ?>" 
                                                    data-nombre="<?= $cat['nombre'] ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#modalCategoria" 
                                                    title="Editar"
                                                    onmouseover="this.classList.replace('btn-light', 'btn-warning'); this.classList.replace('text-warning', 'text-white');" 
                                                    onmouseout="this.classList.replace('btn-warning', 'btn-light'); this.classList.replace('text-white', 'text-warning');">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if($cat['estado'] == 'activo'): ?>
                                                <a href="/inventario/cambiarEstadoCategoria/<?= $cat['id'] ?>/inactivo" 
                                                class="btn btn-light text-danger btn-sm rounded-circle d-flex align-items-center justify-content-center border btn-confirm"
                                                style="width: 35px; height: 35px; transition: all 0.2s;" 
                                                data-title="¿Desactivar categoría?" 
                                                title="Desactivar"
                                                onmouseover="this.classList.replace('btn-light', 'btn-danger'); this.classList.replace('text-danger', 'text-white');" 
                                                onmouseout="this.classList.replace('btn-danger', 'btn-light'); this.classList.replace('text-white', 'text-danger');">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="/inventario/cambiarEstadoCategoria/<?= $cat['id'] ?>/activo" 
                                                class="btn btn-light text-success btn-sm rounded-circle d-flex align-items-center justify-content-center border btn-confirm" 
                                                style="width: 35px; height: 35px; transition: all 0.2s;"
                                                data-title="¿Reactivar categoría?" 
                                                title="Activar"
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

    <!-- Modal Categoría -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom px-4 py-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="modalTitle">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="modalIconContainer">
                            <i class="fas fa-tag text-primary" id="modalIcon"></i>
                        </div>
                        <span id="modalTitleText">Nueva Categoría</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/inventario/guardarCategoria" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="cat_id">
                        
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control rounded-3" name="nombre" id="cat_nombre" required placeholder="Ej: Suplementos, Ropa...">
                            <label for="cat_nombre" class="text-muted"><i class="fas fa-tag me-2"></i>Nombre de la Categoría</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm fw-bold rounded-pill"><i class="fas fa-save me-2"></i>Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Llenar modal para edición
            $('.edit-btn').click(function() {
                $('#modalTitleText').text('Editar Categoría');
                $('#modalIconContainer').removeClass('bg-primary text-primary').addClass('bg-warning text-warning');
                $('#modalIcon').removeClass('fa-tag text-primary').addClass('fa-edit text-warning');
                $('#cat_id').val($(this).data('id'));
                $('#cat_nombre').val($(this).data('nombre'));
            });
            
            // Limpiar modal al cerrar
            $('#modalCategoria').on('hidden.bs.modal', function () {
                $('#modalTitleText').text('Nueva Categoría');
                $('#modalIconContainer').removeClass('bg-warning text-warning').addClass('bg-primary text-primary');
                $('#modalIcon').removeClass('fa-edit text-warning').addClass('fa-tag text-primary');
                $('#cat_id').val('');
                $('#cat_nombre').val('');
            });
        });
    </script>
</body>
</html>
