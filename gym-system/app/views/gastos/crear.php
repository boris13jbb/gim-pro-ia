<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Gasto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <?php require_once '../app/views/inc/navbar.php'; ?>
    <div class="container container-form mt-5 mb-5 fade-in">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="module-container p-0 shadow-lg">
                    <div class="border-bottom px-4 py-4 mb-2 text-center" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                        <div class="d-inline-flex bg-danger bg-opacity-10 p-3 rounded-circle mb-3 align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-money-bill-wave text-danger fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark m-0">Registrar Salida de Dinero</h4>
                        <p class="text-muted small mt-1 mb-0">Ingresa los detalles del nuevo gasto</p>
                    </div>

                    <form action="/gastos/guardar" method="POST" class="p-4 pt-2">
                        <div class="form-floating mb-3">
                            <input type="text" name="descripcion" id="gasto_descripcion" class="form-control rounded-3" placeholder="Ej: Recibo de Luz, Pago Limpieza..." required>
                            <label for="gasto_descripcion" class="text-muted"><i class="fas fa-align-left me-2"></i>Descripción del Gasto *</label>
                        </div>
                        
                        <div class="form-floating mb-3">
                            <input type="number" step="0.01" name="monto" id="gasto_monto" class="form-control rounded-3" placeholder="0.00" required>
                            <label for="gasto_monto" class="text-muted"><span class="me-1 fw-bold"><?= $config['moneda'] ?></span> Monto *</label>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="date" name="fecha" id="gasto_fecha" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                            <label for="gasto_fecha" class="text-muted"><i class="fas fa-calendar-alt me-2"></i>Fecha *</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg rounded-pill shadow-sm fw-bold">
                                <i class="fas fa-save me-2"></i>Guardar Gasto
                            </button>
                            <a href="/gastos/index" class="btn btn-light btn-lg rounded-pill text-muted fw-bold border">
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