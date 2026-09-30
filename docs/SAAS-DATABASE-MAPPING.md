# Mapeo de Base de Datos → Multi-Tenant — Gim Pro IA

**Fecha:** 2026-03-30  
**Fuente canónica:** `backend-nest/prisma/schema.prisma`  
**Motor:** MySQL (`DATABASE_URL` → `ec_gym_system`)  
**ORM:** Prisma  

Clasificación:

| Código | Significado |
|---|---|
| **A** | Global (plataforma / compartido entre tenants) |
| **B** | Tenant-scoped (debe llevar `tenant_id`) |
| **C** | User-scoped (pertenece a un usuario; tenant vía join o columna) |
| **D** | System / admin plataforma |

---

## 1. ¿Existe entidad gimnasio hoy?

**No.** El “gimnasio” está implícito en:

- Branding hardcodeado (“Iron Gym”)
- Fila singleton `configuracion`
- Todos los registros de negocio en una sola BD sin partición

**Entidad propuesta:** `tenants` (organización = gym en Fase 2; branches después).

---

## 2. Tabla de auditoría de mapeo

| TABLA ACTUAL | CLASIFICACIÓN | REQUIERE TENANT_ID | RELACIÓN PROPUESTA | RIESGO | MIGRACIÓN |
|---|---|---|---|---|---|
| *(nueva)* `tenants` | A/D | N/A (es la raíz) | PK | — | Crear en M2 |
| *(nueva)* `tenant_memberships` | B/C | sí (propia) | `tenant_id`→tenants, `user_id`→usuarios | alto | Crear en M2 |
| *(nueva)* `saas_plans` | A | no | catálogo platform | bajo | Crear cuando billing |
| *(nueva)* `saas_subscriptions` | B | sí | `tenant_id`→tenants | medio | Crear cuando billing |
| *(nueva)* `ai_usage_records` | B | sí | `tenant_id`→tenants | medio | Crear con metering |
| *(nueva)* `audit_logs` | B (+D) | sí (nullable platform) | `tenant_id` opcional | medio | Crear con enforcement |
| *(nueva)* `platform_users` | D | no | `user_id`→usuarios | alto | Crear con PLATFORM_ADMIN |
| `usuarios` | A (+ membership) | no obligatorio en fila | acceso vía `tenant_memberships` | alto | Memberships en M3 |
| `socios` | B | **sí** | `tenant_id`→tenants | **crítico** | Backfill M3; unique `(tenant_id,dni)` |
| `planes` | B | **sí** | planes de membresía gym por tenant | alto | Backfill M3 |
| `suscripciones` | B | **sí** (o via socio) | preferible columna + FK socio mismo tenant | alto | Backfill M3 |
| `asistencias` | B | sí (o via socio) | `tenant_id` denormalizado recomendado para reportes | alto | Backfill M3 |
| `medidas` | B | via socio / opcional columna | `socio_id` + tenant del socio | medio | Validar integridad |
| `rutinas` | B | via socio / opcional | igual | medio | Validar integridad |
| `categorias` | B | **sí** | `tenant_id`→tenants | alto | Backfill M3 |
| `productos` | B | **sí** | `tenant_id`→tenants | alto | Backfill M3 |
| `movimientos_inventario` | B | **sí** | `tenant_id` + producto mismo tenant | alto | Backfill M3 |
| `cajas` | B | **sí** | `tenant_id` + usuario membership | **crítico** | Backfill M3 |
| `ventas` | B | **sí** | `tenant_id` | **crítico** | Backfill M3 |
| `detalle_ventas` | B | via venta | integridad referencial basta al inicio | alto | Via parent |
| `gastos` | B | **sí** | hoy sin FK usuario/caja | alto | Backfill M3 |
| `configuracion` | B | **sí** (1:1 tenant) | migrar de singleton a por-tenant | **crítico** | Convertir o `tenant_settings` |
| `comprobantes_electronicos` | B | **sí** | unique incluir tenant | **crítico** | Backfill + unique |
| `comprobantes_detalle` | B | via comprobante | — | alto | Via parent |
| `sri_log` | B | via comprobante / columna | — | medio | Backfill |
| `sri_series` | B | **sí** | unique `(tenant_id,tipo_doc,serie)` | **crítico** | Backfill |
| `auth_refresh_tokens` | C | opcional | scope por user/member; tenant vía join | medio | Opcional `tenant_id` |
| `ai_conversations` | B | **sí** | `tenant_id` + `member_id` mismo tenant | alto | Backfill M3 |
| `ai_messages` | B | via conversation | — | medio | Via parent |
| `notifications` | B | **sí** | `tenant_id` + `member_id` | medio | Backfill M3 |

---

## 3. Datos globales vs tenant vs user (resumen)

