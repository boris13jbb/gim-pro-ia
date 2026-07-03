<?php
// app/views/auth/login.php

// 1. Cargar el modelo de Configuración de forma segura
// Usamos __DIR__ para ubicar el archivo relativo a esta carpeta (views/auth)
require_once __DIR__ . '/../../models/Configuracion.php';

// 2. Obtener los datos de la empresa
$config = Configuracion::getInfo();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= $config['nombre_sistema'] ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #2980b9, #8e44ad);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card-login {
            width: 100%;
            max-width: 400px;
            border-radius: 15px;
            overflow: hidden;
        }
        .logo-login {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
            display: block;
            margin: 0 auto 10px auto;
            background: white;
            padding: 5px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

    <div class="card card-login shadow-lg">
        <div class="card-header bg-dark text-white text-center py-4">
            
            <?php if(!empty($config['logo'])): ?>
                <img src="/img/<?= $config['logo'] ?>?v=<?= time() ?>" class="logo-login">
            <?php endif; ?>

            <h3><?= $config['nombre_sistema'] ?></h3>
            
            <p class="m-0 small">Sistema de Administración</p>
        </div>
        
        <div class="card-body p-4 bg-white">
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger text-center"><?= $error ?></div>
            <?php endif; ?>

            <form action="/auth/login" method="POST">
                <div class="mb-3">
                    <label class="form-label text-secondary">Correo Electrónico</label>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="admin@irongym.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label text-secondary">Contraseña</label>
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="******" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-lg">INGRESAR</button>
            </form>
        </div>
        
        <div class="card-footer text-center bg-light py-3">
            <small class="text-muted">&copy; <?= date('Y') ?> <?= $config['nombre_sistema'] ?></small>
        </div>
    </div>

</body>
</html>