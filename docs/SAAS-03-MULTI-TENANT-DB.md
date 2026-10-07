# SAAS-03 — Fundación multi-tenant de la base de datos

> Estado: implementado y validado en bases desechables `gim_test_*`. **No aplicado en `ec_gym_system`.**
> Sin commit, push ni deploy (pendiente de aprobación).

## Objetivo

Crear la base de datos real del modelo SaaS **base compartida + aislamiento por fila (`tenant_id`)**:

- tablas de plataforma `tenants` y `tenant_memberships`;
- `tenant_id NOT NULL` en todas las tablas de negocio;
- foreign keys que **impiden en la base** referencias entre gimnasios;
- unicidades de negocio por tenant (p. ej. `dni`);
- backfill idempotente y fail-fast de los datos actuales hacia un tenant inicial.

Sin cambiar todavía login, JWT, guards, `AuthService`, consultas `findFirst()`, SRI, Flutter, IA ni WebSockets (SAAS-04+).

## Estado inicial

| Aspecto | Antes de SAAS-03 |
|---|---|
| Modelo | single-tenant de facto (23 tablas, 21 FK, 5 únicos) |
| Migraciones | `0001_baseline_current_schema` (SAAS-02); `ec_gym_system` aún **sin** historial Prisma |
| Unicidades | `socios.dni`, `usuarios.email`, `unq_comprobante`, `unq_tipo_serie`, `auth_refresh_tokens.jti` globales |
| `configuracion` | 1 fila, `id` sin autoincrement, leída con `findFirst()` |
| Drift conocido | `configuracion.sri_ambiente` (`Enum("1")` vs `dbgenerated("1")`), equivalente, no se corrige |

## Arquitectura

```text
tenants (PLATFORM) ──< tenant_memberships >── usuarios (GLOBAL, identidad única por email)
   │
   ├──< socios ──< asistencias / medidas / rutinas / notifications / ai_conversations ──< ai_messages
   ├──< planes, suscripciones (socio?, plan?)
   ├──< categorias ──< productos ──< movimientos_inventario (venta?)
   ├──< cajas ──< ventas (socio?) ──< detalle_ventas (producto)
   ├──< gastos, configuracion (1 por tenant)
   └──< sri_series, comprobantes_electronicos ──< comprobantes_detalle, sri_log (comprobante?)
```

Tres capas de defensa en la base:

1. **FK directa** `tenant_id → tenants(id)` (ON DELETE RESTRICT) en las 21 tablas tenant.
2. **FK compuesta** `(parent_id, tenant_id) → parent(id, tenant_id)` en relaciones obligatorias: un hijo no puede apuntar a un padre de otro tenant (error 1452).
3. **Triggers** `BEFORE INSERT/UPDATE` (42 en total: 21 INSERT + 21 UPDATE, uno de cada por tabla tenant):
   - **INSERT:** resuelven `tenant_id` cuando el código actual no lo envía: un hijo lo hereda del padre; una raíz toma el **único** tenant existente ("modo legado"); validan las relaciones **opcionales** (`SIGNAL SQLSTATE '45000'`), porque Prisma no puede declarar una FK compuesta sobre una relación opcional.
   - **UPDATE:** `tenant_id` es **inmutable** una vez asignado. Un `UPDATE … SET tenant_id = otro` se rechaza en las 21 tablas (`tenant_id is immutable: <tabla>`). Así también quedan protegidas las relaciones opcionales contra el movimiento del **padre** (p. ej. `planes` → `suscripciones`). Los UPDATE de otras columnas siguen permitidos.

`tenant_id Int @default(dbgenerated())` deja la columna `NOT NULL` **sin default** en la base y opcional en los `create` de Prisma, así que el código actual compila sin cambios. MariaDB entrega una columna `NOT NULL` omitida al trigger con el valor implícito `0`; por eso los triggers tratan `NULL` y `0` como "sin tenant". Los ids de tenant empiezan en 1.

Con **dos o más tenants**, una raíz sin `tenant_id` se rechaza: no se adivina el gimnasio (fail-closed). SAAS-04/05 pasarán `tenant_id` explícito desde el contexto autenticado; los triggers de resolución de INSERT (modo legado) podrán retirarse entonces. La inmutabilidad de UPDATE permanece.

## Modelos globales