### A — Globales / plataforma

- Catálogo `saas_plans`
- (Futuro) feature flags globales, release notes

### B — Tenant-scoped (mayoría del negocio)

Socios, planes gym, suscripciones, asistencias, inventario, POS, SRI, IA conversations, notificaciones, configuración comercial.

### C — User-scoped

- `usuarios` (identidad staff global)
- `auth_refresh_tokens` (sesión)
- Membresías en `tenant_memberships` unen C↔B

### D — System

- `platform_users`
- Operaciones auditadas cross-tenant

---

## 4. Relaciones críticas a preservar

```text
tenants 1──* socios 1──* suscripciones *──1 planes
                └──* asistencias, medidas, rutinas, ai_conversations, notifications, ventas

tenants 1──* usuarios (vía tenant_memberships)
tenants 1──* categorias 1──* productos 1──* movimientos_inventario
tenants 1──* cajas 1──* ventas 1──* detalle_ventas
tenants 1──1 configuracion/tenant_settings
tenants 1──* sri_series, comprobantes_electronicos
tenants 1──* saas_subscriptions *──1 saas_plans
```

Regla de integridad: **hijos no pueden apuntar a padres de otro tenant** (checks en servicio + opcional DB triggers/composites).

---

## 5. Unicidades a redefinir

| Actual | Objetivo |
|---|---|
| `socios.dni` UNIQUE | `@@unique([tenant_id, dni])` |
| `usuarios.email` UNIQUE | Mantener global (usuario único platform-wide) |
| `comprobantes_electronicos` `(tipo_doc, serie, correlativo)` | Incluir `tenant_id` |
| `sri_series` `(tipo_doc, serie)` | Incluir `tenant_id` |
| `configuracion.id` singleton | Un registro por `tenant_id` |

---

## 6. Servicios Nest que tocan cada grupo

| Grupo tablas | Services principales |
|---|---|
| Socios / membership | `members/members.service.ts`, `memberships/memberships.service.ts` |
| Asistencia / QR | `attendance/attendance.service.ts`, `qr-access/` |
| Progreso / rutinas | `body-progress/body-progress.service.ts`, `workout-routines/workout-routines.service.ts` |
| Inventario / POS | `products/`, `categories/`, `inventory/`, `sales/`, `cash-registers/` |
| Reportes | `reports/reports.service.ts` |
| SRI | `billing-sri/*.service.ts` |
| IA | `ai-assistant/ai-chat.service.ts`, `ai-tools.service.ts` |
| Auth | `auth/auth.service.ts`, `refresh-token.service.ts` |
| Users | `users/users.service.ts` |
| Alertas | `membership-alerts/membership-alerts.service.ts` |
| Notificaciones | `notifications/notifications.service.ts` |

Todos los listados `findMany` sin `tenant_id` son candidatos a fuga (ver `SAAS-MULTI-TENANT-MIGRATION.md` §6).

---

## 7. Storage de archivos (no tabla, pero scoped)

| Recurso | Path actual | Path objetivo |
|---|---|---|
| Foto socio | `uploads/members/` (`config/upload.config.ts`) | `uploads/tenants/{tenantId}/members/` |
| Foto producto | `uploads/products/` | `uploads/tenants/{tenantId}/products/` |
| Cert SRI | `SRI_CERT_DIR` | `tenants/{tenantId}/sri/cert/` |
| XML SRI | `SRI_XML_DIR` | `tenants/{tenantId}/sri/xml/` |

Hoy `main.ts` sirve static assets con prefijo `/api/uploads/...` **sin** ACL por tenant → riesgo IDOR de archivos al multi-tenantizar.

---

## 8. Caché

**No hay Redis ni CacheModule** en el backend actual.  
Cuando se introduzca caché:

```text
tenant:{tenantId}:member:{id}
tenant:{tenantId}:product:{id}
tenant:{tenantId}:ai:conv:{id}
```

Prohibido: `member:{id}` global si los IDs se reinician por tenant o se comparten secuencia.

---

## 9. Seed tenant inicial (diseño)

```text
tenants:
  id: 1
  slug: iron-gym
  name: <configuracion.nombre_comercial || 'Iron Gym'>
  status: ACTIVE
  created_at: now()
```

Todos los backfills apuntan a `id=1` (o UUID/slug estable).  
No borrar datos legacy.

---

## 10. Criterios de aceptación del mapeo

- [x] Todas las tablas Prisma actuales clasificadas
- [x] Tablas nuevas propuestas sin implementar
- [x] Riesgos de unique/config/SRI documentados
- [x] Storage y ausencia de caché documentados
- [ ] Aprobación humana antes de DDL

**No ejecutar migraciones en esta fase.**
