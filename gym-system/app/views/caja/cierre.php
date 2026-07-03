<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de Caja - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-9">
                
                <div class="module-container text-center">
                    <div class="mb-4 border-bottom pb-3">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex justify-content-center align-items-center mb-2" style="width: 50px; height: 50px;">
                            <i class="fas fa-file-invoice-dollar fs-4"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">CORTE DE CAJA</h4>
                        <p class="m-0 text-muted fs-6">
                            <i class="fas fa-user-circle me-1"></i>Cajero: <strong><?= $_SESSION['user_name'] ?></strong> | 
                            <i class="fas fa-clock me-1 ms-2"></i>Apertura: <strong><?= date('d/m/Y H:i', strtotime($cajaAbierta['fecha_apertura'])) ?></strong>
                        </p>
                    </div>
                    
                    <div class="p-2">
                        
                        <div class="row text-center mb-4 g-3 justify-content-center">
                            <!-- Base Inicial -->
                            <div class="col-md-4">
                                <div class="p-3 border-0 rounded-4 bg-primary bg-opacity-10 h-100 shadow-sm transition-hover">
                                    <div class="text-primary mb-1"><i class="fas fa-wallet fs-5"></i></div>
                                    <small class="text-primary text-opacity-75 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.75rem;">Base Inicial</small>
                                    <h4 class="text-primary fw-bold mt-1 mb-0"><?= $config['moneda'] ?> <?= number_format($monto_inicial, 2) ?></h4>
                                </div>
                            </div>
                            
                            <!-- Ventas -->
                            <div class="col-md-4">
                                <div class="p-3 border-0 rounded-4 bg-success bg-opacity-10 h-100 shadow-sm transition-hover">
                                    <div class="text-success mb-1"><i class="fas fa-arrow-trend-up fs-5"></i></div>
                                    <small class="text-success text-opacity-75 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.75rem;">Ventas Turno (+)</small>
                                    <h4 class="text-success fw-bold mt-1 mb-0"><?= $config['moneda'] ?> <?= number_format($total_ventas, 2) ?></h4>
                                </div>
                            </div>
                            
                            <!-- Gastos -->
                            <div class="col-md-4">
                                <div class="p-3 border-0 rounded-4 bg-danger bg-opacity-10 h-100 shadow-sm transition-hover">
                                    <div class="text-danger mb-1"><i class="fas fa-arrow-trend-down fs-5"></i></div>
                                    <small class="text-danger text-opacity-75 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.75rem;">Gastos Turno (-)</small>
                                    <h4 class="text-danger fw-bold mt-1 mb-0"><?= $config['moneda'] ?> <?= number_format($total_gastos, 2) ?></h4>
                                </div>
                            </div>
                        </div>

                        <!-- DINERO ESPERADO -->
                        <div class="card mb-4 border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #f8f9fc 0%, #e0e7ff 100%);">
                            <div class="card-body p-4">
                                <h6 class="text-uppercase text-primary fw-bold mb-2 d-flex align-items-center justify-content-center gap-2">
                                    <i class="fas fa-cash-register"></i> Dinero esperado en Cajón
                                </h6>
                                <h1 class="fw-bolder text-dark mb-2" style="font-size: 2.5rem;">
                                    <?= $config['moneda'] ?> <?= number_format($saldo_esperado, 2) ?>
                                </h1>
                                <span class="badge bg-white text-muted border px-3 py-2 rounded-pill mt-1" style="font-size: 0.8rem;">
                                    <i class="fas fa-info-circle me-1"></i> Base Inicial + Ventas - Gastos
                                </span>
                            </div>
                        </div>

                        <hr class="my-3 opacity-25">

                        <form action="/caja/cerrar" method="POST" class="needs-validation" id="formCierreCaja">
                            
                            <input type="hidden" name="caja_id" value="<?= $cajaAbierta['id'] ?>">
                            <input type="hidden" name="total_ventas" value="<?= $total_ventas ?>">
                            <input type="hidden" name="total_gastos" value="<?= $total_gastos ?>">
                            <input type="hidden" name="saldo_esperado" value="<?= $saldo_esperado ?>">

                            <div class="mb-4 text-center">
                                <h5 class="fw-bold text-dark mb-3">¿Cuánto dinero hay FÍSICAMENTE?</h5>
                                <div class="d-flex justify-content-center">
                                    <div class="input-group input-group-lg w-75 rounded-pill shadow-sm overflow-hidden" style="border: 2px solid #F59E0B;">
                                        <span class="input-group-text bg-warning text-dark fw-bold border-0 px-4" style="font-size: 0.9rem;">
                                            CONTEO REAL <span class="ms-2 badge bg-dark text-white"><?= $config['moneda'] ?></span>
                                        </span>
                                        <input type="number" step="0.01" name="monto_fisico" class="form-control fw-bold text-center border-0 text-dark" style="font-size: 1.25rem;" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="form-text mt-2 text-muted" style="font-size: 0.8rem;">
                                    <i class="fas fa-hand-holding-dollar me-1"></i> Cuente billetes y monedas del cajón.
                                </div>
                            </div>

                            <div class="d-grid gap-3 d-md-flex justify-content-md-center mt-3">
                                <button type="submit" class="btn btn-danger rounded-pill px-4 py-2 shadow-sm fw-bold">
                                    <i class="fas fa-lock me-2"></i> CERRAR CAJA Y FINALIZAR TURNO
                                </button>
                                <a href="/home/index" class="btn btn-light border rounded-pill px-4 py-2 fw-bold text-secondary shadow-sm">
                                    Volver al Dashboard
                                </a>
                            </div>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formCierre = document.getElementById('formCierreCaja');
            
            formCierre.addEventListener('submit', function(e) {
                e.preventDefault(); // Detiene el envío automático del formulario

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Confirma que has contado el dinero correctamente. Se procederá a cerrar el turno y esta acción no se puede deshacer.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545', // Color rojo peligro (coincide con el botón)
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, cerrar turno',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true // Pone el botón de cancelar a la izquierda
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Si el usuario confirma, enviamos el formulario programáticamente
                        this.submit();
                    }
                });
            });
        });
    </script>
</body>
</html>