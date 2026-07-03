<?php
class Categoria {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerTodos() {
        $stmt = $this->conn->prepare("SELECT * FROM categorias ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivos() {
        $stmt = $this->conn->prepare("SELECT * FROM categorias WHERE estado = 'activo' ORDER BY nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function agregar($nombre) {
        $stmt = $this->conn->prepare("INSERT INTO categorias (nombre, estado) VALUES (:nombre, 'activo')");
        $stmt->bindParam(':nombre', $nombre);
        return $stmt->execute();
    }

    public function actualizar($id, $nombre) {
        $stmt = $this->conn->prepare("UPDATE categorias SET nombre = :nombre WHERE id = :id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->conn->prepare("UPDATE categorias SET estado = :estado WHERE id = :id");
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