| Tabla | Motivo |
|---|---|
| `usuarios` | `usuarios.email` permanece global porque `usuarios` representa identidad de autenticación global y un usuario puede pertenecer a múltiples tenants mediante `tenant_memberships` (diseño SAAS-01: `SAAS-DATABASE-MAPPING.md`, "Mantener global (usuario único platform-wide)"). No es una consecuencia de D1: D1 define el login del socio (`slug + DNI/email + password`; el slug solo resuelve el tenant y la autorización viene del JWT firmado con `tenantId`/`membershipId`/`role` revalidado por el backend). `usuarios.rol` se conserva para el código actual. |
| `auth_refresh_tokens` | Sesión ligada a `usuarios`/`socios`. El tenant se deriva del titular; SAAS-04 añadirá el contexto tenant al JWT/refresh. `jti` sigue único global. |

## Modelos tenant

Raíz con FK directa a `tenants`: `socios`, `planes`, `categorias`, `cajas`, `gastos`, `configuracion`, `sri_series`, `comprobantes_electronicos`, `suscripciones` (padres opcionales `socio`/`plan`, validados por trigger) y `sri_log` (padre opcional `comprobante`, sin FK física, validado por trigger).

## Modelos tenant-child

Además de la FK directa, tienen FK compuesta al padre: `asistencias`, `medidas`, `rutinas`, `notifications`, `ai_conversations` (→ `socios`), `ai_messages` (→ `ai_conversations`), `productos` (→ `categorias`), `ventas` (→ `cajas`; `socio` opcional por trigger), `detalle_ventas` (→ `ventas`, `productos`), `movimientos_inventario` (→ `productos`; `venta` opcional por trigger) y `comprobantes_detalle` (→ `comprobantes_electronicos`).

## Modelos platform

`tenants` y `tenant_memberships`: los administra la plataforma (onboarding/billing en fases futuras), no un gimnasio.

## Tabla tenants

| Columna | Tipo | Regla |
|---|---|---|
| `id` | INT AI | PK interna; **nunca** se asume `1` |
| `public_id` | CHAR(26) | ULID único (`uq_tenants_public_id`), CHECK Crockford case-sensitive. Identificador expuesto en el futuro (no el `id`) |
| `slug` | VARCHAR(63) | único (`uq_tenants_slug`), CHECK kebab-case minúsculas. Solo resuelve el tenant en el login (D1) |
| `name` | VARCHAR(150) | nombre comercial |
| `status` | ENUM | `active`, `suspended`, `cancelled` (índice `idx_tenants_status`) |
| `plan_code` | VARCHAR(40) NULL | plan SaaS (billing futuro) |
| `timezone` / `country` | VARCHAR(64) / CHAR(2) | `America/Guayaquil` / `EC` |
| `metadata` | JSON NULL | extensible |
| `created_at` / `updated_at` | DATETIME | `@updatedAt` en Prisma |

El modelo Prisma se llama `tenants` (minúsculas, plural) por coherencia con los 23 modelos existentes (`socios`, `usuarios`…), no `Tenant`.

## Tenant memberships

`tenant_memberships(tenant_id, user_id, role, status)`, `UNIQUE(tenant_id, user_id)`, índice `(user_id, status)` para el login.

- `role`: `owner`, `admin`, `reception`, `trainer` (SAAS-ROLES-MATRIX).
- `status`: `active`, `invited`, `suspended`, `removed`.
- FK a `tenants` RESTRICT; FK a `usuarios` CASCADE (borrar la identidad borra sus pertenencias).
- Backfill: un membership por usuario existente. `admin→admin`, `entrenador→trainer`, otro→`reception`; `estado=inactivo→suspended`. **No se infiere `owner`**: asignarlo es una decisión de negocio (SAAS-04/onboarding).

## tenant_id

Presente y `NOT NULL` en 21 tablas (todas las de negocio). Ausente en `usuarios`, `auth_refresh_tokens`, `tenants`. En `tenant_memberships` es parte de la clave.

## Índices

| Índice | Uso |
|---|---|
| `idx_<tabla>_tenant` / `idx_cajas_tenant_estado` / `idx_asistencias_tenant_fecha` / `idx_ventas_tenant_fecha` / `idx_gastos_tenant_fecha` / `idx_suscripciones_tenant_fin` / `idx_mov_inv_tenant_created` | filtros por tenant (SAAS-05) con la columna de orden/estado habitual |
| Índices de FK compuesta (`socio_id`, `categoria_id`, `caja_id`, `venta_id`, `producto_id`, `idx_comp`, `idx_ai_conv_member`, `idx_ai_msg_conv`, `idx_notifications_member_tenant`, `idx_mov_inv_producto`) | redefinidos como `(parent_id, tenant_id)`; conservan su nombre original |

