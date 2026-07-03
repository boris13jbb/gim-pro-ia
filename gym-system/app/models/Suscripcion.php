<?php
class Suscripcion {
    private $conn;
    private $table_name = "suscripciones";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Traemos datos cruzados (JOIN) para ver nombres reales
    public function obtenerTodas() {
        $query = "SELECT s.id, s.fecha_inicio, s.fecha_fin, s.estado, s.comprobante_id, s.tipo_comprobante,
                         so.nombre as nombre_socio, p.nombre as nombre_plan, p.precio
                  FROM " . $this->table_name . " s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  ORDER BY s.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function crear($datos) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (socio_id, plan_id, fecha_inicio, fecha_fin, estado) 
                 VALUES (:socio_id, :plan_id, :fecha_inicio, :fecha_fin, 'activa')";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":socio_id", $datos['socio_id']);
        $stmt->bindParam(":plan_id", $datos['plan_id']);
        $stmt->bindParam(":fecha_inicio", $datos['fecha_inicio']);
        $stmt->bindParam(":fecha_fin", $datos['fecha_fin']);

        return $stmt->execute();
    }

    // Método para cancelar suscripción (Soft delete o finalización anticipada)
    public function cancelar($id) {
        $query = "UPDATE " . $this->table_name . " SET estado = 'vencida' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
}