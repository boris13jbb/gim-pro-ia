<?php
require_once '../app/models/Gasto.php';
require_once '../app/lib/Auth.php';

class GastosController {

    private function verificarAuth() {
        Auth::requerirRol(['admin']);
    }

    public function index() {
        $this->verificarAuth();
        $gastoModel = new Gasto();
        $stmt = $gastoModel->obtenerTodos();
        $gastos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../app/views/gastos/index.php';
    }

    public function crear() {
        $this->verificarAuth();
        require_once '../app/views/gastos/crear.php';
    }

    public function guardar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $datos = [
                'descripcion' => $_POST['descripcion'],
                'monto' => $_POST['monto'],
                'fecha' => $_POST['fecha']
            ];

            $gastoModel = new Gasto();
            if ($gastoModel->agregar($datos)) {
                header('Location: /gastos/index');
            }
        }
    }

    public function anular() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id']) && isset($_POST['motivo_anulacion'])) {
            $id = $_POST['id'];
            $motivo = trim($_POST['motivo_anulacion']);
            
            if (!empty($motivo)) {
                $gastoModel = new Gasto();
                $gastoModel->anular($id, $motivo);
            }
        }
        header('Location: /gastos/index?success=1');
        exit;
    }
}