<?php
class Dashboard {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // 1. KPI: Socios Activos
    public function contarSociosActivos() {
        $query = "SELECT COUNT(*) as total FROM socios WHERE estado = 'activo'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    // 2. KPI: Ingresos (Suscripciones) del mes
    public function ingresosEsteMes() {
        $mes_actual = date('m');
        $anio_actual = date('Y');
        
        $query = "SELECT SUM(p.precio) as total 
                  FROM suscripciones s 
                  INNER JOIN planes p ON s.plan_id = p.id 
                  WHERE MONTH(s.fecha_inicio) = :mes AND YEAR(s.fecha_inicio) = :anio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes_actual);
        $stmt->bindParam(':anio', $anio_actual);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }

    // 3. KPI NUEVO: Egresos (Gastos) del mes
    public function egresosEsteMes() {
        $mes_actual = date('m');
        $anio_actual = date('Y');
        
        $query = "SELECT SUM(monto) as total FROM gastos 
                  WHERE MONTH(fecha) = :mes AND YEAR(fecha) = :anio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':mes', $mes_actual);
        $stmt->bindParam(':anio', $anio_actual);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }

    // 4. Tabla: Vencimientos cercanos
    public function obtenerVencimientosCercanos() {
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime($hoy . ' + 7 days'));

        $query = "SELECT s.fecha_fin, so.nombre as socio, p.nombre as plan 
                  FROM suscripciones s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE s.fecha_fin BETWEEN :hoy AND :limite
                  AND s.estado = 'activa'
                  ORDER BY s.fecha_fin ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoy', $hoy);
        $stmt->bindParam(':limite', $limite);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Gráfico Barras: Ventas últimos meses
    public function ventasUltimosMeses() {
        $query = "SELECT MONTHNAME(fecha_inicio) as mes, COUNT(*) as cantidad 
                  FROM suscripciones 
                  WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY MONTH(fecha_inicio), MONTHNAME(fecha_inicio) 
                  ORDER BY MIN(fecha_inicio) ASC"; 
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 6. Gráfico Pastel: Ventas por Plan
    public function ventasPorPlan() {
        $query = "SELECT p.nombre, COUNT(*) as cantidad 
                  FROM suscripciones s 
                  INNER JOIN planes p ON s.plan_id = p.id 
                  GROUP BY p.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 7. Filtrar Vencimientos cercanos por planes y/o búsqueda de socio
    public function filtrarVencimientos($planNombres = [], $busqueda = '') {
        $hoy = date('Y-m-d');
        $limite = date('Y-m-d', strtotime($hoy . ' + 7 days'));

        $query = "SELECT s.fecha_fin, so.nombre as socio, p.nombre as plan
                  FROM suscripciones s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE s.fecha_fin BETWEEN :hoy AND :limite
                  AND s.estado = 'activa'";

        if (!empty($busqueda)) {
            $query .= " AND so.nombre LIKE :busqueda";
        }
        if (!empty($planNombres)) {
            $placeholders = implode(',', array_map(fn($i) => ":pn$i", array_keys($planNombres)));
            $query .= " AND p.nombre IN ($placeholders)";
        }
        $query .= " ORDER BY s.fecha_fin ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoy', $hoy);
        $stmt->bindParam(':limite', $limite);
        if (!empty($busqueda)) {
            $like = "%$busqueda%";
            $stmt->bindParam(':busqueda', $like);
        }
        if (!empty($planNombres)) {
            foreach ($planNombres as $i => $nombre) {
                $stmt->bindValue(":pn$i", $nombre, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 8. Contar socios activos con filtro por planes
    public function contarSociosActivosFiltrado($planNombres = []) {
        $query = "SELECT COUNT(DISTINCT s.socio_id) as total
                  FROM suscripciones s
                  INNER JOIN socios so ON s.socio_id = so.id
                  INNER JOIN planes p ON s.plan_id = p.id
                  WHERE so.estado = 'activo' AND s.estado = 'activa'";

        if (!empty($planNombres)) {
            $placeholders = implode(',', array_map(fn($i) => ":pn$i", array_keys($planNombres)));
            $query .= " AND p.nombre IN ($placeholders)";
        }

        $stmt = $this->conn->prepare($query);
        if (!empty($planNombres)) {
            foreach ($planNombres as $i => $nombre) {
                $stmt->bindValue(":pn$i", $nombre, PDO::PARAM_STR);
            }
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // ============================================================
    //  KPIs SRI — Facturación Electrónica (mes actual)
    // ============================================================
    public function kpisSriMes() {
        try {
            $stmt = $this->conn->query(
                "SELECT
                   COUNT(*)                                              AS total,
                   SUM(estado_sri='autorizado')                          AS aceptados,
                   SUM(estado_sri='pendiente')                           AS pendientes,
                   SUM(estado_sri IN ('no_autorizado','devuelta','error')) AS rechazados,
                   SUM(estado_sri='anulado')                             AS anulados,
                   SUM(CASE WHEN estado_sri='autorizado' THEN igv   ELSE 0 END) AS igv_mes,
                   SUM(CASE WHEN estado_sri='autorizado' THEN total ELSE 0 END) AS total_mes,
                   SUM(CASE WHEN tipo_doc='01' AND estado_sri='autorizado' THEN 1 ELSE 0 END) AS facturas,
                   SUM(CASE WHEN tipo_doc='03' AND estado_sri='autorizado' THEN 1 ELSE 0 END) AS boletas,
                   SUM(CASE WHEN tipo_doc='04' AND estado_sri='autorizado' THEN 1 ELSE 0 END) AS ncreditos
                 FROM comprobantes_electronicos
                 WHERE fecha_emision >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')"
            );
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return []; // si aún no se corrió la migración SRI
        }
    }

    /** Emisión diaria del mes actual (para gráfica). */
    public function emisionDiariaSri() {
        try {
            $stmt = $this->conn->query(
                "SELECT DATE(fecha_emision) AS dia, COUNT(*) AS cant, SUM(total) AS monto
                 FROM comprobantes_electronicos
                 WHERE fecha_emision >= DATE_FORMAT(CURRENT_DATE,'%Y-%m-01')
                   AND estado_sri='autorizado'
                 GROUP BY DATE(fecha_emision) ORDER BY dia ASC"
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}