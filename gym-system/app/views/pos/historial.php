<?php
if (!isset($_SESSION['user_id'])) { header('Location: /auth/index'); exit; }
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
$moneda = $config['moneda'] ?? '$';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ventas - <?= htmlspecialchars($config['nombre_sistema'] ?? 'Gym System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <style>
        .method-badge { font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
        .method-efectivo   { background: #D1FAE5; color: #065F46; }
        .method-tarjeta    { background: #DBEAFE; color: #1E40AF; }
        .method-transferencia { background: #EDE9FE; color: #5B21B6; }
        .kpi-card { border-radius: 18px; padding: 1.4rem 1.8rem; color: white; position: relative; overflow: hidden; }
        .kpi-card::after { content:''; position:absolute; right:-20px; top:-20px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,0.12); }
        .table-hover tbody tr { cursor: pointer; transition: background 0.15s; }
        .search-bar { max-width: 320px; }
        #searchInput { border-radius: 30px; border: 2px solid #E5E7EB; padding-left: 1rem; }
        #searchInput:focus { border-color: var(--gym-primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
    </style>
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-history text-primary me-2"></i>Historial de Ventas</h4>
            <p class="text-muted mb-0">Registro de todas las transacciones del punto de venta.</p>
        </div>
        <a href="/pos/index" class="btn btn-outline-primary rounded-pill fw-bold">
            <i class="fas fa-cash-register me-2"></i>Ir al POS
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #6366F1, #4F46E5);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Total Recaudado</p>
                <h3 class="fw-black m-0"><?= $moneda ?> <?= number_format($totalVentas, 2) ?></h3>
                <i class="fas fa-coins position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #10B981, #059669);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Cantidad de Ventas</p>
                <h3 class="fw-black m-0"><?= $cantVentas ?></h3>
                <i class="fas fa-receipt position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="kpi-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                <p class="mb-1 fw-semibold opacity-75 small text-uppercase">Ticket Promedio</p>
                <h3 class="fw-black m-0"><?= $moneda ?> <?= number_format($ticketProm, 2) ?></h3>
                <i class="fas fa-chart-line position-absolute" style="bottom:12px;right:20px;font-size:2.5rem;opacity:.15;"></i>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="module-container mb-4">
        <form method="GET" action="/pos/historial" class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Desde</label>
                <input type="date" name="desde" class="form-control rounded-3" value="<?= htmlspecialchars($desde) ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label fw-semibold text-dark small">Hasta</label>
                <input type="date" name="hasta" class="form-control rounded-3" value="<?= htmlspecialchars($hasta) ?>">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold text-dark small">Cliente o Cajero</label>
                <input type="text" name="busqueda" class="form-control rounded-3" placeholder="Nombre..." value="<?= htmlspecialchars($busqueda ?? '') ?>">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-primary rounded-3 fw-bold flex-fill"><i class="fas fa-search me-1"></i>Filtrar</button>
                <a href="/pos/historial" class="btn btn-light rounded-3 fw-bold"><i class="fas fa-times"></i></a>
            </div>
        </form>
    </div>

    <!-- Client-side search inside results -->
    <div class="module-container">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="fw-bold text-dark mb-0">
                <?= $cantVentas ?> registros encontrados
            </h6>
            <div class="search-bar">
                <input type="text" id="searchInput" class="form-control" placeholder="🔍 Buscar en resultados...">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle" id="ventasTable">
                <thead class="table-light">
                    <tr>
                        <th class="fw-bold text-muted small text-uppercase ps-3">#</th>
                        <th class="fw-bold text-muted small text-uppercase">Fecha y Hora</th>
                        <th class="fw-bold text-muted small text-uppercase">Cliente</th>
                        <th class="fw-bold text-muted small text-uppercase">Cajero</th>
                        <th class="fw-bold text-muted small text-uppercase">Método</th>
                        <th class="fw-bold text-muted small text-uppercase text-end">Descuento</th>
                        <th class="fw-bold text-muted small text-uppercase text-end pe-3">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ventas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fas fa-box-open fa-2x mb-3 d-block opacity-50"></i>
                            No hay ventas en el período seleccionado.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($ventas as $v): ?>
                    <tr onclick="verDetalle(<?= $v['id'] ?>, '<?= $moneda ?><?= number_format($v['total'], 2) ?>', '<?= htmlspecialchars($v['cliente'], ENT_QUOTES) ?>')">
                        <td class="ps-3 fw-bold text-primary">#<?= $v['id'] ?></td>
                        <td>
                            <span class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($v['fecha'])) ?></span><br>
                            <small class="text-muted"><?= date('H:i', strtotime($v['fecha'])) ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:32px;height:32px;flex-shrink:0;">
                                    <i class="fas fa-user text-primary" style="font-size:.75rem;"></i>
                                </div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($v['cliente']) ?></span>
                            </div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($v['cajero']) ?></td>
                        <td>
                            <span class="method-badge method-<?= $v['metodo_pago'] ?>">
                                <?php
                                $icons = ['efectivo' => 'fa-money-bill', 'tarjeta' => 'fa-credit-card', 'transferencia' => 'fa-mobile-alt'];
                                $icon = $icons[$v['metodo_pago']] ?? 'fa-circle';
                                ?>
                                <i class="fas <?= $icon ?> me-1"></i><?= ucfirst($v['metodo_pago']) ?>
                            </span>
                        </td>
                        <td class="text-end <?= $v['descuento'] > 0 ? 'text-danger fw-semibold' : 'text-muted' ?>">
                            <?= $v['descuento'] > 0 ? '-' . $moneda . number_format($v['descuento'], 2) : '—' ?>
                        </td>
                        <td class="text-end pe-3">
                            <span class="fw-black text-dark fs-5"><?= $moneda ?><?= number_format($v['total'], 2) ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary rounded-3 px-3" onclick="event.stopPropagation(); verDetalle(<?= $v['id'] ?>, '<?= $moneda ?><?= number_format($v['total'], 2) ?>', '<?= htmlspecialchars($v['cliente'], ENT_QUOTES) ?>')">
                                <i class="fas fa-eye me-1"></i>Detalle
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 px-4 pt-4 pb-3" style="background: linear-gradient(135deg, #6366F1, #4F46E5); color: white;">
                    <div>
                        <p class="mb-0 opacity-75 fw-semibold text-uppercase small">Detalle de Venta</p>
                        <h5 class="modal-title fw-bold m-0"><i class="fas fa-receipt me-2"></i> <span id="modalVentaId"></span></h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex justify-content-between mb-3">
                        <div>
                            <p class="text-muted small mb-0">Cliente</p>
                            <p class="fw-bold text-dark mb-0" id="modalCliente"></p>
                        </div>
                        <div class="text-end">
                            <p class="text-muted small mb-0">Total Cobrado</p>
                            <p class="fw-black text-primary fs-5 mb-0" id="modalTotal"></p>
                        </div>
                    </div>
                    <div id="detalleBody">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verDetalle(id, total, cliente) {
            document.getElementById('modalVentaId').textContent = 'Venta #' + id;
            document.getElementById('modalTotal').textContent = total;
            document.getElementById('modalCliente').textContent = cliente;
            document.getElementById('detalleBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';

            new bootstrap.Modal(document.getElementById('detalleModal')).show();

            fetch('/pos/detalleVenta/' + id)
                .then(r => r.json())
                .then(items => {
                    if (!items || items.length === 0) {
                        document.getElementById('detalleBody').innerHTML = '<p class="text-muted text-center">Sin ítems registrados.</p>';
                        return;
                    }
                    let html = '<table class="table table-sm align-middle mb-0">';
                    html += '<thead class="table-light"><tr><th>Producto</th><th class="text-center">Cant.</th><th class="text-end">Precio Unit.</th><th class="text-end">Subtotal</th></tr></thead><tbody>';
                    items.forEach(i => {
                        html += `<tr>
                            <td class="fw-semibold">${i.nombre}</td>
                            <td class="text-center">${i.cantidad}</td>
                            <td class="text-end text-muted">${parseFloat(i.precio_unitario).toFixed(2)}</td>
                            <td class="text-end fw-bold text-dark">${parseFloat(i.subtotal).toFixed(2)}</td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('detalleBody').innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('detalleBody').innerHTML = '<p class="text-danger text-center">Error al cargar el detalle.</p>';
                });
        }

        // Live search within rendered results
        document.getElementById('searchInput').addEventListener('input', function () {
            const term = this.value.toLowerCase();
            document.querySelectorAll('#ventasTable tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
