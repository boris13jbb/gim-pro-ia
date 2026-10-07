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
 * Desde SAAS-03 también compara los triggers de tenant (mysqldump --triggers).
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
import { seedLegacyFixtures } from './lib/legacy-fixtures.mjs';
import { generateUlid } from './lib/tenant-scope.mjs';

/**
 * Esquema completo (multi-tenant) + un único tenant de ensayo: los fixtures sin
 * tenant_id se asignan a él vía triggers (modo legado), como hará la app actual.
 */
async function seedRehearsalData(serverUrl, database) {
  await withConnection(serverUrl, database, async (conn) => {
    await conn.query("INSERT INTO tenants (public_id, slug, name) VALUES (?, 'ensayo-backup', 'Gym Ensayo')", [
      generateUlid(),
    ]);
    await seedLegacyFixtures(conn);
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
    compare('triggers', sourceSummary.triggers, restored.summary.triggers, failures);
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
