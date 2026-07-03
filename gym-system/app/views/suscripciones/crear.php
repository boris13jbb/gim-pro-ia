<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Suscripción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">
    
    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card module-container border-0 mb-4 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-file-signature fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-secondary mb-1">Nueva Suscripción</h4>
                        <p class="text-muted small">Complete los datos para registrar una nueva suscripción</p>
                    </div>
                    <div class="card-body px-4 px-md-5 pb-5 pt-3">
                        <form action="/suscripciones/guardar" method="POST">
                            
                            <div class="form-floating mb-4">
                                <select class="form-select" id="socio_id" name="socio_id" required>
                                    <option value="">-- Elige un socio --</option>
                                    <?php foreach($socios as $socio): ?>
                                        <?php if($socio['estado'] == 'activo'): ?>
                                            <option value="<?= $socio['id'] ?>">
                                                <?= $socio['nombre'] ?> (DNI: <?= $socio['dni'] ?>)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <label for="socio_id"><i class="fas fa-user text-primary me-2"></i>Socio</label>
                            </div>

                            <div class="form-floating mb-4">
                                <select class="form-select" id="plan_id" name="plan_id" required>
                                    <option value="">-- Elige un plan --</option>
                                    <?php foreach($planes as $plan): ?>
                                        <?php if($plan['estado'] == 'activo'): ?>
                                            <option value="<?= $plan['id'] ?>">
                                                <?= $plan['nombre'] ?> - <?= $config['moneda'] ?><?= number_format($plan['precio'], 2) ?> (<?= $plan['duracion_dias'] ?> días)
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                                <label for="plan_id"><i class="fas fa-tags text-primary me-2"></i>Plan</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?= date('Y-m-d') ?>" required>
                                <label for="fecha_inicio"><i class="fas fa-calendar-alt text-primary me-2"></i>Fecha de Inicio</label>
                            </div>

                            <div class="alert alert-info border-info border-opacity-25 bg-info bg-opacity-10 text-info px-4 py-3 rounded-3 mb-4 d-flex align-items-center mt-4">
                                <i class="fas fa-info-circle fs-4 me-3"></i>
                                <div class="small fw-semibold">La fecha de fin se calculará automáticamente según el plan elegido.</div>
                            </div>

                            <div class="d-flex gap-3 mt-5">
                                <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4 py-2 w-100 shadow-sm d-flex justify-content-center align-items-center">
                                    <i class="fas fa-save me-2"></i> Registrar Venta
                                </button>
                                <a href="/suscripciones/index" class="btn btn-light rounded-pill fw-bold text-secondary px-4 py-2 w-100 border shadow-sm d-flex justify-content-center align-items-center">
                                    <i class="fas fa-times me-2"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>