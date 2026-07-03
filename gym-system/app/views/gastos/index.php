<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gastos - <?= $config['nombre_sistema'] ?? 'Gym System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 fade-in mt-5 mb-5">
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-4 d-flex justify-content-between align-items-center mb-2 flex-wrap gap-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                <h3 class="fw-bold text-dark m-0 d-flex align-items-center">
                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fas fa-money-bill-wave text-danger"></i> 
                    </div>
                    Registro de Gastos
                </h3>
                <div class="d-flex gap-2 flex-wrap">
                    <button onclick="ExportHelper.toExcel('tblGastos', 'Gastos', 'Registro de Gastos')" class="btn btn-success btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button onclick="ExportHelper.toPDF('tblGastos', 'Gastos', 'Registro de Gastos')" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button onclick="ExportHelper.print('tblGastos', 'Registro de Gastos')" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 shadow-sm d-flex align-items-center">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                    <a href="/gastos/crear" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold d-flex align-items-center">
                        <i class="fas fa-plus me-2"></i> Nuevo Gasto
                    </a>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="px-4 mt-3">
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                        <i class="fas fa-check-circle me-2"></i> Operación realizada con éxito.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>

            <div class="p-4 pt-0 mt-2">
                <div class="table-responsive">
                    <table id="tblGastos" class="table table-hover align-middle table-data border-0">
                        <thead>
                            <tr>
                                <th class="border-0 bg-transparent text-muted">Fecha</th>
                                <th class="border-0 bg-transparent text-muted">Descripción</th>
                                <th class="border-0 bg-transparent text-muted">Estado</th>
                                <th class="border-0 bg-transparent text-muted">Monto</th>
                                <th class="border-0 bg-transparent text-muted text-end">Acción</th>
                            </tr>
                        </thead>
                    <tbody>
                        <?php foreach ($gastos as $g): ?>
                        <tr class="<?= isset($g['estado']) && $g['estado'] == 'anulado' ? 'opacity-75' : '' ?>">
                            <td class="text-muted fw-bold">
                                <?= date('d/m/Y', strtotime($g['fecha'])) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm border" style="width: 35px; height: 35px;">
                                        <i class="fas fa-file-invoice-dollar text-muted"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark <?= isset($g['estado']) && $g['estado'] == 'anulado' ? 'text-decoration-line-through text-muted' : '' ?>"><?= $g['descripcion'] ?></div>
                                        <?php if(isset($g['estado']) && $g['estado'] == 'anulado' && !empty($g['motivo_anulacion'])): ?>
                                            <small class="text-danger d-block mt-1" style="font-size: 0.75rem;"><i class="fas fa-info-circle me-1"></i>Motivo: <?= $g['motivo_anulacion'] ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if(isset($g['estado']) && $g['estado'] == 'anulado'): ?>
                                    <span class="badge bg-secondary text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-ban me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Anulado</span>
                                <?php else: ?>
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Generado</span>
                                <?php endif; ?>
                            </td>
                            <td class="<?= isset($g['estado']) && $g['estado'] == 'anulado' ? 'text-muted text-decoration-line-through' : 'text-danger' ?> fw-bold">
                                -<?= $config['moneda'] ?> <?= number_format($g['monto'], 2) ?>
                            </td>
                            
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <?php if(!isset($g['estado']) || $g['estado'] != 'anulado'): ?>
                                        <button class="btn btn-light text-danger btn-sm rounded-circle d-flex align-items-center justify-content-center border btn-anular" 
                                                style="width: 35px; height: 35px; transition: all 0.2s;"
                                                data-id="<?= $g['id'] ?>"
                                                data-desc="<?= htmlspecialchars($g['descripcion']) ?>"
                                                data-monto="<?= '-'.$config['moneda'].' '.number_format($g['monto'], 2) ?>"
                                                title="Anular Gasto"
                                                onmouseover="this.classList.replace('btn-light', 'btn-danger'); this.classList.replace('text-danger', 'text-white');" 
                                                onmouseout="this.classList.replace('btn-danger', 'btn-light'); this.classList.replace('text-white', 'text-danger');">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Anular Gasto -->
    <div class="modal fade" id="modalAnularGasto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom px-4 py-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-ban text-danger"></i>
                        </div>
                        Anular Gasto
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/gastos/anular" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="anular_gasto_id">
                        
                        <div class="d-flex align-items-center mb-4 p-3 rounded-3" style="background: rgba(220, 53, 69, 0.05); border: 1px dashed rgba(220, 53, 69, 0.3);">
                            <div class="me-3">
                                <i class="fas fa-file-invoice-dollar text-danger fa-2x"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Gasto Seleccionado</small>
                                    <span class="badge bg-danger px-2 py-1 shadow-sm" id="anular_gasto_monto"></span>
                                </div>
                                <div class="fw-bold text-dark fs-6 mt-1 text-truncate" id="anular_gasto_desc" style="max-width: 300px;"></div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <textarea class="form-control rounded-3" name="motivo_anulacion" id="motivo_anulacion" placeholder="Motivo de la anulación" style="height: 100px" required></textarea>
                            <label for="motivo_anulacion" class="text-muted"><i class="fas fa-comment-dots me-2"></i>Motivo de anulación *</label>
                        </div>
                        <div class="alert alert-warning mb-0 p-2" style="font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle me-1"></i> El gasto seguirá siendo visible pero no sumará en los reportes totales.
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill border" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger text-white px-4 py-2 shadow-sm fw-bold rounded-pill"><i class="fas fa-ban me-2"></i>Anular Gasto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const anularButtons = document.querySelectorAll('.btn-anular');
            anularButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const desc = this.getAttribute('data-desc');
                    const monto = this.getAttribute('data-monto');

                    document.getElementById('anular_gasto_id').value = id;
                    document.getElementById('anular_gasto_desc').textContent = desc;
                    document.getElementById('anular_gasto_monto').textContent = monto;
                    document.getElementById('motivo_anulacion').value = '';
                    
                    var anularModal = new bootstrap.Modal(document.getElementById('modalAnularGasto'));
                    anularModal.show();
                });
            });
        });
    </script>
</body>
</html>