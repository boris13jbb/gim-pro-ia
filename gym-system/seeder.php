<?php
require_once __DIR__ . '/app/config/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // 1. Disable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 0;");
    
    // 2. Truncate tables (Keep usuarios and configuracion intact)
    $tables = ['socios', 'planes', 'suscripciones', 'asistencias', 'gastos', 'categorias', 'productos', 'ventas', 'detalle_ventas', 'cajas'];
    foreach ($tables as $table) {
        $conn->exec("TRUNCATE TABLE `$table`;");
    }
    
    // 3. SEED PLANES (10)
    $stmtPlan = $conn->prepare("INSERT INTO planes (nombre, descripcion, precio, duracion_dias) VALUES (?, ?, ?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $stmtPlan->execute(["Plan Dummy $i", "Descripción del plan $i", rand(50, 300), rand(30, 365)]);
    }
    
    // 4. SEED SOCIOS (10)
    $stmtSocio = $conn->prepare("INSERT INTO socios (nombre, dni, email, telefono, estado) VALUES (?, ?, ?, ?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $stmtSocio->execute(["Socio Prueba $i", "888800" . str_pad($i, 2, '0', STR_PAD_LEFT), "socio$i@test.com", "99988877" . $i, 'activo']);
    }
    
    // 5. SEED SUSCRIPCIONES (10) (Assign each socio to a random plan)
    $stmtSus = $conn->prepare("INSERT INTO suscripciones (socio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES (?, ?, ?, ?, ?)");
    $stmtConfig = $conn->query("SELECT id FROM planes");
    $planesIds = $stmtConfig->fetchAll(PDO::FETCH_COLUMN);
    $stmtSocioIds = $conn->query("SELECT id FROM socios");
    $sociosIds = $stmtSocioIds->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($sociosIds as $sId) {
        $pId = $planesIds[array_rand($planesIds)];
        $fechaInicio = date('Y-m-d', strtotime('-' . rand(1, 60) . ' days'));
        $fechaFin = date('Y-m-d', strtotime($fechaInicio . ' + 30 days')); // Approx 1 month
        $estado = (strtotime($fechaFin) >= time()) ? 'activa' : 'vencida';
        $stmtSus->execute([$sId, $pId, $fechaInicio, $fechaFin, $estado]);
    }
    
    // 6. SEED ASISTENCIAS (10 recent ones)
    $stmtAsis = $conn->prepare("INSERT INTO asistencias (socio_id, fecha_hora) VALUES (?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $sId = $sociosIds[array_rand($sociosIds)];
        $fecha = date('Y-m-d', strtotime('-' . rand(0, 5) . ' days'));
        $hora = sprintf("%02d:%02d:00", rand(6, 22), rand(0, 59));
        $stmtAsis->execute([$sId, "$fecha $hora"]);
    }

    // 7. SEED CATEGORIAS & PRODUCTOS (10)
    $conn->exec("INSERT INTO categorias (nombre, estado) VALUES ('Bebidas', 'activo'), ('Suplementos', 'activo'), ('Accesorios', 'activo');");
    $stmtCat = $conn->query("SELECT id FROM categorias");
    $cats = $stmtCat->fetchAll(PDO::FETCH_COLUMN);
    
    $stmtProd = $conn->prepare("INSERT INTO productos (codigo, nombre, categoria_id, precio_compra, precio_venta, stock, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $cId = $cats[array_rand($cats)];
        $precioCompra = rand(10, 50);
        $precioVenta = $precioCompra + rand(10, 30);
        $stmtProd->execute(["PROD-00$i", "Producto Prueba $i", $cId, $precioCompra, $precioVenta, rand(10, 100), 'activo']);
    }

    // 8. SEED CAJA & VENTAS (10 sales)
    $conn->exec("INSERT INTO cajas (usuario_id, monto_inicial, monto_final, estado, fecha_apertura) VALUES (1, 100.00, 100.00, 'abierta', NOW());");
    $cajaId = $conn->lastInsertId();
    
    $stmtVenta = $conn->prepare("INSERT INTO ventas (socio_id, caja_id, total, metodo_pago, fecha) VALUES (?, ?, ?, ?, ?)");
    $stmtDetalle = $conn->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    
    $stmtProdIds = $conn->query("SELECT id, precio_venta FROM productos");
    $prods = $stmtProdIds->fetchAll(PDO::FETCH_ASSOC);
    
    $metodos = ['efectivo', 'tarjeta', 'transferencia'];
    for ($i = 1; $i <= 10; $i++) {
        $sId = $sociosIds[array_rand($sociosIds)];
        $p = $prods[array_rand($prods)];
        $qty = rand(1, 3);
        $subtotal = $qty * $p['precio_venta'];
        $fecha = date('Y-m-d H:i:s', strtotime('-' . rand(0, 5) . ' days'));
        
        $stmtVenta->execute([$sId, $cajaId, $subtotal, $metodos[array_rand($metodos)], $fecha]);
        $ventaId = $conn->lastInsertId();
        
        $stmtDetalle->execute([$ventaId, $p['id'], $qty, $p['precio_venta'], $subtotal]);
    }
    
    // 9. SEED GASTOS (10)
    $stmtGasto = $conn->prepare("INSERT INTO gastos (descripcion, monto, fecha) VALUES (?, ?, ?)");
    for ($i = 1; $i <= 10; $i++) {
        $fecha = date('Y-m-d', strtotime('-' . rand(0, 30) . ' days'));
        $stmtGasto->execute(["Gasto de prueba $i", rand(50, 500), $fecha]);
    }
    
    // Re-enable foreign key checks
    $conn->exec("SET FOREIGN_KEY_CHECKS = 1;");
    
    echo "¡Base de datos limpiada y poblada con 10 registros por módulo exitosamente!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
