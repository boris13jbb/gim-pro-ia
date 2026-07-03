<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Acceso - Iron Gym</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .big-input { font-size: 2rem; text-align: center; letter-spacing: 5px; }
        /* Estilo para la foto gigante */
        .foto-validacion { 
            width: 220px; height: 220px; object-fit: cover; 
            border: 6px solid #fff; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.2); 
        }
        .card-verificacion { transform: scale(1.02); transition: all 0.3s; }
    </style>
</head>
<body class="bg-light">

    <?php require_once '../app/views/inc/navbar.php'; ?>

    <div class="container mt-4">
        
        <?php if(!empty($mensaje_exito)): ?>
            <div class="alert alert-success text-center shadow fs-4 mb-4 border-0">
                <?= $mensaje_exito ?>
                <script>
                    setTimeout(function() { window.location.href = '/asistencia/index'; }, 1500);
                </script>
            </div>
        <?php endif; ?>

        <?php if(!empty($error_busqueda)): ?>
            <div class="alert alert-danger text-center shadow fw-bold mb-4">
                <?= $error_busqueda ?>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                
                <?php if(isset($perfil_validacion)): ?>
                    
                    <div class="module-container mb-4 text-center">
                        <div class="mb-3">
                            <h5 class="fw-bold text-warning"><i class="fas fa-eye me-2"></i> VERIFICACIÓN VISUAL REQUERIDA</h5>
                        </div>
                        
                        <div class="p-2">
                            
                            <?php if(!empty($perfil_validacion['foto'])): ?>
                                <img src="/img/socios/<?= $perfil_validacion['foto'] ?>" class="rounded-circle foto-validacion mb-3">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center mx-auto foto-validacion mb-3">
                                    <i class="fas fa-user fa-6x"></i>
                                </div>
                            <?php endif; ?>

                            <h2 class="fw-bold display-6"><?= $perfil_validacion['nombre'] ?></h2>
                            
                            <?php if($perfil_validacion['estado_socio'] != 'activo'): ?>
                                <div class="alert alert-danger mt-3 fw-bold">
                                    ⛔ ACCESO DENEGADO: SOCIO INACTIVO
                                </div>
                            
                            <?php elseif(!$perfil_validacion['tiene_acceso']): ?>
                                <div class="alert alert-danger mt-3 fw-bold">
                                    ⚠️ MEMBRESÍA VENCIDA O NO EXISTENTE
                                </div>
                            
                            <?php else: ?>
                                <div class="alert alert-success mt-3">
                                    <i class="fas fa-check-circle"></i> PLAN ACTIVO 
                                    <br>
                                    <small>Vence: <?= $perfil_validacion['fecha_vence'] ?> (Quedan <?= $perfil_validacion['dias_restantes'] ?> días)</small>
                                </div>
                            <?php endif; ?>

                            <hr>
                            <h5 class="text-muted mb-3">¿Es la persona de la foto?</h5>

                            <div class="row gx-2">
                                <div class="col-6">
                                    <a href="/asistencia/index" class="btn btn-secondary btn-lg w-100 py-3">
                                        <i class="fas fa-times"></i> RECHAZAR
                                    </a>
                                </div>
                                <div class="col-6">
                                    <?php if($perfil_validacion['tiene_acceso'] && $perfil_validacion['estado_socio'] == 'activo'): ?>
                                        <form action="/asistencia/registrar" method="POST">
                                            <input type="hidden" name="socio_id" value="<?= $perfil_validacion['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow">
                                                <i class="fas fa-check"></i> CONFIRMAR
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-lg w-100 py-3 disabled" disabled>
                                            NO HABILITADO
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                <?php else: ?>

                    <div class="module-container mb-4 text-center pb-5 pt-5">
                        <div class="mb-4">
                                <i class="fas fa-wifi fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Esperando lectura de DNI...</h5>
                            </div>

                            <form action="/asistencia/validar" method="POST">
                                <input type="text" name="dni" class="form-control big-input rounded-3 mb-3 border-light shadow-sm" style="background:#F9FAFB;"
                                       placeholder="DNI..." autocomplete="off" autofocus required>
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill py-3 fw-bold shadow-sm">BUSCAR SOCIO</button>
                                </div>
                            </form>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>

        <div class="module-container mt-3">
            <div class="mb-4">
                <h5 class="text-secondary fw-bold"><i class="fas fa-history me-2"></i> Ingresos Confirmados Hoy</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle table-data border-0">
                    <thead>
                        <tr>
                            <th class="border-0 bg-transparent text-muted">Hora</th>
                            <th class="border-0 bg-transparent text-muted">Estado</th>
                            <th class="border-0 bg-transparent text-muted">Socio</th>
                            <th class="border-0 bg-transparent text-muted">DNI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($historial as $h): ?>
                            <tr>
                                <td class="fw-bold text-primary align-middle"><?= date('H:i:s', strtotime($h['fecha_hora'])) ?></td>
                                <td class="align-middle"><span class="badge bg-success">Ingresó</span></td>
                                <td class="align-middle"><?= $h['nombre'] ?></td>
                                <td class="align-middle"><?= $h['dni'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <?php require_once '../app/views/inc/footer.php'; ?>
</body>
</html>