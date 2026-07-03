<?php
require_once '../app/models/Socio.php';

class SociosController {

    private function verificarAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/index');
            exit;
        }
    }
    
    public function index() {
        $this->verificarAuth();
        $socioModel = new Socio();
        $stmt = $socioModel->obtenerTodos();
        $socios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require_once '../app/views/socios/index.php';
    }

    public function crear() {
        $this->verificarAuth();
        require_once '../app/views/socios/crear.php';
    }

    public function guardar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            $nombre_foto = null;

            if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                // CORRECCIÓN: Usamos ruta absoluta del sistema
                $carpeta = __DIR__ . '/../../public/img/socios/';
                
                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }

                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nombre_archivo = "socio_" . time() . "_" . rand(100, 999) . "." . $extension;
                $ruta_destino = $carpeta . $nombre_archivo;

                if(move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                    $nombre_foto = $nombre_archivo;
                }
            }

            $datos = [
                'nombre' => $_POST['nombre'],
                'dni' => $_POST['dni'],
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'estado' => $_POST['estado'],
                'foto' => $nombre_foto
            ];

            $socioModel = new Socio();
            if ($socioModel->agregar($datos)) {
                header('Location: /socios/index');
            } else {
                echo "Error al guardar el socio.";
            }
        }
    }

    public function editar($id) {
        $this->verificarAuth();
        $socioModel = new Socio();
        $socio = $socioModel->obtenerPorId($id);
        
        if ($socio) {
            require_once '../app/views/socios/editar.php';
        } else {
            header('Location: /socios/index');
        }
    }

    // --- CORRECCIÓN CRÍTICA EN ACTUALIZAR ---
    public function actualizar() {
        $this->verificarAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            // 1. Recuperamos la foto actual del input hidden
            $nombre_foto_final = isset($_POST['foto_actual']) ? $_POST['foto_actual'] : null;

            // 2. Verificamos si subió una NUEVA foto
            if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                
                // CORRECCIÓN: Ruta absoluta
                $carpeta = __DIR__ . '/../../public/img/socios/';
                
                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }

                $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
                $nombre_archivo_nuevo = "socio_upd_" . time() . "_" . rand(100, 999) . "." . $extension;
                $ruta_destino = $carpeta . $nombre_archivo_nuevo;

                // Intentamos mover el archivo
                if(move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_destino)) {
                    $nombre_foto_final = $nombre_archivo_nuevo;
                    
                    // Opcional: Borrar foto vieja si existe y no es la por defecto
                    // $ruta_vieja = $carpeta . $_POST['foto_actual'];
                    // if(!empty($_POST['foto_actual']) && file_exists($ruta_vieja)) { unlink($ruta_vieja); }
                }
            }

            $datos = [
                'id' => $_POST['id'],
                'nombre' => $_POST['nombre'],
                'dni' => $_POST['dni'],
                'email' => $_POST['email'],
                'telefono' => $_POST['telefono'],
                'estado' => $_POST['estado'],
                'foto' => $nombre_foto_final // Enviamos la nueva o la vieja
            ];

            $socioModel = new Socio();
            
            // Llamamos a actualizar
            if ($socioModel->actualizar($datos)) {
                header('Location: /socios/index');
            } else {
                echo "Error al actualizar. Verifique la base de datos.";
            }
        }
    }

    public function cambiarEstado($id, $estado) {
        $this->verificarAuth();
        $socioModel = new Socio();
        if ($socioModel->cambiarEstado($id, $estado)) {
            header('Location: /socios/index');
        }
    }
}