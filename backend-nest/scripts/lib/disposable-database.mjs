/**
 * SAAS-02 — Utilidades para bases MySQL/MariaDB DESECHABLES.
 *
 * Usado por: validación de migraciones, harness de integración (Jest) y
 * rehearsal de backup/restore. Nunca se usa contra la base de la aplicación.
 *
 * Seguridad:
 * - El servidor se toma de TEST_DATABASE_URL (nunca de DATABASE_URL), para que un
 *   descuido de configuración no apunte los tests a la base real.
 * - Solo se crean/eliminan bases cuyo nombre cumple DISPOSABLE_NAME_PATTERN
 *   (prefijo `gim_test_`). Cualquier otro nombre se rechaza antes de ejecutar SQL.
 * - CREATE DATABASE sin IF NOT EXISTS: si el nombre ya existe, se aborta en vez de
 *   reutilizar una base con datos.
 * - Las URLs con credenciales nunca se imprimen.
 */
import { spawnSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import mariadb from 'mariadb';

export const BACKEND_ROOT = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
export const DISPOSABLE_DB_PREFIX = 'gim_test_';

const DISPOSABLE_NAME_PATTERN = /^gim_test_[a-z0-9_]{1,55}$/;

/**
 * Lee la URL del servidor de pruebas (`mysql://user:pass@host:port`).
 * La base indicada en la ruta, si existe, se ignora: cada ejecución crea la suya.
 */
export function getTestServerUrl(envName = 'TEST_DATABASE_URL') {
  const raw = process.env[envName]?.trim();
  if (!raw) {
    throw new Error(
      `${envName} no está definida. Ejemplo: ${envName}="mysql://usuario:clave@127.0.0.1:3306"`,
    );
  }
  const url = new URL(raw);
  if (url.protocol !== 'mysql:') {
    throw new Error(`${envName} debe usar el esquema mysql://`);
  }
  return url;
}

export function assertDisposableName(name) {
  if (!DISPOSABLE_NAME_PATTERN.test(name)) {
    throw new Error(
      `Nombre de base rechazado: "${name}". Solo se permiten bases desechables con prefijo ${DISPOSABLE_DB_PREFIX}.`,
    );
  }
}

/** Nombre único: gim_test_<label>_<yyyymmddhhmmss>_<hex>. */
export function generateDisposableName(label) {
  const safeLabel = label.toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 16) || 'db';
  const stamp = new Date().toISOString().replace(/\D/g, '').slice(0, 14);
  const name = `${DISPOSABLE_DB_PREFIX}${safeLabel}_${stamp}_${randomBytes(3).toString('hex')}`;
  assertDisposableName(name);
  return name;
}

/** URL completa hacia `databaseName` en el mismo servidor de pruebas. */
export function buildDatabaseUrl(serverUrl, databaseName) {
  assertDisposableName(databaseName);
  const url = new URL(serverUrl.href);
  url.pathname = `/${databaseName}`;
  url.search = '';
  return url.href;
}

export function getConnectionOptions(serverUrl, database) {
  const host = serverUrl.hostname || '127.0.0.1';
  return {
    host: host === 'localhost' ? '127.0.0.1' : host,
    port: serverUrl.port ? Number(serverUrl.port) : 3306,
    user: decodeURIComponent(serverUrl.username || 'root'),
    password: decodeURIComponent(serverUrl.password || ''),
    ...(database ? { database } : {}),
    connectTimeout: 10_000,
    allowPublicKeyRetrieval: true,
  };
}

/** Abre una conexión, ejecuta `fn` y cierra siempre la conexión. */
export async function withConnection(serverUrl, database, fn) {
  const conn = await mariadb.createConnection(getConnectionOptions(serverUrl, database));
  try {
    return await fn(conn);
  } finally {
    await conn.end();
  }
}

/** Crea la base (falla si ya existe). `name` opcional; debe cumplir el prefijo. */
export async function createDisposableDatabase(serverUrl, label, name = generateDisposableName(label)) {
  assertDisposableName(name);
  await withConnection(serverUrl, undefined, (conn) =>
    conn.query(
      `CREATE DATABASE \`${name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`,
    ),
  );
  return { name, url: buildDatabaseUrl(serverUrl, name) };
}

export async function dropDisposableDatabase(serverUrl, name) {
  assertDisposableName(name);
  await withConnection(serverUrl, undefined, (conn) =>
    conn.query(`DROP DATABASE IF EXISTS \`${name}\``),
  );
}

/**
 * Ejecuta la CLI de Prisma con DATABASE_URL sobrescrita hacia la base desechable.
 * prisma.config.ts carga .env con dotenv, que NO sobrescribe variables ya
 * definidas en el proceso; por eso la base real de .env no se usa aquí.
 */
