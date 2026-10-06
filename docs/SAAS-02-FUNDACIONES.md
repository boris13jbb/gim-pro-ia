# SAAS-02 — Fundaciones (migraciones, backup, harness MySQL, CI)

> Fase de fundaciones técnicas previa a multi-tenancy. **No** introduce `tenant_id`, `tenants`, RBAC SaaS, JWT con tenant ni cambios funcionales.

## Objetivo

Dejar una base segura y verificable sobre la que construir SAAS-03+:

1. Baseline de migraciones Prisma que represente el esquema **actual**.
2. Backup y restore reproducibles, con restore limitado a bases desechables.
3. Harness de pruebas de integración contra MySQL real y efímero.
4. Validación de migraciones sobre base vacía.
5. CI mínimo que solo valida (sin deploy ni secretos).

## Estado inicial

| Aspecto | Antes de SAAS-02 |
|---|---|
| Rama / HEAD | `feature/fase-18-salir-ngrok` / `6435013` |
| Migraciones Prisma | No existía `prisma/migrations`; esquema aplicado con `db push` (`scripts/db-push-legacy.mjs`) |
| Backup | Sin script versionado; solo dump legacy de importación |
| Pruebas con BD | Solo e2e de `AppModule` usando la BD de `.env` |
| CI | No existía `.github/` |

## Prisma actual

- Prisma **7.8.0**, `prisma.config.ts` (`migrations.path = prisma/migrations`, `datasource.url = DATABASE_URL`).
- Driver adapter `@prisma/adapter-mariadb`; servidor local MariaDB **10.4.32** (XAMPP).
- `prisma/schema.prisma`: 23 modelos, 21 FK, sin entidades tenant. **No se modificó.**

## Estrategia de migraciones

- A partir de SAAS-02, el esquema evoluciona con `prisma migrate` (migraciones versionadas), no con `db push`.
- `0001_baseline_current_schema` describe el esquema tal como existe hoy.
- Las bases existentes (la BD local `ec_gym_system` y cualquier copia) **ya tienen** ese esquema: en una fase posterior se marcarán como aplicadas con `prisma migrate resolve --applied 0001_baseline_current_schema`, **tras backup**. Ese paso **no se ejecutó** en SAAS-02.
- Las bases nuevas (tests, CI, futuros entornos) se crean con `prisma migrate deploy`.
- `db-push-legacy.mjs` se conserva sin cambios (compatibilidad), pero queda desaconsejado para cambios de esquema nuevos.

## Baseline

| Campo | Valor |
|---|---|
| Ruta | `backend-nest/prisma/migrations/0001_baseline_current_schema/migration.sql` |
| Lock | `backend-nest/prisma/migrations/migration_lock.toml` (`provider = "mysql"`) |
| Método | `npx prisma migrate diff --from-empty --to-schema prisma/schema.prisma --script` (offline, sin tocar ninguna BD) |
| Contenido | 23 `CREATE TABLE`, 21 FK, índices únicos; sin tablas tenant |

## Cómo validar baseline

```powershell
cd backend-nest
$env:TEST_DATABASE_URL = "mysql://<user>:<pass>@127.0.0.1:3306"   # servidor, sin nombre de base
npm run db:validate-migrations
```

El script (`scripts/validate-migrations.mjs`):

1. Crea `gim_test_migrations_<timestamp>_<hex>` (falla si existe).
2. `prisma validate`.
3. Verifica que la base está vacía.
4. `prisma migrate deploy` → `prisma migrate status`.
5. Drift: `prisma migrate diff --from-config-datasource --to-schema ... --exit-code`.
6. Verifica `_prisma_migrations`, 16 tablas críticas, 5 índices únicos críticos y ausencia de tablas `tenant*`.
7. Elimina la base (salvo `--keep`). Exit 1 si hay fallos.

**Resultado local:** OK — 23 tablas, 21 FK, 5 únicos críticos, 1 diferencia aceptada (ver Problemas encontrados).

Comprobación adicional **solo lectura** contra la BD real (`migrate diff --from-config-datasource` → schema, sin aplicar nada): la única diferencia es la misma de `sri_ambiente`. El baseline coincide con la BD en uso.

