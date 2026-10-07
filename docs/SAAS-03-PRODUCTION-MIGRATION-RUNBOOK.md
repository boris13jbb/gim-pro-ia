# SAAS-03 — Runbook de migración multi-tenant en producción

> **NO EJECUTADO.** Documento de procedimiento. Requiere aprobación explícita, ventana de mantenimiento
> y responsable designado. Contexto técnico: [`SAAS-03-MULTI-TENANT-DB.md`](SAAS-03-MULTI-TENANT-DB.md).

## 0. Precondiciones (todas obligatorias)

| # | Condición | Cómo verificar |
|---|---|---|
| P1 | Cambios de SAAS-03 aprobados, commiteados y con CI verde en la rama a desplegar | GitHub Actions `backend-ci` |
| P2 | El backend desplegado incluye el schema/cliente Prisma de SAAS-03 (`npm ci` + `npm run build`) | build del artefacto |
| P3 | Ensayo con copia real reciente: `npm run db:tenant:rehearsal -- --from-backup` = PASS | salida del script |
| P4 | Espacio en disco ≥ 3× el tamaño de la base (backup + reconstrucción de tablas) | `df` / explorador |
| P5 | Ventana de mantenimiento comunicada (estimación actual 15–30 min) | aviso a recepción/socios |
| P6 | Nadie crea un segundo tenant hasta SAAS-04/05 | acuerdo del equipo |

Si alguna falla: **no continuar**.

## 1. Inicio de ventana

1. Avisar del inicio de mantenimiento.
2. Detener el backend NestJS (y el PHP legacy si escribe en la misma base). Objetivo: **cero escrituras** durante la migración.
3. Confirmar que no hay conexiones de aplicación activas (`SHOW PROCESSLIST`).

## 2. Backup verificado

```bash
cd backend-nest
# BACKUP_DATABASE_URL apunta a la base de producción; mysqldump solo LEE (--single-transaction).
BACKUP_DATABASE_URL="mysql://<usuario>:<clave>@<host>:3306/ec_gym_system" \
BACKUP_DIR="<ruta segura fuera del repo>" \
npm run db:backup
```

4. Anotar el archivo y su `.sha256`. Copiar ambos a un segundo medio.
5. Verificar que el backup restaura (en el servidor de pruebas):

```bash
TEST_DATABASE_URL="mysql://<usuario>:<clave>@<host-pruebas>:3306" \
npm run db:restore:test -- --file <backup.sql> --drop-after
```

## 3. Preflight sobre la copia (sin tocar producción)

6. Ejecutar el ensayo completo con el backup recién tomado:

```bash
TEST_DATABASE_URL="mysql://...@<host-pruebas>:3306" \
BACKUP_DATABASE_URL="mysql://...@<host>:3306/ec_gym_system" \
npm run db:tenant:rehearsal -- --from-backup
```

- `PASS` → continuar.
- `BLOCKED_BY_DATA_INTEGRITY` (exit 2) → **abortar la ventana**. Los conteos indican qué revisar (configuración múltiple, duplicados). No corregir datos sin decisión de negocio.
- `FAILED` → abortar y analizar.

## 4. Registrar el baseline

La base de producción se creó sin historial Prisma. Se marca el baseline como aplicado (no ejecuta SQL de esquema; solo crea `_prisma_migrations` y su fila):

```bash
DATABASE_URL="mysql://...@<host>:3306/ec_gym_system" \
npx prisma migrate resolve --applied 0001_baseline_current_schema
DATABASE_URL="..." npx prisma migrate status   # debe listar solo 0002 como pendiente
```

Si `migrate status` muestra otra cosa: **detener**.

## 5. Aplicar 0002

```bash
DATABASE_URL="mysql://...@<host>:3306/ec_gym_system" npx prisma migrate deploy
```

- No usar `prisma db push` ni `migrate dev`.
- Si falla: ir a **Rollback (§8)**. No reintentar ni editar el SQL.

## 6. Validación post-migración (solo lectura)

```sql
SELECT id, slug, status FROM tenants;                         -- 1 fila, slug 'iron-gym'
SELECT COUNT(*) FROM tenant_memberships;                      -- = SELECT COUNT(*) FROM usuarios
SELECT COUNT(*) FROM information_schema.TRIGGERS
 WHERE TRIGGER_SCHEMA = 'ec_gym_system';                      -- 25
SELECT TABLE_NAME FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = 'ec_gym_system' AND COLUMN_NAME = 'tenant_id'
   AND IS_NULLABLE = 'YES';                                   -- 0 filas
SELECT migration_name, finished_at FROM _prisma_migrations;   -- 0001 y 0002 con finished_at
```

Comparar conteos por tabla con los del backup (el ensayo los imprime).

## 7. Arranque y smoke test

7. Arrancar el backend; `GET /api/health` → `database: up`.
8. Login staff, login socio (DNI y email), listar socios, registrar una asistencia, abrir caja y una venta de prueba anulable, consultar un reporte, emitir/consultar un comprobante **solo en ambiente de pruebas SRI**.
9. Verificar en la base que las filas nuevas tienen `tenant_id` del tenant `iron-gym`.
10. Cerrar la ventana y avisar.

## 8. Rollback

| Situación | Acción |
|---|---|
| Falla en §4 (resolve) | Sin cambios de esquema. Opcional: `DROP TABLE _prisma_migrations` solo si se creó en este intento. Reabrir el sistema con el código anterior. |
| Falla en §5 (deploy) | La DDL no es transaccional: la base queda **parcial**. **Restaurar el backup de §2** sobre la base (procedimiento de restore del DBA, con la aplicación detenida), verificar SHA-256 y conteos, desplegar el código anterior. |
| Falla en §6/§7 | Si no hubo escrituras reales: restaurar el backup. Si las hubo: evaluar con el responsable; el rollback manual (eliminar triggers/FK/columnas) está descrito en la guía técnica y debe ensayarse antes en `gim_test_*`. |

Nunca ejecutar rollback sin backup verificado ni sin aprobación del responsable.

## 9. Registro

Anotar en `docs/05-bitacora-migracion.md`: fecha, responsable, archivo y SHA-256 del backup, duración de cada paso, resultados de §6/§7 e incidencias.
