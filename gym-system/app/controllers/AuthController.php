<?php
require_once '../app/models/Usuario.php';

class AuthController {
    
    public function index() {
        // Crear admin si es la primera vez
        $usuarioModel = new Usuario();
        $usuarioModel->crearAdmin();

        if (isset($_SESSION['user_id'])) {
            header('Location: /home/index');
            exit;
        }
        require_once '../app/views/auth/login.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $email = $_POST['email'];
            $password = $_POST['password'];

            $usuarioModel = new Usuario();
            // El modelo devuelve un array, false, o el string 'inactivo'
            $usuario = $usuarioModel->login($email, $password);

            if ($usuario === 'inactivo') {
                // CASO: Usuario desactivado
                $error = "Cuenta inhabilitada. Contacte al administrador.";
                require_once '../app/views/auth/login.php';

            } elseif ($usuario) {
                // CASO: Éxito
                $_SESSION['user_id'] = $usuario['id'];
                $_SESSION['user_name'] = $usuario['nombre'];
                $_SESSION['user_rol'] = $usuario['rol'];
                
                header('Location: /home/index');
                exit;

            } else {
                // CASO: Datos incorrectos
                $error = "Correo o contraseña incorrectos.";
                require_once '../app/views/auth/login.php';
            }
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /auth/index');
    }
}