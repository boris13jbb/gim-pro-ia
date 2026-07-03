<?php
class Plan {
    private $conn;
    private $table_name = "planes";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY precio ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function agregar($datos) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nombre, precio, duracion_dias, descripcion, estado) 
                 VALUES (:nombre, :precio, :duracion, :descripcion, 'activo')";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":precio", $datos['precio']);
        $stmt->bindParam(":duracion", $datos['duracion']);
        $stmt->bindParam(":descripcion", $datos['descripcion']);

        return $stmt->execute();
    }

    public function actualizar($datos) {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre = :nombre, precio = :precio, 
                     duracion_dias = :duracion, descripcion = :descripcion 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":precio", $datos['precio']);
        $stmt->bindParam(":duracion", $datos['duracion']);
        $stmt->bindParam(":descripcion", $datos['descripcion']);
        $stmt->bindParam(":id", $datos['id']);

        return $stmt->execute();
    }

    // Soft Delete (Activar/Desactivar)
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE " . $this->table_name . " SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}