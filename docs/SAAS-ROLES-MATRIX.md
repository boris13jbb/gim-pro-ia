# Matriz RBAC SaaS — Gim Pro IA

**Fecha:** 2026-03-30  
**Fuente actual:** `usuarios_rol` Prisma + `roles.constant.ts` + decoradores `@Roles` en controllers + `staff_permissions.dart`

---

## 1. Roles actuales (producción hoy)

| Rol código | Origen | Alcance |
|---|---|---|
| `admin` | `usuarios.rol` | Staff — acceso administrativo amplio |
| `recepcionista` | `usuarios.rol` | Staff — operación diaria (socios, POS, asistencia) |
| `entrenador` | `usuarios.rol` | Staff — coaching (progreso, rutinas) |
| `socio` | JWT member login (no está en enum `usuarios_rol`) | Cliente — solo sus datos |

**No existen hoy:** `owner`, `manager`, `staff`, `platform_admin`.

---

## 2. Estrategia de evolución de roles

### Opción recomendada (mínimo cambio)

Mantener roles gym actuales en Fase 2–3 y añadir solo lo necesario:

| Rol SaaS | Mapeo | ¿Crear ya? |
|---|---|---|
| `OWNER` | Nuevo o alias de primer admin del tenant | Sí (onboarding) |
| `ADMIN` | = `admin` actual | Reutilizar |
| `RECEPTION` | = `recepcionista` | Reutilizar |
| `TRAINER` | = `entrenador` | Reutilizar |
| `MEMBER` / `CLIENT` | = `socio` | Reutilizar |
| `MANAGER` | Opcional (subconjunto admin sin billing/SRI) | **No** hasta demanda |
| `STAFF` genérico | Opcional | **No** |
| `PLATFORM_ADMIN` | Nivel plataforma | Sí, separado |

No inventar roles “por estética”. Expandir cuando un permiso real lo requiera.

---

## 3. Matriz actual observada (as-is)

Basada en `@Roles` de controllers NestJS (autoridad real). Flutter solo refleja UI.

| Módulo / acción | admin | recepcionista | entrenador | socio |
|---|---|---|---|---|
| Users CRUD | Sí (`users`) | No | No | No |
| Members list/CRUD | Sí | Sí | Lectura/coaching según endpoint | No (usa `/me`) |
| Membership assign/cancel | Sí | Sí | No típico | No |
| Plans | admin (escritura) | Lectura según endpoint | — | No |
| Attendance register/scan | Sí | Sí | Según endpoint | Self `/attendance/self` |
| QR card | Staff | Staff | Staff | `/me/card` |
| Body progress / routines manage | Sí | Parcial | Sí (`canManageCoaching`) | Solo propio |
| Products/inventory | Sí | Lectura/POS | No | No |
| Cash register / sales | Sí | Sí | No | No |
| Reports | admin (típico) | Revisar endpoint | No | No |
| Billing SRI | Sí | Sí (uso) | No | No |
| SRI config | Sí (`sri-config` `@Roles('admin')`) | No | No | No |
| AI chat / voice | No (solo socio) | No | No | Sí |
| Notifications WS | — | — | — | Sí |
| Membership alerts admin | Sí | No | No | Recibe push |

UI Flutter (`staff_permissions.dart`):

- `canManageMembers` → admin, recepcionista  
- `canUsePos` → admin, recepcionista  
- `canManageAdminModules` → admin  
- `canViewCoaching` → admin, recepcionista, entrenador  
- `canManageCoaching` → admin, entrenador  
- `canUseBillingSri` → admin, recepcionista  

---

## 4. Matriz objetivo (to-be) — tenant scoped

Leyenda: C=crear, R=leer, U=actualizar, D=eliminar/anular, A=administrar config, — = denegado.

