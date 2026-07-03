<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/index');
    exit;
}
require_once '../app/models/Configuracion.php';
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mantenimiento - <?= htmlspecialchars($config['nombre_sistema'] ?? 'Gym System') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .module-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .icon-circle {
            width: 80px; height: 80px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: white; margin-bottom: 1.5rem;
        }
        .bg-backup { background: linear-gradient(135deg, #3B82F6, #2563EB); }
        .bg-restore { background: linear-gradient(135deg, #10B981, #059669); }
        .bg-clean { background: linear-gradient(135deg, #EF4444, #DC2626); }
        .card-maintain {
            border: 1px solid #E5E7EB; border-radius: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%; display: flex; flex-direction: column;
        }
        .card-maintain:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.08);
        }
        /* Custom File Input */
        .custom-file-upload {
            border: 2px dashed #CBD5E1; border-radius: 12px;
            padding: 1.5rem; text-align: center; cursor: pointer;
            transition: all 0.2s; background-color: #F8FAFC;
        }
        .custom-file-upload:hover { border-color: #10B981; background-color: #ECFDF5; }
        .custom-file-upload input[type="file"] { display: none; }
    </style>
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-cogs text-secondary me-2"></i>Mantenimiento del Sistema</h4>
            <p class="text-muted mb-0">Gestión de datos, respaldos y reinicio seguro del sistema.</p>
        </div>
    </div>

    <!-- Mensajes de sesión -->
    <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px;" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['mensaje'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mensaje']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" style="border-radius: 12px;" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        
        <!-- 1. BACKUP -->
        <div class="col-12 col-md-4">
            <div class="card-maintain p-4 text-center">
                <div class="icon-circle bg-backup mx-auto shadow"><i class="fas fa-download"></i></div>
                <h5 class="fw-bold text-dark">Copia de Seguridad</h5>
                <p class="text-muted small mb-4 flex-grow-1">Genera y descarga un archivo .sql con toda la información actual de la base de datos (clientes, ventas, configuración).</p>
                <a href="/mantenimiento/backup" class="btn btn-primary rounded-pill fw-bold w-100 py-2">
                    <i class="fas fa-cloud-download-alt me-2"></i> Descargar Copia
                </a>
            </div>
        </div>

        <!-- 2. RESTAURAR -->
        <div class="col-12 col-md-4">
            <div class="card-maintain p-4 text-center">
                <div class="icon-circle bg-restore mx-auto shadow"><i class="fas fa-upload"></i></div>
                <h5 class="fw-bold text-dark">Restaurar Copia</h5>
                <p class="text-muted small mb-3 flex-grow-1">Sube un archivo .sql previamente descargado para sobrescribir y restaurar el sistema a ese punto exacto.</p>
                
                <form action="/mantenimiento/restaurar" method="POST" enctype="multipart/form-data" id="form-restaurar">
                    <label class="custom-file-upload w-100 mb-3" for="backup_file">
                        <i class="fas fa-file-sql text-success fs-3 mb-2 d-block"></i>
                        <span id="file-name" class="fw-semibold text-dark small">Seleccionar archivo .sql...</span>
                        <input type="file" name="backup_file" id="backup_file" accept=".sql" required>
                    </label>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold w-100 py-2" id="btn-restaurar" disabled>
                        <i class="fas fa-sync-alt me-2"></i> Restaurar Sistema
                    </button>
                </form>
            </div>
        </div>

        <!-- 3. LIMPIAR (RESET) -->
        <div class="col-12 col-md-4">
            <div class="card-maintain p-4 text-center" style="border-color: #FCA5A5;">
                <div class="icon-circle bg-clean mx-auto shadow"><i class="fas fa-broom"></i></div>
                <h5 class="fw-bold text-danger">Nuevo Proyecto (Reset)</h5>
                <p class="text-muted small mb-4 flex-grow-1">Vacia todos los registros operativos (ventas, socios) para iniciar un gimnasio desde cero. <strong>Conservará configuraciones y tu cuenta de Admin.</strong></p>
                
                <form action="/mantenimiento/limpiar" method="POST" id="form-limpiar">
                    <button type="button" class="btn btn-outline-danger btn-lg rounded-pill fw-bold w-100 py-2" onclick="confirmarLimpieza()">
                        <i class="fas fa-exclamation-triangle me-2"></i> Limpiar Base de Datos
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Extra Warnings/Info -->
    <div class="module-container bg-white">
        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Información Importante</h6>
        <ul class="text-muted small mb-0 ps-3">
            <li class="mb-1">El proceso de <strong>Copia de Seguridad</strong> descargará un archivo que contiene tanto la estructura como los datos. Guárdalo en un lugar seguro.</li>
            <li class="mb-1">El proceso de <strong>Restaurar</strong> sobrescribirá la base de datos actual de forma irreversible. Se recomienda realizar una copia de seguridad justo antes de restaurar por seguridad.</li>
            <li>El proceso de <strong>Nuevo Proyecto</strong> eliminará <u>definitivamente</u> todos los clientes, ventas, pagos, asistencias y gastos del sistema, comenzando de cero. Tu logo, nombre del local, y el usuario Administrador creador se conservarán intactos.</li>
        </ul>
    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File UI Updater
        const fileInput = document.getElementById('backup_file');
        const fileNameSpan = document.getElementById('file-name');
        const btnRestaurar = document.getElementById('btn-restaurar');

        document.getElementById('backup_file').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : "Seleccionar archivo .sql...";
            fileNameSpan.textContent = fileName;
            
            if (e.target.files.length > 0 && fileName.endsWith('.sql')) {
                btnRestaurar.disabled = false;
                fileNameSpan.classList.add('text-success');
            } else {
                btnRestaurar.disabled = true;
                fileNameSpan.classList.remove('text-success');
                if(e.target.files.length > 0) {
                    Swal.fire('Formato Inválido', 'Por favor, selecciona únicamente un archivo .sql', 'error');
                }
            }
        });

        document.getElementById('form-restaurar').addEventListener('submit', function(e) {
            // Mostrar loading spinner en el botón
            btnRestaurar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Restaurando...';
            btnRestaurar.disabled = true;
        });

        // Alerta de confirmación para Limpieza de BD
        function confirmarLimpieza() {
            Swal.fire({
                title: '¿Estás completamente seguro?',
                html: "Se <b>eliminarán permanentemente</b> todos los socios, pagos, historiales y documentos.<br><br>Solo quedarás tú (Admin) y la configuración visual.<br>¡Esta acción NO se puede deshacer!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check"></i> Sí, vaciar sistema',
                cancelButtonText: 'Cancelar',
                focusCancel: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Confirmación final',
                        text: "Escribe 'CONFIRMAR' para proceder",
                        input: 'text',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ejecutar',
                        cancelButtonText: 'X',
                        preConfirm: (inputValue) => {
                            if (inputValue !== 'CONFIRMAR') {
                                Swal.showValidationMessage('El texto no coincide.');
                            }
                        }
                    }).then((finalResult) => {
                        if (finalResult.isConfirmed) {
                            document.getElementById('form-limpiar').submit();
                        }
                    });
                }
            });
        }
    </script>
</body>
</html>
