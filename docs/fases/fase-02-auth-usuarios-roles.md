# Auth, usuarios y roles

**Estado:** Completada — aprobada avance a Fase 03

## Objetivo de la fase

Migrar autenticación y autorización del PHP legacy (sesiones) hacia **JWT Access + Refresh** en NestJS, con revocación server-side y protección por roles.

## Archivos PHP analizados

- `gym-system/app/controllers/AuthController.php`
- `gym-system/app/lib/Auth.php`
- `gym-system/app/models/Usuario.php`

## Tablas involucradas

- `usuarios` (lectura/validación login)
- `auth_refresh_tokens` (**nueva**, solo NestJS — refresh hasheado + revocación)

## Reglas de negocio detectadas

- Login por `email` + `password` con `password_verify` (bcrypt PHP `$2y$`)
- Usuario `inactivo` → rechazo con mensaje controlado
- Credenciales incorrectas → mensaje genérico
- Roles staff: `admin`, `recepcionista`, `entrenador`
- Sin sesiones PHP en la API nueva

## Nuevos módulos NestJS creados

- `AuthModule` — login, refresh, logout, me
- `UsersModule` — validación credenciales + listado staff
- Guards: `JwtAccessGuard`, `JwtRefreshGuard`, `RolesGuard`
- Strategies: `JwtAccessStrategy`, `JwtRefreshStrategy`
- Decoradores: `@Public()`, `@Roles()`, `@CurrentUser()`
- `RefreshTokenService` — hash SHA-256, rotación y revocación

## Nuevas pantallas o funcionalidades Flutter

Ninguna (consumo API documentado en Swagger).

## Endpoints creados

| Método | Ruta | Auth | Rol |
|--------|------|------|-----|
| POST | `/api/auth/login` | Público | — |
| POST | `/api/auth/refresh` | Refresh token (body) | — |
| POST | `/api/auth/logout` | Refresh token (body) | — |
| GET | `/api/auth/me` | Bearer access | cualquier staff autenticado |
| GET | `/api/users` | Bearer access | `admin` |
| GET | `/api/users/:id` | Bearer access | `admin` |
| POST | `/api/users` | Bearer access | `admin` |
| PATCH | `/api/users/:id` | Bearer access | `admin` |
| PATCH | `/api/users/:id/status` | Bearer access | `admin` |
| PATCH | `/api/users/:id/password` | Bearer access | `admin` |

## Cambios de base de datos

- Tabla nueva `auth_refresh_tokens` aplicada con `prisma db push` (sin reset de datos legacy)
- `prisma migrate dev` no usable por drift en BD importada; se usó `db push` como alternativa segura

## Pruebas realizadas

| Escenario | Resultado |
|-----------|-----------|
| Login correcto (`admin@gym.com`) | OK — tokens emitidos |
| GET `/api/auth/me` con access válido | OK |
| POST `/api/auth/refresh` | OK — rotación de refresh |
| POST `/api/auth/logout` | OK — revocación |
| GET `/api/auth/me` sin token | 401 |
| GET `/api/users` como admin | OK |
| `npm run build` | OK |

## Botones probados

N/A (API only).

## Errores encontrados

1. `prisma migrate dev` pedía reset por drift (BD legacy sin historial de migraciones)
2. Hashes bcrypt PHP (`$2y$`) no comparaban directo en Node → normalización a `$2a$`
3. Password del backup no coincidía con `123456` → se actualizó hash en dev para pruebas

## Soluciones aplicadas

- `prisma db push` para crear solo `auth_refresh_tokens`
- Normalización `$2y$` → `$2a$` en `UsersService`
- Variables JWT en `.env.example`
- Guards globales en `AppModule`

## Pendientes

- Login socio (`userType: member`) en fase de app móvil
- CRUD completo de usuarios (crear/editar/desactivar) — parcial en Fase 02
- Baseline formal de migraciones Prisma (opcional, no bloqueante)

## Cómo hacer rollback

- Eliminar rutas `auth/` y `users/` del backend NestJS
- `DROP TABLE auth_refresh_tokens;` (no afecta PHP legacy)
- PHP sigue usando sesiones sin cambios

## Estado final de la fase

Auth JWT operativo con Access/Refresh, revocación, roles y Swagger documentado.

### Guía de prueba (Fase 02)

1. Variables en `backend-nest/.env`:
   - `JWT_ACCESS_SECRET`, `JWT_REFRESH_SECRET`
   - `JWT_ACCESS_EXPIRES_IN=15m`, `JWT_REFRESH_EXPIRES_IN=7d`

2. Levantar API:
```bash
cd backend-nest
npm run start:dev
```

3. Login (Swagger o curl):
```bash
POST http://localhost:3000/api/auth/login
{ "email": "admin@gym.com", "password": "123456" }
```

4. Perfil:
```bash
GET http://localhost:3000/api/auth/me
Authorization: Bearer <accessToken>
```

5. Refresh:
```bash
POST http://localhost:3000/api/auth/refresh
{ "refreshToken": "<refreshToken>" }
```

6. Logout:
```bash
POST http://localhost:3000/api/auth/logout
{ "refreshToken": "<refreshToken>" }
```

7. Fallos comunes:
   - 401 login → email/contraseña o hash PHP incompatible
   - 401 me → access expirado → usar refresh
   - 403 users → rol distinto de admin

8. Siguiente paso: aprobar **Fase 03 (Socios/membresías)**.

## Riesgos mitigados (auditoría cierre)

| ID | Mitigación |
|----|------------|
| R07 | `THROTTLE_LOGIN_LIMIT` (default 30/min) + auditoría reutiliza tokens |
| R08 | `npm run db:push:legacy` documentado en guía backend |
| R09 | `validateEnvOnBootstrap()` en `main.ts` |
| R10 | Prueba JWT expirado en `npm run audit:phase-02` |

Auditoría: **21/21 OK**.
