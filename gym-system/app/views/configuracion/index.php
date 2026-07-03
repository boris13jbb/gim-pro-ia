<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <?php if(isset($_GET['msg'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Configuración actualizada.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header bg-dark text-white">
                        <h4><i class="fas fa-cogs"></i> Datos de la Empresa</h4>
                    </div>
                    <div class="card-body">
                        <form action="/configuracion/actualizar" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="logo_actual" value="<?= $datos['logo'] ?>">

                            <div class="row mb-4 align-items-center">
                                <div class="col-md-4 text-center">
                                    <label class="fw-bold mb-2">Logo Actual</label>
                                    <div class="border p-2 bg-light rounded">
                                        <?php if(!empty($datos['logo'])): ?>
                                            <img src="/img/<?= $datos['logo'] ?>?v=<?= time() ?>" class="img-fluid" style="max-height: 100px;">
                                        <?php else: ?>
                                            <p class="text-muted small py-3">Sin Logo</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Subir Nuevo Logo (PNG/JPG)</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                </div>
                            </div>
                            <hr>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Nombre del Gimnasio</label>
                                    <input type="text" class="form-control" name="nombre" value="<?= $datos['nombre_sistema'] ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label fw-bold text-success">Símbolo Moneda</label>
                                    <input type="text" class="form-control" name="moneda" value="<?= $datos['moneda'] ?>" placeholder="Ej: $, S/, €" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">RUC / NIT</label>
                                    <input type="text" class="form-control" name="ruc" value="<?= $datos['ruc'] ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" name="telefono" value="<?= $datos['telefono'] ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" class="form-control" name="direccion" value="<?= $datos['direccion'] ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email de Contacto</label>
                                <input type="text" class="form-control" name="email" value="<?= $datos['email'] ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
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