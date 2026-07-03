<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Log SRI — Comprobante #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        pre.xml {
            background:#0F172A; color:#E2E8F0; padding:1rem; border-radius:8px;
            max-height:300px; overflow:auto; font-size:.78rem; white-space:pre-wrap; word-break:break-all;
        }
    </style>
</head>
<body class="bg-light">
<?php require_once '../app/views/inc/navbar.php'; ?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h4><i class="fas fa-list-ul"></i> Log técnico SRI — Comprobante #<?= $id ?></h4>
        <a href="/facturacionelectronica/ver/<?= $id ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Volver al comprobante
        </a>
    </div>

    <?php if (empty($rows)): ?>
        <div class="alert alert-info">Sin registros de envío para este comprobante.</div>
    <?php else: foreach ($rows as $r): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <span class="badge bg-primary"><?= htmlspecialchars($r['accion']) ?></span>
                    <strong class="ms-2"><?= htmlspecialchars($r['codigo'] ?? '-') ?></strong>
                    <small class="text-muted ms-2"><?= htmlspecialchars($r['mensaje'] ?? '') ?></small>
                </div>
                <small class="text-muted"><?= date('d/m/Y H:i:s', strtotime($r['creado_en'])) ?></small>
            </div>
            <div class="card-body p-2">
                <div class="row g-2">
                    <div class="col-md-6">
                        <strong class="small">Request</strong>
                        <pre class="xml"><?= htmlspecialchars(mb_substr($r['request_xml'] ?? '', 0, 4000)) ?></pre>
                    </div>
                    <div class="col-md-6">
                        <strong class="small">Response</strong>
                        <pre class="xml"><?= htmlspecialchars(mb_substr($r['response_xml'] ?? '', 0, 4000)) ?></pre>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>
