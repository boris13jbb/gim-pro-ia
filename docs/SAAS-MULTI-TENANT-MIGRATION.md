# Migración Multi-Tenant — Plan controlado (Fase 1)

**Fecha:** 2026-03-30  
**Principio:** No romper datos ni funcionalidad del gimnasio actual.  
**Estado:** Solo diseño. Ninguna migración Prisma destructiva en esta fase.

---

## 1. Situación de partida

- BD: MySQL `ec_gym_system` (Prisma en `backend-nest/prisma/schema.prisma`)
- Origen: sistema PHP MVC mono-gimnasio (`gym-system/`)
- NestJS consume las mismas tablas legacy + tablas nuevas (`auth_refresh_tokens`, `movimientos_inventario`, `ai_*`, `notifications`)
- **Cero columnas `tenant_id`** en el esquema actual
- Datos existentes = un único negocio (Iron Gym / configuración singleton)

---

## 2. Estrategia por fases (datos + código)

### FASE M1 — Preparación (documentación) ✅ esta entrega

- Auditar tablas y puntos de fuga
- Definir modelo tenant / membership / RBAC
- Diseñar pruebas de aislamiento
- **Sin DDL**

### FASE M2 — Schema aditivo (no destructivo)

Crear tablas nuevas **sin tocar** columnas críticas existentes:

```text
tenants
tenant_memberships
saas_plans                 -- planes comerciales de la plataforma
saas_subscriptions         -- suscripción del tenant al SaaS
ai_usage_records           -- consumo IA/voz
audit_logs                 -- auditoría
-- opcional: tenant_settings (si no se migra configuracion aún)
```

Agregar columnas **nullable** primero:

```text
socios.tenant_id              NULL
usuarios.default_tenant_id    NULL  -- o solo vía tenant_memberships
categorias.tenant_id          NULL
productos.tenant_id           NULL
planes.tenant_id              NULL
cajas.tenant_id               NULL
ventas.tenant_id              NULL
asistencias.tenant_id         NULL  -- o heredar vía socio
suscripciones.tenant_id       NULL
gastos.tenant_id              NULL
comprobantes_electronicos.tenant_id NULL
sri_series.tenant_id          NULL
configuracion.tenant_id       NULL  -- o 1:1 tenant_settings
ai_conversations.tenant_id    NULL
notifications.tenant_id       NULL
movimientos_inventario.tenant_id NULL
```

**Prohibido en M2:** DROP COLUMN, RENAME masivo, NOT NULL inmediato, borrar datos.

### FASE M3 — Tenant semilla + backfill

1. Insertar tenant inicial:

```text
slug: iron-gym (o tenant-demo)
name: Iron Gym / nombre desde configuracion.nombre_comercial
status: ACTIVE
```

2. Backfill SQL (ejemplo conceptual):

```sql
-- Pseudocódigo; ejecutar solo tras backup y aprobación
UPDATE socios SET tenant_id = @seed_tenant_id WHERE tenant_id IS NULL;
UPDATE categorias SET tenant_id = @seed_tenant_id WHERE tenant_id IS NULL;
-- ... todas las tablas B (tenant-scoped)
INSERT INTO tenant_memberships (tenant_id, user_id, role, status)
SELECT @seed_tenant_id, id, rol, 'ACTIVE' FROM usuarios WHERE estado = 'activo';
```

3. Asociar `configuracion` al tenant semilla (settings).

4. Validar conteos:

```text
COUNT(*) tablas hijas con tenant_id NULL → debe ser 0
COUNT(socios) = COUNT(socios WHERE tenant_id = seed)
```

### FASE M4 — Código con dual-mode (compatibilidad)

Introducir resolución de tenant **sin exigir** multi-tenant en UI:

- Si JWT no trae `tenantId` → usar tenant semilla (feature flag `TENANT_ENFORCEMENT=false`)
- Services empiezan a filtrar `where: { tenantId, ... }` cuando el flag está activo
- Flutter sin cambios visibles al inicio

Archivos prioritarios a tocar en implementación futura (no ahora):

| Prioridad | Archivo | Motivo |
|---|---|---|
| P0 | `auth/auth.service.ts`, `jwt-payload.type.ts` | Emitir `tenantId` |
| P0 | `members/members.service.ts` | Listados globales hoy |
| P0 | `products/products.service.ts`, `categories/categories.service.ts` | Inventario global |
| P0 | `sales/sales.service.ts`, `cash-registers/cash-registers.service.ts` | Financiero |
| P0 | `reports/reports.service.ts` | Agregaciones globales |
| P0 | `ai-assistant/ai-tools.service.ts`, `ai-chat.service.ts` | Fuga vía IA |
| P1 | `attendance/attendance.service.ts` | Reportes staff |
| P1 | `billing-sri/*` | Comprobantes / series / config |
| P1 | `websocket/*` | Salas tenant-aware |
| P1 | `config/upload.config.ts` | Paths por tenant |
| P2 | Flutter auth + selector tenant | Solo si multi-membership |

### FASE M5 — Constraints + enforcement

Cuando backfill y tests de aislamiento pasen:

1. `tenant_id NOT NULL` en tablas B
2. FK `tenant_id → tenants.id`
3. Índices compuestos `(tenant_id, id)` / `(tenant_id, dni)` etc.
4. Unicidades re-scoped: `socios.dni` UNIQUE → `@@unique([tenant_id, dni])`
5. `TENANT_ENFORCEMENT=true`
6. Activar creación de nuevos tenants (onboarding)

