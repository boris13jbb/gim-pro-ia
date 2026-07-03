<?php
require_once '../app/models/Configuracion.php';

class ConfiguracionController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }

    public function index() {
        $this->verificarAuth();
        $configModel = new Configuracion();
        $datos = $configModel->obtenerDatos();
        require_once '../app/views/configuracion/index.php';
    }

    public function actualizar() {
        $this->verificarAuth();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $nombre_logo = $_POST['logo_actual'];

            if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $carpeta = __DIR__ . '/../../public/img/';
                if (!file_exists($carpeta)) { mkdir($carpeta, 0777, true); }

                $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = "logo_empresa." . $extension;
                $ruta_destino = $carpeta . $nombre_archivo;

                if(move_uploaded_file($_FILES['logo']['tmp_name'], $ruta_destino)) {
                    $nombre_logo = $nombre_archivo;
                }
            }

            $data = [
                'nombre' => $_POST['nombre'],
                'ruc' => $_POST['ruc'],
                'direccion' => $_POST['direccion'],
                'telefono' => $_POST['telefono'],
                'email' => $_POST['email'],
                'logo' => $nombre_logo,
                'moneda' => $_POST['moneda'] // Nuevo campo
            ];

            $configModel = new Configuracion();
            if ($configModel->actualizar($data)) {
                header('Location: /configuracion/index?msg=ok');
            } else {
                echo "Error al guardar configuración.";
            }
        }
    }

    // =====================================================
    //  CONFIGURACIÓN SRI (Facturación Electrónica - Ecuador)
    // =====================================================
    public function sri() {
        $this->verificarAuth();
        $configModel = new Configuracion();
        $datos = $configModel->obtenerDatos();

        $db = (new Database())->getConnection();
        $series = $db->query("SELECT * FROM sri_series ORDER BY tipo_doc, serie")->fetchAll(PDO::FETCH_ASSOC);

        require_once '../app/views/configuracion/sri.php';
    }

    public function actualizarSri() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /configuracion/sri'); exit;
        }

        $certNombre = $_POST['cert_actual'] ?? '';
        if (isset($_FILES['certificado']) && $_FILES['certificado']['error'] === 0) {
            $carpeta = __DIR__ . '/../../public/cert/';
            if (!file_exists($carpeta)) { mkdir($carpeta, 0775, true); }
            $ext = strtolower(pathinfo($_FILES['certificado']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pem','pfx','p12'])) {
                $_SESSION['flash_msg'] = 'El certificado debe ser .p12, .pfx o .pem';
                $_SESSION['flash_ok']  = false;
                header('Location: /configuracion/sri'); exit;
            }
            $certNombre = 'sri_cert.' . $ext;
            move_uploaded_file($_FILES['certificado']['tmp_name'], $carpeta . $certNombre);
        }

        $datos = [
            'razon_social'          => $_POST['razon_social'] ?? '',
            'nombre_comercial'      => $_POST['nombre_comercial'] ?? '',
            'sri_ambiente'          => $_POST['sri_ambiente'] ?? '1',
            'sri_establecimiento'   => str_pad($_POST['sri_establecimiento'] ?? '001', 3, '0', STR_PAD_LEFT),
            'sri_punto_emision'     => str_pad($_POST['sri_punto_emision'] ?? '001', 3, '0', STR_PAD_LEFT),
            'sri_certificado_p12'   => $certNombre,
            'sri_certificado_clave' => $_POST['cert_clave'] ?? '',
            'iva_tasa'              => (float)($_POST['iva_tasa'] ?? 15.00),
            'incluye_iva'           => isset($_POST['incluye_iva']) ? 1 : 0,
        ];
        $configModel = new Configuracion();
        $configModel->actualizarSri($datos);
        $_SESSION['flash_msg'] = 'Configuración SRI actualizada con éxito.';
        $_SESSION['flash_ok']  = true;
        header('Location: /configuracion/sri');
    }

    public function guardarSerie() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /configuracion/sri'); exit;
        }
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("INSERT INTO sri_series (tipo_doc, serie, correlativo, descripcion, estado)
                              VALUES (:t,:s,:c,:d,'activo')
                              ON DUPLICATE KEY UPDATE descripcion=:d, estado='activo'");
        $stmt->execute([
            ':t' => $_POST['tipo_doc'],
            ':s' => str_pad($_POST['serie'], 6, '0', STR_PAD_LEFT),
            ':c' => (int)($_POST['correlativo'] ?? 0),
            ':d' => $_POST['descripcion'] ?? '',
        ]);
        $_SESSION['flash_msg'] = 'Serie SRI guardada con éxito.';
        $_SESSION['flash_ok']  = true;
        header('Location: /configuracion/sri');
    }
}