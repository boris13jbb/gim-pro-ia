/**
 * SAAS-02 — Elimina la base desechable creada en global-setup.mjs.
 * KEEP_INTEGRATION_DATABASE=true la conserva para depurar un test fallido.
 */
import { dropDisposableDatabase, getTestServerUrl } from '../../scripts/lib/disposable-database.mjs';

export default async function globalTeardown() {
  const name = globalThis.__INTEGRATION_DATABASE_NAME__;
  if (!name) return;

  if (process.env.KEEP_INTEGRATION_DATABASE === 'true') {
    console.log(`\nKEEP_INTEGRATION_DATABASE=true: se conserva ${name}`);
    return;
  }
  await dropDisposableDatabase(getTestServerUrl(), name);
}
