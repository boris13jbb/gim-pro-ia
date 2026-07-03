<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Socio - Iron Gym</title>
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
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-plus fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-secondary mb-1">Registrar Nuevo Socio</h4>
                        <p class="text-muted small">Complete los datos para agregar un miembro al sistema</p>
                    </div>
                    <div class="card-body px-4 px-md-5 pb-5 pt-3">
                        <form action="/socios/guardar" method="POST" enctype="multipart/form-data" class="needs-validation">
                            
                            <div class="mb-5 text-center mt-3">
                                <label for="foto" class="form-label fw-bold text-dark fs-6 mb-3">Foto de Perfil</label>
                                <div class="p-4 bg-light bg-opacity-50 border rounded-4 border-dashed position-relative transition-hover" style="border-style: dashed !important; border-width: 2px !important;">
                                    <div class="text-muted mb-2"><i class="fas fa-cloud-upload-alt fs-2"></i></div>
                                    <input type="file" class="form-control" name="foto" accept="image/png, image/jpeg, image/jpg">
                                    <small class="text-muted mt-2 d-block">Se recomienda una foto cuadrada (JPG o PNG)</small>
                                </div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="text" class="form-control rounded-3" id="nombre" name="nombre" placeholder="Nombre Completo" required>
                                <label for="nombre"><i class="fas fa-user text-muted me-2"></i>Nombre Completo</label>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control rounded-3" id="dni" name="dni" placeholder="DNI / Identificación" required>
                                        <label for="dni"><i class="fas fa-id-card text-muted me-2"></i>DNI / Identificación</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control rounded-3" id="telefono" name="telefono" placeholder="Teléfono">
                                        <label for="telefono"><i class="fas fa-phone text-muted me-2"></i>Teléfono</label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="email" class="form-control rounded-3" id="email" name="email" placeholder="Correo Electrónico">
                                <label for="email"><i class="fas fa-envelope text-muted me-2"></i>Correo Electrónico</label>
                            </div>

                            <div class="form-floating mb-5">
                                <select class="form-select rounded-3" id="estado" name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                                <label for="estado"><i class="fas fa-toggle-on text-muted me-2"></i>Estado Inicial</label>
                            </div>

                            <div class="d-grid gap-3 d-md-flex justify-content-md-center">
                                <a href="/socios/index" class="btn btn-light border rounded-pill px-5 py-2 fw-bold text-secondary shadow-sm">Administrar Socios</a>
                                <button type="submit" class="btn btn-success rounded-pill px-5 py-2 shadow-sm fw-bold"><i class="fas fa-save me-2"></i> Guardar Socio</button>
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