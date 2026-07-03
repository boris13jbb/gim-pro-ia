<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once '../app/views/inc/navbar.php'; ?>
    <div class="container container-form mt-5 mb-5 fade-in">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="module-container p-0 shadow-lg">
                    <div class="border-bottom px-4 py-4 mb-2 text-center" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="d-inline-flex bg-primary bg-opacity-10 p-3 rounded-circle mb-3 align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-id-card text-primary fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark m-0">Registrar Nuevo Plan</h4>
                        <p class="text-muted small mt-1 mb-0">Completa los datos del nuevo plan de membresía</p>
                    </div>

                    <form action="/planes/guardar" method="POST" class="p-4 pt-2">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control rounded-3" name="nombre" id="plan_nombre" placeholder="Ej: Mensual VIP" required>
                            <label for="plan_nombre" class="text-muted"><i class="fas fa-tag me-2"></i>Nombre del Plan *</label>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="number" step="0.01" class="form-control rounded-3" name="precio" id="plan_precio" placeholder="0.00" required>
                                    <label for="plan_precio" class="text-muted"><span class="me-1 fw-bold"><?= $config['moneda'] ?></span> Precio *</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control rounded-3" name="duracion" id="plan_duracion" placeholder="Ej: 30" required>
                                    <label for="plan_duracion" class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Duración (Días) *</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-4">
                            <textarea class="form-control rounded-3" name="descripcion" id="plan_desc" style="height: 100px" placeholder="Detalles del plan..."></textarea>
                            <label for="plan_desc" class="text-muted"><i class="fas fa-align-left me-2"></i>Descripción</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm fw-bold">
                                <i class="fas fa-save me-2"></i>Guardar Plan
                            </button>
                            <a href="/planes/index" class="btn btn-light btn-lg rounded-pill text-muted fw-bold border">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>