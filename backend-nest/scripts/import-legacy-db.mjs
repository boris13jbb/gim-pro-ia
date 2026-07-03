/**
 * Importa gym-system/bk_basededatos.sql en MariaDB/MySQL local.
 * Uso: node scripts/import-legacy-db.mjs
 */
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import mariadb from 'mariadb';

const __dirname = dirname(fileURLToPath(import.meta.url));
const sqlPath = join(__dirname, '../../gym-system/bk_basededatos.sql');

function adaptSqlForMariaDb(raw) {
  return raw
    .replace(/utf8mb4_0900_ai_ci/g, 'utf8mb4_unicode_ci')
    .replace(/\/\*!80016 DEFAULT ENCRYPTION='N' \*\//g, '')
    .replace(/\/\*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci \*\//g, '');
}

async function main() {
  const raw = readFileSync(sqlPath, 'utf8');
  const sql = adaptSqlForMariaDb(raw);

  const conn = await mariadb.createConnection({
    host: '127.0.0.1',
    port: 3306,
    user: 'root',
    password: '',
    multipleStatements: true,
    connectTimeout: 10000,
    allowPublicKeyRetrieval: true,
  });

  try {
    console.log('Importando backup legacy...');
    await conn.query(sql);
    const [{ db }] = await conn.query(
      'SELECT DATABASE() AS db FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
      ['ec_gym_system'],
    );
    const tables = await conn.query(
      'SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?',
      ['ec_gym_system'],
    );
    console.log(`Base ec_gym_system lista. Tablas: ${tables[0].total}`);
    if (!db) {
      throw new Error('La base ec_gym_system no quedó creada.');
    }
  } finally {
    await conn.end();
  }
}

main().catch((err) => {
  console.error('Error importando BD:', err.message);
  process.exit(1);
});
