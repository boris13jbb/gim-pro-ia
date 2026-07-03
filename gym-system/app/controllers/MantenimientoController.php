<?php
require_once '../app/config/Database.php';

class MantenimientoController {

    private $db;
    private $conn;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Solo admins permitidos
        if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
            $_SESSION['acceso_denegado'] = "Acceso denegado: Se requiere rol de administrador.";
            header('Location: /home/index');
            exit;
        }

        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function index() {
        $pagina = 'mantenimiento';
        require_once '../app/views/mantenimiento/index.php';
    }

    public function backup() {
        $config = $this->obtenerConfiguracion();
        $nombreSistema = preg_replace('/[^A-Za-z0-9\-]/', '_', $config['nombre_sistema'] ?? 'Sistema_Gimnasio');
        $fileName = 'backup_' . $nombreSistema . '_' . date('Y-m-d_H-i-s') . '.sql';

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $out = "-- Backup de Base de Datos\n";
        $out .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        // Obtener tablas
        $stmt = $this->conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Estructura
            $stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
            $createTable = $stmt->fetch(PDO::FETCH_ASSOC);
            $out .= "\nDROP TABLE IF EXISTS `$table`;\n";
            $out .= $createTable['Create Table'] . ";\n\n";

            // Datos
            $stmt = $this->conn->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($rows) > 0) {
                $out .= "INSERT INTO `$table` VALUES\n";
                $insertChunks = [];
                foreach ($rows as $row) {
                    $values = array_map(function($val) {
                        return ($val === null) ? 'NULL' : "'" . addslashes($val) . "'";
                    }, array_values($row));
                    $insertChunks[] = "(" . implode(", ", $values) . ")";
                }
                $out .= implode(",\n", $insertChunks) . ";\n";
            }
        }
        $out .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        echo $out;
        exit;
    }

    public function restaurar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
            $file = $_FILES['backup_file'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['error'] = 'Error al subir el archivo.';
                header('Location: /mantenimiento/index');
                exit;
            }

            // Validar extensión
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                $_SESSION['error'] = 'Solo se permiten archivos .sql';
                header('Location: /mantenimiento/index');
                exit;
            }

            // Proceder con la lectura y ejecución
            $sqlContent = file_get_contents($file['tmp_name']);
            if (empty(trim($sqlContent))) {
                $_SESSION['error'] = 'El archivo está vacío.';
                header('Location: /mantenimiento/index');
                exit;
            }

            try {
                $this->conn->exec($sqlContent);
                $_SESSION['mensaje'] = "Base de datos restaurada correctamente desde " . htmlspecialchars($file['name']);
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al restaurar: " . $e->getMessage();
            }

            header('Location: /mantenimiento/index');
            exit;
        }
    }

    public function limpiar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Tablas a vaciar (todo menos configuracion y el superadmin)
            $tablasTruncar = [
                'asistencias',
                'detalle_ventas',
                'ventas',
                'suscripciones',
                'medidas',
                'rutinas',
                'socios',
                'gastos',
                'productos',
                'categorias',
                'planes'
            ];

            try {
                $this->conn->exec("SET FOREIGN_KEY_CHECKS=0");

                // Truncar todas las tablas operativas
                foreach ($tablasTruncar as $tabla) {
                    $this->conn->exec("TRUNCATE TABLE `$tabla`");
                }

                // Limpiar usuarios, dejando solo al admin (ID 1)
                $this->conn->exec("DELETE FROM `usuarios` WHERE id > 1");
                $this->conn->exec("ALTER TABLE `usuarios` AUTO_INCREMENT = 2");

                $this->conn->exec("SET FOREIGN_KEY_CHECKS=1");

                $_SESSION['mensaje'] = "Base de datos reiniciada para un nuevo proyecto correctamente. Configuraciones y credenciales de Admin mantenidas.";
            } catch (PDOException $e) {
                $this->conn->exec("SET FOREIGN_KEY_CHECKS=1");
                $_SESSION['error'] = "Error al reiniciar la base de datos: " . $e->getMessage();
            }

            header('Location: /mantenimiento/index');
            exit;
        }
    }

    private function obtenerConfiguracion() {
        try {
            $stmt = $this->conn->query("SELECT * FROM configuracion LIMIT 1");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return null;
        }
    }
}