### FASE M6 — Multi-tenant real

- Onboarding de Tenant B
- Pruebas cruzadas A/B en CI
- PLATFORM_ADMIN para operar tenants
- Billing SaaS (ver `SAAS-BILLING-ROADMAP.md`) — sin Stripe hasta aprobación

---

## 3. Unicidades y conflictos al multi-tenantizar

| Constraint actual | Problema | Propuesta |
|---|---|---|
| `socios.dni` UNIQUE global | Dos gimnasios no pueden compartir mismo DNI de clientes distintos | `UNIQUE(tenant_id, dni)` |
| `usuarios.email` UNIQUE global | OK si usuario platform-global; o permitir email por tenant | Preferir usuarios globales + memberships |
| `planes.nombre` sin unique | OK | Scope por tenant en queries |
| `comprobantes` `unq_comprobante (tipo_doc, serie, correlativo)` | Colisión entre emisores | Incluir `tenant_id` en unique |
| `sri_series` `unq_tipo_serie` | Igual | Incluir `tenant_id` |
| `configuracion` singleton `id` | Bloquea N tenants | 1 fila por tenant o `tenant_settings` |

---

## 4. Orden de backfill recomendado

1. `tenants` (seed)
2. `tenant_memberships` ← `usuarios`
3. `configuracion` / settings
4. Tablas maestras: `categorias`, `planes`, `productos`, `sri_series`
5. `socios`
6. Hijos de socio: `suscripciones`, `asistencias`, `medidas`, `rutinas`, `ai_conversations`, `notifications`
7. Operación: `cajas` → `ventas` → `detalle_ventas` / `movimientos_inventario`
8. Fiscal: `comprobantes_electronicos`, `sri_log`, `gastos`
9. Tokens: `auth_refresh_tokens` (user/member ya apuntan; tenant opcional vía join)

---

## 5. Rollback

| Paso | Rollback |
|---|---|
| Tablas nuevas | DROP tablas SaaS nuevas (si no hay dependencias productivas) |
| Columnas `tenant_id` NULL | Ignorar / DROP COLUMN tras backup si se aborta |
| Backfill incorrecto | Restaurar dump MySQL pre-M3 |
| Feature flag | `TENANT_ENFORCEMENT=false` vuelve a modo semilla |

**Siempre:** dump completo antes de M2/M3. Documentar en `docs/11-riesgos-y-rollback.md` al implementar.

---

## 6. Puntos de fuga actuales (prioridad)

Consultas sin filtro de gimnasio/tenant (patrón dominante: `findMany()` / `findUnique({ where: { id } })`):

| Módulo | Archivo | Riesgo |
|---|---|---|
| Members | `members/members.service.ts` | Listar/obtener cualquier socio por id |
| Users | `users/users.service.ts` | Listar todos los staff de la BD |
| Products/Categories | `products/products.service.ts`, `categories/categories.service.ts` | Inventario compartido |
| Sales/Cash | `sales/sales.service.ts`, `cash-registers/cash-registers.service.ts` | Ventas/cajas globales |
| Plans/Memberships | `plans/plans.service.ts`, `memberships/memberships.service.ts` | Planes y suscripciones globales |
| Reports | `reports/reports.service.ts` | Totales de toda la BD |
| Attendance | `attendance/attendance.service.ts` | Reportes/ranking globales |
| Billing SRI | `billing-sri/*.service.ts` | Comprobantes + config singleton |
| AI | `ai-assistant/ai-tools.service.ts` | Contexto vía services sin tenant |
| Alerts cron | `membership-alerts/membership-alerts.service.ts` | Escanea todas las suscripciones |
| Uploads | `uploads/members/{file}` estático | URL adivinable sin check tenant |

**Nota positiva actual:** AI chat y notificaciones ya aíslan por `memberId` del JWT (`ai-chat.service.ts` `findOwnedConversation`, `WsAuthService`). Eso **no** es aislamiento multi-tenant: dos gimnasios en la misma BD seguirían colisionando a nivel de IDs y listados staff.

---

## 7. Checklist de integridad post-backfill

- [ ] 0 filas tenant-scoped con `tenant_id IS NULL`
- [ ] Todo `usuarios` activo tiene ≥1 `tenant_memberships`
- [ ] Todo `socios` apunta al seed
- [ ] Login staff/socio OK
- [ ] POS, asistencia, IA, voz, SRI smoke tests OK
- [ ] Tests aislamiento A/B en entorno con 2 tenants (staging)

---

## 8. Qué NO hacer todavía

- No ejecutar migraciones en producción
- No exigir `tenantId` en Flutter
- No integrar Stripe
- No renombrar tablas legacy (`socios` → `members`) en la misma ola que multi-tenant
- No merge a `master` sin aprobación

---

## 9. Próximo paso (solo tras aprobación Fase 1)

Propuesta de **Fase 2 implementación controlada**:

1. Migración Prisma aditiva (tablas + columnas NULL)
2. Seed tenant + script backfill idempotente
3. Extender JWT con `tenantId`
4. TenantGuard + scoping en P0 services
5. Suite de tests de aislamiento

**DETENERSE aquí hasta revisión humana.**
