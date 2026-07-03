<?php
/**
 * Seeder visual — inserta 10 registros por módulo con fechas distribuidas
 * en los últimos 6 meses para que el dashboard refleje actividad real.
 *
 * USO:  http://gym-system.test/seed_data.php
 * SEGURIDAD: bórralo después de ejecutarlo.
 */
require_once __DIR__ . '/../app/config/Database.php';

function fechaAleatoriaUltimosNMeses($n = 6) {
    $hoy = time();
    $hace = strtotime("-$n months", $hoy);
    $ts = rand($hace, $hoy);
    return date('Y-m-d', $ts);
}
function fechaHoraAleatoriaUltimosNMeses($n = 6) {
    return fechaAleatoriaUltimosNMeses($n) . sprintf(' %02d:%02d:%02d', rand(7,22), rand(0,59), rand(0,59));
}

$ejecutar = isset($_GET['run']);
$reset    = isset($_GET['reset']);
$log = [];

if ($ejecutar) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        if (!$conn) throw new Exception('No se pudo conectar a la BD.');

        $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");

        if ($reset) {
            $tablas = ['detalle_ventas','ventas','asistencias','suscripciones','gastos','cajas',
                       'productos','categorias','planes','socios',
                       'comprobantes_detalle','comprobantes_electronicos','sri_log','sri_series'];
            foreach ($tablas as $t) {
                try { $conn->exec("TRUNCATE TABLE `$t`"); $log[] = "[RESET] $t"; }
                catch (Exception $e) { $log[] = "[skip] $t (no existe aún)"; }
            }
        }

        /* =====================================================
           1) PLANES
           ===================================================== */
        $planes = [
            ['Mensual Básico',      80,  30,  'Acceso 6:00-22:00, área de pesas'],
            ['Mensual Premium',     150, 30,  'Incluye clases grupales y sauna'],
            ['Trimestral Básico',   210, 90,  '3 meses con 10% descuento'],
            ['Trimestral Premium',  390, 90,  'Plan completo trimestral'],
            ['Semestral Fitness',   720, 180, '6 meses + entrenador 2 veces/mes'],
            ['Anual Full',         1300, 365, '12 meses ilimitados + nutricionista'],
            ['Day Pass',             15, 1,   'Pase por un día'],
            ['Semanal Estudiante',   35, 7,   'Solo con carnet universitario'],
            ['Plan Empresarial',    600, 180, 'Plan corporativo para 1 colaborador'],
            ['CrossFit Mensual',    200, 30,  'Box exclusivo de CrossFit']
        ];
        $st = $conn->prepare("INSERT INTO planes (nombre, precio, duracion_dias, descripcion, estado) VALUES (?,?,?,?, 'activo')");
        foreach ($planes as $p) { $st->execute([$p[0], $p[1], $p[2], $p[3]]); }
        $log[] = "[OK] planes: 10";

        /* =====================================================
           2) SOCIOS
           ===================================================== */
        $socios = [
            ['Carlos Mendoza Rojas',     '45123456', 'carlos.mendoza@mail.com',    '987654321'],
            ['Lucía Fernández Torres',   '45234567', 'lucia.fernandez@mail.com',   '987111222'],
            ['Jorge Silva Quispe',       '45345678', 'jorge.silva@mail.com',       '987222333'],
            ['María Castillo Ramos',     '45456789', 'maria.castillo@mail.com',    '987333444'],
            ['Diego Vargas Huamán',      '45567890', 'diego.vargas@mail.com',      '987444555'],
            ['Andrea Paredes Soto',      '45678901', 'andrea.paredes@mail.com',    '987555666'],
            ['Fernando Ruiz Cárdenas',   '45789012', 'fernando.ruiz@mail.com',     '987666777'],
            ['Patricia Aguilar Núñez',   '45890123', 'patricia.aguilar@mail.com',  '987777888'],
            ['Roberto Salazar Vega',     '45901234', 'roberto.salazar@mail.com',   '987888999'],
            ['Camila Reyes Apaza',       '46012345', 'camila.reyes@mail.com',      '987999000'],
        ];
        $st = $conn->prepare("INSERT INTO socios (nombre, dni, email, telefono, estado) VALUES (?,?,?,?, 'activo')");
        foreach ($socios as $s) { $st->execute($s); }
        $log[] = "[OK] socios: 10";

        $sociosIds = $conn->query("SELECT id FROM socios")->fetchAll(PDO::FETCH_COLUMN);
        $planesIds = $conn->query("SELECT id, precio, duracion_dias FROM planes")->fetchAll(PDO::FETCH_ASSOC);

        /* =====================================================
           3) SUSCRIPCIONES (10) — distribuidas últimos 6 meses
           ===================================================== */
        $st = $conn->prepare("INSERT INTO suscripciones (socio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES (?,?,?,?,?)");
        foreach ($sociosIds as $sId) {
            $p = $planesIds[array_rand($planesIds)];
            $fIni = fechaAleatoriaUltimosNMeses(6);
            $fFin = date('Y-m-d', strtotime("$fIni + {$p['duracion_dias']} days"));
            $estado = (strtotime($fFin) >= time()) ? 'activa' : 'vencida';
            $st->execute([$sId, $p['id'], $fIni, $fFin, $estado]);
        }
        $log[] = "[OK] suscripciones: 10";

        /* =====================================================
           4) ASISTENCIAS — 30 entradas en los últimos 2 meses
           ===================================================== */
        $st = $conn->prepare("INSERT INTO asistencias (socio_id, fecha_hora) VALUES (?, ?)");
        for ($i = 1; $i <= 30; $i++) {
            $st->execute([
                $sociosIds[array_rand($sociosIds)],
                fechaHoraAleatoriaUltimosNMeses(2)
            ]);
        }
        $log[] = "[OK] asistencias: 30";

        /* =====================================================
           5) CATEGORÍAS + PRODUCTOS
           ===================================================== */
        $categorias = ['Bebidas', 'Suplementos', 'Accesorios', 'Indumentaria', 'Snacks'];
        $st = $conn->prepare("INSERT INTO categorias (nombre, estado) VALUES (?, 'activo')");
        foreach ($categorias as $c) { $st->execute([$c]); }
        $catsIds = $conn->query("SELECT id FROM categorias")->fetchAll(PDO::FETCH_COLUMN);
        $log[] = "[OK] categorias: " . count($categorias);

        $productos = [
            ['BEB-001','Agua mineral 625ml',  3,  5],
            ['BEB-002','Gatorade 500ml',      6, 10],
            ['BEB-003','Bebida proteica',     15, 22],
            ['SUP-001','Creatina 300g',       70, 110],
            ['SUP-002','Proteína Whey 1kg',  120, 180],
            ['SUP-003','BCAA 250g',           55, 85],
            ['ACC-001','Guantes de gym',      25, 45],
            ['ACC-002','Cinturón lumbar',     60, 95],
            ['IND-001','Polo Iron Gym',       30, 55],
            ['IND-002','Short deportivo',     35, 60],
        ];
        $st = $conn->prepare("INSERT INTO productos (codigo, nombre, categoria_id, precio_compra, precio_venta, stock, estado) VALUES (?,?,?,?,?,?, 'activo')");
        foreach ($productos as $p) {
            $st->execute([$p[0], $p[1], $catsIds[array_rand($catsIds)], $p[2], $p[3], rand(20, 100)]);
        }
        $log[] = "[OK] productos: 10";
        $productosFull = $conn->query("SELECT id, precio_venta FROM productos")->fetchAll(PDO::FETCH_ASSOC);

        /* =====================================================
           6) CAJAS abiertas en distintas fechas
           ===================================================== */
        $st = $conn->prepare("INSERT INTO cajas (usuario_id, monto_inicial, monto_final, estado, fecha_apertura, fecha_cierre, total_ventas)
                              VALUES (1, 100.00, ?, 'cerrada', ?, ?, ?)");
        $cajasIds = [];
        for ($i = 1; $i <= 6; $i++) {
            $fechaAp = fechaHoraAleatoriaUltimosNMeses(6);
            $fechaCi = date('Y-m-d H:i:s', strtotime($fechaAp . ' +10 hours'));
            $totalV  = rand(150, 800);
            $st->execute([100 + $totalV, $fechaAp, $fechaCi, $totalV]);
            $cajasIds[] = $conn->lastInsertId();
        }
        $conn->exec("INSERT INTO cajas (usuario_id, monto_inicial, estado, fecha_apertura) VALUES (1, 100.00, 'abierta', NOW())");
        $cajasIds[] = $conn->lastInsertId();
        $log[] = "[OK] cajas: " . count($cajasIds);

        /* =====================================================
           7) VENTAS + DETALLE — distribuidas en últimos 6 meses
           ===================================================== */
        $stV = $conn->prepare("INSERT INTO ventas (socio_id, caja_id, total, metodo_pago, fecha) VALUES (?,?,?,?,?)");
        $stD = $conn->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?,?,?,?,?)");
        $metodos = ['efectivo','tarjeta','transferencia'];
        for ($i = 1; $i <= 25; $i++) {
            $totalVenta = 0;
            $items = [];
            $nItems = rand(1, 3);
            for ($k = 0; $k < $nItems; $k++) {
                $p = $productosFull[array_rand($productosFull)];
                $q = rand(1, 3);
                $sub = $q * $p['precio_venta'];
                $items[] = [$p['id'], $q, $p['precio_venta'], $sub];
                $totalVenta += $sub;
            }
            $fechaV = fechaHoraAleatoriaUltimosNMeses(6);
            $stV->execute([
                $sociosIds[array_rand($sociosIds)],
                $cajasIds[array_rand($cajasIds)],
                $totalVenta,
                $metodos[array_rand($metodos)],
                $fechaV
            ]);
            $vId = $conn->lastInsertId();
            foreach ($items as $it) {
                $stD->execute([$vId, $it[0], $it[1], $it[2], $it[3]]);
            }
        }
        $log[] = "[OK] ventas: 25 (con detalles)";

        /* =====================================================
           8) GASTOS — distribuidos en últimos 6 meses
           ===================================================== */
        $conceptos = [
            'Pago servicios eléctricos',
            'Compra de implementos',
            'Pago de agua',
            'Mantenimiento de máquinas',
            'Sueldo de entrenador',
            'Productos de limpieza',
            'Renta del local',
            'Publicidad redes sociales',
            'Reparación aire acondicionado',
            'Compra de música/licencia',
        ];
        $st = $conn->prepare("INSERT INTO gastos (descripcion, monto, fecha) VALUES (?,?,?)");
        foreach ($conceptos as $c) {
            $st->execute([$c, rand(80, 1200), fechaAleatoriaUltimosNMeses(6)]);
        }
        $log[] = "[OK] gastos: " . count($conceptos);

        /* =====================================================
           9) COMPROBANTES ELECTRÓNICOS SRI (10) en distintos estados
           ===================================================== */
        $tieneSri = false;
        try {
            $conn->query("SELECT 1 FROM comprobantes_electronicos LIMIT 1");
            $tieneSri = true;
        } catch (Exception $e) { /* tabla no existe */ }

        if ($tieneSri) {
            $stC = $conn->prepare("INSERT INTO comprobantes_electronicos
                (origen_tipo, origen_id, tipo_doc, serie, correlativo, fecha_emision, hora_emision,
                 emisor_ruc, emisor_razon,
                 cliente_tipo_doc, cliente_num_doc, cliente_razon,
                 moneda, gravadas, igv, total, total_letras,
                 forma_pago, metodo_pago, estado_sri, cdr_codigo, cdr_descripcion, xml_hash, usuario_id)
                VALUES
                ('manual', NULL, ?, ?, ?, ?, ?,
                 '1790000000001','EMPRESA DE PRUEBAS ECUADOR',
                 ?,?,?,
                 'USD', ?, ?, ?, ?,
                 'Contado','efectivo', ?, ?, ?, ?, 1)");
            $stD = $conn->prepare("INSERT INTO comprobantes_detalle
                (comprobante_id, linea, codigo, descripcion, unidad, cantidad,
                 valor_unitario, precio_unitario, subtotal, igv_linea, total_linea, tipo_afectacion)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,'10')");

            $tiposDoc      = ['01','01','01','01','01','01','01','01','04','01']; // mayoría facturas
            $estados       = ['autorizado','autorizado','autorizado','autorizado','autorizado','pendiente','no_autorizado','autorizado','autorizado','autorizado'];
            $codigos       = ['0','0','0','0','0','','2335','0','0','0'];
            $descs         = ['La Factura numérica…','La Boleta numérica…','La Boleta numérica…','La Boleta numérica…','La Factura numérica…','Pendiente de envío','El XML está mal formado','La Boleta numérica…','La Nota de Crédito…','La Boleta numérica…'];

            $correlatBoleta = 1;
            $correlatFactura = 1;
            $correlatNC = 1;

            for ($i = 0; $i < 10; $i++) {
                $tipo = $tiposDoc[$i];
                if      ($tipo === '01') { $serie='001001'; $corr = $correlatFactura++; }
                else if ($tipo === '04') { $serie='001001'; $corr = $correlatNC++; }
                else                     { $serie='001001'; $corr = $correlatBoleta++; }

                $fecha = fechaAleatoriaUltimosNMeses(5);
                $hora  = sprintf('%02d:%02d:%02d', rand(8,21), rand(0,59), rand(0,59));
                $cliTipoDoc = ($tipo === '01') ? '04' : '05';
                $cliNum     = ($tipo === '01') ? '1700000000001' : str_pad(rand(1000000000,9999999999), 10, '0', STR_PAD_LEFT);
                $cliRazon   = $sociosIds ? $socios[array_rand($socios)][0] : 'CLIENTE VARIOS';
                if ($tipo === '01') $cliRazon = ['CONSTRUCTORA ABC SAC','SERVICIOS LIMA EIRL','GRUPO ANDINO SAC','EMPRESA NORTE SRL'][array_rand([0,1,2,3])];

                // Importes
                $gravadas = round(rand(800, 12000) / 100, 2); // 8 - 120 usd
                $igv      = round($gravadas * 0.15, 2); // Ecuador IVA 15% (o 12%, asumimos 15)
                $total    = round($gravadas + $igv, 2);

                $hash = bin2hex(random_bytes(10));
                $stC->execute([
                    $tipo, $serie, $corr, $fecha, $hora,
                    $cliTipoDoc, $cliNum, $cliRazon,
                    $gravadas, $igv, $total, "SON: $total SOLES",
                    $estados[$i], $codigos[$i], $descs[$i], $hash
                ]);
                $cid = $conn->lastInsertId();

                // detalle: 1 ítem
                $stD->execute([
                    $cid, 1, 'ITM-001', 'Servicio / producto de prueba',
                    'ZZ', 1, $gravadas, $total, $gravadas, $igv, $total
                ]);
            }

            // Actualizar correlativos en sri_series
            try {
                $conn->exec("UPDATE sri_series SET correlativo = (SELECT IFNULL(MAX(correlativo),0) FROM comprobantes_electronicos
                              WHERE comprobantes_electronicos.tipo_doc = sri_series.tipo_doc
                                AND comprobantes_electronicos.serie    = sri_series.serie)");
            } catch (Exception $e) { /* ignore */ }
            $log[] = "[OK] comprobantes_electronicos: 10";
        } else {
            $log[] = "[!!] comprobantes_electronicos: tabla NO existe";
        }

        $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
        $log[] = "\n=== SEED COMPLETADO ===";

    } catch (Exception $e) {
        $log[] = "[FATAL] " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Seeder — gym-system</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #0F172A, #1E293B); min-height: 100vh; color: #E5E7EB; padding: 2rem 0; }
    .card-mig { background: #fff; color: #1F2937; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
    pre.log { background: #0B1120; color: #94D3A2; padding: 1rem; border-radius: 8px; font-size: .85rem; max-height: 500px; overflow:auto; }
    .ok-line { color: #10B981; }
    .ko-line { color: #EF4444; }
    .warn-line { color: #F59E0B; }
</style>
</head>
<body>
<div class="container">
    <div class="card card-mig p-4">
        <h3 class="fw-bold"><i class="fas fa-database text-primary"></i> Seeder de datos demo — gym-system</h3>
        <p class="text-muted mb-2">Inserta datos repartidos en los últimos 6 meses para que el dashboard tenga gráficos con sentido.</p>

<?php if (!$ejecutar): ?>
        <div class="alert alert-info">
            <strong>¿Qué se va a insertar?</strong>
            <ul class="mb-0">
                <li>10 <strong>planes</strong> realistas (mensual, anual, CrossFit, etc.)</li>
                <li>10 <strong>socios</strong> con nombres peruanos</li>
                <li>10 <strong>suscripciones</strong> repartidas en 6 meses</li>
                <li>30 <strong>asistencias</strong> de los últimos 2 meses</li>
                <li>5 <strong>categorías</strong> + 10 <strong>productos</strong> reales</li>
                <li>7 <strong>cajas</strong> (6 cerradas en fechas distintas + 1 abierta hoy)</li>
                <li>25 <strong>ventas</strong> con detalle, distribuidas en 6 meses</li>
                <li>10 <strong>gastos</strong> con descripciones realistas</li>
                <li>10 <strong>comprobantes SRI</strong> (mezcla de facturas y NC, distintos estados)</li>
            </ul>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="?run=1" class="btn btn-primary btn-lg">
                <i class="fas fa-play"></i> Insertar (sin borrar lo existente)
            </a>
            <a href="?run=1&reset=1" class="btn btn-danger btn-lg"
               onclick="return confirm('Esto BORRARÁ todos los socios, planes, ventas, gastos, suscripciones, asistencias, productos y comprobantes electrónicos antes de insertar los demo. ¿Continuar?')">
                <i class="fas fa-trash"></i> Reset + Insertar (vacía las tablas primero)
            </a>
            <a href="/home/index" class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-arrow-left"></i> Cancelar
            </a>
        </div>

<?php else: ?>
        <pre class="log"><?php foreach ($log as $l) {
            $cls = 'ok-line';
            if (strpos($l, '[FATAL]') !== false || strpos($l, '[!!]') !== false) $cls = 'ko-line';
            if (strpos($l, '[skip]') !== false) $cls = 'warn-line';
            if (strpos($l, '[RESET]') !== false) $cls = 'warn-line';
            echo "<span class='$cls'>" . htmlspecialchars($l) . "</span>\n";
        } ?></pre>

        <div class="alert alert-danger mt-3">
            <i class="fas fa-shield-alt"></i> <strong>IMPORTANTE:</strong> elimina este archivo (<code>public/seed_data.php</code>) ahora — es un script de instalación.
        </div>

        <div class="d-flex gap-2">
            <a href="/home/index" class="btn btn-success btn-lg"><i class="fas fa-tachometer-alt"></i> Ver el Dashboard</a>
            <a href="/facturacionelectronica/index" class="btn btn-primary btn-lg"><i class="fas fa-file-invoice"></i> Bandeja SRI</a>
            <a href="/suscripciones/index" class="btn btn-outline-primary btn-lg"><i class="fas fa-id-card"></i> Suscripciones</a>
            <a href="/pos/historial" class="btn btn-outline-primary btn-lg"><i class="fas fa-history"></i> Ventas</a>
        </div>
<?php endif; ?>

    </div>
</div>
</body>
</html>
