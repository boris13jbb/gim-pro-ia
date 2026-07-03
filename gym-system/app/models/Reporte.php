<?php
class Reporte {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // ── KPI: Ingresos por membresías en el período
    public function ingresosSuscripciones($desde, $hasta) {
        $q = "SELECT COALESCE(SUM(p.precio), 0) as total
              FROM suscripciones s
              INNER JOIN planes p ON s.plan_id = p.id
              WHERE DATE(s.fecha_inicio) BETWEEN :desde AND :hasta";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde', $desde);
        $st->bindParam(':hasta', $hasta);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ── KPI: Ingresos por ventas POS en el período
    public function ingresosPOS($desde, $hasta) {
        $q = "SELECT COALESCE(SUM(total), 0) as total
              FROM ventas
              WHERE DATE(fecha) BETWEEN :desde AND :hasta";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde', $desde);
        $st->bindParam(':hasta', $hasta);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ── KPI: Gastos en el período
    public function totalGastos($desde, $hasta) {
        $q = "SELECT COALESCE(SUM(monto), 0) as total
              FROM gastos
              WHERE DATE(fecha) BETWEEN :desde AND :hasta";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde', $desde);
        $st->bindParam(':hasta', $hasta);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ── Gráfico Barras: Ingresos totales por mes (suscripciones + POS)
    public function ingresosPorMes($desde, $hasta) {
        $q = "SELECT mes, SUM(total) as total FROM (
                  SELECT DATE_FORMAT(s.fecha_inicio, '%Y-%m') as mes, p.precio as total
                  FROM suscripciones s
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE DATE(s.fecha_inicio) BETWEEN :desde1 AND :hasta1
                  UNION ALL
                  SELECT DATE_FORMAT(fecha, '%Y-%m') as mes, total
                  FROM ventas
                  WHERE DATE(fecha) BETWEEN :desde2 AND :hasta2
              ) combined
              GROUP BY mes
              ORDER BY mes ASC";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde1', $desde);
        $st->bindParam(':hasta1', $hasta);
        $st->bindParam(':desde2', $desde);
        $st->bindParam(':hasta2', $hasta);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Gráfico Torta: Ventas POS por método de pago
    public function ventasPorMetodoPago($desde, $hasta) {
        $q = "SELECT metodo_pago, COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total
              FROM ventas
              WHERE DATE(fecha) BETWEEN :desde AND :hasta
              GROUP BY metodo_pago
              ORDER BY total DESC";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde', $desde);
        $st->bindParam(':hasta', $hasta);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Gráfico Línea: Socios nuevos por mes
    public function sociosNuevosPorMes($desde, $hasta) {
        $q = "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as cantidad
              FROM socios
              WHERE DATE(fecha_registro) BETWEEN :desde AND :hasta
              GROUP BY mes
              ORDER BY mes ASC";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde', $desde);
        $st->bindParam(':hasta', $hasta);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Tabla detalle: Movimientos financieros del período
    public function movimientosDetalle($desde, $hasta) {
        $q = "SELECT fecha, descripcion, monto, tipo FROM (
                  SELECT s.fecha_inicio as fecha,
                         CONCAT('Membresía: ', p.nombre, ' — ', so.nombre) as descripcion,
                         p.precio as monto, 'ingreso' as tipo
                  FROM suscripciones s
                  INNER JOIN planes p ON s.plan_id = p.id
                  INNER JOIN socios so ON s.socio_id = so.id
                  WHERE DATE(s.fecha_inicio) BETWEEN :desde1 AND :hasta1
                  UNION ALL
                  SELECT v.fecha,
                         CONCAT('Venta POS #', v.id, ' (', v.metodo_pago, ')') as descripcion,
                         v.total as monto, 'ingreso' as tipo
                  FROM ventas v
                  WHERE DATE(v.fecha) BETWEEN :desde2 AND :hasta2
                  UNION ALL
                  SELECT g.fecha,
                         CONCAT('Gasto: ', g.descripcion) as descripcion,
                         g.monto as monto, 'gasto' as tipo
                  FROM gastos g
                  WHERE DATE(g.fecha) BETWEEN :desde3 AND :hasta3
              ) todos
              ORDER BY fecha DESC";
        $st = $this->conn->prepare($q);
        $st->bindParam(':desde1', $desde);
        $st->bindParam(':hasta1', $hasta);
        $st->bindParam(':desde2', $desde);
        $st->bindParam(':hasta2', $hasta);
        $st->bindParam(':desde3', $desde);
        $st->bindParam(':hasta3', $hasta);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Conteo socios activos al final del período
    public function sociosActivos() {
        $q = "SELECT COUNT(*) as total FROM socios WHERE estado = 'activo'";
        $st = $this->conn->prepare($q);
        $st->execute();
        return $st->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
