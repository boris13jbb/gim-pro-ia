<?php
class Socio {
    private $conn;
    private $table_name = "socios";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Listar todos los socios
    public function obtenerTodos() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Obtener un socio por ID (para editar)
    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Insertar nuevo socio (Incluye Foto)
    public function agregar($datos) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nombre, dni, email, telefono, estado, foto) 
                 VALUES (:nombre, :dni, :email, :telefono, :estado, :foto)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":dni", $datos['dni']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":telefono", $datos['telefono']);
        $stmt->bindParam(":estado", $datos['estado']);
        $stmt->bindParam(":foto", $datos['foto']); 

        return $stmt->execute();
    }

    // --- CORRECCIÓN IMPORTANTE AQUÍ ---
    // Actualizar datos del socio (Incluyendo la Foto)
    public function actualizar($datos) {
        $query = "UPDATE " . $this->table_name . " 
                 SET nombre = :nombre, dni = :dni, email = :email, 
                     telefono = :telefono, estado = :estado, foto = :foto 
                 WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $datos['nombre']);
        $stmt->bindParam(":dni", $datos['dni']);
        $stmt->bindParam(":email", $datos['email']);
        $stmt->bindParam(":telefono", $datos['telefono']);
        $stmt->bindParam(":estado", $datos['estado']);
        $stmt->bindParam(":foto", $datos['foto']); // <--- ¡Esto faltaba para guardar el cambio!
        $stmt->bindParam(":id", $datos['id']);

        return $stmt->execute();
    }

    // Cambiar estado (Activar/Desactivar)
    public function cambiarEstado($id, $estado) {
        $query = "UPDATE " . $this->table_name . " SET estado = :estado WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":estado", $estado);
        $stmt->bindParam(":id", $id);
        
        return $stmt->execute();
    }

    // Obtener socios cuya suscripción vence en los próximos N días
    public function obtenerVencimientosProximos($dias = 7) {
        $query = "SELECT s.id, s.nombre, s.telefono, s.whatsapp_api_key,
                         sus.fecha_fin, p.nombre as nombre_plan,
                         DATEDIFF(sus.fecha_fin, CURDATE()) as dias_restantes
                  FROM socios s
                  INNER JOIN suscripciones sus ON sus.socio_id = s.id
                  INNER JOIN planes p ON sus.plan_id = p.id
                  WHERE sus.estado = 'activa'
                    AND sus.fecha_fin >= CURDATE()
                    AND sus.fecha_fin <= DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                    AND s.estado = 'activo'
                  ORDER BY sus.fecha_fin ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Guardar la WhatsApp API Key de un socio
    public function guardarApiKey($id, $apikey) {
        $stmt = $this->conn->prepare("UPDATE socios SET whatsapp_api_key = :apikey WHERE id = :id");
        $stmt->bindParam(':apikey', $apikey);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}