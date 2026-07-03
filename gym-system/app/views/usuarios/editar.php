<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once '../app/views/inc/navbar.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="module-container border-0 shadow-sm" style="border-radius: 20px;">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-user-edit fa-2x"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Editar Usuario</h3>
                        <p class="text-muted">Modifique los datos de <span class="fw-bold"><?= $usuario['nombre'] ?></span></p>
                    </div>
                    
                    <form action="/usuarios/actualizar" method="POST">
                        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
                        
                        <div class="form-floating mb-3">
                            <input type="text" name="nombre" class="form-control rounded-3" id="nombreInput" placeholder="Nombre Completo" value="<?= $usuario['nombre'] ?>" required>
                            <label for="nombreInput" class="text-muted"><i class="fas fa-user me-2"></i>Nombre Completo</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control rounded-3" id="emailInput" placeholder="Email" value="<?= $usuario['email'] ?>" required>
                            <label for="emailInput" class="text-muted"><i class="fas fa-envelope me-2"></i>Email</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" name="password" class="form-control rounded-3" id="passwordInput" placeholder="Nueva Contraseña">
                            <label for="passwordInput" class="text-muted"><i class="fas fa-lock me-2"></i>Nueva Contraseña</label>
                            <div class="form-text text-muted ms-1 mt-2 mb-0" style="font-size: 0.8rem;"><i class="fas fa-info-circle me-1"></i>Dejar en blanco para mantener la actual.</div>
                        </div>
                        
                        <div class="form-floating mb-4 mt-3">
                            <select name="rol" class="form-select rounded-3" id="rolSelect">
                                <option value="recepcionista" <?= $usuario['rol']=='recepcionista'?'selected':'' ?>>Recepcionista</option>
                                <option value="entrenador" <?= $usuario['rol']=='entrenador'?'selected':'' ?>>Entrenador</option>
                                <option value="admin" <?= $usuario['rol']=='admin'?'selected':'' ?>>Administrador</option>
                            </select>
                            <label for="rolSelect" class="text-muted"><i class="fas fa-user-tag me-2"></i>Rol</label>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="/usuarios/index" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-warning px-5 py-2 shadow-sm fw-bold rounded-pill text-dark"><i class="fas fa-save me-2"></i>Actualizar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>