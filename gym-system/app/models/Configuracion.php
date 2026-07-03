<?php
class Configuracion {
    private $conn;
    private $table_name = "configuracion";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function obtenerDatos() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // --- ACTUALIZADO CON MONEDA ---
    public function actualizar($datos) {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre_sistema = :nombre, 
                     ruc = :ruc, 
                     direccion = :dir, 
                     telefono = :tel, 
                     email = :email, 
                     logo = :logo,
                     moneda = :moneda
                 WHERE id = 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":ruc", $datos['ruc']);
        $stmt->bindParam(":dir", $datos['direccion']);
        $stmt->bindParam(":tel", $datos['telefono']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":logo", $datos['logo']);
        $stmt->bindParam(":moneda", $datos['moneda']); // Nuevo
        
        return $stmt->execute();
    }

    public static function getInfo() {
        $database = new Database();
        $conn = $database->getConnection();
        $query = "SELECT * FROM configuracion WHERE id = 1 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Actualiza únicamente la configuración SRI Ecuador (facturación electrónica).
     */
    public function actualizarSri($datos) {
        $query = "UPDATE " . $this->table_name . " SET
            razon_social          = :razon_social,
            nombre_comercial      = :nombre_comercial,
            sri_ambiente          = :sri_ambiente,
            sri_establecimiento   = :sri_establecimiento,
            sri_punto_emision     = :sri_punto_emision,
            sri_certificado_p12   = :sri_certificado_p12,
            sri_certificado_clave = :sri_certificado_clave,
            iva_tasa              = :iva_tasa,
            incluye_iva           = :incluye_iva
            WHERE id = 1";
        $stmt = $this->conn->prepare($query);
        foreach ($datos as $k => $v) {
            $stmt->bindValue(':'.$k, $v);
        }
        return $stmt->execute();
    }
}