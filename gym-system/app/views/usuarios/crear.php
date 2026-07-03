<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once '../app/views/inc/navbar.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="module-container border-0 shadow-sm" style="border-radius: 20px;">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 70px; height: 70px;">
                            <i class="fas fa-user-plus fa-2x"></i>
                        </div>
                        <h3 class="fw-bold text-dark">Nuevo Usuario</h3>
                        <p class="text-muted">Complete los datos para registrar un nuevo acceso</p>
                    </div>
                    
                    <form action="/usuarios/guardar" method="POST">
                        <div class="form-floating mb-3">
                            <input type="text" name="nombre" class="form-control rounded-3" id="nombreInput" placeholder="Nombre Completo" required>
                            <label for="nombreInput" class="text-muted"><i class="fas fa-user me-2"></i>Nombre Completo</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="email" name="email" class="form-control rounded-3" id="emailInput" placeholder="Email" required>
                            <label for="emailInput" class="text-muted"><i class="fas fa-envelope me-2"></i>Email (Usuario)</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="password" name="password" class="form-control rounded-3" id="passwordInput" placeholder="Contraseña" required>
                            <label for="passwordInput" class="text-muted"><i class="fas fa-lock me-2"></i>Contraseña</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <select name="rol" class="form-select rounded-3" id="rolSelect">
                                <option value="recepcionista">Recepcionista</option>
                                <option value="entrenador">Entrenador</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <label for="rolSelect" class="text-muted"><i class="fas fa-user-tag me-2"></i>Rol de Acceso</label>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="/usuarios/index" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm fw-bold rounded-pill"><i class="fas fa-save me-2"></i>Guardar Usuario</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>