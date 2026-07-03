<?php
// Calcular el progreso de configuración del SRI
$checks = [
    'razon'    => !empty($datos['razon_social']),
    'ruc'      => !empty($datos['ruc']) && strlen(preg_replace('/[^0-9]/', '', $datos['ruc'])) === 13,
    'estab'    => !empty($datos['sri_establecimiento']),
    'pto'      => !empty($datos['sri_punto_emision']),
    'cert'     => !empty($datos['sri_certificado_p12']),
    'iva'      => !empty($datos['iva_tasa']),
];
$completados = array_sum($checks);
$totalCheck  = count($checks);
$progreso    = (int) round(($completados / $totalCheck) * 100);
$esProd      = ($datos['sri_ambiente'] ?? '1') === '2';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración SRI - Facturación Electrónica</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sri-hero {
            background: linear-gradient(135deg, #059669 0%, #10b981 60%, #3b82f6 100%);
            border-radius: 18px;
            padding: 1.6rem 1.8rem;
            color: white;
            box-shadow: 0 10px 35px rgba(16,185,129,0.25);
            position: relative;
            overflow: hidden;
        }
        .sri-hero::before {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.07);
            border-radius: 50%;
        }
        .sri-hero::after {
            content: '';
            position: absolute;
            right: 80px; bottom: -50px;
            width: 140px; height: 140px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .sri-hero h2 { font-weight: 800; letter-spacing: -0.5px; margin: 0; font-size: 1.7rem; position: relative; z-index: 1;}
        .sri-hero p  { opacity: 0.9; margin: 0.3rem 0 0; position: relative; z-index: 1; }
        .badge-ambiente {
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.3);
            color: #fff;
            font-weight: 600;
            padding: 0.55rem 1rem;
            border-radius: 50rem;
        }

        .progress-card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.3rem;
            border: 1px solid #E5E7EB;
        }
        .progress-card .progress {
            height: 8px;
            background: #F3F4F6;
            border-radius: 50rem;
        }
        .progress-card .progress-bar {
            background: linear-gradient(90deg, #10B981, #3B82F6);
            border-radius: 50rem;
        }

        .section-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #E5E7EB;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .section-card-header {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 1rem 1.3rem;
            border-bottom: 1px solid #F3F4F6;
            background: #FAFAFC;
        }
        .section-card-header .icon-box {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
        }
        .section-card-header h6 { font-weight: 700; margin: 0; color: #1F2937; font-size: 1rem; }
        .section-card-header small { color: #9CA3AF; }
        .section-card-body { padding: 1.3rem; }

        .form-floating-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }
        .form-control, .form-select {
            border-radius: 10px !important;
            border: 1px solid #E5E7EB;
            padding: 0.6rem 0.85rem;
            font-size: 0.94rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #10B981;
            box-shadow: 0 0 0 0.2rem rgba(16,185,129,0.15);
        }

        .check-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 50rem;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .check-ok { background: #ECFDF5; color: #047857; }
        .check-ko { background: #FEF2F2; color: #B91C1C; }

        .table-series th {
            background: #F9FAFB;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6B7280;
            border-bottom: 2px solid #E5E7EB;
        }
        .table-series td { vertical-align: middle; }
        .badge-tipo {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.73rem;
            font-weight: 700;
        }
        .badge-tipo-01 { background: #ECFDF5; color: #047857; }
        .badge-tipo-04 { background: #EEF2FF; color: #4338CA; }

        .help-card {
            background: linear-gradient(135deg, #FAFAFC, #F3F4F6);
            border-radius: 14px;
            border: 1px solid #E5E7EB;
            padding: 1rem 1.2rem;
        }
        .help-card .help-item {
            display: flex;
            gap: 10px;
            margin-bottom: 0.75rem;
            font-size: 0.85rem;
            color: #374151;
        }
        .help-card .help-item i {
            color: #10B981;
            margin-top: 3px;
            min-width: 18px;
        }

        .btn-save-sri {
            background: linear-gradient(135deg, #10B981, #059669);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 0.85rem 1.5rem;
            border-radius: 10px;
            transition: transform .15s, box-shadow .15s;
        }
        .btn-save-sri:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(16,185,129,0.35);
            color: #fff;
        }
    </style>
</head>
<body class="bg-light">

<?php require_once '../app/views/inc/navbar.php'; ?>

<div class="container-fluid p-3 p-md-4">

    <!-- ============== HERO ============== -->
    <div class="sri-hero mb-3 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h2><i class="fas fa-receipt me-2"></i> Facturación Electrónica SRI (Ecuador)</h2>
            <p>Configuración del emisor, establecimiento, certificado digital .p12 y series del SRI.</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge-ambiente">
                <i class="fas fa-circle" style="color: <?= $esProd ? '#10B981' : '#FBBF24' ?>;"></i>
                Ambiente: <strong><?= $esProd ? 'PRODUCCIÓN' : 'PRUEBAS' ?></strong>
            </span>
            <a href="/facturacionelectronica/index" class="btn btn-light fw-bold text-success">
                <i class="fas fa-list"></i> Bandeja
            </a>
        </div>
    </div>

    <!-- ============== FLASH ============== -->
    <?php if (!empty($_SESSION['flash_msg'])): ?>
        <div class="alert alert-<?= ($_SESSION['flash_ok'] ?? false) ? 'success' : 'warning' ?> alert-dismissible fade show shadow-sm">
            <i class="fas fa-<?= ($_SESSION['flash_ok'] ?? false) ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_msg'], $_SESSION['flash_ok']); ?>
    <?php endif; ?>

    <!-- ============== PROGRESS + CHECKLIST ============== -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-8">
            <div class="progress-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h6 class="fw-bold mb-1">Progreso de configuración SRI</h6>
                        <small class="text-muted"><?= $completados ?> de <?= $totalCheck ?> elementos configurados</small>
                    </div>
                    <h3 class="fw-bold mb-0 text-success"><?= $progreso ?>%</h3>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar" style="width: <?= $progreso ?>%"></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <span class="check-pill <?= $checks['ruc'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['ruc'] ? 'check' : 'times' ?>"></i> RUC Ecuador (13 Dig)
                    </span>
                    <span class="check-pill <?= $checks['razon'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['razon'] ? 'check' : 'times' ?>"></i> Razón social
                    </span>
                    <span class="check-pill <?= $checks['estab'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['estab'] ? 'check' : 'times' ?>"></i> Establecimiento
                    </span>
                    <span class="check-pill <?= $checks['pto'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['pto'] ? 'check' : 'times' ?>"></i> Punto Emisión
                    </span>
                    <span class="check-pill <?= $checks['cert'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['cert'] ? 'check' : 'times' ?>"></i> Certificado .p12
                    </span>
                    <span class="check-pill <?= $checks['iva'] ? 'check-ok' : 'check-ko' ?>">
                        <i class="fas fa-<?= $checks['iva'] ? 'check' : 'times' ?>"></i> IVA (15%)
                    </span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="section-card h-100">
                <div class="section-card-header">
                    <div class="icon-box" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fas fa-id-card"></i></div>
                    <div>
                        <h6>RUC Emisor Ecuador</h6>
                        <small><?= htmlspecialchars($datos['ruc'] ?? '—') ?></small>
                    </div>
                </div>
                <div class="section-card-body py-3">
                    <p class="mb-1 fw-bold"><?= htmlspecialchars($datos['razon_social'] ?? $datos['nombre_sistema'] ?? '—') ?></p>
                    <p class="small text-muted mb-0"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($datos['direccion'] ?? '—') ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- ============== COLUMNA IZQUIERDA: form principal ============== -->
        <div class="col-lg-8">

            <form action="/configuracion/actualizarSri" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="cert_actual" value="<?= htmlspecialchars($datos['sri_certificado_p12'] ?? '') ?>">

                <!-- DATOS FISCALES -->
                <div class="section-card mb-3">
                    <div class="section-card-header">
                        <div class="icon-box" style="background: linear-gradient(135deg, #3B82F6, #1E40AF);"><i class="fas fa-building"></i></div>
                        <div>
                            <h6>Datos fiscales del emisor (SRI)</h6>
                            <small>Razón social, nombre comercial e identificación</small>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-floating-label">RUC (13 Dígitos) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="ruc" value="<?= htmlspecialchars($datos['ruc'] ?? '') ?>" disabled>
                                <small class="text-muted">Se cambia en Config. general</small>
                            </div>
                            <div class="col-md-8">
                                <label class="form-floating-label">Razón social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="razon_social" required
                                       value="<?= htmlspecialchars($datos['razon_social'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-floating-label">Nombre comercial</label>
                                <input type="text" class="form-control" name="nombre_comercial"
                                       value="<?= htmlspecialchars($datos['nombre_comercial'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-floating-label">Establecimiento <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="sri_establecimiento" maxlength="3" required
                                       value="<?= htmlspecialchars($datos['sri_establecimiento'] ?? '001') ?>" placeholder="Ej: 001">
                            </div>
                            <div class="col-md-3">
                                <label class="form-floating-label">Punto de Emisión <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="sri_punto_emision" maxlength="3" required
                                       value="<?= htmlspecialchars($datos['sri_punto_emision'] ?? '001') ?>" placeholder="Ej: 001">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CREDENCIALES -->
                <div class="section-card mb-3">
                    <div class="section-card-header">
                        <div class="icon-box" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="fas fa-key"></i></div>
                        <div>
                            <h6>Credenciales y Ambiente SRI</h6>
                            <small>Servicio offline de pruebas o producción</small>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-floating-label">Ambiente de Trabajo SRI <span class="text-danger">*</span></label>
                                <select class="form-select" name="sri_ambiente">
                                    <option value="1" <?= ($datos['sri_ambiente'] ?? '1') === '1' ? 'selected' : '' ?>>
                                        🟡 1 - Ambiente de Pruebas / Homologación
                                    </option>
                                    <option value="2" <?= ($datos['sri_ambiente'] ?? '1') === '2' ? 'selected' : '' ?>>
                                        🟢 2 - Ambiente de Producción
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CERTIFICADO -->
                <div class="section-card mb-3">
                    <div class="section-card-header">
                        <div class="icon-box" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="fas fa-shield-alt"></i></div>
                        <div>
                            <h6>Firma Electrónica (.p12)</h6>
                            <small>Firma digital en formato PKCS#12 obligatoria para firmar comprobantes</small>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-floating-label">Archivo de Firma (.p12 / .pfx)</label>
                                <input type="file" class="form-control" name="certificado" accept=".p12,.pfx">
                                <?php if (!empty($datos['sri_certificado_p12'])): ?>
                                    <div class="mt-2 p-2 rounded" style="background: #F0FDF4; border: 1px dashed #10B981;">
                                        <small class="text-success fw-bold">
                                            <i class="fas fa-check-circle"></i>
                                            Firma digital activa: <?= htmlspecialchars($datos['sri_certificado_p12']) ?>
                                        </small>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2 p-2 rounded" style="background: #FFFBEB; border: 1px dashed #F59E0B;">
                                        <small class="text-warning fw-bold">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Sin firma cargada. (En ambiente de pruebas se simulará de forma local).
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-floating-label">Contraseña del certificado</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="cert_clave" id="certclave"
                                           value="<?= htmlspecialchars($datos['sri_certificado_clave'] ?? '') ?>">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass('certclave', this)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <small class="text-muted">La contraseña con la que se protegió la firma digital.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TRIBUTOS -->
                <div class="section-card mb-3">
                    <div class="section-card-header">
                        <div class="icon-box" style="background: linear-gradient(135deg, #EC4899, #BE185D);"><i class="fas fa-percent"></i></div>
                        <div>
                            <h6>Impuestos de Ecuador (IVA)</h6>
                            <small>Tarifa de IVA del gimnasio</small>
                        </div>
                    </div>
                    <div class="section-card-body">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-floating-label">Tarifa de IVA (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="iva_tasa"
                                           value="<?= htmlspecialchars($datos['iva_tasa'] ?? '15.00') ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Ecuador IVA estándar actual: 15%</small>
                            </div>
                            <div class="col-md-8">
                                <div class="form-check form-switch d-flex align-items-center gap-2 p-3 rounded" style="background: #FAFAFC; border: 1px solid #E5E7EB;">
                                    <input class="form-check-input ms-0" type="checkbox" role="switch"
                                           name="incluye_iva" id="incluyeIva"
                                           style="width: 3rem; height: 1.5rem;"
                                           <?= !empty($datos['incluye_iva']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="incluyeIva">
                                        <strong>Los precios mostrados YA incluyen IVA</strong>
                                        <div class="small text-muted mt-1">Si está activo, los precios de planes y productos se "desinflarán" para calcular la base imponible y el IVA. De lo contrario, se sumará el IVA al precio total.</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTÓN GUARDAR -->
                <div class="d-grid d-md-flex justify-content-md-end mb-4">
                    <button class="btn-save-sri w-100 w-md-auto">
                        <i class="fas fa-save me-1"></i> Guardar Configuración SRI
                    </button>
                </div>
            </form>
        </div>

        <!-- ============== COLUMNA DERECHA: series + ayuda ============== -->
        <div class="col-lg-4">

            <!-- SERIES Y SECUENCIALES -->
            <div class="section-card mb-3">
                <div class="section-card-header">
                    <div class="icon-box" style="background: linear-gradient(135deg, #6366F1, #4338CA);"><i class="fas fa-hashtag"></i></div>
                    <div>
                        <h6>Series y Secuenciales SRI</h6>
                        <small>Secuenciales activos del punto de emisión</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-series mb-0">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Est.-Pto</th>
                                <th class="text-end">Siguiente</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                          $nombresTipo = ['01' => 'Factura', '04' => 'N. Crédito'];
                          foreach ($series as $s):
                            $tipoClass = 'badge-tipo-' . $s['tipo_doc'];
                        ?>
                            <tr>
                                <td>
                                    <span class="badge-tipo <?= $tipoClass ?>">
                                        <?= $nombresTipo[$s['tipo_doc']] ?? $s['tipo_doc'] ?>
                                    </span>
                                </td>
                                <td><strong class="text-dark"><?= substr($s['serie'], 0, 3) . '-' . substr($s['serie'], 3, 3) ?></strong></td>
                                <td class="text-end">
                                    <code class="text-muted"><?= str_pad($s['correlativo'] + 1, 9, '0', STR_PAD_LEFT) ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="section-card-body" style="background: #FAFAFC;">
                    <form action="/configuracion/guardarSerie" method="POST">
                        <p class="small text-muted mb-2"><i class="fas fa-plus-circle text-primary"></i> Agregar nueva serie de emisión</p>
                        <div class="row g-2">
                            <div class="col-5">
                                <select class="form-select form-select-sm" name="tipo_doc">
                                    <option value="01">01 Factura</option>
                                    <option value="04">04 N. Crédito</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <input type="text" class="form-control form-control-sm" name="serie" placeholder="001001" maxlength="6" required>
                            </div>
                            <div class="col-3">
                                <button class="btn btn-success btn-sm w-100" title="Agregar">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- AYUDA -->
            <div class="help-card">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-success"></i> Normativa SRI Ecuador</h6>

                <div class="help-item">
                    <i class="fas fa-key"></i>
                    <div>
                        <strong>Clave de Acceso (49 Dígitos)</strong><br>
                        Código numérico único que genera la aplicación para validar el estado de tus facturas.
                    </div>
                </div>

                <div class="help-item">
                    <i class="fas fa-server"></i>
                    <div>
                        <strong>Esquema Off-line</strong><br>
                        Se envía directamente en formato XML firmado y el SRI lo procesa en segundos de forma asíncrona.
                    </div>
                </div>

                <div class="help-item">
                    <i class="fas fa-lock"></i>
                    <div>
                        <strong>Firma en Formato .p12</strong><br>
                        Debes conseguir un certificado de firma digital provisto por entidades autorizadas en Ecuador (ej: Banco Central, Security Data, ANF, Uanataca, Consejo de la Judicatura).
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye');
        }
    }
</script>

<?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>
