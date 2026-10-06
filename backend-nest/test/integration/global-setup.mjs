/**
 * SAAS-02 — Harness de integración MySQL (globalSetup de Jest).
 *
 * Crea una base DESECHABLE gim_test_int_* en el servidor de TEST_DATABASE_URL,
 * aplica las migraciones Prisma y expone DATABASE_URL para los tests.
 * Los tests corren con --runInBand, en el mismo proceso que este setup, así que
 * PrismaService (vía ConfigService) usa la base desechable y nunca la de .env.
 */
import {
  applyMigrations,
  createDisposableDatabase,
  dropDisposableDatabase,
  getTestServerUrl,
} from '../../scripts/lib/disposable-database.mjs';

export default async function globalSetup() {
  const serverUrl = getTestServerUrl();
  const database = await createDisposableDatabase(serverUrl, 'int');

  try {
    applyMigrations(database.url);
  } catch (error) {
    await dropDisposableDatabase(serverUrl, database.name);
    throw error;
  }

  process.env.DATABASE_URL = database.url;
  process.env.INTEGRATION_DATABASE_NAME = database.name;
  globalThis.__INTEGRATION_DATABASE_NAME__ = database.name;
}
