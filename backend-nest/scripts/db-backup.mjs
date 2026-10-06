/**
 * SAAS-02 — Backup lógico de MySQL/MariaDB con mysqldump.
 *
 * Uso:
 *   BACKUP_DATABASE_URL="mysql://usuario:clave@host:3306/base" \
 *   [BACKUP_DIR=./backups] [MYSQLDUMP_PATH=mysqldump] \
 *   node scripts/db-backup.mjs
 *
 * Genera `<base>_<timestamp>.sql` y `<archivo>.sha256` en BACKUP_DIR.
 *
 * Seguridad:
 * - La contraseña se entrega a mysqldump por la variable MYSQL_PWD del proceso
 *   hijo (no aparece en la línea de comandos ni en logs).
 * - Nunca sobrescribe un backup existente (apertura con flag 'wx').
 * - Si mysqldump falla, el archivo parcial se elimina y el script termina con error.
 * - mysqldump con --single-transaction solo LEE la base de origen.
 * - BACKUP_DIR por defecto (backend-nest/backups/) está en .gitignore: un dump
 *   contiene datos personales y nunca debe versionarse.
 */
import { spawn } from 'node:child_process';
import { createHash } from 'node:crypto';
import { createReadStream, createWriteStream, mkdirSync, rmSync, statSync, writeFileSync } from 'node:fs';
import { basename, join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';
import { BACKEND_ROOT, getConnectionOptions } from './lib/disposable-database.mjs';

export function sha256File(file) {
  return new Promise((resolvePromise, reject) => {
    const hash = createHash('sha256');
    createReadStream(file)
      .on('data', (chunk) => hash.update(chunk))
      .on('error', reject)
      .on('end', () => resolvePromise(hash.digest('hex')));
  });
}

/**
 * Ejecuta mysqldump de la base indicada en `databaseUrl` hacia `outputDir`.
 * Devuelve { file, bytes, sha256 }.
 */
export async function backupDatabase({ databaseUrl, outputDir, mysqldumpPath = 'mysqldump' }) {
  const url = new URL(databaseUrl);
  if (url.protocol !== 'mysql:') {
    throw new Error('La URL de backup debe usar el esquema mysql://');
  }
  const { host, port, user, password, database } = getConnectionOptions(
    url,
    decodeURIComponent(url.pathname.replace(/^\//, '')),
  );
  if (!database) {
    throw new Error('La URL de backup debe incluir el nombre de la base (mysql://.../base).');
  }

  mkdirSync(outputDir, { recursive: true });
  const stamp = new Date().toISOString().replace(/\D/g, '').slice(0, 17);
  const file = join(outputDir, `${database}_${stamp}.sql`);
  const output = createWriteStream(file, { flags: 'wx' });
  await new Promise((resolvePromise, reject) => {
    output.once('open', resolvePromise);
    output.once('error', reject);
  });

  const args = [
    `--host=${host}`,
    `--port=${port}`,
    `--user=${user}`,
    '--single-transaction',
    '--routines',
    '--triggers',
    '--hex-blob',
    '--default-character-set=utf8mb4',
    database,
  ];

  const stderr = [];
  const exitCode = await new Promise((resolvePromise, reject) => {
    const child = spawn(mysqldumpPath, args, {
      env: { ...process.env, MYSQL_PWD: password },
      windowsHide: true,
    });
    child.stdout.pipe(output);
    child.stderr.on('data', (chunk) => stderr.push(chunk));
    child.once('error', reject);
    child.once('close', resolvePromise);
  }).catch((error) => {
    output.destroy();
    rmSync(file, { force: true });
    throw new Error(`No se pudo ejecutar mysqldump (${mysqldumpPath}): ${error.message}`);
  });

  await new Promise((resolvePromise) => output.end(resolvePromise));

  if (exitCode !== 0) {
    rmSync(file, { force: true });
    throw new Error(`mysqldump terminó con código ${exitCode}: ${Buffer.concat(stderr).toString().trim()}`);
  }

  const bytes = statSync(file).size;
  if (bytes === 0) {
    rmSync(file, { force: true });
    throw new Error('mysqldump generó un archivo vacío.');
  }

  const sha256 = await sha256File(file);
  writeFileSync(`${file}.sha256`, `${sha256}  ${basename(file)}\n`, { flag: 'wx' });
  return { file, bytes, sha256 };
}

async function main() {
  const databaseUrl = process.env.BACKUP_DATABASE_URL?.trim();
  if (!databaseUrl) {
    throw new Error('BACKUP_DATABASE_URL no está definida (mysql://usuario:clave@host:3306/base).');
  }
  const outputDir = resolve(process.env.BACKUP_DIR?.trim() || join(BACKEND_ROOT, 'backups'));
  const result = await backupDatabase({
    databaseUrl,
    outputDir,
    mysqldumpPath: process.env.MYSQLDUMP_PATH?.trim() || 'mysqldump',
  });
  console.log(`Backup OK: ${result.file}`);
  console.log(`Tamaño: ${result.bytes} bytes`);
  console.log(`SHA-256: ${result.sha256}`);
}

if (import.meta.url === pathToFileURL(process.argv[1]).href) {
  main().catch((error) => {
    console.error(`Backup FALLIDO: ${error.message}`);
    process.exit(1);
  });
}
