<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-4 d-flex justify-content-between align-items-center mb-2" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-user-shield text-primary"></i>
                    </div>
                    Usuarios del Sistema
                </h3>
                <a href="/usuarios/crear" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-plus me-2"></i> Nuevo Usuario</a>
            </div>
            <div class="p-4 pt-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-data border-0">
                        <thead>
                            <tr>
                                <th class="border-0 bg-transparent text-muted">ID</th>
                                <th class="border-0 bg-transparent text-muted">Nombre</th>
                                <th class="border-0 bg-transparent text-muted">Email</th>
                                <th class="border-0 bg-transparent text-muted">Rol</th>
                                <th class="border-0 bg-transparent text-muted">Estado</th>
                                <th class="border-0 bg-transparent text-muted text-end">Acciones</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($u['nombre']) ?>&background=random&color=fff&rounded=true&size=35&bold=true" alt="<?= $u['nombre'] ?>" class="rounded-circle me-3 shadow-sm">
                                    <div>
                                        <div class="fw-bold text-dark"><?= $u['nombre'] ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted"><?= $u['email'] ?></td>
                            <td>
                                <?php 
                                    $colorRol = 'secondary';
                                    if($u['rol'] == 'admin') $colorRol = 'danger';
                                    if($u['rol'] == 'recepcionista') $colorRol = 'primary';
                                    if($u['rol'] == 'entrenador') $colorRol = 'success';
                                ?>
                                <span class="badge bg-<?= $colorRol ?> text-white text-uppercase px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.5px;"><?= $u['rol'] ?></span>
                            </td>
                            <td>
                                <?php if($u['estado'] == 'activo'): ?>
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/usuarios/editar/<?= $u['id'] ?>" class="btn btn-light text-warning btn-sm rounded-circle d-flex align-items-center justify-content-center border" style="width: 35px; height: 35px; transition: all 0.2s;" title="Editar" onmouseover="this.classList.replace('btn-light', 'btn-warning'); this.classList.replace('text-warning', 'text-white');" onmouseout="this.classList.replace('btn-warning', 'btn-light'); this.classList.replace('text-white', 'text-warning');">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if($u['id'] != $_SESSION['user_id']): ?>
                                        
                                        <?php if($u['estado'] == 'activo'): ?>
                                            <a href="/usuarios/cambiarEstado/<?= $u['id'] ?>/inactivo" 
                                               class="btn btn-light text-danger btn-sm rounded-circle d-flex align-items-center justify-content-center border btn-confirm"
                                               style="width: 35px; height: 35px; transition: all 0.2s;"
                                               data-title="¿Bloquear acceso a <?= $u['nombre'] ?>?"
                                               title="Desactivar cuenta"
                                               onmouseover="this.classList.replace('btn-light', 'btn-danger'); this.classList.replace('text-danger', 'text-white');" 
                                               onmouseout="this.classList.replace('btn-danger', 'btn-light'); this.classList.replace('text-white', 'text-danger');">
                                                <i class="fas fa-user-slash"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/usuarios/cambiarEstado/<?= $u['id'] ?>/activo" 
                                               class="btn btn-light text-success btn-sm rounded-circle d-flex align-items-center justify-content-center border btn-confirm"
                                               style="width: 35px; height: 35px; transition: all 0.2s;"
                                               data-title="¿Reactivar acceso a <?= $u['nombre'] ?>?"
                                               title="Activar cuenta"
                                               onmouseover="this.classList.replace('btn-light', 'btn-success'); this.classList.replace('text-success', 'text-white');" 
                                               onmouseout="this.classList.replace('btn-success', 'btn-light'); this.classList.replace('text-white', 'text-success');">
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
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>