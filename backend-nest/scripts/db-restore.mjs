/**
 * SAAS-02 — Restore de un backup .sql en una base DESECHABLE (gim_test_*).
 *
 * Uso:
 *   TEST_DATABASE_URL="mysql://usuario:clave@127.0.0.1:3306" [MYSQL_CLIENT_PATH=mysql] \
 *   node scripts/db-restore.mjs --file ./backups/base_xxx.sql [--name gim_test_restore_x] [--drop-after]
 *
 * Seguridad:
 * - Solo restaura en bases con prefijo gim_test_ y que NO existan todavía
 *   (CREATE DATABASE sin IF NOT EXISTS). No puede sobrescribir ec_gym_system
 *   ni ninguna base existente. El restore sobre producción NO es objetivo de
 *   este script (procedimiento manual documentado para SAAS-14).
 * - Si existe `<archivo>.sha256`, se verifica antes de restaurar.
 * - La contraseña viaja en MYSQL_PWD del proceso hijo, no en argumentos.
 */
import { spawn } from 'node:child_process';
import { createReadStream, existsSync, readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { sha256File } from './db-backup.mjs';
import {
  createDisposableDatabase,
  dropDisposableDatabase,
  generateDisposableName,
  getConnectionOptions,
  getTestServerUrl,
  inspectDatabase,
} from './lib/disposable-database.mjs';

async function verifyChecksum(file) {
  const checksumFile = `${file}.sha256`;
  if (!existsSync(checksumFile)) {
    return 'sin archivo .sha256 (no verificado)';
  }
  const expected = readFileSync(checksumFile, 'utf8').trim().split(/\s+/)[0];
  const actual = await sha256File(file);
  if (expected !== actual) {
    throw new Error(`SHA-256 no coincide (esperado ${expected}, obtenido ${actual}). Backup corrupto.`);
  }
  return 'SHA-256 verificado';
}

/**
 * Crea `targetName` (gim_test_*) e importa `file` con el cliente mysql.
 * Devuelve { name, checksum, summary } con el inventario de la base restaurada.
 */
export async function restoreDatabase({ serverUrl, file, targetName, mysqlPath = 'mysql' }) {
  if (!existsSync(file)) {
    throw new Error(`No existe el archivo de backup: ${file}`);
  }
  const checksum = await verifyChecksum(file);
  const { name } = await createDisposableDatabase(serverUrl, 'restore', targetName);

  const { host, port, user, password } = getConnectionOptions(serverUrl);
  const stderr = [];
  const exitCode = await new Promise((resolvePromise, reject) => {
    const child = spawn(
      mysqlPath,
      [`--host=${host}`, `--port=${port}`, `--user=${user}`, '--default-character-set=utf8mb4', name],
      { env: { ...process.env, MYSQL_PWD: password }, windowsHide: true },
    );
    child.stderr.on('data', (chunk) => stderr.push(chunk));
    child.once('error', reject);
    child.once('close', resolvePromise);
    // Si mysql aborta a mitad del import, el EPIPE de stdin no debe tumbar el
    // proceso: el código de salida de mysql ya reporta el error real.
    child.stdin.on('error', () => {});
    createReadStream(file).on('error', reject).pipe(child.stdin);
  });

  if (exitCode !== 0) {
    throw new Error(
      `mysql terminó con código ${exitCode} restaurando en ${name}: ${Buffer.concat(stderr).toString().trim()}`,
    );
  }

  const summary = await inspectDatabase(serverUrl, name, { countRows: true });
  return { name, checksum, summary };
}

function readArg(flag) {
  const index = process.argv.indexOf(flag);
  return index >= 0 ? process.argv[index + 1] : undefined;
}

async function main() {
  const fileArg = readArg('--file');
  if (!fileArg) {
    throw new Error('Falta --file <ruta-al-backup.sql>');
  }
  const serverUrl = getTestServerUrl();
  const result = await restoreDatabase({
    serverUrl,
    file: resolve(fileArg),
    targetName: readArg('--name') ?? generateDisposableName('restore'),
    mysqlPath: process.env.MYSQL_CLIENT_PATH?.trim() || 'mysql',
  });

  const { tables, foreignKeys, uniqueIndexes, rowCounts } = result.summary;
  console.log(`Restore OK en ${result.name} (${result.checksum})`);
  console.log(`Tablas: ${tables.length} | FKs: ${foreignKeys.length} | Únicos: ${uniqueIndexes.length}`);
  for (const [table, total] of Object.entries(rowCounts)) {
    console.log(`  ${table}: ${total} filas`);
  }

  if (process.argv.includes('--drop-after')) {
    await dropDisposableDatabase(serverUrl, result.name);
    console.log(`Base ${result.name} eliminada (--drop-after).`);
  } else {
    console.log(`La base ${result.name} se conserva. Eliminarla al terminar la verificación.`);
  }
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  main().catch((error) => {
    console.error(`Restore FALLIDO: ${error.message}`);
    process.exit(1);
  });
}