## Foreign keys

- 21 FK `fk_<tabla>_tenant → tenants(id)` ON DELETE/UPDATE RESTRICT: un tenant con datos no puede borrarse.
- 12 FK compuestas con el **nombre original** (`asistencias_ibfk_1`, `medidas_ibfk_1`, `rutinas_ibfk_1`, `fk_notifications_member`, `fk_ai_conv_socio`, `fk_ai_msg_conv`, `productos_ibfk_1`, `ventas_ibfk_1`, `detalle_ventas_ibfk_1`, `detalle_ventas_ibfk_2`, `movimientos_inventario_ibfk_1`, `fk_comp_detalle`). Conservan su ON DELETE original (CASCADE donde ya lo era).
- Padres con `UNIQUE(id, tenant_id)`: `uq_socios_id_tenant`, `uq_categorias_id_tenant`, `uq_cajas_id_tenant`, `uq_productos_id_tenant`, `uq_ventas_id_tenant`, `uq_comprobantes_id_tenant`, `uq_ai_conversations_id_tenant`.
- Relaciones opcionales sin FK compuesta (validadas por trigger): `suscripciones.socio_id/plan_id`, `ventas.socio_id`, `movimientos_inventario.venta_id`, `sri_log.comprobante_id`.
- Relaciones tenant→global sin cambio: `cajas.usuario_id`, `movimientos_inventario.usuario_id` (staff global; la pertenencia la validará SAAS-04 con `tenant_memberships`).

Total resultante: 44 FK (21 originales reorganizadas + 21 tenant + 2 de memberships).

## Unique constraints

| Clave | Antes | Después | Motivo |
|---|---|---|---|
| `socios.dni` | global | `uq_socios_tenant_dni (tenant_id, dni)` | una persona puede ser socia de dos gimnasios |
| `comprobantes_electronicos` | `(tipo_doc, serie, correlativo)` | `unq_comprobante (tenant_id, tipo_doc, serie, correlativo)` | cada emisor (RUC) numera sus comprobantes |
| `sri_series` | `(tipo_doc, serie)` | `unq_tipo_serie (tenant_id, tipo_doc, serie)` | series por emisor |
| `configuracion` | — | `uq_configuracion_tenant (tenant_id)` | una configuración por gimnasio; `findFirst()` intacto |
| `usuarios.email` | global | **global** | identidad de autenticación global; pertenencia por `tenant_memberships` (SAAS-01) |
| `auth_refresh_tokens.jti` | global | **global** | identificador de token |
| `comprobantes_electronicos.clave_acceso` | sin unique | sin unique (sin cambio) | la clave SRI incluye el RUC del emisor; no se toca la lógica SRI en SAAS-03 |

`configuracion.id` pasa a `AUTO_INCREMENT` para admitir una fila por tenant (la fila existente conserva su id).

## Backfill

Dentro de `0002_multi_tenant_foundation/migration.sql`, por etapas:

1. `tenant_id` NULL en las 21 tablas.
2. Tenant inicial (ver abajo).
3. Raíces → tenant inicial; `suscripciones` → tenant del socio, luego del plan, luego el inicial; `sri_log` → tenant del comprobante o el inicial; hijos → tenant del padre (JOIN). Siempre `WHERE tenant_id IS NULL`.
4. `tenant_memberships` para todos los usuarios (`NOT EXISTS`).
5. **Guardas fail-fast**: cada validación inserta su número de violaciones en `_saas03_guard`, cuyo `CHECK (violations = 0)` aborta la migración en la primera violación. Se valida: `tenant_id` NULL por tabla, padres opcionales de otro tenant, duplicados de las nuevas unicidades, más de una `configuracion` por tenant y usuarios sin membership.
6. Solo entonces: `NOT NULL`, índices/únicos, FK y triggers. `_saas03_guard` se elimina al final.

Idempotencia: Prisma registra la migración y no la repite (`migrate deploy` repetido = no-op, verificado). Las sentencias de backfill solo tocan filas sin tenant.

Preflight de solo lectura (`preflightTenantMigration` en `scripts/lib/tenant-scope.mjs`): antes de migrar reporta `BLOCKED_BY_DATA_INTEGRITY` con **conteos** (sin datos personales) si hay más de una `configuracion` o duplicados. No borra ni corrige datos.

