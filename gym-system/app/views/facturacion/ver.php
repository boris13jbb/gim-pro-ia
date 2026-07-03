<?php
$nombresTipo = ['01'=>'Factura Electrónica','04'=>'Nota de Crédito'];
$colorEstado = [
    'autorizado'    => 'success',
    'pendiente'     => 'secondary',
    'no_autorizado' => 'danger',
    'devuelta'      => 'warning',
    'recibida'      => 'info',
    'anulado'       => 'dark',
    'error'         => 'danger'
];
$estadoActual = $cab['estado_sri'] ?: 'pendiente';
$col = $colorEstado[$estadoActual] ?? 'secondary';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante <?= substr($cab['serie'], 0, 3) . '-' . substr($cab['serie'], 3, 3) ?>-<?= str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .clave-acceso {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem;
            font-family: monospace;
            font-size: 0.95rem;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body class="bg-light">
<?php require_once '../app/views/inc/navbar.php'; ?>

<div class="container-fluid p-4">

    <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= ($_SESSION['flash_ok'] ?? false) ? 'success' : 'warning' ?> alert-dismissible fade show shadow-sm">
            <i class="fas fa-<?= ($_SESSION['flash_ok'] ?? false) ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_ok']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <a href="/facturacionelectronica/index" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Volver a la Bandeja
            </a>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-danger" href="/facturacionelectronica/pdf/<?= $cab['id'] ?>" target="_blank">
                <i class="fas fa-file-pdf"></i> Imprimir RIDE (PDF)
            </a>
            <a class="btn btn-secondary" href="/facturacionelectronica/descargarXml/<?= $cab['id'] ?>">
                <i class="fas fa-file-code"></i> Descargar XML
            </a>
            
            <?php if ($estadoActual === 'autorizado' && $cab['tipo_doc'] === '01'): ?>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalNC">
                <i class="fas fa-undo"></i> Nota de Crédito
            </button>
            <?php endif; ?>

            <?php if (in_array($estadoActual, ['pendiente', 'recibida', 'devuelta', 'no_autorizado', 'error'])): ?>
            <a class="btn btn-success animate-pulse" href="/facturacionelectronica/reintentar/<?= $cab['id'] ?>">
                <i class="fas fa-sync-alt"></i> Consultar/Autorizar SRI
            </a>
            <?php endif; ?>

            <a class="btn btn-outline-info" href="/facturacionelectronica/logs/<?= $cab['id'] ?>">
                <i class="fas fa-list-ul"></i> Ver Transacciones
            </a>
            
            <?php if ($estadoActual === 'autorizado'): ?>
            <a class="btn btn-primary" href="/facturacionelectronica/enviarCliente/<?= $cab['id'] ?>"
               title="Enviar comprobante por email al cliente">
                <i class="fas fa-paper-plane"></i> Enviar al Cliente
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <!-- CABECERA DE COMPROBANTE -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-receipt text-success"></i> <?= $nombresTipo[$cab['tipo_doc']] ?? 'Comprobante' ?>
                    </h5>
                    <span class="fs-5 fw-bold">
                        <?= substr($cab['serie'], 0, 3) . '-' . substr($cab['serie'], 3, 3) ?>-<?= str_pad($cab['correlativo'], 9, '0', STR_PAD_LEFT) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-2 g-2">
                        <div class="col-md-6">
                            <strong>Emisor:</strong> <?= htmlspecialchars($cab['emisor_razon']) ?><br>
                            <small class="text-muted">RUC: <?= $cab['emisor_ruc'] ?></small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <strong>Fecha de Emisión:</strong> <?= date('d/m/Y', strtotime($cab['fecha_emision'])) ?><br>
                            <strong>Moneda:</strong> Dólar Estadounidense ($)
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row mb-2 g-2">
                        <div class="col-md-6">
                            <strong>Cliente / Comprador:</strong> <?= htmlspecialchars($cab['cliente_razon']) ?><br>
                            <strong>Identificación:</strong> <?= htmlspecialchars($cab['cliente_num_doc']) ?>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <strong>Forma de Pago:</strong> <?= $cab['forma_pago'] === 'Contado' ? 'Efectivo / Contado' : 'Crédito' ?><br>
                            <strong>Método de Pago:</strong> <?= ucfirst($cab['metodo_pago']) ?>
                        </div>
                    </div>
                    <?php if (!empty($cab['cliente_direccion'])): ?>
                        <div class="row"><div class="col-12"><strong>Dirección:</strong> <?= htmlspecialchars($cab['cliente_direccion']) ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($cab['ref_serie'])): ?>
                        <hr class="my-2">
                        <div class="row bg-light p-2 rounded">
                            <div class="col-md-6"><strong>Modifica Comprobante:</strong>
                                <?= substr($cab['ref_serie'], 0, 3) . '-' . substr($cab['ref_serie'], 3, 3) ?>-<?= str_pad($cab['ref_correlativo'], 9, '0', STR_PAD_LEFT) ?>
                            </div>
                            <div class="col-md-6"><strong>Motivo SRI:</strong> <?= htmlspecialchars($cab['motivo_descripcion']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DETALLE DE COMPROBANTE -->
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong class="text-dark"><i class="fas fa-list"></i> Detalle de Ítems</strong></div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Descripción del Ítem</th>
                                <th class="text-center">Unidad</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">V. Unit</th>
                                <th class="text-end">IVA</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($det as $d): ?>
                            <tr>
                                <td class="text-center"><?= $d['linea'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($d['descripcion']) ?></strong>
                                    <?php if(!empty($d['codigo'])): ?>
                                        <br><small class="text-muted">Código: <?= htmlspecialchars($d['codigo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-secondary"><?= $d['unidad'] ?></span></td>
                                <td class="text-end"><?= number_format($d['cantidad'], 2) ?></td>
                                <td class="text-end">$ <?= number_format($d['valor_unitario'], 4) ?></td>
                                <td class="text-end">$ <?= number_format($d['igv_linea'], 2) ?></td>
                                <td class="text-end"><strong>$ <?= number_format($d['total_linea'], 2) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="6" class="text-end">Subtotal Sin Impuestos (Neto)</th>
                                <th class="text-end">$ <?= number_format($cab['gravadas'], 2) ?></th>
                            </tr>
                            <tr>
                                <th colspan="6" class="text-end">IVA (Ecuador)</th>
                                <th class="text-end">$ <?= number_format($cab['igv'], 2) ?></th>
                            </tr>
                            <?php if ($cab['descuentos'] > 0): ?>
                            <tr>
                                <th colspan="6" class="text-end">Descuento</th>
                                <th class="text-end">-$ <?= number_format($cab['descuentos'], 2) ?></th>
                            </tr>
                            <?php endif; ?>
                            <tr class="table-success">
                                <th colspan="6" class="text-end fs-5">TOTAL COMPROBANTE</th>
                                <th class="text-end fs-5"><strong>$ <?= number_format($cab['total'], 2) ?></strong></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer bg-white border-top">
                    <small class="text-muted"><strong>Importe en letras:</strong> <?= htmlspecialchars($cab['total_letras']) ?></small>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- ESTADO SRI -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-<?= $col ?> text-white fw-bold">
                    <i class="fas fa-server"></i> Estado SRI (Servicio de Rentas Internas)
                </div>
                <div class="card-body text-center">
                    <span class="fs-3 fw-bold text-<?= $col ?> d-block mb-3">
                        <?= strtoupper($estadoActual) ?>
                    </span>
                    
                    <?php if (!empty($cab['clave_acceso'])): ?>
                        <div class="text-start mb-3">
                            <strong class="small text-muted d-block mb-1">Clave de Acceso (49 Dígitos):</strong>
                            <div class="clave-acceso text-break select-all"><?= htmlspecialchars($cab['clave_acceso']) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($estadoActual === 'autorizado'): ?>
                        <div class="alert alert-success py-2 small mb-0">
                            <i class="fas fa-check-circle"></i> Comprobante Autorizado Oficialmente por el SRI.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cab['mensaje_error'])): ?>
                        <div class="alert alert-warning small mb-0 text-start">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Mensaje SRI:</strong><br>
                            <?= htmlspecialchars($cab['mensaje_error']) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($cab['xml_hash'])): ?>
                        <hr class="my-3">
                        <div class="text-start small">
                            <strong class="text-muted">Huella Digital XML (SHA-1):</strong><br>
                            <code class="text-break text-secondary"><?= htmlspecialchars($cab['xml_hash']) ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- GUÍA DE DOCUMENTOS -->
            <div class="card shadow-sm">
                <div class="card-body small text-muted">
                    <h6><i class="fas fa-info-circle text-info"></i> Información del Documento</h6>
                    <p class="mb-1">Este comprobante electrónico ha sido generado de acuerdo con el esquema off-line exigido por el SRI de Ecuador.</p>
                    <p class="mb-1"><strong>RIDE:</strong> La representación impresa del documento electrónico (PDF RIDE) sirve como respaldo físico legal de la transacción.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nota de Crédito SRI -->
<div class="modal fade" id="modalNC" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="/facturacionelectronica/notaCredito/<?= $cab['id'] ?>">
            <div class="modal-header bg-warning">
                <h5 class="modal-title fw-bold"><i class="fas fa-undo"></i> Generar Nota de Crédito SRI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Se generará una Nota de Crédito electrónica del SRI para anular o realizar una devolución total sobre esta Factura.</p>
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo de Anulación SRI</label>
                    <select name="motivo_codigo" class="form-select">
                        <option value="01">Anulación de la operación</option>
                        <option value="02">Devolución total de los servicios/productos</option>
                        <option value="03">Descuento aplicado posterior a la emisión</option>
                        <option value="04">Corrección de datos del comprador</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Descripción Adicional del Motivo</label>
                    <textarea name="motivo_descripcion" class="form-control" rows="2" required>Anulación de la operación</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-warning fw-bold"><i class="fas fa-paper-plane"></i> Autorizar Nota de Crédito en SRI</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>