| Recurso | OWNER | ADMIN | RECEPTION | TRAINER | MEMBER | PLATFORM_ADMIN |
|---|---|---|---|---|---|---|
| Datos de **otro** tenant | — | — | — | — | — | A (explícito + audit) |
| Tenant settings / branding | A | A | R | — | — | A |
| Invitar/cambiar roles staff | A | A (no elevar a OWNER sin regla) | — | — | — | A |
| Socios/clientes | CRUD | CRUD | CRU | R (+escribir progreso) | R propio | — |
| Membresías gym | CRUD | CRUD | CRU | R | R propio | — |
| Asistencias | R + override | R + register | register + R | R limitado | self | — |
| Productos/stock | CRUD | CRUD | RU (POS) | — | — | — |
| Ventas/caja | CRUD | CRUD | CRU | — | — | — |
| Reportes financieros | R | R | R limitado | — | — | R agregado platform |
| SRI emitir | A | A | U (emitir) | — | — | — |
| SRI certificado/config | A | A | — | — | — | soporte |
| Rutinas/progreso | A | A | R | CU | R propio | — |
| AI chat/voz | —* | —* | —* | —* | Usar (límites plan) | Ver usage |
| SaaS billing del tenant | A | R | — | — | — | A |
| Crear/suspender tenants | — | — | — | — | — | A |
| Audit logs tenant | R | R | — | — | — | R global |

\* Staff AI opcional en fase futura; hoy el asistente es **solo socio** (`ai-chat.controller`, `WsAuthService`).

---

## 5. Regla de oro

```text
permiso_efectivo = (rol_en_tenant ∩ acción) ∧ (recurso.tenant_id == jwt.tenantId)
```

Sin la segunda parte, el RBAC es insuficiente.

---

## 6. Membership model propuesto

Tabla `tenant_memberships`:

```text
id
tenant_id          FK → tenants
user_id            FK → usuarios (staff)
role               OWNER | ADMIN | RECEPTION | TRAINER | ...
status             ACTIVE | INVITED | SUSPENDED | REMOVED
invited_by
created_at
updated_at
UNIQUE(tenant_id, user_id)
```

Socios **no** van en `tenant_memberships` al inicio: usan `socios.tenant_id` + login member.

Usuarios platform:

```text
platform_users (user_id, role=PLATFORM_ADMIN, status)
```

Separado de `tenant_memberships`.

---

## 7. JWT objetivo (staff)

```json
{
  "sub": 1,
  "email": "admin@gym.com",
  "userType": "staff",
  "tenantId": 10,
  "roles": ["ADMIN"],
  "membershipId": 55
}
```

### JWT objetivo (socio)

```json
{
  "sub": 25,
  "email": "socio@email.com",
  "userType": "member",
  "role": "socio",
  "memberId": 25,
  "tenantId": 10
}
```

### JWT platform

```json
{
  "sub": 1,
  "userType": "platform",
  "role": "PLATFORM_ADMIN"
}
```

Sin `tenantId` por defecto; operaciones cross-tenant requieren header/contexto **adicional validado** y audit log.

---

## 8. Compatibilidad con enum actual

Durante la migración:

1. Seguir aceptando `admin` / `recepcionista` / `entrenador` en `@Roles`
2. Mapear a constantes SaaS en capa de membership
3. Documentar cambio de nombre en `docs/10-decisiones-tecnicas.md` si se renombra en código

No renombrar el enum MySQL en la misma ola que el backfill de `tenant_id` sin plan de rollback.

---

## 9. Pruebas RBAC + tenant (diseño)

| Caso | Esperado |
|---|---|
| recepcionista Tenant A crea venta en A | 201 |
| recepcionista Tenant A lee producto de B | 404 |
| entrenador intenta abrir caja | 403 |
| socio A lee progreso de socio B | 403/404 |
| admin A llama endpoint platform | 403 |
| PLATFORM_ADMIN lista tenants | 200 |
| PLATFORM_ADMIN lee socios sin impersonation scope | denegar o requerir tenant target auditado |
