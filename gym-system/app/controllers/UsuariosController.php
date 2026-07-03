<?php
require_once '../app/models/Usuario.php';
require_once '../app/lib/Auth.php';

class UsuariosController {

    // Seguridad: Solo el ADMIN puede gestionar usuarios
    private function verificarAuth() {
        Auth::requerirRol(['admin']);
    }

    public function index() {
        $this->verificarAuth();
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->obtenerTodos();
        require_once '../app/views/usuarios/index.php';
    }

    public function crear() {
        $this->verificarAuth();
        require_once '../app/views/usuarios/crear.php';
    }

    public function guardar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'nombre' => $_POST['nombre'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'rol' => $_POST['rol']
            ];

            $usuarioModel = new Usuario();
            if ($usuarioModel->crear($datos)) {
                header('Location: /usuarios/index');
            } else {
                echo "Error al crear usuario.";
            }
        }
    }

    public function editar($id) {
        $this->verificarAuth();
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorId($id);
        require_once '../app/views/usuarios/editar.php';
    }

    public function actualizar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'id' => $_POST['id'],
                'nombre' => $_POST['nombre'],
                'email' => $_POST['email'],
                'password' => $_POST['password'], 
                'rol' => $_POST['rol']
            ];

            $usuarioModel = new Usuario();
            if ($usuarioModel->actualizar($datos)) {
                header('Location: /usuarios/index');
            }
        }
    }

    // --- CAMBIO DE ESTADO (En vez de eliminar) ---
    public function cambiarEstado($id, $estado) {
        $this->verificarAuth();
        
        // Protección: No puedes desactivarte a ti mismo
        if ($id == $_SESSION['user_id']) {
            echo "<script>
                    alert('Seguridad: No puedes desactivar tu propia cuenta.'); 
                    window.location.href='/usuarios/index';
                  </script>";
            return;
        }

        $usuarioModel = new Usuario();
        $usuarioModel->cambiarEstado($id, $estado);
        header('Location: /usuarios/index');
    }
}