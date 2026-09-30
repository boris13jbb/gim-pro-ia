# SaaS Architecture — Gim Pro IA (Fase 1: Auditoría + Diseño)

**Fecha:** 2026-03-30  
**Rama:** `feature/saas-foundation`  
**Estado:** Diseño / no implementado  
**Alcance:** Documentación de arquitectura objetivo. Sin cambios destructivos de BD ni código productivo.

---

## 1. Veredicto de la auditoría

El sistema actual es un **monogimnasio implícito** (single-tenant de facto):

| Hallazgo | Evidencia |
|---|---|
| No existe entidad `tenants` / `organizations` / `gimnasios` | `backend-nest/prisma/schema.prisma` — ningún modelo con `tenant_id` / `gym_id` |
| Branding hardcodeado a “Iron Gym” | `backend-nest/src/ai-assistant/ai-system-instruction.ts` |
| Configuración singleton | Modelo Prisma `configuracion` (fila típica `id=1`) |
| Roles staff globales a la BD | Enum `usuarios_rol`: `admin`, `recepcionista`, `entrenador` |
| Socio = cliente del único gimnasio | Modelo `socios` sin scope de organización |
| Autorización actual = JWT + RolesGuard + ownership de socio | Sin aislamiento entre gimnasios |

**Conclusión:** Hoy un segundo gimnasio en la misma BD compartiría socios, productos, ventas, IA y reportes. La transformación SaaS debe ser progresiva y no romper el gimnasio actual.

---

## 2. Arquitectura actual (as-is)

```text
Flutter (staff + socio)
        │  JWT Bearer + Socket.IO (auth.token)
        ▼
NestJS API (localhost:3000, prefix /api)
  ├── JwtAccessGuard + RolesGuard + ThrottlerGuard (APP_GUARD)
  ├── Auth (staff login + member login + refresh + logout)
  ├── Domain modules → Prisma → MySQL `ec_gym_system`
  ├── AI (Ollama / Gemini / Z.AI) + Voice (Whisper + Piper)
  └── WebSocket namespaces /ai y /events
        │
        ▼
MySQL 8 (Prisma ORM) — esquema legacy PHP + tablas NestJS
```

### 2.1 Backend NestJS — módulos reales

Registrados en `backend-nest/src/app.module.ts`:

| Carpeta | Responsabilidad |
|---|---|
| `auth/` | Login staff/socio, JWT access/refresh, guards, strategies |
| `users/` | CRUD usuarios staff (`usuarios`) |
| `members/` | Socios, fotos, membresía resumen |
| `plans/` | Planes de membresía gym (`planes`) |
| `memberships/` | Suscripciones gym (`suscripciones`) |
| `attendance/` | Asistencias + reportes/export |
| `qr-access/` | Validación QR / carnet |
| `body-progress/` | Medidas corporales |
| `workout-routines/` | Rutinas |
| `categories/`, `products/`, `inventory/` | Inventario POS |
| `cash-registers/`, `sales/` | Caja y ventas |
| `reports/` | Reportes administrativos |
| `billing-sri/` | Facturación electrónica Ecuador |
| `ai-assistant/` | Chat, intent, tools, voz |
| `websocket/` | Gateways `/ai`, `/events` |
| `notifications/`, `membership-alerts/` | Notificaciones + cron alertas |
| `common/`, `config/`, `database/`, `health/` | Transversal |

### 2.2 Autenticación actual

- **Staff:** `POST /api/auth/login` → tabla `usuarios`  
  Payload: `sub`, `email`, `role`, `userType: 'staff'`  
  Archivos: `auth/auth.service.ts`, `auth/types/jwt-payload.type.ts`
- **Socio:** `POST /api/auth/member/login` → tabla `socios`  
  Payload: `sub`, `email`, `role: 'socio'`, `userType: 'member'`, `memberId`
- **Refresh:** hasheado en `auth_refresh_tokens` (`auth/services/refresh-token.service.ts`)
- **Guards globales:** `JwtAccessGuard`, `RolesGuard` (`app.module.ts`)
- **Ownership socio:** `common/utils/member-access.util.ts` → `assertMemberResourceAccess`

### 2.3 Autorización actual (RBAC limitado)

Roles en código: `backend-nest/src/common/constants/roles.constant.ts`

```ts
STAFF_ROLES = ['admin', 'recepcionista', 'entrenador']
ALL_ROLES = [...STAFF_ROLES, 'socio']
```

Coincide con enum Prisma `usuarios_rol`. No existen `owner`, `manager`, `staff` genérico ni `platform_admin`.

UI Flutter refleja permisos en `frontend-flutter/lib/core/staff_permissions.dart` (solo UX; la API es autoridad).

### 2.4 Flutter actual

- Tokens: `frontend-flutter/lib/services/auth_storage.dart` (`FlutterSecureStorage`)
- HTTP: `frontend-flutter/lib/services/api_client.dart` (Dio + refresh en 401)
- Features staff + socio + AI chat/voz
- **No hay** selector de gimnasio / organización / tenant

### 2.5 Infraestructura

| Componente | Estado |
|---|---|
| MySQL `127.0.0.1:3306` / BD `ec_gym_system` | Activo (`.env.example`) |
| ngrok | Scripts `scripts/start-ngrok-gym.ps1`, puerto web **8888** |
| CORS | `backend-nest/src/config/cors.config.ts` + `CORS_ORIGINS` |
| Docker / CI | **No encontrado** en el repo |
| Redis / cache distribuida | **No existe** |
| Uploads | Disco local `uploads/members`, `uploads/products` |

---

## 3. Arquitectura SaaS objetivo (to-be)

