<?php
class Asistencia {
    private $conn;
    private $table_name = "asistencias";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Registrar una nueva entrada
    public function registrar($socio_id) {
        $query = "INSERT INTO " . $this->table_name . " (socio_id) VALUES (:id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $socio_id);
        return $stmt->execute();
    }

    // Ver quiénes han entrado hoy (para mostrar en la pantalla)
    public function obtenerDeHoy() {
        $hoy = date('Y-m-d');
        $query = "SELECT a.fecha_hora, s.nombre, s.dni 
                  FROM " . $this->table_name . " a
                  INNER JOIN socios s ON a.socio_id = s.id
                  WHERE DATE(a.fecha_hora) = :hoy
                  ORDER BY a.fecha_hora DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoy', $hoy);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Reporte de asistencias filtrado por rango y socio opcional
    public function obtenerReporte($desde, $hasta, $socio_id = null) {
        $sql = "SELECT a.fecha_hora, s.nombre, s.dni
                FROM " . $this->table_name . " a
                INNER JOIN socios s ON a.socio_id = s.id
                WHERE DATE(a.fecha_hora) BETWEEN :desde AND :hasta";
        if ($socio_id) {
            $sql .= " AND a.socio_id = :socio_id";
        }
        $sql .= " ORDER BY a.fecha_hora DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':desde', $desde);
        $stmt->bindParam(':hasta', $hasta);
        if ($socio_id) {
            $stmt->bindParam(':socio_id', $socio_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ranking de socios más frecuentes en el período
    public function rankingSocios($desde, $hasta) {
        $sql = "SELECT s.nombre, COUNT(*) as visitas
                FROM " . $this->table_name . " a
                INNER JOIN socios s ON a.socio_id = s.id
                WHERE DATE(a.fecha_hora) BETWEEN :desde AND :hasta
                GROUP BY a.socio_id
                ORDER BY visitas DESC
                LIMIT 5";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':desde', $desde);
        $stmt->bindParam(':hasta', $hasta);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}