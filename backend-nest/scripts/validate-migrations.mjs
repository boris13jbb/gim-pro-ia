/**
 * SAAS-02 — Valida que las migraciones Prisma reconstruyen el esquema actual
 * desde una base VACÍA y DESECHABLE:
 *
 *   base vacía → prisma migrate deploy → migrate status → diff vs schema.prisma
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
} from './lib/disposable-database.mjs';

// Tablas de negocio que el baseline debe contener (esquema actual, sin modelo SaaS).
const CRITICAL_TABLES = [
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

// Unicidades actuales cuyo cambio está planificado para SAAS-05; deben existir hoy.
const CRITICAL_UNIQUES = [
  'socios.dni',
  'usuarios.email',
  'comprobantes_electronicos.unq_comprobante',
  'sri_series.unq_tipo_serie',
  'auth_refresh_tokens.jti',
];

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
    check(
      !summary.tables.some((t) => t.includes('tenant')),
      'sin tablas tenant (SAAS-02 no implementa multi-tenancy)',
      failures,
    );
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
  console.log('\nValidación de migraciones OK: base vacía → baseline → esquema actual.');
}

main().catch((error) => {
  console.error(`Error validando migraciones: ${error.message}`);
  process.exit(1);
});
