<?php
$db = new PDO('mysql:host=localhost;dbname=ec_gym_system', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$columns = [
    'ruc' => 'VARCHAR(20) NULL',
    'direccion' => 'VARCHAR(255) NULL',
    'telefono' => 'VARCHAR(20) NULL',
    'email' => 'VARCHAR(100) NULL',
    'logo' => 'VARCHAR(255) NULL',
    'moneda' => "VARCHAR(10) NULL DEFAULT 'S/'"
];

foreach ($columns as $name => $type) {
    try {
        $db->exec("ALTER TABLE configuracion ADD COLUMN $name $type");
        echo "Column $name added.\n";
    } catch(PDOException $e) {
        echo "Column $name exists or error: " . $e->getMessage() . "\n";
    }
}

try {
    $db->exec("INSERT INTO configuracion (id, nombre_sistema, moneda) VALUES (1, 'Iron Gym', 'S/') ON DUPLICATE KEY UPDATE id=1");
    echo "Base row initialized.\n";
} catch(PDOException $e) {}

echo "Done.\n";
