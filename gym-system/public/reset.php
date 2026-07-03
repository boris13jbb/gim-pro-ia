<?php
// public/reset.php

require_once '../app/config/Database.php';

// Conectar a la BD
$db = new Database();
$conn = $db->getConnection();

$email = "admin@irongym.com";
$passwordNuevia = "123456";

// Generar el Hash seguro compatible con tu versión de PHP
$passwordHash = password_hash($passwordNuevia, PASSWORD_DEFAULT);

// Actualizar en la base de datos
$sql = "UPDATE usuarios SET password = :pass WHERE email = :email";
$stmt = $conn->prepare($sql);

if ($stmt->execute([':pass' => $passwordHash, ':email' => $email])) {
    echo "<h1>¡Éxito!</h1>";
    echo "<p>La contraseña para <b>$email</b> se ha reseteado a: <b>$passwordNuevia</b></p>";
    echo "<a href='/auth/login'>Ir al Login</a>";
} else {
    echo "Hubo un error al actualizar.";
}
?>