```text
PLATFORM (Gim Pro IA)
│
├── PLATFORM_ADMIN (operador SaaS — cross-tenant explícito)
│
└── Tenant / Organization  ← unidad de aislamiento (gimansio/negocio)
    ├── Settings (ex-configuracion, branding, SRI)
    ├── Branches (opcional, fase posterior)
    ├── Users (staff vía tenant_memberships + roles)
    ├── Clients / Members (socios)
    ├── Membership plans & subscriptions (gym)
    ├── Attendance, POS, Inventory, Billing SRI
    ├── AI usage + conversation history
    └── Files: tenants/{tenantId}/...
```

### 3.1 Jerarquía recomendada

| Nivel | Nombre propuesto | Equivalente actual |
|---|---|---|
| Platform | Gim Pro IA | N/A (nuevo) |
| Tenant | `tenants` | Implícito: un solo Iron Gym + `configuracion` |
| Branch | `branches` (fase 2+) | No existe |
| Staff user | `usuarios` + `tenant_memberships` | `usuarios` sin tenant |
| Client | `socios` + `tenant_id` | `socios` globales a la BD |
| SaaS plan | `saas_plans` / `saas_subscriptions` | **No confundir** con `planes` / `suscripciones` (membresías gym) |

### 3.2 Principios de diseño

1. **Tenant derivado del JWT / membership verificada en backend** — nunca del body/query/header del cliente como autorización.
2. **Deny by default** + least privilege.
3. **RBAC dentro del tenant**; no sustituye el filtro `tenant_id`.
4. **PLATFORM_ADMIN ≠ TENANT ADMIN**.
5. Migración no destructiva: primero tenant semilla, luego backfill, luego constraints, luego multi-tenant activo.

### 3.3 Capas a introducir (fases futuras, no esta)

| Capa | Propósito | Ubicación sugerida |
|---|---|---|
| `TenantContext` | `tenantId` + `membershipId` + roles en request | `common/tenant/` |
| `TenantGuard` / interceptor | Exige contexto tenant en rutas privadas | APP_GUARD o middleware |
| Prisma middleware / extension | Auto-filtro `tenant_id` donde aplique | `database/` |
| `TenantMembershipService` | Resolver membresías usuario↔tenant | nuevo módulo |
| Usage metering | Contadores IA/voz | tabla `ai_usage_records` |

---

## 4. Flujo de identidad objetivo

```text
Login staff
  → valida usuario
  → carga tenant_memberships activas
  → si 1 tenant: emite JWT con tenantId + roles
  → si N tenants: emite token “pre-tenant” o lista permitida;
     cliente elige; backend confirma membership y emite JWT scoped

Login socio
  → valida socio
  → tenantId del socio (columna tenant_id)
  → JWT con tenantId + memberId
```

**Prohibido:** `X-Tenant-Id` como única fuente de verdad sin validar membership.

---

## 5. Superficie de aislamiento obligatoria

Todo canal debe heredar el mismo `tenantId` autorizado:

| Canal | Archivos actuales a evolucionar |
|---|---|
| REST | Todos los `*.service.ts` con `findMany`/`findUnique` globales |
| WebSocket `/ai` | `websocket/ai-chat.gateway.ts`, salas → `tenant:{id}:member:{id}` |
| WebSocket `/events` | `websocket/realtime.gateway.ts` |
| IA tools | `ai-assistant/ai-tools.service.ts` |
| Voz | `ai-assistant/voice/*`, `ai-voice.controller.ts` |
| Uploads | `config/upload.config.ts`, static assets en `main.ts` |
| Cron | `membership-alerts/membership-alerts.service.ts` |
| Reportes/export | `reports/`, `*-export.service.ts` |
| SRI | `billing-sri/*` + `configuracion` por tenant |

---

## 6. Decisiones abiertas (requieren aprobación)

1. ¿El tenant es la organización comercial y el “gym/branch” es secundario, o tenant = gym 1:1 al inicio?
2. ¿Un usuario staff puede pertenecer a varios tenants?
3. ¿Los socios pueden existir en un solo tenant (recomendado al inicio)?
4. ¿Estrategia ante ID cross-tenant: **404** (ocultar existencia) o **403**?
5. ¿Reutilizar tabla `configuracion` como settings por tenant o nueva `tenant_settings`?

**Recomendación Fase 1→2:** Tenant = organización/gym 1:1; staff multi-tenant opcional después; socio single-tenant; respuestas **404** en lecturas cross-tenant; migrar `configuracion` a settings por tenant.

---

## 7. Criterio de no-regresión

Mientras no exista segundo tenant real:

- El tenant semilla debe contener **todos** los datos actuales.
- Staff y socios actuales deben seguir logueando sin cambios de UX visibles.
- Endpoints y Flutter existentes deben seguir funcionando (compatibilidad).

---

## 8. Relación con documentación del proyecto

| Documento | Rol |
|---|---|
| `docs/03-arquitectura-objetivo-nestjs-flutter.md` | Arquitectura migración PHP→Nest/Flutter (previa) |
| `docs/SAAS-ARCHITECTURE.md` (este) | Arquitectura SaaS multi-tenant |
| `docs/SAAS-MULTI-TENANT-MIGRATION.md` | Plan de migración de datos y código |
| `docs/SAAS-SECURITY-MODEL.md` | Modelo de seguridad |
| `docs/SAAS-ROLES-MATRIX.md` | Matriz RBAC |
| `docs/SAAS-DATABASE-MAPPING.md` | Clasificación de tablas |
| `docs/SAAS-BILLING-ROADMAP.md` | Planes y billing futuro |
| `docs/SAAS-AI-USAGE.md` | Uso y límites de IA |

**No avanzar a implementación de tablas/guards hasta aprobación explícita de esta Fase 1.**
