# Guía de Instalación — Backend NestJS

**Estado:** Actualizada (Fases 01–02)

## Requisitos

- Node.js 20+
- npm 10+
- MySQL/MariaDB con base `ec_gym_system`
- (Opcional) Laragon/XAMPP con MariaDB en `127.0.0.1:3306`

## Instalación

```bash
cd backend-nest
npm install
copy .env.example .env
```

## Base de datos

Importar backup legacy (primera vez):

```bash
npm run db:import-legacy
npm run db:pull
npm run db:generate
```

Tabla adicional NestJS (refresh tokens) — **usar en BD legacy importada**:

```bash
npm run db:push:legacy
```

No usar `prisma migrate dev` si pide reset destructivo (ver R08 en `docs/11-riesgos-y-rollback.md`).

## Variables de entorno (`.env`)

| Variable | Descripción |
|----------|-------------|
| `PORT` | Puerto API (default 3000) |
| `DATABASE_URL` | Conexión MySQL/MariaDB |
| `JWT_ACCESS_SECRET` | Secreto access token |
| `JWT_REFRESH_SECRET` | Secreto refresh token |
| `JWT_ACCESS_EXPIRES_IN` | Ej. `15m` |
| `JWT_REFRESH_EXPIRES_IN` | Ej. `7d` |
| `CORS_ORIGINS` | Orígenes CSV permitidos |
| `SWAGGER_ENABLED` | `true` en desarrollo |
| `THROTTLE_TTL_MS` / `THROTTLE_LIMIT` | Rate limit global (default 120/min) |
| `THROTTLE_LOGIN_TTL_MS` / `THROTTLE_LOGIN_LIMIT` | Rate limit login (default 30/min) |

**Producción:** `NODE_ENV=production` exige `JWT_*_SECRET` únicos de 32+ caracteres (sin placeholders).

**No commitear `.env`** (está en `.gitignore`).

## Ejecutar

```bash
# desarrollo
npm run start:dev

# producción
npm run build
npm run start:prod
```

## Verificación

- Health: `GET http://localhost:3000/api/health` → `database.status = up`
- Swagger: `GET http://localhost:3000/api/docs`
- Login: `POST http://localhost:3000/api/auth/login`

## Scripts útiles

| Script | Uso |
|--------|-----|
| `npm run db:import-legacy` | Importa `gym-system/bk_basededatos.sql` |
| `npm run db:push:legacy` | Sincroniza schema Prisma sin reset (BD legacy) |
| `npm run db:generate` | Genera Prisma Client |
| `npm run audit:phase-02` | Auditoría runtime Fases 01–02 (21 pruebas) |
| `npm run audit:phase-03` | Auditoría runtime Fase 03 (21 pruebas) |

## Estructura principal

```text
backend-nest/src/
  auth/          # JWT login, refresh, logout, me
  users/         # CRUD staff (admin)
  members/       # CRUD socios + membresía calculada
  plans/         # CRUD planes
  memberships/   # Crear/cancelar membresías
  common/        # decorators, filters, interceptors
  database/      # PrismaService
  health/        # Health check
```

## Notas

- `prisma migrate dev` puede pedir reset en BD importada (drift). Usar `npm run db:push:legacy` para tablas nuevas NestJS.
- Usar `127.0.0.1` en `DATABASE_URL` en Windows evita problemas IPv6.
