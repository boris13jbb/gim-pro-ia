<?php
class Progreso {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // --- MEDIDAS ---
    public function obtenerMedidas($socio_id) {
        $query = "SELECT * FROM medidas WHERE socio_id = :id ORDER BY fecha ASC"; // Ascendente para el gráfico
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $socio_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarMedida($datos) {
        $query = "INSERT INTO medidas (socio_id, peso, grasa, cintura, brazo, fecha) 
                  VALUES (:uid, :peso, :grasa, :cintura, :brazo, :fecha)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $datos['socio_id']);
        $stmt->bindParam(':peso', $datos['peso']);
        $stmt->bindParam(':grasa', $datos['grasa']);
        $stmt->bindParam(':cintura', $datos['cintura']);
        $stmt->bindParam(':brazo', $datos['brazo']);
        $stmt->bindParam(':fecha', $datos['fecha']);
        return $stmt->execute();
    }

    public function eliminarMedida($id) {
        $query = "DELETE FROM medidas WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // --- RUTINAS ---
    public function obtenerRutina($socio_id) {
        // Obtenemos la última rutina asignada
        $query = "SELECT * FROM rutinas WHERE socio_id = :id ORDER BY id DESC LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $socio_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function guardarRutina($datos) {
        // Insertamos una nueva versión de la rutina (para tener historial si quisiéramos)
        // O podríamos hacer UPDATE. Aquí haremos INSERT para simplificar.
        $query = "INSERT INTO rutinas (socio_id, dia1, dia2, dia3, dia4, dia5, dia6, observaciones) 
                  VALUES (:uid, :d1, :d2, :d3, :d4, :d5, :d6, :obs)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $datos['socio_id']);
        $stmt->bindParam(':d1', $datos['dia1']);
        $stmt->bindParam(':d2', $datos['dia2']);
        $stmt->bindParam(':d3', $datos['dia3']);
        $stmt->bindParam(':d4', $datos['dia4']);
        $stmt->bindParam(':d5', $datos['dia5']);
        $stmt->bindParam(':d6', $datos['dia6']);
        $stmt->bindParam(':obs', $datos['observaciones']);
        
        return $stmt->execute();
    }
}