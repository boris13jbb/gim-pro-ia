<?php
require_once '../app/models/Caja.php';
require_once '../app/models/Configuracion.php';

class CajaController {

    private function verificarAuth() {
        // 1. Verificar Login
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
        // 2. SEGURIDAD: Si es entrenador, lo expulsamos
        if ($_SESSION['user_rol'] == 'entrenador') {
            header('Location: /home/index');
            exit;
        }
    }

    public function index() {
        $this->verificarAuth();
        
        $cajaModel = new Caja();
        $usuario_id = $_SESSION['user_id'];
        
        // Obtenemos la moneda
        $config = Configuracion::getInfo();
        
        $cajaAbierta = $cajaModel->obtenerCajaAbierta($usuario_id);

        if ($cajaAbierta) {
            // Pantalla de Cierre
            $totales = $cajaModel->obtenerTotalesSesion($cajaAbierta['fecha_apertura']);
            
            $monto_inicial = $cajaAbierta['monto_inicial'];
            $total_ventas = $totales['ventas'];
            $total_gastos = $totales['gastos'];
            $saldo_esperado = $monto_inicial + $total_ventas - $total_gastos;

            require_once '../app/views/caja/cierre.php';
        } else {
            // Pantalla de Apertura
            $historial = $cajaModel->historial();
            require_once '../app/views/caja/apertura.php';
        }
    }

    public function abrir() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $monto = $_POST['monto_inicial'];
            $cajaModel = new Caja();
            if ($cajaModel->abrir($_SESSION['user_id'], $monto)) {
                header('Location: /caja/index');
            }
        }
    }

    public function cerrar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $caja_id = $_POST['caja_id'];
            $monto_fisico = $_POST['monto_fisico'];
            
            $total_ventas = $_POST['total_ventas'];
            $total_gastos = $_POST['total_gastos'];
            $saldo_esperado = $_POST['saldo_esperado'];
            $diferencia = $monto_fisico - $saldo_esperado;

            $cajaModel = new Caja();
            if ($cajaModel->cerrar($caja_id, $monto_fisico, $total_ventas, $total_gastos, $diferencia)) {
                header('Location: /caja/index?msg=cerrado');
            }
        }
    }
}