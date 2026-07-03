<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // --- LOGIN MEJORADO ---
    public function login($email, $password) {
        // Obtenemos también el ROL y el ESTADO
        $query = "SELECT id, nombre, password, rol, estado 
                  FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña
            if (password_verify($password, $row['password'])) {
                // VALIDACIÓN CRÍTICA: Si está inactivo, no puede entrar
                if ($row['estado'] == 'inactivo') {
                    return 'inactivo'; 
                }
                return $row; // Todo OK
            }
        }
        return false; // No existe o clave errónea
    }
    
    // Crear Admin por defecto si no hay nadie
    public function crearAdmin() {
        $check = $this->conn->query("SELECT count(*) FROM usuarios")->fetchColumn();
        if($check == 0) {
            $passHash = password_hash("123456", PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nombre, email, password, rol, estado) 
                    VALUES ('Admin', 'admin@irongym.com', '$passHash', 'admin', 'activo')";
            $this->conn->exec($sql);
        }
    }

    // --- CRUD ---

    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function crear($datos) {
        // Por defecto se crea como 'activo'
        $query = "INSERT INTO " . $this->table_name . " (nombre, email, password, rol, estado) 
                  VALUES (:nombre, :email, :pass, :rol, 'activo')";
        
        $stmt = $this->conn->prepare($query);
        $passHash = password_hash($datos['password'], PASSWORD_DEFAULT);

        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":pass", $passHash);
        $stmt->bindParam(":rol", $datos['rol']);
        
        return $stmt->execute();
    }

    public function actualizar($datos) {
        // Lógica para actualizar contraseña solo si se envía una nueva
        if (!empty($datos['password'])) {
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre=:nombre, email=:email, password=:pass, rol=:rol 
                      WHERE id=:id";
            $passHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre=:nombre, email=:email, rol=:rol 
                      WHERE id=:id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":rol", $datos['rol']);
        $stmt->bindParam(":id", $datos['id']);

        if (!empty($datos['password'])) {
            $stmt->bindParam(":pass", $passHash);
        }

        return $stmt->execute();
    }

    // --- SOFT DELETE (Activar/Desactivar) ---
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE " . $this->table_name . " SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}