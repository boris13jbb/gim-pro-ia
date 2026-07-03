# Decisiones Técnicas

| Fecha | Decisión | Motivo |
|---|---|---|
| 2026-07-02 | MySQL existente `ec_gym_system` como BD central inicial | Compatibilidad con PHP legacy durante migración |
| 2026-07-02 | Prisma como ORM preferido para NestJS | Tipado, migraciones, alineado con reglas del proyecto |
| 2026-07-02 | No modificar PHP en Fase 00 | Solo diagnóstico y documentación |
| 2026-07-02 | Corregir bugs E01–E13 en NestJS, no en PHP | Evitar romper legacy; mejorar en nueva arquitectura |
| 2026-07-02 | JWT Access + Refresh (sin sesiones PHP) | Regla obligatoria migración; Flutter solo consume tokens |
| 2026-07-02 | Refresh token hasheado SHA-256 en `auth_refresh_tokens` | Nunca persistir refresh en texto plano |
| 2026-07-02 | Rotación de refresh en `/api/auth/refresh` | Revoca token anterior al emitir uno nuevo |
| 2026-07-02 | `JwtAccessGuard` global + `@Public()` | Equivalente a JwtAuthGuard; rutas públicas explícitas |
| 2026-07-02 | `prisma db push` para BD legacy importada | `migrate dev` detecta drift y pediría reset destructivo |
| 2026-07-02 | Compatibilidad bcrypt PHP `$2y$` → `$2a$` | Validar contraseñas del backup legacy |
| 2026-07-02 | Regla Cursor `migracion-gym-nombres-estandar.mdc` (`alwaysApply: true`) | Nombres claros, en inglés en código, diccionario único y consistencia PHP → NestJS → Flutter |
| 2026-07-02 | Diccionario oficial: `member`, `membership`, `cashRegister`, etc. | Evitar sinónimos (`subscriptions`, `socios`, `clients`) en código nuevo |
| 2026-07-02 | Renombrar API Fase 03: `subscriptions` → `memberships` | Alinear con diccionario oficial sin cambiar tabla legacy `suscripciones` |
| 2026-07-02 | `parseLocalDateString` para fechas `YYYY-MM-DD` | Evitar desfase UTC que marcaba membresías vencidas el mismo día |
| 2026-07-02 | Anti-duplicado asistencia mismo día (409) | Mejora sobre PHP; regla de negocio migración |
| 2026-07-02 | `validateEnvOnBootstrap()` en producción | Bloquea arranque con secretos JWT placeholder o &lt; 32 chars (R09) |
| 2026-07-02 | Throttle configurable vía `THROTTLE_*` | Evita 429 en auditoría y pruebas; login default 30/min (R07) |
| 2026-07-02 | Script `db:push:legacy` | Flujo documentado para BD importada sin reset (R08) |
| 2026-07-02 | Auditoría JWT expirado en `audit-phase-02` | Firma token con `expiresIn: -1` y valida 401 (R10) |

---

## Cambio de nombre

### Nombre anterior
- Módulo/rutas: `SubscriptionsModule`, `/api/subscriptions`
- Ruta anidada: `GET /api/members/:id/subscriptions`
- DTO: `CreateSubscriptionDto` (`socioId`, `fechaInicio`)
- Respuestas: `socioId`, `estadoEfectivo`, `membresiaValida`, `suscripcionActual`

### Nombre nuevo
- Módulo/rutas: `MembershipsModule`, `/api/memberships`
- Ruta anidada: `GET /api/members/:id/memberships`
- DTO: `CreateMembershipDto` (`memberId`, `startDate`, `receiptType`)
- Respuestas: `memberId`, `effectiveStatus`, `isMembershipValid`, `currentMembership`

### Motivo
Aplicar diccionario oficial (`membership`) y regla `migracion-gym-nombres-estandar.mdc`. La tabla BD `suscripciones` permanece sin cambios (legacy PHP).

### Archivos afectados
- `backend-nest/src/memberships/**` (nuevo)
- `backend-nest/src/subscriptions/**` (eliminado)
- `backend-nest/src/app.module.ts`
- `backend-nest/src/members/members.controller.ts`
- `backend-nest/src/members/members.service.ts`
- `docs/07-endpoints-api.md`, `docs/fases/fase-03-socios-membresias.md`

### Riesgo
**Breaking change** en clientes que consumían `/api/subscriptions` o campos en español (`socioId`, `fechaInicio`). Flutter aún no implementado en este repo.

### Pruebas
- `npm run build` OK tras el renombrado

---

## Convención de nombres (regla oficial)

**Fuente de verdad:** `.cursor/rules/migracion-gym-nombres-estandar.mdc`

Aplica a archivos, carpetas, variables, funciones, clases, servicios, controladores, DTOs, entidades, módulos, componentes Flutter, providers, endpoints, tablas/columnas nuevas y documentación técnica.

### Principios

1. **No inventar nombres** sin revisar PHP (`gym-system/`), BD, `docs/`, NestJS (`backend-nest/`) y Flutter (`frontend-flutter/`).
2. **Código en inglés**; documentación puede estar en español.
3. **Nombres cortos y descriptivos** (funciones 2–4 palabras, variables 1–3 palabras).
4. **Un concepto = un nombre** en todo el proyecto.

### Diccionario oficial del proyecto

| Concepto (español) | Nombre estándar (código) |
|---|---|
| Usuario del sistema | `user` |
| Socio del gimnasio | `member` |
| Rol | `role` |
| Permiso | `permission` |
| Plan | `plan` |
| Membresía / suscripción | `membership` |
| Asistencia | `attendance` |
| Producto | `product` |
| Categoría | `category` |
| Inventario | `inventory` |
| Venta | `sale` |
| Detalle de venta | `saleItem` |
| Caja | `cashRegister` |
| Pago | `payment` |
| Reporte | `report` |
| Progreso físico | `bodyProgress` |
| Medida corporal | `bodyMeasurement` |
| Rutina | `workoutRoutine` |
| Facturación SRI | `billingSri` |
| Notificación | `notification` |
| Asistente IA | `aiAssistant` |
| Auditoría | `auditLog` |

### Convenciones por stack

| Stack | Carpetas/archivos | Clases | Variables/funciones | Constantes |
|---|---|---|---|---|
| NestJS | kebab-case (`members.service.ts`) | PascalCase | camelCase | UPPER_SNAKE_CASE |
| Flutter | snake_case (`login_page.dart`) | PascalCase | camelCase | — |
| BD (nuevo) | snake_case (`member_id`, `created_at`) | — | — | — |
| Endpoints REST | kebab-case plural (`/api/members`, `/api/cash-register`) | — | — | — |

### Endpoints REST estándar

`/api/users`, `/api/members`, `/api/plans`, `/api/memberships`, `/api/attendance`, `/api/products`, `/api/sales`, `/api/cash-register`, `/api/body-progress`, `/api/workout-routines`, `/api/billing-sri`, `/api/ai-assistant`

Verbos en el método HTTP (`GET`, `POST`, `PATCH`, `DELETE`), no en la ruta.

### Nota de migración

La tabla legacy `suscripciones` y el PHP `/suscripciones/*` no se renombran. Solo la **API NestJS** usa `memberships` y campos en inglés en request/response.

### Formato para cambios de nombre

```md
## Cambio de nombre
### Nombre anterior
### Nombre nuevo
### Motivo
### Archivos afectados
### Riesgo
### Pruebas
```
