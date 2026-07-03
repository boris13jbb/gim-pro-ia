<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobantes Electrónicos - SRI Ecuador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-light">

<?php require_once '../app/views/inc/navbar.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold text-dark mb-0">
            <i class="fas fa-file-invoice text-success"></i> Comprobantes Electrónicos (SRI)
        </h3>
        <div>
            <span class="badge bg-<?= ($config['sri_ambiente']??'1')==='2'?'success':'warning' ?> p-2">
                <i class="fas fa-circle"></i> Ambiente:
                <?= ($config['sri_ambiente'] ?? '1') === '2' ? 'PRODUCCIÓN' : 'PRUEBAS' ?>
            </span>
            <a href="/configuracion/sri" class="btn btn-outline-success btn-sm ms-2">
                <i class="fas fa-cog"></i> Configuración SRI
            </a>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= ($_SESSION['flash_ok'] ?? false) ? 'success' : 'warning' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_ok']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small">Desde</label>
                    <input type="date" name="desde" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filtros['desde']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Hasta</label>
                    <input type="date" name="hasta" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filtros['hasta']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Tipo Comprobante</label>
                    <select name="tipo_doc" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="01" <?= $filtros['tipo_doc']==='01'?'selected':'' ?>>Factura (01)</option>
                        <option value="04" <?= $filtros['tipo_doc']==='04'?'selected':'' ?>>Nota de Crédito (04)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Estado SRI</label>
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="autorizado"    <?= $filtros['estado']==='autorizado'?'selected':'' ?>>Autorizado</option>
                        <option value="pendiente"     <?= $filtros['estado']==='pendiente'?'selected':'' ?>>Pendiente</option>
                        <option value="recibida"      <?= $filtros['estado']==='recibida'?'selected':'' ?>>Recibida (En Proceso)</option>
                        <option value="no_autorizado" <?= $filtros['estado']==='no_autorizado'?'selected':'' ?>>No Autorizado</option>
                        <option value="devuelta"      <?= $filtros['estado']==='devuelta'?'selected':'' ?>>Devuelta (Error)</option>
                        <option value="anulado"       <?= $filtros['estado']==='anulado'?'selected':'' ?>>Anulado</option>
                        <option value="error"         <?= $filtros['estado']==='error'?'selected':'' ?>>Error de Firma/Red</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-success btn-sm w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha Emisión</th>
                        <th>Tipo</th>
                        <th>Secuencial SRI</th>
                        <th>Cliente</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Estado SRI</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $nombresTipo = ['01'=>'Factura','04'=>'Nota de Crédito'];
                $colores = [
                    'autorizado'    => ['success','check-circle'],
                    'pendiente'     => ['secondary','clock'],
                    'no_autorizado' => ['danger','times-circle'],
                    'devuelta'      => ['warning','exclamation-triangle'],
                    'recibida'      => ['info','paper-plane'],
                    'anulado'       => ['dark','ban'],
                    'error'         => ['danger','bug'],
                ];
                if (empty($comprobantes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron comprobantes en el rango.</td></tr>
                <?php else: foreach ($comprobantes as $c):
                    $estadoActual = $c['estado_sri'] ?: 'pendiente';
                    $col = $colores[$estadoActual] ?? ['secondary','question'];
                ?>
                    <tr>
                        <td><small><?= date('d/m/Y', strtotime($c['fecha_emision'])) ?></small></td>
                        <td><span class="badge bg-light text-dark"><?= $nombresTipo[$c['tipo_doc']] ?? $c['tipo_doc'] ?></span></td>
                        <td>
                            <strong><?= substr($c['serie'], 0, 3) . '-' . substr($c['serie'], 3, 3) ?>-<?= str_pad($c['correlativo'], 9, '0', STR_PAD_LEFT) ?></strong>
                        </td>
                        <td>
                            <div class="fw-bold small"><?= htmlspecialchars($c['cliente_razon']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($c['cliente_num_doc']) ?></small>
                        </td>
                        <td class="text-end"><strong>$ <?= number_format($c['total'], 2) ?></strong></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $col[0] ?>">
                                <i class="fas fa-<?= $col[1] ?>"></i>
                                <?= strtoupper($estadoActual) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a class="btn btn-sm btn-outline-primary" href="/facturacionelectronica/ver/<?= $c['id'] ?>" title="Ver Detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-danger" href="/facturacionelectronica/pdf/<?= $c['id'] ?>" target="_blank" title="Imprimir PDF RIDE">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary" href="/facturacionelectronica/descargarXml/<?= $c['id'] ?>" title="Descargar XML">
                                <i class="fas fa-file-code"></i>
                            </a>
                            <?php if (in_array($estadoActual, ['pendiente', 'recibida', 'devuelta', 'no_autorizado', 'error'])): ?>
                            <a class="btn btn-sm btn-warning" href="/facturacionelectronica/reintentar/<?= $c['id'] ?>" title="Consultar/Reintentar SRI">
                                <i class="fas fa-redo"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>