## Tenant inicial

- `slug = 'iron-gym'`, `status = active`, `America/Guayaquil`, `EC`, `public_id` ULID generado en SQL.
- `name` = `configuracion.nombre_comercial`, si no `nombre_sistema`, si no `'Iron Gym'`.
- Se crea **solo si existen datos legados**: una base nueva queda con 0 tenants (verificado).
- Su `id` se resuelve por `slug` (`@seed_tenant_id`), nunca se asume `1`.

## Matriz de triggers (42)

| Tabla | INSERT (`_bi`) | UPDATE (`_bu`) | Propósito INSERT | Inmutable | Hereda / mode legado |
|---|---|---|---|---|---|
| socios, planes, categorias, cajas, gastos, configuracion, sri_series, comprobantes_electronicos | resolución | inmutabilidad | raíz → único tenant si COUNT=1 | sí | modo legado |
| suscripciones, sri_log | resolución + validación padres opcionales | inmutabilidad + validación padres | COALESCE(padre, legado) | sí | padre o legado |
| ventas | herencia caja + validación socio opcional | inmutabilidad + validación socio | hereda de caja | sí | de caja |
| movimientos_inventario | herencia producto + validación venta opcional | inmutabilidad + validación venta | hereda de producto | sí | de producto |
| asistencias, medidas, rutinas, notifications, ai_conversations | herencia socio | inmutabilidad | hereda del padre | sí | del padre |
| ai_messages | herencia conversación | inmutabilidad | hereda del padre | sí | del padre |
| productos | herencia categoría | inmutabilidad | hereda del padre | sí | del padre |
| detalle_ventas | herencia venta | inmutabilidad | hereda del padre | sí | del padre |
| comprobantes_detalle | herencia comprobante | inmutabilidad | hereda del padre | sí | del padre |

Un solo trigger UPDATE por tabla (sin duplicados). No hay ruta alternativa que permita cambiar `tenant_id`.

## Pruebas A/B

`test/integration/tenant-foundation.int-spec.ts` (Prisma real sobre MariaDB):

| Prueba | Resultado |
|---|---|
| A/B: mismo `dni` en tenant A y B | permitido |
| Duplicado de `dni` en el mismo tenant | rechazado |
| `asistencias` con socio de A y `tenant_id` de B | rechazado (FK compuesta) |
| `productos` con categoría de A y `tenant_id` de B | rechazado (FK compuesta) |
| `suscripciones` con socio de A y plan de B | rechazado (`cross-tenant reference rejected`) |
| Mismo usuario con membership en A y B | permitido; duplicado en A rechazado |

## Validación

| Verificación | Herramienta | Resultado |
|---|---|---|
| Base vacía → 0001 + 0002, sin drift | `npm run db:validate-migrations` | OK (25 tablas, 44 FK, 16 únicos, 42 triggers, 0 tenants) |
| Base legada + fixtures → 0002 | `npm run db:tenant:rehearsal` | PASS |
| Copia restaurada de la base real → 0002 | `npm run db:tenant:rehearsal -- --from-backup` | PASS: 23 tablas preservadas, 588 filas con tenant, 23 memberships, 0 violaciones |
| Escenario negativo (2 configuraciones) | rehearsal | preflight bloquea y el guard aborta `migrate deploy` |
| Backup/restore del esquema multi-tenant (incluye triggers) | `npm run db:backup:rehearsal` | PASS |
| Pruebas 1–10 + A/B + fail-closed + RESTRICT | `npm run test:integration` | 17/17 |
| Unitarios / e2e (base desechable) / build | `npx jest` / `test:e2e` / `build` | 6/6 · 2/2 · OK |
| `ec_gym_system` | consulta de solo lectura a `information_schema` | intacta (sin `tenants`, sin `tenant_id`, sin triggers) |

## Riesgos