## Backup

`scripts/db-backup.mjs` (`npm run db:backup`):

- Entrada: `BACKUP_DATABASE_URL` (obligatoria, explícita; no lee `DATABASE_URL`), `BACKUP_DIR` (por defecto `backend-nest/backups/`, ignorado por Git), `MYSQLDUMP_PATH`.
- `mysqldump --single-transaction --routines --triggers --hex-blob --default-character-set=utf8mb4`.
- Contraseña vía `MYSQL_PWD` (nunca en argumentos ni logs).
- Archivo `<db>_<timestamp>.sql` creado en modo exclusivo + `<archivo>.sha256`.
- Si falla o queda vacío, borra el parcial.
- Solo lectura sobre la BD origen.

## Restore

`scripts/db-restore.mjs` (`npm run db:restore:test -- --file <dump.sql> [--name gim_test_x] [--drop-after]`):

- Usa `TEST_DATABASE_URL`; el destino **debe** cumplir `^gim_test_[a-z0-9_]{1,55}$` y **no debe existir** (no sobrescribe).
- Verifica el `.sha256` si existe antes de restaurar.
- Restaura con el cliente `mysql` vía stdin (`MYSQL_CLIENT_PATH`).
- **Nunca** restaurar sobre producción ni sobre `ec_gym_system`: la guarda de nombre lo impide.

## Backup rehearsal

`scripts/db-backup-rehearsal.mjs` (`npm run db:backup:rehearsal`):

1. Base origen `gim_test_rehsrc_*` → `migrate deploy` → fixtures de prueba (configuración, usuario, 2 socios, plan, suscripción, categoría, producto, caja, venta, detalle, asistencia, notificación).
2. Backup a un directorio temporal → restore a `gim_test_rehdst_*`.
3. Compara tablas, FK, índices únicos, filas por tabla y `CHECKSUM TABLE`; `migrate status` sobre la restaurada.
4. Elimina ambas bases y el directorio temporal.

**Resultado local:** `BACKUP_REHEARSAL: PASS`.

## MySQL test harness

- Config: `backend-nest/test/jest-integration.json` (`*.int-spec.ts`, `--runInBand`).
- `test/integration/global-setup.mjs`: crea `gim_test_int_*`, aplica migraciones y fija `DATABASE_URL` solo para el proceso de Jest.
- `test/integration/global-teardown.mjs`: elimina la base (conservar con `KEEP_INTEGRATION_DATABASE=true`).
- `test/integration/database-baseline.int-spec.ts`: usa el `DatabaseModule` real y verifica base conectada, health `up`, baseline registrado, FK y único `socios.dni`.
- Librería común: `scripts/lib/disposable-database.mjs` (prefijo, nombres, crear/eliminar, `migrate deploy`, drift, inspección).

**Resultado local:** 5/5.

## CI

`.github/workflows/backend-ci.yml`, que se dispara en push y PR sobre `backend-nest/**`:

| Paso | Bloqueante |
|---|---|
| `npm ci` (incluye `prisma generate`) | Sí |
| `npx prisma validate` | Sí |
| `npm run build` | Sí |
| ESLint sin `--fix` | **No** (error de formato preexistente) |
| `npm test` | Sí |
| `npm run db:validate-migrations` | Sí |
| `npm run test:integration` | Sí |
| `prisma migrate deploy` + `npm run test:e2e` | Sí |

- BD: service container `mariadb:10.4`, efímero y sin contraseña (solo dentro del runner).
- Secretos JWT del e2e: se generan con `openssl rand` en cada ejecución; no hay `secrets.*` en el workflow.
- `permissions: contents: read`; sin deploy ni conexión a bases reales, Firebase o producción.
- El ensayo de backup **no** corre en CI (requiere clientes `mysqldump`/`mysql` en el runner); queda como validación local.
- Validación: estática (parseo YAML con `js-yaml`: 1 job, 12 pasos). **No se ejecutó en GitHub** porque no hubo push.

## Comandos validados

