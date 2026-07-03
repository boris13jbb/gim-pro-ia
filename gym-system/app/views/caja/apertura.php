<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Apertura de Caja - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        
        <?php if(isset($_GET['msg']) && $_GET['msg']=='cerrado'): ?>
            <div class="alert alert-success text-center mb-4 shadow-sm">
                <h4><i class="fas fa-check-circle"></i> Turno cerrado correctamente</h4>
                <p class="m-0">La caja está lista para una nueva apertura.</p>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center mb-5 mt-4">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card module-container border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-cash-register fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-secondary mb-1">Apertura de Caja</h4>
                        <p class="text-muted small">Ingrese el dinero base para iniciar operaciones</p>
                    </div>
                    <div class="card-body px-4 px-md-5 pb-5 pt-3">
                        <form action="/caja/abrir" method="POST">
                            <div class="mb-5 text-center mt-3">
                                <label class="form-label fw-bold text-dark fs-5 mb-3">Monto Inicial (Sencillo)</label>
                                <div class="d-flex justify-content-center">
                                    <div class="input-group input-group-lg w-100 rounded-pill shadow-sm overflow-hidden" style="border: 2px solid #6366F1;">
                                        <span class="input-group-text bg-primary text-white fw-bold border-0 px-4">
                                            <?= $config['moneda'] ?>
                                        </span>
                                        <input type="number" step="0.01" name="monto_inicial" class="form-control fw-bold text-center border-0 text-primary" style="font-size: 1.5rem;" placeholder="0.00" required autofocus>
                                    </div>
                                </div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm py-3 fw-bold">
                                    <i class="fas fa-key me-2"></i> INICIAR TURNO
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="module-container mt-4">
            <div class="mb-4">
                <h5 class="text-secondary fw-bold m-0"><i class="fas fa-history me-2"></i> Historial de Cierres Recientes</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-data border-0">
                    <thead>
                        <tr>
                            <th class="border-0 bg-transparent text-muted">Fecha Cierre</th>
                            <th class="border-0 bg-transparent text-muted">Cajero</th>
                            <th class="border-0 bg-transparent text-muted">Inicial</th>
                            <th class="border-0 bg-transparent text-muted">Ventas</th>
                            <th class="border-0 bg-transparent text-muted">Gastos</th>
                            <th class="border-0 bg-transparent text-muted">Esperado</th>
                            <th class="border-0 bg-transparent text-muted">Real</th>
                            <th class="border-0 bg-transparent text-muted">Cuadre</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historial as $h): ?>
                            <?php if($h['estado'] == 'cerrada'): ?>
                            <tr class="align-middle">
                                <td><span class="text-secondary fw-semibold"><?= date('d/m/Y H:i', strtotime($h['fecha_cierre'])) ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user-circle fs-6"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?= $h['cajero'] ?></span>
                                    </div>
                                </td>
                                <td><span class="text-muted"><?= $config['moneda'] . number_format($h['monto_inicial'], 2) ?></span></td>
                                <td class="text-success fw-bold"><i class="fas fa-arrow-up text-success opacity-50 me-1"></i><?= number_format($h['total_ventas'], 2) ?></td>
                                <td class="text-danger fw-bold"><i class="fas fa-arrow-down text-danger opacity-50 me-1"></i><?= number_format($h['total_gastos'], 2) ?></td>
                                
                                <?php $sistema = $h['monto_inicial'] + $h['total_ventas'] - $h['total_gastos']; ?>
                                <td class="text-primary fw-bold"><?= $config['moneda'] . number_format($sistema, 2) ?></td>
                                
                                <td><span class="badge border border-secondary text-dark bg-light px-2 py-1"><?= $config['moneda'] . number_format($h['monto_final'], 2) ?></span></td>
                                
                                <td>
                                    <?php if($h['diferencia'] == 0): ?>
                                        <span class="badge badge-soft-success rounded-pill px-3 py-1"><i class="fas fa-check-circle me-1"></i> OK</span>
                                    <?php elseif($h['diferencia'] < 0): ?>
                                        <span class="badge badge-soft-danger rounded-pill px-3 py-1"><i class="fas fa-arrow-down me-1"></i> <?= $h['diferencia'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-warning rounded-pill px-3 py-1"><i class="fas fa-arrow-up me-1"></i> +<?= $h['diferencia'] ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>