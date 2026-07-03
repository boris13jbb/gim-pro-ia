<?php
require_once '../app/models/Asistencia.php';
require_once '../app/models/Socio.php';
require_once '../app/models/Suscripcion.php';
require_once '../app/models/Configuracion.php';
define('FPDF_FONTPATH', '../app/lib/fpdf/font/');
require_once '../app/lib/fpdf/fpdf.php';

class AsistenciaController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    public function index() {
        $this->verificarAuth();
        // Cargar historial del día para la tabla inferior
        $asistenciaModel = new Asistencia();
        $historial = $asistenciaModel->obtenerDeHoy();
        require_once '../app/views/asistencia/index.php';
    }

    // PASO 1: VALIDAR Y MOSTRAR PERFIL (SIN GUARDAR)
    public function validar() {
        $this->verificarAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $dni = $_POST['dni'];
            
            $perfil_validacion = null; // Datos para la vista
            $error_busqueda = "";

            $db = new Database();
            $conn = $db->getConnection();

            // 1. Buscar socio (ID, Nombre, Estado, Foto)
            $querySocio = "SELECT id, nombre, estado, foto FROM socios WHERE dni = :dni LIMIT 1";
            $stmt = $conn->prepare($querySocio);
            $stmt->bindParam(':dni', $dni);
            $stmt->execute();
            $socio = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$socio) {
                $error_busqueda = "❌ DNI no encontrado en la base de datos.";
            } else {
                // 2. Verificar suscripción activa
                $hoy = date('Y-m-d');
                $querySub = "SELECT * FROM suscripciones 
                             WHERE socio_id = :id 
                             AND estado = 'activa' 
                             AND fecha_fin >= :hoy 
                             LIMIT 1";
                
                $stmtSub = $conn->prepare($querySub);
                $stmtSub->bindParam(':id', $socio['id']);
                $stmtSub->bindParam(':hoy', $hoy);
                $stmtSub->execute();
                
                $tiene_acceso = false;
                $dias_restantes = 0;
                $vencimiento = "";

                if ($stmtSub->rowCount() > 0) {
                    $tiene_acceso = true;
                    $sub = $stmtSub->fetch(PDO::FETCH_ASSOC);
                    $diasRestantes = (strtotime($sub['fecha_fin']) - strtotime($hoy)) / (60 * 60 * 24);
                    $vencimiento = date('d/m/Y', strtotime($sub['fecha_fin']));
                }

                // Preparamos los datos para que la Vista los muestre (NO GUARDAMOS AÚN)
                $perfil_validacion = [
                    'id' => $socio['id'],
                    'nombre' => $socio['nombre'],
                    'foto' => $socio['foto'],
                    'estado_socio' => $socio['estado'],
                    'tiene_acceso' => $tiene_acceso, // Booleano (True/False)
                    'dias_restantes' => round($diasRestantes),
                    'fecha_vence' => $vencimiento
                ];
            }

            // Mantenemos el historial visible
            $asistenciaModel = new Asistencia();
            $historial = $asistenciaModel->obtenerDeHoy();
            
            require_once '../app/views/asistencia/index.php';
        }
    }

    // PASO 2: REGISTRAR EL INGRESO (ACCIÓN DEL OPERADOR)
    public function registrar() {
        $this->verificarAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $socio_id = $_POST['socio_id'];
            
            // AHORA SÍ: Guardar en la base de datos
            $asistenciaModel = new Asistencia();
            if($asistenciaModel->registrar($socio_id)) {
                $mensaje_exito = "✅ Ingreso registrado correctamente.";
            } else {
                $error_busqueda = "Error al guardar la asistencia.";
            }

            // Recargar historial actualizado
            $historial = $asistenciaModel->obtenerDeHoy();
            
            require_once '../app/views/asistencia/index.php';
        }
    }

    // Reporte de asistencias
    public function reporte() {
        $this->verificarAuth();

        $desde    = $_GET['desde']    ?? date('Y-m-01');
        $hasta    = $_GET['hasta']    ?? date('Y-m-d');
        $socio_id = $_GET['socio_id'] ?? null;

        $asistenciaModel = new Asistencia();
        $socioModel      = new Socio();
        $config          = Configuracion::getInfo();

        $registros = $asistenciaModel->obtenerReporte($desde, $hasta, $socio_id);
        $ranking   = $asistenciaModel->rankingSocios($desde, $hasta);
        $socios    = $socioModel->obtenerTodos()->fetchAll(PDO::FETCH_ASSOC);

        $totalVisitas  = count($registros);
        $diasPeriodo   = max(1, (strtotime($hasta) - strtotime($desde)) / 86400 + 1);
        $promDiario    = round($totalVisitas / $diasPeriodo, 1);
        $liderr        = !empty($ranking) ? $ranking[0]['nombre'] : 'N/A';

        require_once '../app/views/asistencia/reporte.php';
    }

    // Exportar PDF de asistencias
    public function exportarPDF() {
        $this->verificarAuth();

        $desde    = $_GET['desde']    ?? date('Y-m-01');
        $hasta    = $_GET['hasta']    ?? date('Y-m-d');
        $socio_id = $_GET['socio_id'] ?? null;

        $asistenciaModel = new Asistencia();
        $config          = Configuracion::getInfo();
        $registros       = $asistenciaModel->obtenerReporte($desde, $hasta, $socio_id);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();

        // Header
        $pdf->SetFont('Arial', 'B', 16);
        $text = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $config['nombre_sistema'] ?? 'Gym System');
        $pdf->Cell(0, 10, $text, 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 5, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Reporte de Asistencias: ' . date('d/m/Y', strtotime($desde)) . ' al ' . date('d/m/Y', strtotime($hasta))), 0, 1, 'C');
        $pdf->Ln(6);

        // Table header
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(99, 102, 241);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(55, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Fecha y Hora'), 1, 0, 'C', true);
        $pdf->Cell(85, 8, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', 'Nombre del Socio'), 1, 0, 'L', true);
        $pdf->Cell(50, 8, 'DNI', 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);

        foreach ($registros as $i => $r) {
            $pdf->SetFillColor($i % 2 === 0 ? 245 : 255, $i % 2 === 0 ? 247 : 255, $i % 2 === 0 ? 250 : 255);
            $pdf->Cell(55, 7, date('d/m/Y H:i', strtotime($r['fecha_hora'])), 1, 0, 'C', true);
            $pdf->Cell(85, 7, iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $r['nombre']), 1, 0, 'L', true);
            $pdf->Cell(50, 7, $r['dni'], 1, 1, 'C', true);
        }

        $pdf->Output('I', 'Reporte_Asistencias_' . $desde . '_' . $hasta . '.pdf');
    }
}