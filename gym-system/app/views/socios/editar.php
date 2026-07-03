<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Socio - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card module-container border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-edit fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-secondary mb-1">Editar Socio</h4>
                        <p class="text-muted small">Modifique los datos actuales del miembro</p>
                    </div>
                    <div class="card-body px-4 px-md-5 pb-5 pt-3">
                        <form action="/socios/actualizar" method="POST" enctype="multipart/form-data" class="needs-validation">
                            <input type="hidden" name="id" value="<?= $socio['id'] ?>">
                            <input type="hidden" name="foto_actual" value="<?= $socio['foto'] ?>">

                            <div class="mb-5 text-center mt-3">
                                <label for="foto" class="form-label fw-bold text-dark fs-6 mb-3">Foto de Perfil</label>
                                <div class="p-4 bg-light bg-opacity-50 border rounded-4 border-dashed position-relative transition-hover" style="border-style: dashed !important; border-width: 2px !important;">
                                    
                                    <?php if(!empty($socio['foto'])): ?>
                                        <div class="mb-4 position-relative d-inline-block">
                                            <img src="/img/socios/<?= $socio['foto'] ?>" alt="Foto actual" 
                                                 class="rounded-circle shadow-sm border border-2 border-white" width="120" height="120" 
                                                 style="object-fit: cover;">
                                            <span class="position-absolute bottom-0 start-50 translate-middle-x badge bg-success rounded-pill px-2 shadow-sm" style="font-size: 0.65rem;">Actual</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3 text-muted">
                                            <i class="fas fa-user-circle" style="font-size: 4rem; opacity: 0.5;"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <h6 class="text-muted mb-2"><i class="fas fa-cloud-upload-alt me-1"></i> Actualizar foto (Opcional)</h6>
                                    <input type="file" class="form-control" name="foto" accept="image/png, image/jpeg, image/jpg">
                                    <small class="text-muted mt-2 d-block">Deja en blanco para mantener la foto actual. Formatos: JPG, PNG.</small>
                                </div>
                            </div>

                            <hr class="mb-5 opacity-25">

                            <div class="form-floating mb-4">
                                <input type="text" class="form-control rounded-3" id="nombre" name="nombre" value="<?= $socio['nombre'] ?>" placeholder="Nombre Completo" required>
                                <label for="nombre"><i class="fas fa-user text-muted me-2"></i>Nombre Completo</label>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control rounded-3" id="dni" name="dni" value="<?= $socio['dni'] ?>" placeholder="DNI / Identificación" required>
                                        <label for="dni"><i class="fas fa-id-card text-muted me-2"></i>DNI / Identificación</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control rounded-3" id="telefono" name="telefono" value="<?= $socio['telefono'] ?>" placeholder="Teléfono">
                                        <label for="telefono"><i class="fas fa-phone text-muted me-2"></i>Teléfono</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="email" class="form-control rounded-3" id="email" name="email" value="<?= $socio['email'] ?>" placeholder="Correo Electrónico">
                                <label for="email"><i class="fas fa-envelope text-muted me-2"></i>Correo Electrónico</label>
                            </div>

                            <div class="form-floating mb-5">
                                <select class="form-select rounded-3" id="estado" name="estado">
                                    <option value="activo" <?= $socio['estado'] == 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="inactivo" <?= $socio['estado'] == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                    <option value="pendiente" <?= $socio['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                                </select>
                                <label for="estado"><i class="fas fa-toggle-on text-muted me-2"></i>Estado del Socio</label>
                            </div>

                            <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                                <a href="/socios/index" class="btn btn-light border rounded-pill px-5 py-2 fw-bold text-secondary shadow-sm">Cancelar</a>
                                <button type="submit" class="btn btn-warning rounded-pill px-5 py-2 shadow-sm fw-bold border-0 text-dark">
                                    <i class="fas fa-sync me-2"></i> Actualizar Socio
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>