| Riesgo | Mitigación |
|---|---|
| DDL MySQL no transaccional: un fallo a mitad deja la base parcialmente migrada | backup verificado previo + restore (runbook); preflight antes de migrar |
| Triggers de modo legado: con 2+ tenants las raíces sin `tenant_id` fallan | intencional (fail-closed); SAAS-04/05 deben pasar `tenant_id` antes de crear el segundo tenant |
| Las consultas aún no filtran por tenant | aceptable con un solo tenant; **no crear un segundo tenant en producción antes de SAAS-05** |
| `findFirst({ dni })` en login de socio, `ensureUniqueDni` y `validateAccessByDni` | Idéntico a `findUnique` con un solo tenant (máximo 1 fila). Con 2+ tenants y el mismo DNI en ambos, `findFirst` sin filtro de tenant devuelve un socio arbitrario: login/asistencia en el gimnasio equivocado y falso conflicto de DNI. **Deuda SAAS-04/05** (TenantContext); mitigado operativamente por S03-R2 (no crear un segundo tenant). SAAS-03 no introduce filtros falsos ni JWT tenant |
| `UPDATE … SET tenant_id = otro` | **Cerrado:** `BEFORE UPDATE` en las 21 tablas rechaza el cambio (`tenant_id is immutable`). Incluye padres de relaciones opcionales. Los UPDATE de otras columnas siguen funcionando |
| Triggers invisibles para Prisma | listados en esta guía; el backup usa `--triggers`; `validate-migrations` / ensayo esperan exactamente 42 (`expectedTenantTriggers`) |

## Performance

- Migración medida con la copia de la base real (23 tablas, 588 filas tenant): `migrate deploy` de 0002 ≈ **5,6 s** (incluye el arranque de la CLI de Prisma ≈ 2–3 s). Con fixtures ≈ 4,9 s.
- Cada `ALTER TABLE` reconstruye la tabla (MariaDB 10.4, sin `ALGORITHM=INSTANT` para FK/NOT NULL). Coste ≈ lineal en filas; con el volumen actual es despreciable. Con 10⁶ filas por tabla, estimar minutos y ensayar con `--from-backup`.
- Coste por INSERT: un trigger con 1–3 lecturas por PK/índice. Despreciable frente a la latencia HTTP.
- Las consultas actuales no usan aún los índices por tenant; SAAS-05 los aprovechará.

**Downtime:** no se promete cero downtime. Se requiere una **ventana de mantenimiento** con el backend detenido (los ALTER bloquean escrituras). Estimación con el volumen actual: 15–30 min en total (backup + verificación + resolve + deploy + validación + smoke test); la migración en sí son segundos.

## Rollback

- **Antes de producción**: los cambios son solo locales; revertir = descartar los archivos de SAAS-03 (sin efecto en ninguna base).
- **Si falla durante el deploy en producción**: no reparar a mano. Detener, **restaurar el backup** verificado (SHA-256), y retirar el registro fallido con `prisma migrate resolve --rolled-back 0002_multi_tenant_foundation` sobre la base restaurada si quedó registrado.
- **Si se detecta un problema después**: no hay down-migration automática (destruiría `tenant_id` y FK). Opción preferida: restore del backup pre-migración (se pierden escrituras posteriores; evaluar). Opción manual (documentada, no ejecutada): eliminar triggers, FK tenant/compuestas, recrear FK y únicos originales, `DROP COLUMN tenant_id`, `DROP TABLE tenant_memberships, tenants`, borrar la fila de `_prisma_migrations`. Ensayar primero en `gim_test_*`.
- Nunca ejecutar rollback contra producción sin backup y aprobación.

## Producción

Procedimiento completo: [`docs/SAAS-03-PRODUCTION-MIGRATION-RUNBOOK.md`](SAAS-03-PRODUCTION-MIGRATION-RUNBOOK.md). **No ejecutado.** Resumen: ventana de mantenimiento → backup + SHA-256 → ensayo `--from-backup` → preflight → `migrate resolve --applied 0001_baseline_current_schema` → `migrate deploy` → validaciones → smoke test.

## Próxima fase

**SAAS-04 — TenantContext + Auth multi-tenant** (no implementado):

- login `slug + DNI/email + password` (D1); JWT con `tenantId`, `membershipId`, `role`; revalidación de usuario + membership + tenant + estado;
- refresh tokens con contexto tenant;
- `TenantContext` por request y paso de `tenant_id` explícito en los `create`;
- asignación de `owner`;
- después, SAAS-05: filtrar todas las consultas por tenant (incluidos los `findFirst()` de `configuracion` y `dni`) y retirar los triggers de modo legado.

## Inventario completo de tablas

