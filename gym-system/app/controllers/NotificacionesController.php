<?php
require_once __DIR__ . '/../models/Socio.php';
require_once __DIR__ . '/../lib/WhatsAppNotifier.php';

class NotificacionesController {
    private $socioModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Solo admin y recepcionistas
        if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] == 'entrenador') {
            header('Location: /auth/index');
            exit();
        }
        $this->socioModel = new Socio();
    }

    // Panel principal de notificaciones
    public function index() {
        $dias = isset($_GET['dias']) ? (int)$_GET['dias'] : 7;
        $proximos = $this->socioModel->obtenerVencimientosProximos($dias);
        require_once __DIR__ . '/../views/notificaciones/index.php';
    }

    // Enviar WhatsApp a un socio específico
    public function enviarAlerta($socio_id) {
        $socios    = $this->socioModel->obtenerVencimientosProximos(30);
        $socio     = null;

        foreach ($socios as $s) {
            if ($s['id'] == $socio_id) {
                $socio = $s;
                break;
            }
        }

        if (!$socio) {
            $_SESSION['notif_mensaje'] = ['tipo' => 'danger', 'texto' => 'Socio no encontrado o sin suscripción próxima a vencer.'];
            header('Location: /notificaciones/index');
            exit();
        }

        if (empty($socio['whatsapp_api_key']) || empty($socio['telefono'])) {
            $_SESSION['notif_mensaje'] = ['tipo' => 'warning', 'texto' => "El socio <strong>{$socio['nombre']}</strong> no tiene teléfono o API Key configurado."];
            header('Location: /notificaciones/index');
            exit();
        }

        // Obtener nombre del gym desde configuración (si tiene el modelo, si no usamos default)
        $nombre_gym = 'Iron Gym';
        if (file_exists(__DIR__ . '/../models/Configuracion.php')) {
            require_once __DIR__ . '/../models/Configuracion.php';
            $config = Configuracion::getInfo();
            $nombre_gym = $config['nombre_negocio'] ?? 'Iron Gym';
        }

        $mensaje   = WhatsAppNotifier::mensajeVencimiento($socio['nombre'], $socio['nombre_plan'], $socio['fecha_fin'], $nombre_gym);
        $resultado = WhatsAppNotifier::enviar($socio['telefono'], $socio['whatsapp_api_key'], $mensaje);

        if ($resultado['success']) {
            $_SESSION['notif_mensaje'] = ['tipo' => 'success', 'texto' => "✅ Mensaje enviado a <strong>{$socio['nombre']}</strong> ({$socio['telefono']})"];
        } else {
            $_SESSION['notif_mensaje'] = ['tipo' => 'danger', 'texto' => "❌ Error al enviar a {$socio['nombre']}: " . htmlspecialchars($resultado['response'])];
        }

        header('Location: /notificaciones/index');
        exit();
    }

    // Enviar WhatsApp a todos los socios que vencen en X días y tienen api_key
    public function enviarTodos() {
        $dias    = isset($_POST['dias']) ? (int)$_POST['dias'] : 7;
        $proximos = $this->socioModel->obtenerVencimientosProximos($dias);

        $nombre_gym = 'Iron Gym';
        if (file_exists(__DIR__ . '/../models/Configuracion.php')) {
            require_once __DIR__ . '/../models/Configuracion.php';
            $config = Configuracion::getInfo();
            $nombre_gym = $config['nombre_sistema'] ?? 'Iron Gym';
        }

        $enviados = 0;
        $fallidos = 0;

        foreach ($proximos as $socio) {
            if (empty($socio['whatsapp_api_key']) || empty($socio['telefono'])) {
                continue;
            }
            $mensaje   = WhatsAppNotifier::mensajeVencimiento($socio['nombre'], $socio['nombre_plan'], $socio['fecha_fin'], $nombre_gym);
            $resultado = WhatsAppNotifier::enviar($socio['telefono'], $socio['whatsapp_api_key'], $mensaje);

            if ($resultado['success']) {
                $enviados++;
            } else {
                $fallidos++;
            }
            // Small delay to avoid rate limiting
            usleep(300000); // 300ms
        }

        $_SESSION['notif_mensaje'] = [
            'tipo'  => $fallidos === 0 ? 'success' : 'warning',
            'texto' => "Mensajes enviados: <strong>$enviados</strong> ✅ &nbsp; Fallidos: <strong>$fallidos</strong> ❌"
        ];

        header('Location: /notificaciones/index');
        exit();
    }

    // Guardar/actualizar la WhatsApp API Key de un socio (modal form)
    public function guardarApiKey() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $socio_id = $_POST['socio_id'];
            $apikey   = trim($_POST['whatsapp_api_key']);
            $this->socioModel->guardarApiKey($socio_id, $apikey);
            header('Location: /notificaciones/index?saved=1');
            exit();
        }
    }
}
