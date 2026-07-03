<?php
class Producto {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerTodos() {
        $stmt = $this->conn->prepare("SELECT p.*, c.nombre as categoria_nombre 
                                      FROM productos p 
                                      INNER JOIN categorias c ON p.categoria_id = c.id 
                                      ORDER BY p.nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerActivos() {
        $stmt = $this->conn->prepare("SELECT p.*, c.nombre as categoria_nombre 
                                      FROM productos p 
                                      INNER JOIN categorias c ON p.categoria_id = c.id 
                                      WHERE p.estado = 'activo' AND c.estado = 'activo'
                                      ORDER BY p.nombre ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId($id) {
        $stmt = $this->conn->prepare("SELECT * FROM productos WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function agregar($datos) {
        $stmt = $this->conn->prepare("INSERT INTO productos (categoria_id, codigo, nombre, precio_compra, precio_venta, stock, foto, estado) 
                                      VALUES (:categoria_id, :codigo, :nombre, :precio_compra, :precio_venta, :stock, :foto, 'activo')");
        $stmt->bindParam(':categoria_id', $datos['categoria_id']);
        $stmt->bindParam(':codigo',       $datos['codigo']);
        $stmt->bindParam(':nombre',       $datos['nombre']);
        $stmt->bindParam(':precio_compra',$datos['precio_compra']);
        $stmt->bindParam(':precio_venta', $datos['precio_venta']);
        $stmt->bindParam(':stock',        $datos['stock']);
        $stmt->bindParam(':foto',         $datos['foto']);
        return $stmt->execute();
    }

    public function actualizar($datos) {
        if (!empty($datos['foto'])) {
            $stmt = $this->conn->prepare("UPDATE productos SET categoria_id = :categoria_id, codigo = :codigo, nombre = :nombre, 
                                          precio_compra = :precio_compra, precio_venta = :precio_venta, stock = :stock, foto = :foto 
                                          WHERE id = :id");
            $stmt->bindParam(':foto', $datos['foto']);
        } else {
            $stmt = $this->conn->prepare("UPDATE productos SET categoria_id = :categoria_id, codigo = :codigo, nombre = :nombre, 
                                          precio_compra = :precio_compra, precio_venta = :precio_venta, stock = :stock 
                                          WHERE id = :id");
        }
        $stmt->bindParam(':id',           $datos['id']);
        $stmt->bindParam(':categoria_id', $datos['categoria_id']);
        $stmt->bindParam(':codigo',       $datos['codigo']);
        $stmt->bindParam(':nombre',       $datos['nombre']);
        $stmt->bindParam(':precio_compra',$datos['precio_compra']);
        $stmt->bindParam(':precio_venta', $datos['precio_venta']);
        $stmt->bindParam(':stock',        $datos['stock']);
        return $stmt->execute();
    }

    public function cambiarEstado($id, $estado) {
        $stmt = $this->conn->prepare("UPDATE productos SET estado = :estado WHERE id = :id");
        $stmt->bindParam(':estado', $estado);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function actualizarStock($id, $cantidad, $operacion = 'restar') {
        if ($operacion === 'restar') {
            $stmt = $this->conn->prepare("UPDATE productos SET stock = stock - :cantidad WHERE id = :id");
        } else {
            $stmt = $this->conn->prepare("UPDATE productos SET stock = stock + :cantidad WHERE id = :id");
        }
        $stmt->bindParam(':cantidad', $cantidad, PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
