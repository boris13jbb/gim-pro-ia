/**
 * SAAS-02/03 — Valida que las migraciones Prisma reconstruyen el esquema actual
 * desde una base VACÍA y DESECHABLE:
 *
 *   base vacía → prisma migrate deploy (0001 + 0002) → migrate status → diff vs schema.prisma
 *   → tablas/únicos críticos → tenant_id NOT NULL → triggers de tenant
 *
 * Uso:
 *   TEST_DATABASE_URL="mysql://root:@127.0.0.1:3306" node scripts/validate-migrations.mjs [--keep]
 *
 * Nunca toca DATABASE_URL (base de la app). La base temporal se elimina al final
 * salvo que se pase --keep (útil para inspección manual).
 */
import {
  applyMigrations,
  checkSchemaDrift,
  createDisposableDatabase,
  dropDisposableDatabase,
  getTestServerUrl,
  inspectDatabase,
  runPrisma,
  withConnection,
} from './lib/disposable-database.mjs';
import { TENANT_TABLES, expectedTenantTriggers } from './lib/tenant-scope.mjs';

const EXPECTED_MIGRATIONS = ['0001_baseline_current_schema', '0002_multi_tenant_foundation'];

// Tablas de negocio + tablas de plataforma (SAAS-03).
const CRITICAL_TABLES = [
  'tenants',
  'tenant_memberships',
  'socios',
  'usuarios',
  'planes',
  'suscripciones',
  'asistencias',
  'productos',
  'categorias',
  'movimientos_inventario',
  'cajas',
  'ventas',
  'detalle_ventas',
  'comprobantes_electronicos',
  'configuracion',
  'auth_refresh_tokens',
  'ai_conversations',
  'notifications',
];

// Unicidades: globales (usuarios.email, jti) y por tenant (SAAS-03).
const CRITICAL_UNIQUES = [
  'tenants.uq_tenants_public_id',
  'tenants.uq_tenants_slug',
  'tenant_memberships.uq_tenant_memberships_tenant_user',
  'socios.uq_socios_tenant_dni',
  'configuracion.uq_configuracion_tenant',
  'usuarios.email',
  'comprobantes_electronicos.unq_comprobante',
  'sri_series.unq_tipo_serie',
  'auth_refresh_tokens.jti',
];

/** tenant_id NOT NULL en todas las tablas tenant-scoped + triggers de resolución. */
async function inspectTenantColumns(serverUrl, name) {
  return withConnection(serverUrl, undefined, async (conn) => {
    const columns = await conn.query(
      `SELECT TABLE_NAME AS tableName, IS_NULLABLE AS nullable FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = 'tenant_id'`,
      [name],
    );
    const [{ tenants }] = await conn.query(`SELECT COUNT(*) AS tenants FROM \`${name}\`.tenants`);
    const migrations = (
      await conn.query(`SELECT migration_name AS name FROM \`${name}\`._prisma_migrations ORDER BY migration_name`)
    ).map((row) => row.name);
    return {
      notNull: new Set(columns.filter((c) => c.nullable === 'NO').map((c) => c.tableName)),
      tenants: Number(tenants),
      migrations,
    };
  });
}

function check(condition, message, failures) {
  console.log(`${condition ? '  OK ' : '  FAIL'} ${message}`);
  if (!condition) failures.push(message);
}

async function main() {
  const keep = process.argv.includes('--keep');
  const serverUrl = getTestServerUrl();
  const failures = [];

  const db = await createDisposableDatabase(serverUrl, 'migrations');
  console.log(`Base desechable creada: ${db.name}`);

  try {
    const validate = runPrisma(['validate'], db.url);
    check(validate.status === 0, 'prisma validate', failures);

    const initial = await inspectDatabase(serverUrl, db.name);
    check(initial.tables.length === 0, 'la base parte vacía (0 tablas)', failures);

    applyMigrations(db.url);
    check(true, 'prisma migrate deploy aplicado', failures);

    const status = runPrisma(['migrate', 'status'], db.url);
    check(status.status === 0, 'prisma migrate status: sin migraciones pendientes', failures);

    const drift = checkSchemaDrift(db.url);
    check(drift.inSync, 'migrate diff (base migrada vs schema.prisma): sin drift', failures);
    for (const line of drift.accepted) {
      console.log(`       diferencia equivalente aceptada: ${line}`);
    }
    for (const line of drift.unexpected) {
      console.log(`       drift: ${line}`);
    }

    const summary = await inspectDatabase(serverUrl, db.name);
    const businessTables = summary.tables.filter((t) => t !== '_prisma_migrations');
    console.log(
      `Resumen: ${businessTables.length} tablas de negocio, ${summary.foreignKeys.length} FKs, ${summary.uniqueIndexes.length} índices únicos`,
    );

    check(summary.tables.includes('_prisma_migrations'), 'tabla _prisma_migrations creada', failures);
    for (const table of CRITICAL_TABLES) {
      check(businessTables.includes(table), `tabla ${table}`, failures);
    }
    for (const unique of CRITICAL_UNIQUES) {
      check(summary.uniqueIndexes.includes(unique), `unique ${unique}`, failures);
    }

    const tenancy = await inspectTenantColumns(serverUrl, db.name);
    check(
      JSON.stringify(tenancy.migrations) === JSON.stringify(EXPECTED_MIGRATIONS),
      `historial de migraciones = ${EXPECTED_MIGRATIONS.join(' + ')}`,
      failures,
    );
    for (const { table } of TENANT_TABLES) {
      check(tenancy.notNull.has(table), `${table}.tenant_id NOT NULL`, failures);
    }
    const missingTriggers = expectedTenantTriggers().filter((name) => !summary.triggers.includes(name));
    check(
      missingTriggers.length === 0 && summary.triggers.length === expectedTenantTriggers().length,
      `triggers tenant: INSERT (resolución) + UPDATE (inmutabilidad) por tabla (${summary.triggers.length})`,
      failures,
    );
    check(tenancy.tenants === 0, 'base vacía: la migración no crea tenant semilla sin datos legados', failures);
  } finally {
    if (keep) {
      console.log(`--keep: la base ${db.name} se conserva para inspección.`);
    } else {
      await dropDisposableDatabase(serverUrl, db.name);
      console.log(`Base desechable eliminada: ${db.name}`);
    }
  }

  if (failures.length > 0) {
    console.error(`\nValidación de migraciones FALLIDA (${failures.length} checks).`);
    process.exit(1);
  }
  console.log('\nValidación de migraciones OK: base vacía → baseline → multi-tenant → esquema actual.');
}

main().catch((error) => {
  console.error(`Error validando migraciones: ${error.message}`);
  process.exit(1);
});
