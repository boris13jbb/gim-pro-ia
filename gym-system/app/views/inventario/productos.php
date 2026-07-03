<?php
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body>

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 fade-in mt-5 mb-5">
        <div class="module-container">
            <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h3 class="fw-bold text-dark m-0">
                <i class="fas fa-box-open text-primary me-2"></i> Gestión de Productos
            </h3>
            <div class="d-flex gap-2 flex-wrap">
                <button onclick="ExportHelper.toExcel('tblProductos', 'Productos_Inventario', 'Inventario de Productos')" class="btn btn-success btn-sm rounded-pill fw-bold px-3 shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> Excel
                </button>
                <button onclick="ExportHelper.toPDF('tblProductos', 'Productos_Inventario', 'Inventario de Productos')" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 shadow-sm">
                    <i class="fas fa-file-pdf me-1"></i> PDF
                </button>
                <button onclick="ExportHelper.print('tblProductos', 'Inventario de Productos')" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 shadow-sm">
                    <i class="fas fa-print me-1"></i> Imprimir
                </button>
                <button class="btn btn-primary px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProducto">
                    <i class="fas fa-plus me-2"></i> Nuevo Producto
                </button>
            </div>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
                <i class="fas fa-check-circle me-2"></i> Operación realizada con éxito.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="table-responsive mt-2">
            <table id="tblProductos" class="table table-hover align-middle table-data w-100 border-0">
                <thead>
                    <tr>
                        <th class="border-0 bg-transparent text-muted">Foto</th>
                        <th class="border-0 bg-transparent text-muted">Producto</th>
                        <th class="border-0 bg-transparent text-muted">Categoría</th>
                        <th class="border-0 bg-transparent text-muted">Costo</th>
                        <th class="border-0 bg-transparent text-muted">Precio Venta</th>
                        <th class="border-0 bg-transparent text-muted">Stock</th>
                        <th class="border-0 bg-transparent text-muted">Estado</th>
                        <th class="border-0 bg-transparent text-muted text-end">Acciones</th>
                    </tr>
                </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($p['foto'])): ?>
                                        <img src="/img/productos/<?= $p['foto'] ?>" class="rounded-circle border" width="45" height="45" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width:45px;height:45px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= $p['nombre'] ?></div>
                                    <?php if(!empty($p['codigo'])): ?>
                                        <small class="text-muted">Cód: <?= $p['codigo'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-soft-primary rounded-pill px-3 py-2"><?= htmlspecialchars($p['categoria_nombre'] ?? '—') ?></span></td>
                                <td class="text-muted"><?= $config['moneda'] ?><?= number_format($p['precio_compra'], 2) ?></td>
                                <td class="fw-bold text-success"><?= $config['moneda'] ?><?= number_format($p['precio_venta'], 2) ?></td>
                                <td>
                                    <?php
                                        $stockClass = 'bg-success text-white';
                                        if ($p['stock'] <= 5) $stockClass = 'bg-danger text-white';
                                        elseif ($p['stock'] <= 15) $stockClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $stockClass ?> px-3 py-2 fw-bold shadow-sm" style="font-size: 0.75rem;">
                                        <?= $p['stock'] ?> uds
                                    </span>
                                </td>
                                <td>
                                    <?php if($p['estado'] == 'activo'): ?>
                                        <span class="badge bg-success text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Activo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary text-white px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.7rem; font-weight: 700;"><i class="fas fa-circle me-1" style="font-size: 0.5rem; vertical-align: middle;"></i> Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- Ajustar Stock -->
                                    <button class="btn btn-sm btn-outline-info me-1 stock-btn" 
                                            data-id="<?= $p['id'] ?>" 
                                            data-nombre="<?= $p['nombre'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalStock" title="Ajustar Stock">
                                        <i class="fas fa-cubes"></i>
                                    </button>
                                    <!-- Editar -->
                                    <button class="btn btn-sm btn-outline-warning me-1 edit-btn"
                                            data-id="<?= $p['id'] ?>"
                                            data-categoria="<?= $p['categoria_id'] ?>"
                                            data-codigo="<?= $p['codigo'] ?>"
                                            data-nombre="<?= $p['nombre'] ?>"
                                            data-compra="<?= $p['precio_compra'] ?>"
                                            data-venta="<?= $p['precio_venta'] ?>"
                                            data-stock="<?= $p['stock'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalProducto" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Estado -->
                                    <?php if($p['estado'] == 'activo'): ?>
                                        <a href="/inventario/cambiarEstadoProducto/<?= $p['id'] ?>/inactivo" class="btn btn-sm btn-outline-danger btn-confirm" data-title="¿Desactivar producto?" title="Desactivar"><i class="fas fa-ban"></i></a>
                                    <?php else: ?>
                                        <a href="/inventario/cambiarEstadoProducto/<?= $p['id'] ?>/activo" class="btn btn-sm btn-outline-success btn-confirm" data-title="¿Reactivar producto?" title="Activar"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Producto -->
    <div class="modal fade" id="modalProducto" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom px-4 py-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center" id="modalProdTitle">
                        <div class="bg-primary bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="modalProdIconContainer">
                            <i class="fas fa-box text-primary" id="modalProdIcon"></i>
                        </div>
                        <span id="modalProdTitleText">Nuevo Producto</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/inventario/guardarProducto" method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="prod_id">
                        <div class="row g-3">
                            <div class="col-md-6 form-floating mb-2">
                                <select name="categoria_id" id="prod_cat" class="form-select rounded-3" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= $cat['nombre'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="prod_cat" class="text-muted ms-2"><i class="fas fa-tag me-2"></i>Categoría *</label>
                            </div>
                            <div class="col-md-6 form-floating mb-2">
                                <input type="text" class="form-control rounded-3" name="codigo" id="prod_codigo" placeholder="Ej: PROT-001">
                                <label for="prod_codigo" class="text-muted ms-2"><i class="fas fa-barcode me-2"></i>Código (opcional)</label>
                            </div>
                            <div class="col-12 form-floating mb-2">
                                <input type="text" class="form-control rounded-3" name="nombre" id="prod_nombre" required placeholder="Ej: Proteína Whey 1kg">
                                <label for="prod_nombre" class="text-muted ms-2"><i class="fas fa-box-open me-2"></i>Nombre del Producto *</label>
                            </div>
                            <div class="col-md-4 form-floating mb-2">
                                <input type="number" step="0.01" class="form-control rounded-3" name="precio_compra" id="prod_compra" required placeholder="0.00">
                                <label for="prod_compra" class="text-muted ms-2"><span class="me-1 fw-bold"><?= $config['moneda'] ?></span> Precio Compra *</label>
                            </div>
                            <div class="col-md-4 form-floating mb-2">
                                <input type="number" step="0.01" class="form-control rounded-3" name="precio_venta" id="prod_venta" required placeholder="0.00">
                                <label for="prod_venta" class="text-muted ms-2"><i class="fas fa-tag me-2"></i>Precio Venta *</label>
                            </div>
                            <div class="col-md-4 form-floating mb-3">
                                <input type="number" class="form-control rounded-3" name="stock" id="prod_stock" placeholder="0" value="0">
                                <label for="prod_stock" class="text-muted ms-2"><i class="fas fa-cubes me-2"></i>Stock Inicial</label>
                            </div>
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold ms-1" style="font-size: 0.9rem;"><i class="fas fa-image me-2"></i>Foto del Producto</label>
                                <input type="file" class="form-control form-control-lg rounded-3 border-light shadow-sm" name="foto" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary px-5 py-2 shadow-sm fw-bold rounded-pill"><i class="fas fa-save me-2"></i>Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajuste de Stock -->
    <div class="modal fade" id="modalStock" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
                <div class="modal-header border-bottom px-4 py-3" style="background: var(--gym-bg-light); border-top-left-radius: 20px; border-top-right-radius: 20px;">
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-2 rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-cubes text-info"></i>
                        </div>
                        Ajustar Stock
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/inventario/ajusteStock" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="producto_id" id="stock_prod_id">
                        
                        <div class="d-flex align-items-center mb-4 p-3 rounded-3" style="background: rgba(14, 165, 233, 0.05); border: 1px dashed rgba(14, 165, 233, 0.3);">
                            <div class="me-3">
                                <i class="fas fa-box-open text-info fa-2x"></i>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Producto Seleccionado</small>
                                <div class="fw-bold text-dark fs-5" id="stock_prod_nombre"></div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-7 form-floating mb-2">
                                <input type="number" min="1" class="form-control rounded-3" name="cantidad" id="stock_cantidad" required placeholder="Ej: 10">
                                <label for="stock_cantidad" class="text-muted ms-2"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad</label>
                            </div>
                            <div class="col-md-5 form-floating mb-2">
                                <select name="operacion" id="stock_operacion" class="form-select rounded-3">
                                    <option value="sumar">➕ Entrada</option>
                                    <option value="restar">➖ Salida</option>
                                </select>
                                <label for="stock_operacion" class="text-muted ms-2">Operación</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light px-4 py-3" style="border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                        <button type="button" class="btn btn-light px-4 py-2 text-muted fw-bold rounded-pill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info text-white px-4 py-2 shadow-sm fw-bold rounded-pill"><i class="fas fa-check-circle me-2"></i>Confirmar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
        $(document).ready(function() {
            // Editar producto - llenar modal
            $('.edit-btn').click(function() {
                $('#modalProdTitleText').text('Editar Producto');
                $('#modalProdIconContainer').removeClass('bg-primary text-primary').addClass('bg-warning text-warning');
                $('#modalProdIcon').removeClass('fa-box text-primary').addClass('fa-edit text-warning');
                $('#prod_id').val($(this).data('id'));
                $('#prod_cat').val($(this).data('categoria'));
                $('#prod_codigo').val($(this).data('codigo'));
                $('#prod_nombre').val($(this).data('nombre'));
                $('#prod_compra').val($(this).data('compra'));
                $('#prod_venta').val($(this).data('venta'));
                $('#prod_stock').val($(this).data('stock'));
            });

            // Limpiar al cerrar
            $('#modalProducto').on('hidden.bs.modal', function() {
                $('#modalProdTitleText').text('Nuevo Producto');
                $('#modalProdIconContainer').removeClass('bg-warning text-warning').addClass('bg-primary text-primary');
                $('#modalProdIcon').removeClass('fa-edit text-warning').addClass('fa-box text-primary');
                this.querySelector('form').reset();
                $('#prod_id').val('');
            });

            // Ajuste de stock
            $('.stock-btn').click(function() {
                $('#stock_prod_id').val($(this).data('id'));
                $('#stock_prod_nombre').text($(this).data('nombre'));
            });
        });
    </script>
</body>
</html>
