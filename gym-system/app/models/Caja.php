<?php
class Caja {
    private $conn;
    private $table_name = "cajas";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 1. Verificar si el usuario ya tiene una caja abierta
    public function obtenerCajaAbierta($usuario_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE usuario_id = :uid AND estado = 'abierta' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uid", $usuario_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Abrir una nueva caja
    public function abrir($usuario_id, $monto_inicial) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (usuario_id, monto_inicial, fecha_apertura, estado) 
                 VALUES (:uid, :monto, NOW(), 'abierta')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":uid", $usuario_id);
        $stmt->bindParam(":monto", $monto_inicial);
        return $stmt->execute();
    }

    // 3. Calcular totales (Ventas y Gastos) de la sesión actual
    // CORREGIDO: Hacemos JOIN con planes para obtener el precio
    public function obtenerTotalesSesion($fecha_apertura) {
        
        // Sumar Ventas (Precio viene de la tabla planes)
        $sqlVentas = "SELECT SUM(p.precio) as total 
                      FROM suscripciones s
                      INNER JOIN planes p ON s.plan_id = p.id
                      WHERE s.fecha_inicio >= :fecha"; 
        
        $stmtV = $this->conn->prepare($sqlVentas);
        $stmtV->bindParam(":fecha", $fecha_apertura);
        $stmtV->execute();
        // Si devuelve null (sin ventas), asignamos 0
        $ventas = $stmtV->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Sumar Gastos
        // Asumimos que se suman los gastos registrados desde la fecha de apertura
        $sqlGastos = "SELECT SUM(monto) as total FROM gastos 
                      WHERE fecha >= DATE(:fecha)"; 
        
        $stmtG = $this->conn->prepare($sqlGastos);
        $stmtG->bindParam(":fecha", $fecha_apertura);
        $stmtG->execute();
        $gastos = $stmtG->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        return ['ventas' => $ventas, 'gastos' => $gastos];
    }

    // 4. Cerrar Caja
    public function cerrar($id, $monto_final, $ventas, $gastos, $diferencia) {
        $query = "UPDATE " . $this->table_name . " 
                 SET monto_final = :m_final,
                     total_ventas = :ventas,
                     total_gastos = :gastos,
                     diferencia = :dif,
                     fecha_cierre = NOW(),
                     estado = 'cerrada'
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":m_final", $monto_final);
        $stmt->bindParam(":ventas", $ventas);
        $stmt->bindParam(":gastos", $gastos);
        $stmt->bindParam(":dif", $diferencia);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // 5. Historial de Cajas Cerradas
    public function historial() {
        $query = "SELECT c.*, u.nombre as cajero 
                  FROM cajas c 
                  INNER JOIN usuarios u ON c.usuario_id = u.id 
                  ORDER BY c.id DESC LIMIT 50";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}