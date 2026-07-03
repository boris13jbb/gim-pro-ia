# Backend base NestJS

**Estado:** Completada y validada — pendiente aprobación para Fase 02

## Objetivo de la fase

Crear `backend-nest/` con una base profesional NestJS preparada para la migración, **sin modificar** el sistema PHP legacy.

## Archivos PHP analizados

Sin análisis adicional (la referencia principal es la documentación de Fase 00).

## Tablas involucradas

- Base `ec_gym_system` importada desde `gym-system/bk_basededatos.sql`
- **18 tablas** introspectadas en `prisma/schema.prisma` (incluye tablas SRI/facturación)

## Reglas de negocio detectadas

N/A (Fase 01 no migra lógica de negocio).

## Nuevos módulos NestJS creados

- Base NestJS (`src/main.ts`, `src/app.module.ts`)
- `DatabaseModule` + `PrismaService` (adapter MariaDB para Prisma 7)
- `HealthController` (Terminus + ping DB)
- `HttpExceptionFilter` global (errores consistentes)
- `ResponseTransformInterceptor` global (respuesta consistente)

## Nuevas pantallas o funcionalidades Flutter

Ninguna.

## Endpoints creados

- `GET /api/health` (verifica API + conexión DB)
- `GET /api/docs` (Swagger, si `SWAGGER_ENABLED=true`)

## Cambios de base de datos

- **Local:** creada e importada `ec_gym_system` desde backup legacy
- **Prisma:** `prisma db pull` generó 18 modelos
- **Ajuste manual:** enum `configuracion_sri_ambiente` mapeado (`pruebas`→`1`, `produccion`→`2`) porque Prisma no admite valores numéricos como nombre de enum

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| `npm run db:import-legacy` | OK — 18 tablas |
| `npx prisma db pull` | OK — 18 modelos |
| `npx prisma generate` | OK |
| `npm run build` | OK |
| `GET /api/health` | OK — `database.status = "up"` |
| `GET /api/docs` | OK — HTTP 200 |

## Botones probados

N/A.

## Errores encontrados

1. **BD inexistente:** `ec_gym_system` no existía en MariaDB local → pool timeout en health
2. **SQL MySQL 8 vs MariaDB 10.4:** collation `utf8mb4_0900_ai_ci` y `ENCRYPTION='N'` no compatibles
3. **Enum Prisma:** `configuracion_sri_ambiente` con valores `1`/`2` comentados por introspección

## Soluciones aplicadas

- Script `backend-nest/scripts/import-legacy-db.mjs` adapta SQL y importa backup
- Scripts npm: `db:import-legacy`, `db:pull`, `db:generate`
- Enum SRI corregido con `@map("1")` y `@map("2")`
- Base lista para Fase 02 (JWT Access/Refresh) según `.cursor/rules/migracion-gym-jwt-auth.mdc`

## Pendientes

- Implementar módulos `auth/users/roles` en Fase 02
- Revisar comentarios de columnas en BD (advertencia Prisma, no bloqueante)

## Cómo hacer rollback

- Eliminar/ignorar `backend-nest/` no afecta a `gym-system/` (PHP sigue operativo)
- Para revertir BD local: `DROP DATABASE ec_gym_system;` (solo entorno dev)

## Estado final de la fase

Base NestJS operativa con conexión real a `ec_gym_system`, health check en verde y Swagger accesible.

### Guía de prueba (Fase 01)

1. **Importar BD (solo primera vez o si falta la base):**

```bash
cd backend-nest
copy .env.example .env
npm run db:import-legacy
npm run db:pull
npm run db:generate
```

2. **Levantar API:**

```bash
npm run start:dev
```

3. **Verificar en navegador:**
   - `http://localhost:3000/api/health` → `database.status = "up"`
   - `http://localhost:3000/api/docs` → UI Swagger visible

4. **Posibles fallos:**
   - MariaDB/MySQL apagado → levantar servicio (Laragon/XAMPP)
   - `ER_BAD_DB_ERROR` → ejecutar `npm run db:import-legacy`
   - Puerto 3000 ocupado → cambiar `PORT` en `.env`

5. **Siguiente paso:** aprobar inicio de **Fase 02 (JWT + usuarios + roles)**.
