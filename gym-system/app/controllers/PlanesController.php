<?php
require_once '../app/models/Plan.php';
require_once '../app/models/Configuracion.php';
require_once '../app/lib/Auth.php';

class PlanesController {
    
    private function verificarAuth() {
        Auth::requerirRol(['admin']);
    }

    public function index() {
        $this->verificarAuth();
        $config = Configuracion::getInfo();
        $planModel = new Plan();
        $stmt = $planModel->obtenerTodos();
        $planes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../app/views/planes/index.php';
    }

    public function crear() {
        $this->verificarAuth();
        $config = Configuracion::getInfo();
        require_once '../app/views/planes/crear.php';
    }

    public function guardar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'nombre' => $_POST['nombre'],
                'precio' => $_POST['precio'],
                'duracion' => $_POST['duracion'],
                'descripcion' => $_POST['descripcion']
            ];

            $planModel = new Plan();
            if ($planModel->agregar($datos)) {
                header('Location: /planes/index');
            }
        }
    }

    public function editar($id) {
        $this->verificarAuth();
        $config = Configuracion::getInfo();
        $planModel = new Plan();
        $plan = $planModel->obtenerPorId($id);
        if ($plan) {
            require_once '../app/views/planes/editar.php';
        } else {
            header('Location: /planes/index');
        }
    }

    public function actualizar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'id' => $_POST['id'],
                'nombre' => $_POST['nombre'],
                'precio' => $_POST['precio'],
                'duracion' => $_POST['duracion'],
                'descripcion' => $_POST['descripcion']
            ];

            $planModel = new Plan();
            if ($planModel->actualizar($datos)) {
                header('Location: /planes/index');
            }
        }
    }

    public function cambiarEstado($id, $estado) {
        $this->verificarAuth();
        $planModel = new Plan();
        $planModel->cambiarEstado($id, $estado);
        header('Location: /planes/index');
    }
}