| Tabla | Scope | tenant_id | FK | Unique | Motivo |
|---|---|---|---|---|---|
| `tenants` | PLATFORM | — (es el tenant) | — | `public_id`, `slug` | raíz del aislamiento |
| `tenant_memberships` | PLATFORM | NOT NULL | → tenants (RESTRICT), → usuarios (CASCADE) | `(tenant_id, user_id)` | staff ↔ gimnasio + rol |
| `usuarios` | GLOBAL | — | — | `email` global | identidad de autenticación global; N tenants vía `tenant_memberships` |
| `auth_refresh_tokens` | GLOBAL | — | → usuarios, → socios, → self | `jti` global | sesión; tenant derivable del titular (SAAS-04) |
| `socios` | TENANT | NOT NULL | → tenants | `(tenant_id, dni)`, `(id, tenant_id)` | socio pertenece a un gimnasio |
| `planes` | TENANT | NOT NULL | → tenants | — | catálogo por gimnasio |
| `suscripciones` | TENANT | NOT NULL | → tenants; socio?/plan? + trigger | — | membresía; padres opcionales validados |
| `asistencias` | TENANT_CHILD | NOT NULL | → tenants; (socio_id, tenant_id) → socios | — | hereda del socio |
| `medidas` | TENANT_CHILD | NOT NULL | → tenants; (socio_id, tenant_id) → socios | — | hereda del socio |
| `rutinas` | TENANT_CHILD | NOT NULL | → tenants; (socio_id, tenant_id) → socios | — | hereda del socio |
| `notifications` | TENANT_CHILD | NOT NULL | → tenants; (member_id, tenant_id) → socios | — | hereda del socio |
| `ai_conversations` | TENANT_CHILD | NOT NULL | → tenants; (member_id, tenant_id) → socios | `(id, tenant_id)` | hereda del socio |
| `ai_messages` | TENANT_CHILD | NOT NULL | → tenants; (conversation_id, tenant_id) → ai_conversations | — | hereda de la conversación |
| `categorias` | TENANT | NOT NULL | → tenants | `(id, tenant_id)` | catálogo por gimnasio |
| `productos` | TENANT_CHILD | NOT NULL | → tenants; (categoria_id, tenant_id) → categorias | `(id, tenant_id)` | inventario por gimnasio |
| `movimientos_inventario` | TENANT_CHILD | NOT NULL | → tenants; (producto_id, tenant_id) → productos; venta? + trigger; usuario? (global) | — | kardex hereda del producto |
| `cajas` | TENANT | NOT NULL | → tenants; usuario (global) | `(id, tenant_id)` | caja por gimnasio |
| `ventas` | TENANT_CHILD | NOT NULL | → tenants; (caja_id, tenant_id) → cajas; socio? + trigger | `(id, tenant_id)` | hereda de la caja |
| `detalle_ventas` | TENANT_CHILD | NOT NULL | → tenants; (venta_id, tenant_id) → ventas; (producto_id, tenant_id) → productos | — | hereda de la venta |
| `gastos` | TENANT | NOT NULL | → tenants | — | finanzas por gimnasio |
| `configuracion` | TENANT | NOT NULL | → tenants | `(tenant_id)` | 1 configuración (incl. datos SRI del emisor) por gimnasio |
| `sri_series` | TENANT | NOT NULL | → tenants | `(tenant_id, tipo_doc, serie)` | numeración por emisor |
| `comprobantes_electronicos` | TENANT | NOT NULL | → tenants | `(tenant_id, tipo_doc, serie, correlativo)`, `(id, tenant_id)` | comprobante por emisor |
| `comprobantes_detalle` | TENANT_CHILD | NOT NULL | → tenants; (comprobante_id, tenant_id) → comprobantes | — | hereda del comprobante |
| `sri_log` | TENANT | NOT NULL | → tenants; comprobante? (sin FK física) + trigger | — | auditoría SRI por emisor |

### Desviaciones documentadas

- **`sri_ambiente`**: drift equivalente heredado de SAAS-02, no corregido (exige tocar el modelo SRI). Sigue en la allowlist exacta de `checkSchemaDrift`.
- **Triggers** para relaciones opcionales y para el modo legado: limitación de Prisma (FK compuesta sobre relación opcional) + compatibilidad con el código actual sin tocar servicios.
- **Nombres de modelo** en minúsculas (`tenants`, `tenant_memberships`) por coherencia con el esquema existente.
- **Código de aplicación**: solo 3 cambios mínimos de compilación (`findUnique({ dni })` → `findFirst`) en `members.service.ts` (login de socio y validación de dni) y `attendance.service.ts` (`validateAccessByDni`). Mismo comportamiento con un solo tenant.
