<?php
require_once '../app/models/Progreso.php';
require_once '../app/models/Socio.php';

class ProgresoController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) { header('Location: /auth/index'); exit; }
    }

    // Vista principal del perfil de progreso
    public function ver($socio_id) {
        $this->verificarAuth();

        $progresoModel = new Progreso();
        $socioModel = new Socio();

        // 1. Datos del Socio
        $socio = $socioModel->obtenerPorId($socio_id);
        if(!$socio) { header('Location: /socios/index'); }

        // 2. Datos de Medidas (Historial)
        $medidas = $progresoModel->obtenerMedidas($socio_id);

        // 3. Datos de Rutina Actual
        $rutina = $progresoModel->obtenerRutina($socio_id);

        // 4. Preparar datos para los GRÁFICOS (todas las métricas)
        $labels = [];
        $dataPeso    = [];
        $dataGrasa   = [];
        $dataCintura = [];
        $dataBrazo   = [];
        foreach($medidas as $m) {
            $labels[]      = date('d/m', strtotime($m['fecha']));
            $dataPeso[]    = floatval($m['peso'])    ?: null;
            $dataGrasa[]   = floatval($m['grasa'])   ?: null;
            $dataCintura[] = floatval($m['cintura']) ?: null;
            $dataBrazo[]   = floatval($m['brazo'])   ?: null;
        }

        require_once '../app/views/progreso/ver.php';
    }

    public function guardar_medida() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['socio_id'];
            $datos = [
                'socio_id' => $id,
                'peso' => $_POST['peso'],
                'grasa' => $_POST['grasa'],
                'cintura' => $_POST['cintura'],
                'brazo' => $_POST['brazo'],
                'fecha' => $_POST['fecha']
            ];
            
            $progresoModel = new Progreso();
            $progresoModel->guardarMedida($datos);
            header("Location: /progreso/ver/$id");
        }
    }

    public function guardar_rutina() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['socio_id'];
            $datos = [
                'socio_id' => $id,
                'dia1' => $_POST['dia1'],
                'dia2' => $_POST['dia2'],
                'dia3' => $_POST['dia3'],
                'dia4' => $_POST['dia4'],
                'dia5' => $_POST['dia5'],
                'dia6' => $_POST['dia6'],
                'observaciones' => $_POST['observaciones']
            ];
            
            $progresoModel = new Progreso();
            $progresoModel->guardarRutina($datos);
            header("Location: /progreso/ver/$id?tab=rutina"); // Volver a la pestaña rutina
        }
    }

    public function eliminar_medida($id_medida, $id_socio) {
        $this->verificarAuth();
        $progresoModel = new Progreso();
        $progresoModel->eliminarMedida($id_medida);
        header("Location: /progreso/ver/$id_socio");
    }
}