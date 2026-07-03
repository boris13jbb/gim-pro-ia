<?php
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punto de Venta - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <style>
        body { background: #F3F4F6; }

        /* POS Layout */
        .pos-wrapper { 
            height: calc(100vh - 76px); 
            display: flex; 
            padding: 1.5rem;
            gap: 1.5rem;
            overflow: hidden;
        }
        
        /* Left product grid panel */
        .pos-products-panel {
            flex: 1;
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .product-grid-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 0.5rem;
            padding-bottom: 1rem;
        }

        /* Right cart panel */
        .pos-cart-panel {
            width: 420px;
            min-width: 420px;
            background: white;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.03);
            overflow: hidden;
        }

        .pos-cart-header {
            padding: 1.8rem 1.8rem 1.5rem;
            background: linear-gradient(135deg, var(--gym-primary), #7c3aed);
            color: white;
            position: relative;
        }
        
        /* Decorative element in header */
        .pos-cart-header::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="100" cy="0" r="50" fill="white" fill-opacity="0.1"/></svg>') no-repeat top right;
            pointer-events: none;
        }

        .pos-cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 1.2rem;
            background: #FAFAFA;
        }

        .pos-cart-footer {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #F3F4F6;
            z-index: 10;
            box-shadow: 0 -10px 20px rgba(0,0,0,0.02);
        }

        /* Product Cards in Grid */
        .product-card {
            cursor: pointer;
            border: 2px solid #F3F4F6 !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 16px !important;
            background: white;
            overflow: hidden;
        }
        .product-card:hover {
            border-color: var(--gym-primary) !important;
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(99,102,241,0.15) !important;
        }
        .product-card.out-of-stock {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(100%);
        }
        .product-card .card-img-top {
            height: 140px;
            object-fit: cover;
        }
        .product-placeholder-img {
            height: 140px;
            background: linear-gradient(135deg, #F3F4F6, #E5E7EB);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Cart Item row */
        .cart-item {
            background: white;
            border-radius: 14px;
            padding: 1rem 1.2rem;
            margin-bottom: 0.8rem;
            border: 1px solid #E5E7EB;
            box-shadow: 0 2px 8px rgba(0,0,0,0.03);
            transition: all 0.2s;
        }
        .cart-item:hover {
            border-color: var(--gym-primary);
            box-shadow: 0 4px 12px rgba(99,102,241,0.1);
        }
        .cart-item .item-name { font-weight: 700; font-size: 0.95rem; color: #1F2937; }
        .cart-item .item-price { color: var(--gym-primary); font-weight: 800; font-size: 1.1rem; }

        /* Quantity Buttons in Cart */
        .qty-btn {
            background: #F3F4F6;
            color: #4B5563;
            border: none;
            border-radius: 6px;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            transition: all 0.2s;
        }
        .qty-btn:hover {
            background: var(--gym-primary);
            color: white;
        }
        .hover-danger:hover { color: #DC2626 !important; }

        /* Category filter pills */
        .cat-pill {
            border-radius: 30px;
            padding: 8px 18px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            background: white;
            border: 2px solid #E5E7EB;
            color: var(--gym-text-muted);
            transition: all 0.2s;
            white-space: nowrap;
        }
        .cat-pill.active, .cat-pill:hover {
            background: var(--gym-primary);
            border-color: var(--gym-primary);
            color: white;
            box-shadow: 0 4px 12px rgba(99,102,241,0.2);
        }

        /* Total amount */
        .pos-total {
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--gym-primary);
            line-height: 1;
            letter-spacing: -1px;
        }

        /* Success notification bar */
        .pos-notification {
            position: fixed;
            top: 20px;
            right: 50%;
            transform: translateX(50%);
            z-index: 9999;
            min-width: 350px;
        }

        /* Numpad for cash */
        .cash-input-wrap input {
            font-size: 1.2rem;
            font-weight: 700;
            height: 45px;
            border-radius: 12px;
            border: 2px solid #E5E7EB;
            text-align: right;
            padding-right: 1rem;
            background: white;
        }
        .cash-input-wrap input:focus {
            border-color: var(--gym-primary);
            box-shadow: 0 0 0 4px rgba(99,102,241,0.1);
        }

        .numpad-display {
            background: white;
            border-radius: 12px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 1rem;
            font-size: 1.4rem;
            font-weight: 800;
            color: #1F2937;
            border: 2px solid #E5E7EB;
        }

        /* Method Buttons */
        .metodo-btn { 
            background: #F3F4F6; 
            color: #6B7280; 
            border: 2px solid transparent; 
            border-radius: 12px; 
            padding: 0.8rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .metodo-btn:hover { background: #E5E7EB; color: #1F2937; }
        .metodo-btn.active-metodo { 
            background: rgba(99,102,241,0.1); 
            color: var(--gym-primary); 
            border-color: var(--gym-primary); 
            box-shadow: 0 4px 12px rgba(99,102,241,0.1);
        }
    </style>
</head>
<body>

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <!-- Alert Notifications -->
    <div class="pos-notification" id="posNotification" style="display:none;"></div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mx-4 mt-3" role="alert">
            <i class="fas fa-check-circle me-2"></i> ¡Venta registrada exitosamente! N° <?= $_GET['venta_id'] ?? '' ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mx-4 mt-3" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php
                $errors = ['carrito_vacio' => 'El carrito está vacío.', 'error_procesar' => 'Error al procesar la venta.'];
                echo $errors[$_GET['error']] ?? 'Error desconocido.';
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="pos-wrapper">
        <!-- LEFT: Products Panel -->
        <div class="pos-products-panel">
            <!-- Search + Category Filters -->
            <div class="d-flex flex-wrap gap-2 align-items-center mb-4 pb-2 border-bottom">
                <div class="input-group input-group-lg" style="max-width: 350px; flex-shrink: 0;">
                    <span class="input-group-text bg-light border-0 rounded-start-pill"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchProducto" class="form-control bg-light border-0 rounded-end-pill fw-bold text-dark px-2" placeholder="Buscar producto...">
                </div>
                <!-- Categories scrollable wrapper -->
                <div class="d-flex gap-2 ms-2 overflow-auto py-1" style="scrollbar-width: none;">
                    <button class="cat-pill active" data-cat="all">Todos</button>
                    <?php foreach ($categorias as $cat): ?>
                        <button class="cat-pill" data-cat="<?= $cat['id'] ?>"><?= $cat['nombre'] ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Product Cards Container -->
            <div class="product-grid-container">
                <div class="row g-3" id="productGrid">
                    <?php foreach ($productos as $p): ?>
                    <div class="col-6 col-md-4 col-xl-3 product-item" data-cat="<?= $p['categoria_id'] ?>" data-nombre="<?= strtolower($p['nombre']) ?>">
                        <div class="card product-card h-100 <?= ($p['stock'] <= 0) ? 'out-of-stock' : '' ?>"
                             onclick="<?= ($p['stock'] > 0) ? 'agregarAlCarrito(' . $p['id'] . ')' : '' ?>">
                            <?php if(!empty($p['foto'])): ?>
                                <img src="/img/productos/<?= $p['foto'] ?>" class="card-img-top" alt="<?= $p['nombre'] ?>">
                            <?php else: ?>
                                <div class="product-placeholder-img">
                                    <i class="fas fa-box fa-2x text-muted opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <p class="card-title fw-bold text-dark mb-1 small"><?= $p['nombre'] ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bolder text-primary fs-5"><?= $config['moneda'] ?><?= number_format($p['precio_venta'], 2) ?></span>
                                    <?php if($p['stock'] > 0): ?>
                                        <span class="badge fw-bold rounded-pill badge-soft-success px-3 py-2"><?= $p['stock'] ?> uds</span>
                                    <?php else: ?>
                                        <span class="badge fw-bold rounded-pill badge-soft-danger px-3 py-2">Agotado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: Cart Panel -->
        <div class="pos-cart-panel">
            <!-- Cart Header -->
            <div class="pos-cart-header" style="z-index: 1;">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="m-0 fw-bold position-relative"><i class="fas fa-shopping-basket me-2 opacity-75"></i> Carrito</h4>
                    <button class="btn btn-light text-danger rounded-circle p-0 shadow-sm position-relative d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" onclick="vaciarCarrito()" title="Vaciar carrito">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
                <!-- Socio selector -->
                <div class="position-relative">
                    <label class="text-white-50 fw-semibold small mb-1">Cliente / Socio</label>
                    <select id="socioSelect" class="form-select form-select-lg bg-white border-0 shadow-sm fw-bold text-dark rounded-3" style="font-size: 0.95rem;">
                        <option value="">-- Público General --</option>
                        <?php foreach ($socios as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= $s['nombre'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="pos-cart-items d-flex flex-column h-100" id="cartItems">
                <div class="text-center py-5 d-flex flex-column align-items-center justify-content-center m-auto opacity-50" id="emptyCartMsg">
                    <div style="background: rgba(0,0,0,0.04); width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border-radius: 50%; margin-bottom: 1rem;">
                        <i class="fas fa-shopping-basket fa-3x text-secondary"></i>
                    </div>
                    <span class="fw-bold fs-5 d-block text-dark">Carrito vacío</span>
                    <span class="text-muted mt-1 small">Selecciona productos del catálogo izquierdo</span>
                </div>
            </div>

            <!-- Cart Footer: Total + Checkout -->
            <div class="pos-cart-footer">
                <!-- Total Bruto -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Subtotal</span>
                    <span class="fw-bold text-muted" id="subtotalAmount"><?= $config['moneda'] ?>0.00</span>
                </div>

                <!-- Discount -->
                <div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded-3 border">
                    <label class="text-muted fw-semibold small text-uppercase mb-0 flex-shrink-0"><i class="fas fa-tag me-1"></i>Descuento</label>
                    <input type="number" id="discountInput" min="0" step="0.01" class="form-control form-control-sm text-end fw-bold border-0 bg-transparent" style="max-width:100px;" placeholder="0.00" oninput="recalcularTotal()">
                    <span class="text-muted small"><?= $config['moneda'] ?></span>
                </div>

                <!-- Total a Pagar -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted fw-bold text-uppercase fs-6">Total a Pagar</span>
                    <span class="pos-total" id="totalAmount"><?= $config['moneda'] ?>0.00</span>
                </div>

                <!-- Cash calculator -->
                <div class="mb-3 p-3 bg-light rounded-4 border" id="cashCalculator" style="display:none;">
                    <div class="row g-2 align-items-center">
                        <div class="col-6 text-center">
                            <label class="text-muted fw-bold small text-uppercase mb-1">Efectivo Recibido</label>
                            <div class="cash-input-wrap">
                                <input type="number" step="0.01" class="form-control" id="cashReceived" placeholder="0.00" oninput="calcularVuelto()">
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <label class="text-muted fw-bold small text-uppercase mb-1">Vuelto Estimado</label>
                            <div class="numpad-display justify-content-center" id="cambioDisplay">0.00</div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-sm flex-fill metodo-btn active-metodo fw-bold" data-metodo="efectivo" id="btnEfectivo" onclick="setMetodo('efectivo')">
                        <i class="fas fa-money-bill me-1"></i> Efectivo
                    </button>
                    <button class="btn btn-sm flex-fill fw-bold metodo-btn" data-metodo="tarjeta" id="btnTarjeta" onclick="setMetodo('tarjeta')">
                        <i class="fas fa-credit-card me-1"></i> Tarjeta
                    </button>
                    <button class="btn btn-sm flex-fill fw-bold metodo-btn" data-metodo="transferencia" id="btnTransferencia" onclick="setMetodo('transferencia')">
                        <i class="fas fa-mobile-alt me-1"></i> Transf.
                    </button>
                </div>

                <!-- Tipo de comprobante (SRI) -->
                <div class="mt-2">
                    <label class="form-label small fw-bold text-muted">Comprobante a emitir (SRI)</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="cpe_tipo_choice" id="cpe_factura" value="01" checked>
                        <label class="btn btn-outline-success" for="cpe_factura"><i class="fas fa-file-invoice-dollar"></i> Factura</label>
                        <input type="radio" class="btn-check" name="cpe_tipo_choice" id="cpe_ninguno" value="ninguno">
                        <label class="btn btn-outline-secondary" for="cpe_ninguno"><i class="fas fa-ban"></i> Ninguno</label>
                    </div>
                </div>

                <!-- Datos del cliente para factura SRI -->
                <div id="datosClienteCpe" class="border rounded p-2 mt-2 bg-light">
                    <div class="row g-1">
                        <div class="col-4">
                            <select class="form-select form-select-sm" id="cpe_tipo_doc" name="cpe_tipo_doc">
                                <option value="04">RUC</option>
                                <option value="05">Cédula</option>
                                <option value="06">Pasaporte</option>
                                <option value="07">Consumidor Final</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm" id="cpe_num_doc" name="cpe_num_doc" placeholder="N° documento" maxlength="15">
                                <button type="button" class="btn btn-outline-info" id="btnConsultarDoc" onclick="consultarSri()" title="Consultar SRI">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control form-control-sm" id="cpe_razon" name="cpe_razon" placeholder="Razón social / Nombre">
                        </div>
                        <div class="col-12">
                            <input type="text" class="form-control form-control-sm" id="cpe_direccion" name="cpe_direccion" placeholder="Dirección fiscal (opcional)">
                        </div>
                    </div>
                    <div id="rucMsg" class="small mt-1"></div>
                </div>

                <!-- Checkout Button -->
                <form id="checkoutForm" action="/pos/procesarVenta" method="POST">
                    <input type="hidden" name="metodo_pago" id="metodoPago" value="efectivo">
                    <input type="hidden" name="socio_id" id="socioId">
                    <input type="hidden" name="descuento" id="descuentoHidden" value="0">
                    <input type="hidden" name="tipo_comprobante" id="hiddenTipoComp" value="boleta">
                    <input type="hidden" name="cliente_tipo_doc" id="hiddenTipoDoc">
                    <input type="hidden" name="cliente_num_doc"  id="hiddenNumDoc">
                    <input type="hidden" name="cliente_razon"    id="hiddenRazon">
                    <input type="hidden" name="cliente_direccion" id="hiddenDireccion">
                    <button type="button" id="btnCobrar" class="btn btn-primary py-3 fw-bolder fs-4 w-100 rounded-4 shadow-sm mt-2 pt-3 pb-3 d-flex align-items-center justify-content-center" onclick="confirmarVenta()">
                        <i class="fas fa-cash-register me-2"></i> COBRAR AHORA
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Confirm Sale Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg overflow-hidden rounded-4">
                <div class="modal-header pb-4 px-4" style="background: linear-gradient(135deg, var(--gym-primary), #7c3aed); color: white; border: none;">
                    <div>
                        <p class="mb-0 opacity-75 fw-semibold text-uppercase small">Resumen de Venta</p>
                        <h4 class="modal-title fw-bold m-0"><i class="fas fa-cash-register me-2"></i> Confirmar Cobro</h4>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-4 p-3 bg-light rounded-4">
                        <p class="text-muted fw-semibold small text-uppercase mb-1">Total a Cobrar</p>
                        <p class="fw-bolder m-0" style="font-size: 3rem; color: var(--gym-primary); line-height: 1; letter-spacing: -1px;" id="confirmTotal"></p>
                    </div>
                    <div id="confirmDetalles" class="text-muted small bg-light rounded-3 p-3"></div>
                </div>
                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-light fw-bold px-4 rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary fw-bolder px-5 py-2 rounded-3 flex-grow-1" onclick="document.getElementById('checkoutForm').submit()">
                        <i class="fas fa-check me-2"></i> Confirmar y Cobrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
    // Cart state
    let cart = <?= json_encode($carrito) ?>;
    let moneda = '<?= $config['moneda'] ?>';
    let metodoPagoActual = 'efectivo';
    let totalBruto = <?= $totalCarrito ?>;
    let totalActual = <?= $totalCarrito ?>;

    function recalcularTotal() {
        const descuento = parseFloat(document.getElementById('discountInput').value) || 0;
        totalActual = Math.max(0, totalBruto - descuento);
        document.getElementById('totalAmount').textContent = moneda + totalActual.toFixed(2);
        document.getElementById('subtotalAmount').textContent = moneda + totalBruto.toFixed(2);
        document.getElementById('descuentoHidden').value = descuento.toFixed(2);
        calcularVuelto();
    }

    function agregarAlCarrito(id) {
        fetch('/pos/agregarAlCarrito', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                cart = data.carrito;
                totalActual = parseFloat(data.total);
                renderCart();
                showNotification('Producto añadido al carrito', 'success');
            } else {
                showNotification(data.message || 'Error al agregar', 'danger');
            }
        });
    }

    function eliminarDelCarrito(id) {
        fetch('/pos/eliminarDelCarrito', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                cart = data.carrito;
                totalActual = parseFloat(data.total);
                renderCart();
            }
        });
    }

    function actualizarCantidad(id, cantidad) {
        fetch('/pos/actualizarCantidad', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'id=' + id + '&cantidad=' + cantidad
        })
        .then(r => r.json())
        .then(data => {
            if(data.status === 'success') {
                cart = data.carrito;
                totalActual = parseFloat(data.total);
                renderCart();
            } else {
                showNotification(data.message || 'Error de stock', 'warning');
            }
        });
    }

    function vaciarCarrito() {
        fetch('/pos/vaciarCarrito', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax=1'
        })
        .then(r => r.json())
        .then(() => {
            cart = {};
            totalActual = 0;
            renderCart();
        });
    }

    function renderCart() {
        const cartDiv = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyCartMsg');
        const totalEl = document.getElementById('subtotalAmount');

        totalBruto = parseFloat(Object.values(cart).reduce((s, i) => s + i.precio * i.cantidad, 0));
        totalEl.textContent = moneda + totalBruto.toFixed(2);
        recalcularTotal();

        const items = Object.values(cart);
        if(items.length === 0) {
            cartDiv.innerHTML = '';
            if(!emptyMsg.parentElement) cartDiv.appendChild(emptyMsg);
            emptyMsg.style.display = 'flex';
            return;
        }
        emptyMsg.style.display = 'none';

        cartDiv.innerHTML = items.map(item => `
            <div class="cart-item">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="item-name">${item.nombre}</span>
                    <button class="btn btn-sm p-0 text-muted ms-2 border-0 bg-transparent hover-danger" onclick="eliminarDelCarrito(${item.id})">
                        <i class="fas fa-times fs-5"></i>
                    </button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="d-flex align-items-center gap-1">
                        <button class="qty-btn" onclick="actualizarCantidad(${item.id}, ${item.cantidad - 1})">−</button>
                        <span class="fw-bolder px-2 fs-5 text-dark">${item.cantidad}</span>
                        <button class="qty-btn" onclick="actualizarCantidad(${item.id}, ${item.cantidad + 1})">+</button>
                    </div>
                    <span class="item-price">${moneda}${(item.precio * item.cantidad).toFixed(2)}</span>
                </div>
            </div>
        `).join('');
    }

    function setMetodo(metodo) {
        metodoPagoActual = metodo;
        document.getElementById('metodoPago').value = metodo;
        document.querySelectorAll('.metodo-btn').forEach(b => b.classList.remove('active-metodo'));
        document.getElementById('btn' + metodo.charAt(0).toUpperCase() + metodo.slice(1)).classList.add('active-metodo');
        document.getElementById('cashCalculator').style.display = metodo === 'efectivo' ? 'block' : 'none';
    }

    function calcularVuelto() {
        const recibido = parseFloat(document.getElementById('cashReceived').value) || 0;
        const vuelto = recibido - totalActual;
        document.getElementById('cambioDisplay').textContent = vuelto >= 0 ? moneda + vuelto.toFixed(2) : '-';
        document.getElementById('cambioDisplay').style.color = vuelto >= 0 ? '#10B981' : '#EF4444';
    }

    function confirmarVenta() {
        if(Object.keys(cart).length === 0) {
            showNotification('El carrito está vacío', 'warning');
            return;
        }
        // Sync socio
        document.getElementById('socioId').value = document.getElementById('socioSelect').value;

        const descuento = parseFloat(document.getElementById('discountInput').value) || 0;
        document.getElementById('descuentoHidden').value = descuento.toFixed(2);

        // Set confirm modal details
        document.getElementById('confirmTotal').textContent = moneda + totalActual.toFixed(2);
        let detalles = Object.values(cart).map(i => `• ${i.nombre} x${i.cantidad} = ${moneda}${(i.precio * i.cantidad).toFixed(2)}`).join('<br>');
        if (descuento > 0) detalles += `<br><strong class="text-danger">Descuento aplicado: -${moneda}${descuento.toFixed(2)}</strong>`;
        document.getElementById('confirmDetalles').innerHTML = detalles;

        new bootstrap.Modal(document.getElementById('confirmModal')).show();
    }

    function showNotification(msg, type) {
        const el = document.getElementById('posNotification');
        el.innerHTML = `<div class="alert alert-${type} shadow-lg border-0 fw-semibold"><i class="fas fa-info-circle me-2"></i>${msg}</div>`;
        el.style.display = 'block';
        setTimeout(() => { el.style.display = 'none'; }, 2500);
    }

    // Category filter
    document.querySelectorAll('.cat-pill').forEach(pill => {
        pill.addEventListener('click', function() {
            document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const cat = this.dataset.cat;
            document.querySelectorAll('.product-item').forEach(item => {
                item.style.display = (cat === 'all' || item.dataset.cat === cat) ? '' : 'none';
            });
        });
    });

    // Search filter
    document.getElementById('searchProducto').addEventListener('input', function() {
        const term = this.value.toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            item.style.display = item.dataset.nombre.includes(term) ? '' : 'none';
        });
    });

    // ==== Tipo comprobante / receptor ====
    document.querySelectorAll('input[name="cpe_tipo_choice"]').forEach(r => {
        r.addEventListener('change', function() {
            const v = this.value;
            const box = document.getElementById('datosClienteCpe');
            const tipoDoc = document.getElementById('cpe_tipo_doc');
            const hidden = document.getElementById('hiddenTipoComp');
            if (v === '01') {                         // Factura SRI
                box.style.display = '';
                tipoDoc.value = '04'; // RUC por defecto
                hidden.value = 'factura';
            } else {
                box.style.display = 'none';
                hidden.value = 'ninguno';
            }
        });
    });

    function consultarSri() {
        const numDoc = document.getElementById('cpe_num_doc').value.trim();
        const tipoDoc = document.getElementById('cpe_tipo_doc').value;
        const msg = document.getElementById('rucMsg');
        if (!numDoc) { msg.innerHTML = '<span class="text-warning">Ingresa el número</span>'; return; }
        msg.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Consultando...';
        fetch('/facturacionelectronica/consultarDoc?tipo=' + tipoDoc + '&numero=' + encodeURIComponent(numDoc))
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('cpe_razon').value = data.razon || '';
                    document.getElementById('cpe_direccion').value = data.direccion || '';
                    msg.innerHTML = '<span class="text-success"><i class="fas fa-check"></i> Encontrado</span>';
                } else {
                    msg.innerHTML = '<span class="text-warning">' + (data.error || 'No encontrado') + '</span>';
                }
            })
            .catch(() => msg.innerHTML = '<span class="text-danger">Error de red</span>');
    }

    // Antes de enviar, copiar a los hidden
    document.getElementById('checkoutForm').addEventListener('submit', function() {
        document.getElementById('hiddenTipoDoc').value   = document.getElementById('cpe_tipo_doc')?.value || '';
        document.getElementById('hiddenNumDoc').value    = document.getElementById('cpe_num_doc')?.value || '';
        document.getElementById('hiddenRazon').value     = document.getElementById('cpe_razon')?.value || '';
        document.getElementById('hiddenDireccion').value = document.getElementById('cpe_direccion')?.value || '';
    });

    // Init
    setMetodo('efectivo');
    renderCart();
    </script>
</body>
</html>