| Comando | Resultado |
|---|---|
| `npx prisma validate` | OK |
| `npm run db:validate-migrations` | OK |
| `npm run db:backup:rehearsal` (con binarios XAMPP) | PASS |
| `npm run test:integration` | 5/5 |
| `npx jest` (unitarios) | 6/6 (2 suites) |
| `npm run test:e2e` con `DATABASE_URL` → base `gim_test_e2e_*` migrada | 2/2 |
| `npm run build` | OK |
| `npx eslint "{src,apps,libs,test}/**/*.ts"` | 1 error + 3 warnings **preexistentes** |

Rutas XAMPP usadas: `MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe`, `MYSQL_CLIENT_PATH=C:\xampp\mysql\bin\mysql.exe`.

## Problemas encontrados

| Problema | Causa | Solución | Origen |
|---|---|---|---|
| Drift en `configuracion.sri_ambiente` | `@default(dbgenerated("1"))` sobre un ENUM: Prisma lo lee como `Enum("1")` y el schema lo declara como `DbGenerated("1")`. Es solo representación; el valor por defecto real es el mismo | Allowlist de coincidencia exacta en `KNOWN_EQUIVALENT_DIFFS`; cualquier otra diferencia falla | PREEXISTENTE |
| Lint: `src/config/cors.config.ts` (prettier, CRLF) y 3 `no-floating-promises` | Código previo | No se toca (fuera de alcance); en CI el lint no bloquea | PREEXISTENTE |
| `npm run lint` usa `--fix` | Script del proyecto | Se usa `npx eslint` sin `--fix` | PREEXISTENTE |
| Base huérfana durante el desarrollo | Truncar la salida del script (`Select-Object -First`) mató el proceso | Eliminada con `dropDisposableDatabase`; no truncar estos scripts | INTRODUCIDO y resuelto |

## Riesgos pendientes

- El baseline aún no está marcado como aplicado en `ec_gym_system`: hasta hacerlo, **no** ejecutar `migrate deploy` contra esa base (intentaría recrear tablas).
- El CI no se ha ejecutado en GitHub (requiere push autorizado).
- Las restauraciones reales dependen de tener `mysqldump`/`mysql` compatibles con el servidor.
- Los backups contienen datos personales: `backend-nest/backups/` está ignorado; falta definir dónde se almacenan y cuánto tiempo se retienen.

## Decisiones

| ID | Decisión |
|---|---|
| S02-D1 | Migraciones versionadas con `prisma migrate` desde un baseline único; `db push` queda como legado |
| S02-D2 | Prefijo obligatorio `gim_test_` y guarda regex para crear, eliminar o restaurar bases |
| S02-D3 | Los scripts de prueba usan `TEST_DATABASE_URL` y nunca `DATABASE_URL` |
| S02-D4 | `sri_ambiente`: se acepta la diferencia equivalente; la normalización del schema (`@default(pruebas)` o similar) se difiere a una fase con permiso para tocar SRI |
| S02-D5 | CI con `mariadb:10.4` para igualar la versión local; lint no bloqueante hasta corregir el error preexistente |

## Criterios de salida

- [x] Baseline creado y aplicable a una base vacía; coincide con el esquema actual
- [x] Validación de migraciones automatizada
- [x] Backup y restore implementados; ensayo PASS
- [x] Harness de integración funcionando
- [x] CI creado y validado estáticamente
- [x] Sin `tenant_id`/`tenants`, sin cambios en el código de negocio, Flutter, SRI ni producción
- [x] Sin secretos versionados; `docs/PLAN-SALIR-DE-NGROK.md` intacto
- [ ] Ejecución del CI en GitHub (requiere push autorizado)
- [ ] Aprobación del usuario

## Próxima fase

SAAS-03 (requiere aprobación y la decisión D1 de SAAS-01). Quedan **fuera** de SAAS-02 y para fases posteriores: **SAAS-03 … SAAS-14** (modelo tenant, `tenant_id`, TenantContext, extensión Prisma, JWT con tenant, RBAC SaaS, aislamiento A/B, Flutter, WebSockets e IA por tenant, billing SaaS, etc.).
