<?php
class Gasto {
    private $conn;
    private $table_name = "gastos";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerTodos() {
        // Ordenamos por fecha descendente (lo más nuevo primero)
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function agregar($datos) {
        $query = "INSERT INTO " . $this->table_name . " (descripcion, monto, fecha) VALUES (:descripcion, :monto, :fecha)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":descripcion", $datos['descripcion']);
        $stmt->bindParam(":monto", $datos['monto']);
        $stmt->bindParam(":fecha", $datos['fecha']);
        return $stmt->execute();
    }

    public function anular($id, $motivo) {
        $query = "UPDATE " . $this->table_name . " SET estado = 'anulado', motivo_anulacion = :motivo WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":motivo", $motivo);
        return $stmt->execute();
    }
}