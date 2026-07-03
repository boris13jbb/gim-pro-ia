<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificaciones WhatsApp - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/css/gym-theme.css?v=<?= time() ?>">
    <style>
        .whatsapp-badge { background: #25D366; color: white; padding: 4px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
        .no-config-badge { background: #F3F4F6; color: #9CA3AF; padding: 4px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; border: 1px dashed #D1D5DB; }
        .days-badge-danger { background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; padding: 4px 12px; }
        .days-badge-warning { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; padding: 4px 12px; }
        .days-badge-ok { background: #D1FAE5; color: #059669; border: 1px solid #A7F3D0; padding: 4px 12px; }
        .wa-send-btn { background: #25D366; border: none; color: white; border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 0.85rem; transition: all 0.2s; }
        .wa-send-btn:hover { background: #1ebe5d; transform: scale(1.04); }
        .wa-send-btn:disabled { background: #94A3B8; cursor: not-allowed; }
        .explanation-card { background: linear-gradient(135deg, #ECFDF5, #D1FAE5); border-left: 4px solid #25D366; }
    </style>
</head>
<body>
    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container-fluid fade-in mt-4">

        <!-- Header -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark m-0">
                    <i class="fab fa-whatsapp me-2" style="color: #25D366;"></i> Notificaciones WhatsApp
                </h3>
                <p class="text-muted small mt-1 mb-0">Alerta automática a socios con membresías próximas a vencer.</p>
            </div>
            <!-- Filtro de días + Enviar Todos -->
            <form action="/notificaciones/enviarTodos" method="POST" class="d-flex gap-2 align-items-center">
                <select name="dias" class="form-select form-select-sm border-0 shadow-sm" style="width:auto;">
                    <option value="3" <?= ($dias == 3) ? 'selected' : '' ?>>Vencen en 3 días</option>
                    <option value="7" <?= ($dias == 7) ? 'selected' : '' ?>>Vencen en 7 días</option>
                    <option value="14" <?= ($dias == 14) ? 'selected' : '' ?>>Vencen en 14 días</option>
                    <option value="30" <?= ($dias == 30) ? 'selected' : '' ?>>Vencen en 30 días</option>
                </select>
                <button type="submit" class="wa-send-btn px-4 shadow-sm btn-confirm" 
                        data-title="¿Enviar WhatsApp a todos los socios configurados que vencen próximamente?">
                    <i class="fab fa-whatsapp me-2"></i> Enviar a Todos
                </button>
            </form>
        </div>

        <!-- Alerts -->
        <?php if(isset($_SESSION['notif_mensaje'])): ?>
            <div class="alert alert-<?= $_SESSION['notif_mensaje']['tipo'] ?> border-0 shadow-sm alert-dismissible fade show">
                <?= $_SESSION['notif_mensaje']['texto'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['notif_mensaje']); ?>
        <?php endif; ?>

        <?php if(isset($_GET['saved'])): ?>
            <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> API Key guardada correctamente.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- How It Works (collapsible guide) -->
        <div class="explanation-card rounded-3 p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h6 class="fw-bold mb-2" style="color:#059669;"><i class="fab fa-whatsapp me-2"></i> ¿Cómo activar WhatsApp por socio?</h6>
                    <ol class="mb-0 text-secondary small">
                        <li>El socio abre WhatsApp y envía el mensaje: <code class="text-dark fw-bold">I allow callmebot to send me messages</code></li>
                        <li>Al número: <strong>+34 644 68 38 85</strong></li>
                        <li>Recibirá un mensaje con su <strong>API Key personal</strong>.</li>
                        <li>El admin guarda esa API Key en el perfil del socio usando el botón <i class="fas fa-key"></i>.</li>
                        <li>¡Listo! Ya puede recibir alertas automáticas.</li>
                    </ol>
                </div>
                <a href="https://wa.me/34644683885" target="_blank" class="btn btn-sm btn-success ms-3 text-nowrap">
                    <i class="fab fa-whatsapp me-1"></i> Abrir Chat
                </a>
            </div>
        </div>

        <!-- Members Table -->
        <div class="module-container p-0">
            <div class="border-bottom px-4 py-3 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark">
                    <i class="fas fa-users me-2 text-primary"></i> 
                    Socios con vencimiento próximo 
                    <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= count($proximos) ?></span>
                </span>
                <!-- Quick filter días -->
                <div class="d-flex gap-2">
                    <?php foreach ([3, 7, 14, 30] as $d): ?>
                        <a href="/notificaciones/index?dias=<?= $d ?>" class="btn btn-sm fw-bold px-3 <?= ($dias == $d) ? 'btn-primary rounded-pill shadow-sm' : 'bg-light text-secondary border-0 rounded-pill' ?>"><?= $d ?>d</a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="p-4">
                <?php if (empty($proximos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success opacity-50 mb-3"></i>
                        <h5 class="text-muted fw-bold">Sin vencimientos en los próximos <?= $dias ?> días</h5>
                        <p class="text-muted small">¡Todo al día! Intenta ampliar el rango de días.</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle table-data border-0 w-100">
                        <thead>
                            <tr>
                                <th class="border-0 bg-transparent text-muted">Socio</th>
                                <th class="border-0 bg-transparent text-muted">Plan</th>
                                <th class="border-0 bg-transparent text-muted">Vence</th>
                                <th class="border-0 bg-transparent text-muted">Días Rest.</th>
                                <th class="border-0 bg-transparent text-muted">Teléfono</th>
                                <th class="border-0 bg-transparent text-muted">WhatsApp</th>
                                <th class="border-0 bg-transparent text-muted text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($proximos as $s): ?>
                            <?php
                                $dr = (int)$s['dias_restantes'];
                                $dayClass = 'days-badge-ok';
                                if ($dr <= 3) $dayClass = 'days-badge-danger';
                                elseif ($dr <= 7) $dayClass = 'days-badge-warning';
                                $tieneConfig = !empty($s['telefono']) && !empty($s['whatsapp_api_key']);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($s['nombre']) ?></div>
                                </td>
                                <td>
                                    <span class="badge rounded-pill fw-bold badge-soft-primary px-3 py-2">
                                        <?= htmlspecialchars($s['nombre_plan']) ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= date('d/m/Y', strtotime($s['fecha_fin'])) ?></td>
                                <td>
                                    <span class="badge rounded-pill px-3 py-2 fw-bold <?= $dayClass ?>">
                                        <?= $dr ?> d
                                    </span>
                                </td>
                                <td class="text-muted small"><?= $s['telefono'] ?: '<span class="text-danger">Sin teléfono</span>' ?></td>
                                <td>
                                    <?php if($tieneConfig): ?>
                                        <span class="whatsapp-badge"><i class="fab fa-whatsapp me-1"></i> Listo</span>
                                    <?php else: ?>
                                        <span class="no-config-badge"><i class="fas fa-exclamation me-1"></i> Sin configurar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <!-- Configurar API Key -->
                                    <button class="btn btn-sm btn-outline-secondary me-1 apikey-btn"
                                            data-id="<?= $s['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($s['nombre']) ?>"
                                            data-apikey="<?= htmlspecialchars($s['whatsapp_api_key'] ?? '') ?>"
                                            data-bs-toggle="modal" data-bs-target="#modalApiKey" title="Configurar API Key">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <!-- Copiar mensaje -->
                                    <button class="btn btn-sm btn-outline-info me-1 copy-msg-btn"
                                            data-nombre="<?= htmlspecialchars($s['nombre']) ?>"
                                            data-plan="<?= htmlspecialchars($s['nombre_plan']) ?>"
                                            data-fecha="<?= $s['fecha_fin'] ?>" title="Copiar mensaje">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <!-- Enviar WhatsApp -->
                                    <a href="/notificaciones/enviarAlerta/<?= $s['id'] ?>"
                                       class="wa-send-btn btn-confirm <?= !$tieneConfig ? 'disabled' : '' ?>"
                                       data-title="¿Enviar alerta de vencimiento a <?= htmlspecialchars($s['nombre']) ?>"
                                       title="Enviar WhatsApp">
                                        <i class="fab fa-whatsapp"></i> Enviar
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Configurar API Key -->
    <div class="modal fade" id="modalApiKey" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title fw-bold">
                        <i class="fab fa-whatsapp me-2" style="color:#25D366;"></i> API Key WhatsApp
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="/notificaciones/guardarApiKey" method="POST">
                    <div class="modal-body p-4">
                        <input type="hidden" name="socio_id" id="modal_socio_id">
                        <p class="text-muted small mb-3">
                            Socio: <strong id="modal_nombre" class="text-dark"></strong>
                        </p>
                        <div class="alert alert-info border-0 small py-2">
                            <i class="fas fa-info-circle me-2"></i>
                            El socio debe enviar <code>I allow callmebot to send me messages</code> al <strong>+34 644 68 38 85</strong> para obtener su API Key personal.
                        </div>
                        <label class="form-label text-muted fw-bold">API Key del Socio</label>
                        <input type="text" name="whatsapp_api_key" id="modal_apikey" class="form-control form-control-lg bg-light border-0"
                               placeholder="Ej: 1234567">
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn fw-bold px-4" style="background:#25D366; color:white;">
                            <i class="fas fa-save me-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast de copiado -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="copyToast" class="toast bg-dark text-white border-0" role="alert">
            <div class="toast-body">
                <i class="fas fa-check-circle text-success me-2"></i> Mensaje copiado al portapapeles.
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script>
        // Llenar modal de API Key
        document.querySelectorAll('.apikey-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('modal_socio_id').value = this.dataset.id;
                document.getElementById('modal_nombre').textContent   = this.dataset.nombre;
                document.getElementById('modal_apikey').value         = this.dataset.apikey;
            });
        });

        // Copiar mensaje al portapapeles
        document.querySelectorAll('.copy-msg-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const fecha = new Date(this.dataset.fecha).toLocaleDateString('es', {day:'2-digit', month:'2-digit', year:'numeric'});
                const msg = `⚠️ Iron Gym\n\nHola *${this.dataset.nombre}*! 👋\n\nTe recordamos que tu membresía *${this.dataset.plan}* vence el *${fecha}*.\n\nRenuévala a tiempo para no perder tus beneficios. 💪`;
                navigator.clipboard.writeText(msg).then(() => {
                    const toastEl = document.getElementById('copyToast');
                    new bootstrap.Toast(toastEl, {delay: 2500}).show();
                });
            });
        });
    </script>
</body>
</html>
