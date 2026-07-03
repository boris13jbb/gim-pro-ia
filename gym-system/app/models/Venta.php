<?php
class Venta {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Registra una venta completa en una sola transacción atómica:
     * 1. Inserta la cabecera en `ventas`
     * 2. Inserta cada línea en `detalle_ventas`
     * 3. Reduce el stock de cada producto vendido
     * 4. Suma el total a `cajas.total_ventas`
     */
    public function registrarVenta($caja_id, $socio_id, $total, $metodo_pago, $carrito, $descuento = 0) {
        try {
            $this->conn->beginTransaction();

            // 1. Cabecera de venta
            $stmt = $this->conn->prepare("INSERT INTO ventas (caja_id, socio_id, total, descuento, metodo_pago) 
                                          VALUES (:caja_id, :socio_id, :total, :descuento, :metodo_pago)");
            $stmt->bindParam(':caja_id',     $caja_id,    PDO::PARAM_INT);
            $stmt->bindValue(':socio_id',    $socio_id ?: null, PDO::PARAM_INT);
            $stmt->bindParam(':total',       $total);
            $stmt->bindParam(':descuento',   $descuento);
            $stmt->bindParam(':metodo_pago', $metodo_pago);
            $stmt->execute();
            $venta_id = $this->conn->lastInsertId();

            // 2 & 3. Detalles y descuento de stock
            $stmtDetalle = $this->conn->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) 
                                                  VALUES (:venta_id, :producto_id, :cantidad, :precio_unitario, :subtotal)");
            $stmtStock   = $this->conn->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :producto_id");
            
            foreach ($carrito as $item) {
                $subtotal = $item['precio'] * $item['cantidad'];
                
                $stmtDetalle->bindParam(':venta_id',       $venta_id,        PDO::PARAM_INT);
                $stmtDetalle->bindParam(':producto_id',    $item['id'],      PDO::PARAM_INT);
                $stmtDetalle->bindParam(':cantidad',       $item['cantidad'],PDO::PARAM_INT);
                $stmtDetalle->bindParam(':precio_unitario',$item['precio']);
                $stmtDetalle->bindParam(':subtotal',       $subtotal);
                $stmtDetalle->execute();

                $stmtStock->bindParam(':cantidad',    $item['cantidad'], PDO::PARAM_INT);
                $stmtStock->bindParam(':producto_id', $item['id'],      PDO::PARAM_INT);
                $stmtStock->execute();
            }

            // 4. Sumar al total_ventas de la caja activa
            $stmtCaja = $this->conn->prepare("UPDATE cajas SET total_ventas = total_ventas + :total WHERE id = :caja_id");
            $stmtCaja->bindParam(':total',   $total);
            $stmtCaja->bindParam(':caja_id', $caja_id, PDO::PARAM_INT);
            $stmtCaja->execute();

            $this->conn->commit();
            return $venta_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Historial filtrable con socios y cajero
    public function obtenerHistorialFiltrado($desde, $hasta, $busqueda = '') {
        $sql = "SELECT v.id, v.fecha, v.total, v.descuento, v.metodo_pago,
                       u.nombre as cajero,
                       IFNULL(s.nombre, 'Cliente General') as cliente
                FROM ventas v
                INNER JOIN cajas c ON v.caja_id = c.id
                INNER JOIN usuarios u ON c.usuario_id = u.id
                LEFT JOIN socios s ON v.socio_id = s.id
                WHERE DATE(v.fecha) BETWEEN :desde AND :hasta";
        if (!empty($busqueda)) {
            $sql .= " AND (s.nombre LIKE :busqueda OR u.nombre LIKE :busqueda2)";
        }
        $sql .= " ORDER BY v.fecha DESC LIMIT 200";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':desde', $desde);
        $stmt->bindParam(':hasta', $hasta);
        if (!empty($busqueda)) {
            $like = '%' . $busqueda . '%';
            $stmt->bindParam(':busqueda', $like);
            $stmt->bindParam(':busqueda2', $like);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Detalle de ítems de una venta
    public function obtenerDetalle($venta_id) {
        $stmt = $this->conn->prepare(
            "SELECT dv.cantidad, dv.precio_unitario, dv.subtotal, p.nombre
             FROM detalle_ventas dv
             INNER JOIN productos p ON dv.producto_id = p.id
             WHERE dv.venta_id = :id"
        );
        $stmt->bindParam(':id', $venta_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener historial resumido de ventas por fecha
    public function obtenerHistorial($limite = 50) {
        $stmt = $this->conn->prepare("SELECT v.*, u.nombre as cajero 
                                      FROM ventas v 
                                      INNER JOIN cajas c ON v.caja_id = c.id 
                                      INNER JOIN usuarios u ON c.usuario_id = u.id 
                                      ORDER BY v.fecha DESC LIMIT :limite");
        $stmt->bindParam(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
