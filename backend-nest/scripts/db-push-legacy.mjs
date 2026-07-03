/**
 * Sincroniza schema Prisma con BD legacy importada sin reset destructivo.
 * Usar en lugar de `prisma migrate dev` cuando la BD viene de gym-system/bk_basededatos.sql.
 */
import { execSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

console.log('Sincronizando schema (db push, sin reset)...');
execSync('npx prisma db push', { stdio: 'inherit', cwd: root });
console.log('Listo. Para tablas nuevas NestJS usar siempre este comando en BD legacy.');
