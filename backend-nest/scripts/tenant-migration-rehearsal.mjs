/**
 * SAAS-03 — Ensayo de la migración multi-tenant (0002) sobre datos LEGADOS, siempre en
 * bases desechables gim_test_saas03_*.
 *
 * Modo por defecto (CI):
 *   base vacía → SQL del baseline (sin historial Prisma, como ec_gym_system hoy)
 *   → fixtures legados → preflight → migrate resolve 0001 → migrate deploy (0002)
 *   → conteos preservados → tenant inicial → integridad → drift → triggers
 *   → herencia de tenant en inserts legados → deploy repetido = no-op
 *   + escenario negativo: datos ambiguos ⇒ la migración aborta (fail-fast).
 *
 * --from-backup (solo local, opcional):
 *   BACKUP_DATABASE_URL (base real, SOLO LECTURA vía mysqldump --single-transaction)
 *   → backup temporal con SHA-256 → restore en gim_test_saas03_* → mismo flujo.
 *   El dump contiene datos personales: vive en un directorio temporal y se borra al final.
 *   La base de origen nunca recibe escrituras: todas las operaciones de Prisma pasan por
 *   runPrisma(), que exige una URL gim_test_*.
 *
 * Salida: solo conteos y nombres de verificación (sin datos personales).
 * Códigos: 0 PASS · 1 FAILED · 2 BLOCKED_BY_DATA_INTEGRITY.
 */
import { mkdtempSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { backupDatabase } from './db-backup.mjs';
import { restoreDatabase } from './db-restore.mjs';
import {
  applyBaselineSqlOnly,
  applyMigrations,
  buildDatabaseUrl,
  checkSchemaDrift,
  createDisposableDatabase,
  dropDisposableDatabase,
  generateDisposableName,
  getTestServerUrl,
  inspectDatabase,
  markBaselineApplied,
  runPrisma,
  withConnection,
} from './lib/disposable-database.mjs';
import { seedLegacyFixtures } from './lib/legacy-fixtures.mjs';
import {
  checkTenantIntegrity,
  expectedTenantTriggers,
  preflightTenantMigration,
} from './lib/tenant-scope.mjs';

const SEED_SLUG = 'iron-gym';
const ULID_PATTERN = /^[0-7][0-9A-HJKMNP-TV-Z]{25}$/;

class DataIntegrityBlock extends Error {}

function check(condition, label, failures) {
  console.log(`${condition ? '  OK ' : '  FAIL'} ${label}`);
  if (!condition) failures.push(label);
}

async function hasMigrationHistory(serverUrl, name) {
  return withConnection(serverUrl, name, async (conn) => {
    const rows = await conn.query(
      `SELECT COUNT(*) AS total FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = ? AND TABLE_NAME = '_prisma_migrations'`,
      [name],
    );
    return Number(rows[0].total) > 0;
  });
}

/** Nombre que la migración debe asignar al tenant inicial (misma regla que el SQL). */
async function expectedTenantName(serverUrl, name) {
  return withConnection(serverUrl, name, async (conn) => {
    const [config] = await conn.query(
      'SELECT nombre_comercial, nombre_sistema FROM configuracion ORDER BY id LIMIT 1',
    );
    const pick = (value) => (value && value.trim() !== '' ? value.trim() : null);
    return (pick(config?.nombre_comercial) ?? pick(config?.nombre_sistema) ?? 'Iron Gym').slice(0, 150);
  });
}

/**
 * Inserts como los hace hoy la aplicación (sin tenant_id), dentro de una transacción
 * que se revierte: el tenant debe resolverse por herencia o modo legado.
 */
async function checkLegacyInserts(serverUrl, name, seedId, failures) {
  await withConnection(serverUrl, name, async (conn) => {
    await conn.beginTransaction();
    try {
      const member = await conn.query(
        "INSERT INTO socios (nombre, dni, estado) VALUES ('Socio Herencia', ?, 'activo')",
        [`R${Date.now()}`],
      );
      const memberId = Number(member.insertId);
      await conn.query("INSERT INTO asistencias (socio_id, metodo_ingreso) VALUES (?, 'manual')", [memberId]);
      const [root] = await conn.query('SELECT tenant_id FROM socios WHERE id = ?', [memberId]);
      const [child] = await conn.query('SELECT tenant_id FROM asistencias WHERE socio_id = ?', [memberId]);
      check(Number(root.tenant_id) === seedId, 'insert raíz sin tenant_id → tenant inicial (modo legado)', failures);
      check(Number(child.tenant_id) === seedId, 'insert hijo sin tenant_id → hereda el tenant del padre', failures);
    } finally {
      await conn.rollback();
    }
  });
}

/** Flujo común sobre una base que ya contiene el esquema baseline + datos legados. */
async function migrateAndVerify(serverUrl, name, failures) {
  const url = buildDatabaseUrl(serverUrl, name);
  const before = await inspectDatabase(serverUrl, name, { countRows: true });
  const tenantName = await expectedTenantName(serverUrl, name);
  const usuarios = before.rowCounts.usuarios ?? 0;

  const issues = await withConnection(serverUrl, name, (conn) => preflightTenantMigration(conn));
  if (issues.length > 0) {
    for (const issue of issues) console.error(`  BLOCK ${issue.check}: ${issue.count}`);
    throw new DataIntegrityBlock('preflight detectó datos que la migración no puede resolver sin inventar reglas');
  }
  check(true, 'preflight: sin duplicados ni configuracion múltiple', failures);

  if (!(await hasMigrationHistory(serverUrl, name))) {
    markBaselineApplied(url);
    check(true, 'migrate resolve --applied 0001 (base legada sin historial)', failures);
  }
  const startedAt = Date.now();
  applyMigrations(url);
  check(true, `migrate deploy (0002_multi_tenant_foundation) en ${Date.now() - startedAt} ms`, failures);

  const after = await inspectDatabase(serverUrl, name, { countRows: true });
  const lost = before.tables.filter((t) => after.rowCounts[t] !== before.rowCounts[t]);
  check(lost.length === 0, `filas preservadas en ${before.tables.length} tablas legadas`, failures);

  await withConnection(serverUrl, name, async (conn) => {
    const tenants = await conn.query('SELECT id, public_id, slug, name, status FROM tenants');
    const seed = tenants.find((t) => t.slug === SEED_SLUG);
    check(tenants.length === 1 && Boolean(seed), `exactamente 1 tenant inicial (slug ${SEED_SLUG})`, failures);
    if (!seed) return;
    check(ULID_PATTERN.test(seed.public_id), 'public_id del tenant inicial es ULID', failures);
    check(seed.name === tenantName, 'nombre del tenant tomado de configuracion', failures);
    check(seed.status === 'active', 'tenant inicial activo', failures);

    const [{ total: memberships }] = await conn.query('SELECT COUNT(*) AS total FROM tenant_memberships');
    check(Number(memberships) === usuarios, `tenant_memberships = usuarios (${usuarios})`, failures);

    const integrity = await checkTenantIntegrity(conn);
    for (const violation of integrity.violations) console.error(`  FAIL ${violation.check}: ${violation.count}`);
    check(integrity.violations.length === 0, 'integridad tenant: 0 NULL, 0 cruces, 0 duplicados', failures);
    const migratedRows = integrity.tables.reduce((sum, t) => sum + t.withTenant, 0);
    console.log(`       ${migratedRows} filas con tenant_id en ${integrity.tables.length} tablas tenant`);

    const [{ total: guardLeft }] = await conn.query(
      `SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = '_saas03_guard'`,
      [name],
    );
    check(Number(guardLeft) === 0, 'tabla temporal _saas03_guard eliminada', failures);
    await checkLegacyInserts(serverUrl, name, Number(seed.id), failures);
  });

  check(
    JSON.stringify([...after.triggers].sort()) === JSON.stringify(expectedTenantTriggers()),
    `triggers de tenant: INSERT + UPDATE por tabla (${after.triggers.length})`,
    failures,
  );
  const drift = checkSchemaDrift(url);
  if (!drift.inSync) console.error(drift.unexpected.join('\n'));
  check(drift.inSync, 'migrate diff vs schema.prisma: sin drift', failures);

  const again = runPrisma(['migrate', 'deploy'], url);
  check(
    again.status === 0 && /No pending migrations/i.test(again.output),
    'migrate deploy repetido = no-op (idempotente)',
    failures,
  );
}

/**
 * Escenario negativo: dos filas en configuracion (no se sabe cuál es la del gimnasio).
 * Preflight debe detectarlo y, aun forzando el deploy, el guard debe abortar la migración.
 */
async function rehearseFailFast(serverUrl, failures) {
  const db = await createDisposableDatabase(serverUrl, 'saas03neg');
  try {
    applyBaselineSqlOnly(db.url);
    await withConnection(serverUrl, db.name, async (conn) => {
      await seedLegacyFixtures(conn);
      await conn.query("INSERT INTO configuracion (id, nombre_sistema, moneda) VALUES (2, 'Segunda', '$')");
    });
    const issues = await withConnection(serverUrl, db.name, (conn) => preflightTenantMigration(conn));
    check(issues.some((i) => i.check.startsWith('configuracion')), 'negativo: preflight bloquea configuracion múltiple', failures);

    markBaselineApplied(db.url);
    const forced = runPrisma(['migrate', 'deploy'], db.url);
    check(forced.status !== 0, 'negativo: migrate deploy forzado aborta (fail-fast)', failures);
    check(/chk_saas03_guard_zero/.test(forced.output), 'negativo: abortó el guard de validación', failures);
  } finally {
    await dropDisposableDatabase(serverUrl, db.name);
  }
}

async function prepareFromFixtures(serverUrl, created) {
  const db = await createDisposableDatabase(serverUrl, 'saas03fix');
  created.push(db.name);
  applyBaselineSqlOnly(db.url);
  await withConnection(serverUrl, db.name, (conn) => seedLegacyFixtures(conn));
  console.log(`1. ${db.name}: baseline legado + fixtures`);
  return db.name;
}

async function prepareFromBackup(serverUrl, created, workDir) {
  const source = process.env.BACKUP_DATABASE_URL?.trim();
  if (!source) throw new Error('--from-backup requiere BACKUP_DATABASE_URL (solo se lee con mysqldump).');
  const backup = await backupDatabase({
    databaseUrl: source,
    outputDir: workDir,
    mysqldumpPath: process.env.MYSQLDUMP_PATH?.trim() || 'mysqldump',
  });
  console.log(`1a. Backup temporal: ${backup.bytes} bytes, SHA-256 ${backup.sha256.slice(0, 16)}…`);
  const restored = await restoreDatabase({
    serverUrl,
    file: backup.file,
    targetName: generateDisposableName('saas03bak'),
    mysqlPath: process.env.MYSQL_CLIENT_PATH?.trim() || 'mysql',
  });
  created.push(restored.name);
  console.log(`1b. Restore en ${restored.name} (${restored.checksum}), ${restored.summary.tables.length} tablas`);
  return restored.name;
}

async function main() {
  const fromBackup = process.argv.includes('--from-backup');
  const serverUrl = getTestServerUrl();
  const workDir = mkdtempSync(join(tmpdir(), 'gim-saas03-rehearsal-'));
  const created = [];
  const failures = [];
  let blocked = null;

  try {
    const name = fromBackup
      ? await prepareFromBackup(serverUrl, created, workDir)
      : await prepareFromFixtures(serverUrl, created);
    console.log('2. Migración multi-tenant y validación:');
    await migrateAndVerify(serverUrl, name, failures);
    if (!fromBackup) {
      console.log('3. Escenario negativo (fail-fast):');
      await rehearseFailFast(serverUrl, failures);
    }
  } catch (error) {
    if (!(error instanceof DataIntegrityBlock)) throw error;
    blocked = error.message;
  } finally {
    for (const name of created) await dropDisposableDatabase(serverUrl, name);
    rmSync(workDir, { recursive: true, force: true });
    console.log('Limpieza: bases desechables y backup temporal eliminados');
  }

  if (blocked) {
    console.error(`\nTENANT_REHEARSAL: BLOCKED_BY_DATA_INTEGRITY — ${blocked}`);
    process.exit(2);
  }
  if (failures.length > 0) {
    console.error(`\nTENANT_REHEARSAL: FAILED (${failures.join('; ')})`);
    process.exit(1);
  }
  console.log(`\nTENANT_REHEARSAL: PASS (${fromBackup ? 'copia restaurada de la base real' : 'fixtures legados'})`);
}

main().catch((error) => {
  console.error(`TENANT_REHEARSAL: FAILED — ${error.message}`);
  process.exit(1);
});
