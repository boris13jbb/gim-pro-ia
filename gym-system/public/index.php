<?php
session_start(); // <--- IMPORTANTE: Inicia las sesiones para todo el sistema

require_once '../app/config/Database.php';

// Obtener la URL
$url = isset($_GET['url']) ? $_GET['url'] : 'home/index';
$url = rtrim($url, '/');
$url = explode('/', $url);

// Definir Controlador y Método
$controllerName = isset($url[0]) ? ucfirst($url[0]) . 'Controller' : 'HomeController';
$method = isset($url[1]) ? $url[1] : 'index';
$params = isset($url[2]) ? array_slice($url, 2) : [];

// Rutas a archivos
$controllerPath = '../app/controllers/' . $controllerName . '.php';

if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $controller = new $controllerName;
    
    if (method_exists($controller, $method)) {
        call_user_func_array([$controller, $method], $params);
    } else {
        echo "Error: El método no existe.";
    }
} else {
    // Si el controlador no existe (ej: 404), podrías redirigir al login o home
    header('Location: /auth/index');
}