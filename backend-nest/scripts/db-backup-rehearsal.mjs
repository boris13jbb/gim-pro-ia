/**
 * SAAS-02 — Ensayo completo de backup/restore sobre bases DESECHABLES:
 *
 *   base test (baseline + datos de ensayo) → backup → base restore → validación
 *
 * Uso:
 *   TEST_DATABASE_URL="mysql://usuario:clave@127.0.0.1:3306" \
 *   [MYSQLDUMP_PATH=mysqldump] [MYSQL_CLIENT_PATH=mysql] \
 *   node scripts/db-backup-rehearsal.mjs [--keep]
 *
 * Los datos insertados son FIXTURES de ensayo (no datos reales) y viven solo en
 * la base gim_test_* de origen, que se elimina al final junto con la restaurada
 * y el archivo de backup temporal (salvo --keep).
 */
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { backupDatabase } from './db-backup.mjs';
import { restoreDatabase } from './db-restore.mjs';
import {
  applyMigrations,
  buildDatabaseUrl,
  createDisposableDatabase,
  dropDisposableDatabase,
  getTestServerUrl,
  inspectDatabase,
  runPrisma,
  withConnection,
} from './lib/disposable-database.mjs';

/** Fixtures mínimas que recorren las FK principales (POS, membresías, auth). */
async function seedRehearsalData(serverUrl, database) {
  await withConnection(serverUrl, database, async (conn) => {
    await conn.query(
      "INSERT INTO configuracion (id, nombre_sistema, nombre_comercial, moneda) VALUES (1, 'Ensayo', 'Gym Ensayo', '$')",
    );
    await conn.query(
      "INSERT INTO usuarios (id, nombre, email, password, rol, estado) VALUES (1, 'Staff Ensayo', 'staff.ensayo@gim-test.local', 'hash-no-valido', 'admin', 'activo')",
    );
    await conn.query(
      "INSERT INTO socios (id, nombre, dni, email, estado) VALUES (1, 'Socio Uno', 'T0000001', 'uno@gim-test.local', 'activo'), (2, 'Socio Dos', 'T0000002', NULL, 'activo')",
    );
    await conn.query(
      "INSERT INTO planes (id, nombre, precio, duracion_dias, estado) VALUES (1, 'Mensual ensayo', 30.00, 30, 'activo')",
    );
    await conn.query(
      "INSERT INTO suscripciones (id, socio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES (1, 1, 1, '2026-01-01', '2026-01-31', 'vencida')",
    );
    await conn.query("INSERT INTO categorias (id, nombre, estado) VALUES (1, 'Bebidas', 'activo')");
    await conn.query(
      "INSERT INTO productos (id, categoria_id, nombre, precio_compra, precio_venta, stock, estado) VALUES (1, 1, 'Agua ensayo', 0.50, 1.00, 10, 'activo')",
    );
    await conn.query("INSERT INTO cajas (id, usuario_id, monto_inicial, estado) VALUES (1, 1, 20.00, 'abierta')");
    await conn.query("INSERT INTO ventas (id, caja_id, socio_id, total) VALUES (1, 1, 1, 2.00)");
    await conn.query(
      'INSERT INTO detalle_ventas (id, venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (1, 1, 1, 2, 1.00, 2.00)',
    );
    await conn.query("INSERT INTO asistencias (id, socio_id, metodo_ingreso) VALUES (1, 1, 'manual')");
    await conn.query(
      "INSERT INTO notifications (id, member_id, type, title, body) VALUES (1, 1, 'rehearsal', 'Ensayo', 'Notificación de ensayo')",
    );
  });
}

async function checksumTables(serverUrl, database, tables) {
  return withConnection(serverUrl, database, async (conn) => {
    const result = {};
    for (const table of tables) {
      const [row] = await conn.query(`CHECKSUM TABLE \`${table}\``);
      result[table] = String(row.Checksum);
    }
    return result;
  });
}

function compare(label, source, restored, failures) {
  const ok = JSON.stringify(source) === JSON.stringify(restored);
  console.log(`${ok ? '  OK ' : '  FAIL'} ${label}`);
  if (!ok) failures.push(label);
}

async function main() {
  const keep = process.argv.includes('--keep');
  const serverUrl = getTestServerUrl();
  const workDir = mkdtempSync(join(tmpdir(), 'gim-saas02-rehearsal-'));
  const failures = [];
  const createdDatabases = [];

  try {
    const source = await createDisposableDatabase(serverUrl, 'rehsrc');
    createdDatabases.push(source.name);
    applyMigrations(source.url);
    await seedRehearsalData(serverUrl, source.name);
    const sourceSummary = await inspectDatabase(serverUrl, source.name, { countRows: true });
    console.log(`1. Base origen ${source.name}: ${sourceSummary.tables.length} tablas con datos de ensayo`);

    const backup = await backupDatabase({
      databaseUrl: source.url,
      outputDir: workDir,
      mysqldumpPath: process.env.MYSQLDUMP_PATH?.trim() || 'mysqldump',
    });
    console.log(`2. Backup: ${backup.bytes} bytes, SHA-256 ${backup.sha256.slice(0, 16)}…`);

    const restored = await restoreDatabase({
      serverUrl,
      file: backup.file,
      mysqlPath: process.env.MYSQL_CLIENT_PATH?.trim() || 'mysql',
    });
    createdDatabases.push(restored.name);
    console.log(`3. Restore en ${restored.name} (${restored.checksum})`);

    console.log('4. Validación origen vs restaurada:');
    compare('lista de tablas', sourceSummary.tables, restored.summary.tables, failures);
    compare('foreign keys', sourceSummary.foreignKeys, restored.summary.foreignKeys, failures);
    compare('índices únicos', sourceSummary.uniqueIndexes, restored.summary.uniqueIndexes, failures);
    compare('filas por tabla', sourceSummary.rowCounts, restored.summary.rowCounts, failures);
    compare(
      'CHECKSUM TABLE por tabla',
      await checksumTables(serverUrl, source.name, sourceSummary.tables),
      await checksumTables(serverUrl, restored.name, restored.summary.tables),
      failures,
    );

    const status = runPrisma(['migrate', 'status'], buildDatabaseUrl(serverUrl, restored.name));
    const prismaOk = status.status === 0;
    console.log(`${prismaOk ? '  OK ' : '  FAIL'} Prisma conecta a la restaurada y ve el historial de migraciones`);
    if (!prismaOk) failures.push('prisma migrate status sobre la base restaurada');
  } finally {
    if (keep) {
      console.log(`--keep: se conservan ${createdDatabases.join(', ')} y ${workDir}`);
    } else {
      for (const name of createdDatabases) {
        await dropDisposableDatabase(serverUrl, name);
      }
      rmSync(workDir, { recursive: true, force: true });
      console.log('5. Limpieza: bases desechables y backup temporal eliminados');
    }
  }

  if (failures.length > 0) {
    console.error(`\nBACKUP_REHEARSAL: FAILED (${failures.join('; ')})`);
    process.exit(1);
  }
  console.log('\nBACKUP_REHEARSAL: PASS');
}

main().catch((error) => {
  console.error(`BACKUP_REHEARSAL: FAILED — ${error.message}`);
  process.exit(1);
});