export function runPrisma(args, databaseUrl) {
  const prismaCli = join(BACKEND_ROOT, 'node_modules', 'prisma', 'build', 'index.js');
  const result = spawnSync(process.execPath, [prismaCli, ...args], {
    cwd: BACKEND_ROOT,
    env: { ...process.env, DATABASE_URL: databaseUrl, PRISMA_HIDE_UPDATE_MESSAGE: '1' },
    encoding: 'utf8',
  });
  return {
    status: result.status,
    output: `${result.stdout ?? ''}${result.stderr ?? ''}`.trim(),
  };
}

export function applyMigrations(databaseUrl) {
  const result = runPrisma(['migrate', 'deploy'], databaseUrl);
  if (result.status !== 0) {
    throw new Error(`prisma migrate deploy falló:\n${result.output}`);
  }
  return result.output;
}

/**
 * Diferencias que `migrate diff` reporta aunque la base y schema.prisma son
 * equivalentes. Se aceptan solo por coincidencia EXACTA de línea.
 *
 * configuracion.sri_ambiente: schema.prisma declara `@default(dbgenerated("1"))`
 * sobre el enum `configuracion_sri_ambiente` (pruebas = "1"). La columna queda con
 * DEFAULT '1' tanto en la base migrada como en ec_gym_system, pero Prisma la
 * introspecta como `Enum("1")`. Corregirlo exige tocar el modelo SRI
 * (`@default(pruebas)`), decisión diferida a SAAS-03 (docs/SAAS-02-FUNDACIONES.md).
 */
const KNOWN_EQUIVALENT_DIFFS = new Set([
  '[*] Altered column `sri_ambiente` (default changed from `Some(Value(Enum("1")))` to `Some(DbGenerated(Some("1")))`)',
]);

/** Agrupa la salida legible de `migrate diff` en bloques: cabecera + líneas hijas. */
function parseDiffBlocks(output) {
  const blocks = [];
  for (const line of output.split(/\r?\n/)) {
    if (!/\[[*+-]\]/.test(line)) continue;
    if (/^\s/.test(line) && blocks.length > 0) {
      blocks[blocks.length - 1].children.push(line.trim());
    } else {
      blocks.push({ header: line.trim(), children: [] });
    }
  }
  return blocks;
}

/**
 * Compara la base (ya migrada) contra schema.prisma.
 * `inSync` es true si no hay diferencias, o si las únicas diferencias están en
 * KNOWN_EQUIVALENT_DIFFS. `unexpected` lista lo que sí es drift real.
 */
export function checkSchemaDrift(databaseUrl) {
  const result = runPrisma(
    ['migrate', 'diff', '--from-config-datasource', '--to-schema', 'prisma/schema.prisma', '--exit-code'],
    databaseUrl,
  );
  if (result.status !== 0 && result.status !== 2) {
    throw new Error(`prisma migrate diff falló:\n${result.output}`);
  }

  const unexpected = [];
  const accepted = [];
  for (const block of parseDiffBlocks(result.output)) {
    const remaining = block.children.filter((child) => !KNOWN_EQUIVALENT_DIFFS.has(child));
    accepted.push(...block.children.filter((child) => KNOWN_EQUIVALENT_DIFFS.has(child)));
    if (block.children.length === 0 || remaining.length > 0) {
      unexpected.push(block.header, ...remaining.map((child) => `  ${child}`));
    }
  }

  return { inSync: unexpected.length === 0, unexpected, accepted, output: result.output };
}

/** Resumen estructural de una base: tablas, FKs, índices únicos y filas por tabla. */
export async function inspectDatabase(serverUrl, name, { countRows = false } = {}) {
  assertDisposableName(name);
  return withConnection(serverUrl, undefined, async (conn) => {
    const tables = (
      await conn.query(
        `SELECT TABLE_NAME AS name FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME`,
        [name],
      )
    ).map((row) => row.name);

    const foreignKeys = (
      await conn.query(
        `SELECT CONSTRAINT_NAME AS name, TABLE_NAME AS tableName, REFERENCED_TABLE_NAME AS refTable
         FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = ? ORDER BY TABLE_NAME, CONSTRAINT_NAME`,
        [name],
      )
    ).map((row) => ({ name: row.name, table: row.tableName, references: row.refTable }));

    const uniqueIndexes = (
      await conn.query(
        `SELECT DISTINCT TABLE_NAME AS tableName, INDEX_NAME AS indexName
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = ? AND NON_UNIQUE = 0 AND INDEX_NAME <> 'PRIMARY'
         ORDER BY TABLE_NAME, INDEX_NAME`,
        [name],
      )
    ).map((row) => `${row.tableName}.${row.indexName}`);

    const rowCounts = {};
    if (countRows) {
      for (const table of tables) {
        const [{ total }] = await conn.query(
          `SELECT COUNT(*) AS total FROM \`${name}\`.\`${table}\``,
        );
        rowCounts[table] = Number(total);
      }
    }

    return { tables, foreignKeys, uniqueIndexes, rowCounts };
  });
}
