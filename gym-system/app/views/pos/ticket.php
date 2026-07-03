<?php
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta #<?= str_pad($venta['id'], 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <style>
        body { background: #F3F4F6; }

        .ticket-page {
            min-height: calc(100vh - 76px);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            gap: 2rem;
            padding: 2rem 1.5rem;
        }

        /* Ticket receipt styling */
        .ticket-receipt {
            background: white;
            width: 340px;
            min-width: 340px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            overflow: hidden;
            position: relative;
        }

        .ticket-header {
            background: linear-gradient(135deg, var(--gym-primary), #7c3aed);
            color: white;
            text-align: center;
            padding: 2rem 1.5rem 1.5rem;
        }

        .ticket-header .ticket-num {
            font-size: 0.8rem;
            opacity: 0.75;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .ticket-header h2 { font-size: 2rem; font-weight: 900; letter-spacing: -1px; margin: 0; }
        .ticket-header small { opacity: 0.8; font-size: 0.85rem; }

        .ticket-body { padding: 1.5rem; }

        .ticket-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #E5E7EB;
            font-size: 0.9rem;
        }
        .ticket-row:last-child { border: none; }
        .ticket-row .label { color: #6B7280; font-weight: 600; }
        .ticket-row .val { font-weight: 700; color: #1F2937; }

        .ticket-items-header {
            background: #F9FAFB;
            border-radius: 10px;
            padding: 0.5rem 0.75rem;
            margin: 1rem 0 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9CA3AF;
            display: flex;
            justify-content: space-between;
        }

        .ticket-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 0.25rem;
            border-bottom: 1px solid #F3F4F6;
        }
        .ticket-item .product-name { font-weight: 700; font-size: 0.9rem; color: #1F2937; }
        .ticket-item .product-meta { font-size: 0.78rem; color: #9CA3AF; }
        .ticket-item .product-total { font-weight: 800; color: var(--gym-primary); font-size: 0.95rem; }

        .ticket-total {
            background: linear-gradient(135deg, #F0FDF4, #D1FAE5);
            border-radius: 14px;
            margin-top: 1rem;
            padding: 1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .ticket-total .total-label { font-weight: 800; font-size: 0.9rem; color: #065F46; text-transform: uppercase; letter-spacing: 0.05em; }
        .ticket-total .total-amount { font-weight: 900; font-size: 1.8rem; color: #059669; letter-spacing: -1px; line-height: 1; }

        /* Serrated bottom edge */
        .ticket-serrated {
            background: radial-gradient(circle at 8px 8px, #F3F4F6 8px, white 8px);
            background-size: 16px 16px;
            background-position: 0 0;
            height: 16px;
            margin: 0 -0px;
        }

        .ticket-footer {
            text-align: center;
            padding: 1rem 1.5rem 1.5rem;
            font-size: 0.78rem;
            color: #9CA3AF;
            border-top: 1px dashed #E5E7EB;
        }

        /* Action buttons panel */
        .action-panel {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            padding: 1.5rem;
            min-width: 260px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            height: fit-content;
        }

        .action-panel h5 { font-weight: 800; color: #1F2937; margin-bottom: 0.3rem; }

        .btn-action {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.9rem 1.2rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.2s;
            text-decoration: none;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .btn-action .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }
        .btn-action-print { background: #EEF2FF; color: var(--gym-primary); }
        .btn-action-print:hover { background: var(--gym-primary); color: white; }
        .btn-action-pdf { background: #FEF2F2; color: #EF4444; }
        .btn-action-pdf:hover { background: #EF4444; color: white; }
        .btn-action-new { background: linear-gradient(135deg, var(--gym-primary), #7c3aed); color: white; box-shadow: 0 4px 15px rgba(99,102,241,0.3); }
        .btn-action-new:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(99,102,241,0.4); }

        /* Print: hide everything except the receipt */
        @media print {
            body * { visibility: hidden; }
            .ticket-receipt, .ticket-receipt * { visibility: visible; }
            .ticket-receipt { position: fixed; left: 50%; top: 0; transform: translateX(-50%); box-shadow: none; border-radius: 0; }
            .action-panel, nav, header { display: none !important; }
        }
    </style>
</head>
<body>
    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="ticket-page fade-in">

        <!-- Receipt Card -->
        <div class="ticket-receipt">
            <!-- Header -->
            <div class="ticket-header">
                <?php if (!empty($config['logo'])): ?>
                    <img src="/img/<?= $config['logo'] ?>" height="40" style="margin-bottom: 0.7rem; filter: brightness(0) invert(1) opacity(0.85);" alt="Logo">
                <?php endif; ?>
                <div class="ticket-num">Comprobante de Venta</div>
                <h2><?= $config['moneda'] ?><?= number_format($venta['total'], 2) ?></h2>
                <small><i class="fas fa-check-circle me-1"></i> Venta procesada exitosamente</small>
            </div>

            <!-- Info rows -->
            <div class="ticket-body">
                <div class="ticket-row">
                    <span class="label">N° Ticket</span>
                    <span class="val">#<?= str_pad($venta['id'], 6, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="ticket-row">
                    <span class="label">Fecha</span>
                    <span class="val"><?= date('d/m/Y H:i', strtotime($venta['fecha'])) ?></span>
                </div>
                <div class="ticket-row">
                    <span class="label">Cajero</span>
                    <span class="val"><?= htmlspecialchars($venta['cajero']) ?></span>
                </div>
                <div class="ticket-row">
                    <span class="label">Cliente</span>
                    <span class="val"><?= htmlspecialchars($venta['cliente']) ?></span>
                </div>
                <div class="ticket-row">
                    <span class="label">Método de Pago</span>
                    <span class="val">
                        <?php 
                        $metodoIcon = ['efectivo' => 'fa-money-bill', 'tarjeta' => 'fa-credit-card', 'transferencia' => 'fa-mobile-alt'];
                        $icon = $metodoIcon[$venta['metodo_pago']] ?? 'fa-money-bill';
                        ?>
                        <i class="fas <?= $icon ?> me-1 text-primary"></i>
                        <?= ucfirst($venta['metodo_pago']) ?>
                    </span>
                </div>

                <!-- Products List -->
                <div class="ticket-items-header">
                    <span>Producto</span>
                    <span>Subtotal</span>
                </div>
                <?php foreach ($detalle as $d): ?>
                <div class="ticket-item">
                    <div>
                        <div class="product-name"><?= htmlspecialchars($d['producto']) ?></div>
                        <div class="product-meta"><?= $d['cantidad'] ?> × <?= $config['moneda'] ?><?= number_format($d['precio_unitario'], 2) ?></div>
                    </div>
                    <div class="product-total"><?= $config['moneda'] ?><?= number_format($d['subtotal'], 2) ?></div>
                </div>
                <?php endforeach; ?>

                <!-- Total -->
                <div class="ticket-total">
                    <div>
                        <div class="total-label">Total Pagado</div>
                        <div class="product-meta text-muted mt-1"><?= count($detalle) ?> producto<?= count($detalle) > 1 ? 's' : '' ?></div>
                    </div>
                    <div class="total-amount"><?= $config['moneda'] ?><?= number_format($venta['total'], 2) ?></div>
                </div>
            </div>

            <!-- Serrated edge -->
            <div class="ticket-serrated"></div>

            <!-- Footer -->
            <div class="ticket-footer">
                <i class="fas fa-heart text-danger me-1"></i>
                ¡Gracias por tu compra!<br>
                <?= htmlspecialchars($config['nombre_sistema']) ?>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="action-panel">
            <div>
                <h5><i class="fas fa-receipt me-2 text-primary"></i>Ticket Listo</h5>
                <p class="text-muted small mb-0">Selecciona una acción para continuar</p>
            </div>

            <hr class="my-0">

            <a href="javascript:window.print()" class="btn-action btn-action-print">
                <div class="action-icon" style="background: rgba(99,102,241,0.1);">
                    <i class="fas fa-print text-primary"></i>
                </div>
                <span>Imprimir Ticket</span>
            </a>

            <a href="/ticket/generar/<?= $venta['id'] ?>" target="_blank" class="btn-action btn-action-pdf">
                <div class="action-icon" style="background: rgba(239,68,68,0.1);">
                    <i class="fas fa-file-pdf text-danger"></i>
                </div>
                <span>Descargar PDF</span>
            </a>

            <?php if (empty($venta['comprobante_id'])): ?>
            <a href="/facturacionelectronica/emitirVenta/<?= $venta['id'] ?>"
               class="btn-action" style="background:#ECFDF5;color:#059669;">
                <div class="action-icon" style="background: rgba(16,185,129,0.15);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <span>Emitir Factura SRI</span>
            </a>
            <?php else: ?>
            <a href="/facturacionelectronica/ver/<?= $venta['comprobante_id'] ?>"
               class="btn-action" style="background:#ECFDF5;color:#059669;">
                <div class="action-icon" style="background: rgba(16,185,129,0.2);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <span>Ver Comprobante SRI</span>
            </a>
            <?php endif; ?>

            <a href="/pos/index" class="btn-action btn-action-new">
                <div class="action-icon" style="background: rgba(255,255,255,0.2);">
                    <i class="fas fa-shopping-cart text-white"></i>
                </div>
                <span>Nueva Venta</span>
            </a>

            <hr class="my-0">

            <a href="/reportes/index" class="text-center text-muted small text-decoration-none">
                <i class="fas fa-chart-line me-1"></i> Ver Reportes
            </a>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>
