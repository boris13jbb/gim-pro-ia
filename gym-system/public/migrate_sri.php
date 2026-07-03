<?php
/**
 * Migrador SRI Ecuador - ejecuta el archivo SQL contra la BD configurada.
 *
 * USO:  http://gym-system.test/migrate_sri.php
 *
 * SEGURIDAD: bórralo después de ejecutarlo.
 */
require_once __DIR__ . '/../app/config/Database.php';

$archivos = [
    __DIR__ . '/../app/config/migration_sri.sql',
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Migración SRI Ecuador — gym-system</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body { background: linear-gradient(135deg, #0F172A, #1E293B); min-height: 100vh; color: #E5E7EB; }
    .card-mig { background: #fff; color: #1F2937; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.4); }
    pre.log { background: #0B1120; color: #94D3A2; padding: 1rem; border-radius: 8px; font-size: .82rem; max-height: 460px; overflow:auto; }
    .ok { color: #10B981; font-weight: bold; }
    .ko { color: #EF4444; font-weight: bold; }
    .warn { color: #F59E0B; }
</style>
</head>
<body>
<div class="container py-5">
    <div class="card card-mig p-4">
        <h3 class="fw-bold"><i class="fas fa-database text-success"></i> Migración SRI Ecuador — Facturación Electrónica</h3>
        <p class="text-muted">Aplica el script <code>migration_sri.sql</code> sobre la base de datos <code>ec_gym_system</code>.</p>
        <hr>
<?php

if (!isset($_GET['run'])) {
    // Mostrar prerrequisitos antes de ejecutar
    echo '<p>Se ejecutarán los siguientes scripts en orden:</p><ol>';
    foreach ($archivos as $f) {
        $existe = file_exists($f);
        echo '<li>' . htmlspecialchars(basename($f))
           . ' <span class="' . ($existe?'ok':'ko') . '">'
           . ($existe ? '<i class="fas fa-check"></i> encontrado' : '<i class="fas fa-times"></i> NO encontrado')
           . '</span></li>';
    }
    echo '</ol>';
    echo '<div class="alert alert-warning mt-3"><i class="fas fa-exclamation-triangle"></i>
            Asegúrate de tener un backup de la BD antes de continuar.
            Si ya ejecutaste las migraciones, algunas sentencias <code>ALTER TABLE ... ADD COLUMN</code> fallarán
            (es seguro: el migrador continúa con la siguiente).
          </div>';
    echo '<a href="?run=1" class="btn btn-success btn-lg"><i class="fas fa-play"></i> Ejecutar migraciones SRI</a>';
    echo '</div></div></body></html>';
    exit;
}

// --- Ejecutar migraciones ---
echo '<pre class="log">';

try {
    $db = new Database();
    $conn = $db->getConnection();
    if (!$conn) throw new Exception('No se pudo obtener la conexión.');
    echo "[OK] Conexión a MySQL establecida.\n\n";
} catch (Exception $e) {
    echo '<span class="ko">[FATAL]</span> ' . htmlspecialchars($e->getMessage());
    echo '</pre></div></div></body></html>';
    exit;
}

$totalOk = 0; $totalKo = 0; $totalIgn = 0;

foreach ($archivos as $arch) {
    echo "==========================================================\n";
    echo " Procesando: " . basename($arch) . "\n";
    echo "==========================================================\n";

    if (!file_exists($arch)) {
        echo "<span class='ko'>[FAIL]</span> archivo no encontrado.\n\n";
        continue;
    }
    $sql = file_get_contents($arch);

    // Quitar comentarios -- ... y /* ... */
    $sql = preg_replace('!/\*.*?\*/!s', '', $sql);
    $sql = preg_replace('/^--.*$/m', '', $sql);

    // Partir por ';' al final de línea
    $sentencias = array_filter(array_map('trim', preg_split('/;\s*[\r\n]+/', $sql)));

    $i = 0;
    foreach ($sentencias as $s) {
        $s = trim($s, " \t\n\r\0\x0B;");
        if ($s === '' || stripos($s, 'USE ') === 0) {
            continue;
        }
        $i++;
        $resumen = preg_replace('/\s+/', ' ', mb_substr($s, 0, 80));
        try {
            $conn->exec($s);
            echo "  <span class='ok'>[OK]</span> #$i  $resumen…\n";
            $totalOk++;
        } catch (PDOException $e) {
            $msg = $e->getMessage();
            $cod = $e->getCode();
            // Códigos esperables al re-ejecutar: 1060 col existe, 1061 índice, 1050 tabla existe
            if (preg_match('/Duplicate (column|key)|already exists/i', $msg) || in_array($cod, ['42S21','42S01'])) {
                echo "  <span class='warn'>[IGN]</span> #$i  $resumen…  (ya existe)\n";
                $totalIgn++;
            } else {
                echo "  <span class='ko'>[ERR]</span> #$i  $resumen…\n        " . htmlspecialchars($msg) . "\n";
                $totalKo++;
            }
        }
    }
    echo "\n";
}

echo "==========================================================\n";
echo " RESUMEN\n";
echo "==========================================================\n";
echo "  Sentencias OK     : <span class='ok'>$totalOk</span>\n";
echo "  Sentencias IGNO   : <span class='warn'>$totalIgn</span>  (ya existían)\n";
echo "  Sentencias ERROR  : <span class='ko'>$totalKo</span>\n";

echo '</pre>';

// Verificación rápida del estado
echo '<h5 class="mt-3"><i class="fas fa-clipboard-check text-success"></i> Verificación de tablas</h5>';
$tablas = ['comprobantes_electronicos','comprobantes_detalle','sri_series','sri_log'];
echo '<table class="table table-sm"><thead><tr><th>Tabla</th><th>Estado</th><th>Filas</th></tr></thead><tbody>';
foreach ($tablas as $t) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM `$t`");
        $cant = $stmt->fetchColumn();
        echo "<tr><td><code>$t</code></td><td><span class='text-success fw-bold'><i class='fas fa-check'></i> existe</span></td><td>$cant</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td><code>$t</code></td><td><span class='text-danger fw-bold'><i class='fas fa-times'></i> NO existe</span></td><td>—</td></tr>";
    }
}
echo '</tbody></table>';

// Verificación de columnas nuevas en `configuracion`
echo '<h6 class="mt-3">Columnas SRI en <code>configuracion</code>:</h6>';
try {
    $cols = $conn->query("SHOW COLUMNS FROM configuracion LIKE 'sri_%'")->fetchAll(PDO::FETCH_COLUMN);
    $cols = array_merge($cols, $conn->query("SHOW COLUMNS FROM configuracion LIKE 'iva_%'")->fetchAll(PDO::FETCH_COLUMN));
    if ($cols) {
        echo '<ul class="small">';
        foreach ($cols as $c) echo '<li><code>' . htmlspecialchars($c) . '</code></li>';
        echo '</ul>';
    } else {
        echo '<p class="text-danger">No se detectaron columnas SRI en configuracion.</p>';
    }
} catch (Exception $e) {
    echo '<p class="text-danger">' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<div class="alert alert-danger mt-4">';
echo '<i class="fas fa-shield-alt"></i> <strong>IMPORTANTE:</strong> elimina este archivo (<code>public/migrate_sri.php</code>) una vez finalizada la migración.';
echo '</div>';

echo '<a href="/configuracion/sri" class="btn btn-success"><i class="fas fa-cog"></i> Ir a Configuración SRI</a> ';
echo '<a href="/home/index" class="btn btn-secondary"><i class="fas fa-home"></i> Volver al Dashboard</a>';
?>
        </div>
    </div>
</div>
</body>
</html>
