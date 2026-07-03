<?php
require_once '../app/models/Suscripcion.php';
require_once '../app/models/Socio.php';
require_once '../app/models/Plan.php';
require_once '../app/models/Configuracion.php';

class SuscripcionesController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
        if ($_SESSION['user_rol'] == 'entrenador') {
            header('Location: /home/index');
            exit;
        }
    }

    public function index() {
        $this->verificarAuth();
        
        // Cargar configuración para la moneda en el listado
        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();

        $suscripcionModel = new Suscripcion();
        $stmt = $suscripcionModel->obtenerTodas();
        $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../app/views/suscripciones/index.php';
    }

    public function crear() {
        $this->verificarAuth();
        
        $socioModel = new Socio();
        $planModel = new Plan();
        // NUEVO: Cargar configuración para saber la moneda en el formulario
        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos(); // Esto nos da $config['moneda']
        
        $stmtSocios = $socioModel->obtenerTodos();
        $socios = $stmtSocios->fetchAll(PDO::FETCH_ASSOC);
        
        $stmtPlanes = $planModel->obtenerTodos();
        $planes = $stmtPlanes->fetchAll(PDO::FETCH_ASSOC);

        require_once '../app/views/suscripciones/crear.php';
    }

    public function guardar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $socio_id = $_POST['socio_id'];
            $plan_id = $_POST['plan_id'];
            $fecha_inicio = $_POST['fecha_inicio'];

            $planModel = new Plan();
            $plan = $planModel->obtenerPorId($plan_id);
            $dias = $plan['duracion_dias'];
            $fecha_fin = date('Y-m-d', strtotime($fecha_inicio . " + $dias days"));

            $datos = [
                'socio_id' => $socio_id,
                'plan_id' => $plan_id,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin
            ];

            $suscripcionModel = new Suscripcion();
            if ($suscripcionModel->crear($datos)) {
                header('Location: /suscripciones/index');
            } else {
                echo "Error al registrar.";
            }
        }
    }

    public function cancelar($id) {
        $this->verificarAuth();
        $suscripcionModel = new Suscripcion();
        $suscripcionModel->cancelar($id);
        header('Location: /suscripciones/index');
    }

    public function exportarExcel() {
        $this->verificarAuth();

        $configModel = new Configuracion();
        $config = $configModel->obtenerDatos();
        $moneda = $config['moneda'];

        $suscripcionModel = new Suscripcion();
        $stmt = $suscripcionModel->obtenerTodas();
        $suscripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $filename = "Reporte_Suscripciones_" . date('Y-m-d') . ".xls";
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header("Content-Disposition: attachment; filename=$filename");
        header("Pragma: no-cache");
        header("Expires: 0");

        echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
        echo "<table border='1'>";
        echo "<tr style='background-color: #2E7D32; color: white;'><th>ID</th><th>SOCIO</th><th>PLAN</th><th>PRECIO ($moneda)</th><th>INICIO</th><th>FIN</th><th>ESTADO</th></tr>";

        foreach ($suscripciones as $row) {
            $bg = ($row['estado'] == 'activa') ? '#ffffff' : '#ffcdd2';
            echo "<tr style='background-color: $bg;'>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['nombre_socio'] . "</td>";
            echo "<td>" . $row['nombre_plan'] . "</td>";
            echo "<td>" . $moneda . " " . number_format($row['precio'], 2) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_inicio'])) . "</td>";
            echo "<td>" . date('d/m/Y', strtotime($row['fecha_fin'])) . "</td>";
            echo "<td>" . strtoupper($row['estado']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }
}