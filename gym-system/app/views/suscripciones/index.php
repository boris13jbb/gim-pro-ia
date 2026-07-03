<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripciones - <?= $config['nombre_sistema'] ?? 'Gym System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid px-4 mt-5 mb-5">
        <div class="module-container">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <h2 class="h4 mb-0 text-secondary fw-bold">
                    <i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Suscripciones
                </h2>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <button onclick="ExportHelper.toExcel('tblSuscripciones', 'Suscripciones', 'Listado de Suscripciones')" class="btn btn-success btn-sm rounded-pill fw-bold px-3 shadow-sm">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </button>
                    <button onclick="ExportHelper.toPDF('tblSuscripciones', 'Suscripciones', 'Listado de Suscripciones')" class="btn btn-danger btn-sm rounded-pill fw-bold px-3 shadow-sm">
                        <i class="fas fa-file-pdf me-1"></i> PDF
                    </button>
                    <button onclick="ExportHelper.print('tblSuscripciones', 'Listado de Suscripciones')" class="btn btn-secondary btn-sm rounded-pill fw-bold px-3 shadow-sm">
                        <i class="fas fa-print me-1"></i> Imprimir
                    </button>
                    <a href="/suscripciones/crear" class="btn btn-primary btn-sm rounded-pill fw-bold px-3 shadow-sm">
                        <i class="fas fa-plus me-1"></i> Nueva Suscripción
                    </a>
                </div>
            </div>

            <table id="tblSuscripciones" class="table table-hover align-middle table-data border-0">
                <thead>
                    <tr>
                        <th class="border-0 bg-transparent text-muted">ID</th>
                        <th class="border-0 bg-transparent text-muted">Socio</th>
                        <th class="border-0 bg-transparent text-muted">Plan (Precio)</th>
                        <th class="border-0 bg-transparent text-muted">Inicio</th>
                        <th class="border-0 bg-transparent text-muted">Vencimiento</th>
                        <th class="border-0 bg-transparent text-muted">Estado</th>
                        <th class="border-0 bg-transparent text-muted">Acciones</th>
                    </tr>
                </thead>
                    <tbody>
                        <?php if(empty($suscripciones)): ?>
                             <?php else: ?>
                            <?php foreach ($suscripciones as $sub): ?>
                            <?php 
                                $hoy = date('Y-m-d');
                                $vencida = ($hoy > $sub['fecha_fin'] || $sub['estado'] == 'vencida');
                            ?>
                            <tr class="align-middle">
                                <td><span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2 py-1">#<?= $sub['id'] ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-2" style="width: 32px; height: 32px;">
                                            <i class="fas fa-user fs-6"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?= $sub['nombre_socio'] ?></span>
                                    </div>
                                </td>
                                
                                <td>
                                    <span class="text-secondary fw-semibold"><?= $sub['nombre_plan'] ?></span> 
                                    <br>
                                    <small class="text-muted">(<?= $config['moneda'] ?><?= number_format($sub['precio'], 2) ?>)</small>
                                </td>
                                
                                <td class="text-muted"><?= date('d/m/Y', strtotime($sub['fecha_inicio'])) ?></td>
                                <td>
                                    <strong class="<?= $vencida ? 'text-danger' : 'text-dark' ?>">
                                        <?= date('d/m/Y', strtotime($sub['fecha_fin'])) ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if($vencida): ?>
                                        <span class="badge badge-soft-danger px-3 py-2 rounded-pill"><i class="fas fa-exclamation-circle me-1"></i>Vencida</span>
                                    <?php else: ?>
                                        <span class="badge badge-soft-success px-3 py-2 rounded-pill"><i class="fas fa-check-circle me-1"></i>Activa</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-start">
                                        <a href="/comprobante/generar/<?= $sub['id'] ?>"
                                           target="_blank"
                                           class="btn btn-outline-secondary btn-sm rounded-circle d-flex justify-content-center align-items-center"
                                           style="width: 32px; height: 32px;" title="Comprobante interno (PDF)">
                                            <i class="fas fa-print"></i>
                                        </a>

                                        <?php if (empty($sub['comprobante_id'])): ?>
                                            <a href="/facturacionelectronica/emitirSuscripcion/<?= $sub['id'] ?>?tipo=03"
                                               class="btn btn-outline-primary btn-sm rounded-circle d-flex justify-content-center align-items-center"
                                               style="width: 32px; height: 32px;"
                                               title="Emitir Boleta Electrónica">
                                                <i class="fas fa-receipt"></i>
                                            </a>
                                            <a href="/facturacionelectronica/emitirSuscripcion/<?= $sub['id'] ?>?tipo=01"
                                               class="btn btn-outline-success btn-sm rounded-circle d-flex justify-content-center align-items-center"
                                               style="width: 32px; height: 32px;"
                                               title="Emitir Factura Electrónica">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/facturacionelectronica/ver/<?= $sub['comprobante_id'] ?>"
                                               class="btn btn-success btn-sm rounded-circle d-flex justify-content-center align-items-center"
                                               style="width: 32px; height: 32px;"
                                               title="Ver Comprobante Electrónico">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if(!$vencida): ?>
                                        <a href="/suscripciones/cancelar/<?= $sub['id'] ?>"
                                           class="btn btn-outline-danger btn-sm rounded-circle btn-confirm d-flex justify-content-center align-items-center"
                                           style="width: 32px; height: 32px;"
                                           data-title="¿Anular suscripción de <?= $sub['nombre_socio'] ?>?"
                                           title="Anular Suscripción">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
        </div>
    </div>
    
    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>