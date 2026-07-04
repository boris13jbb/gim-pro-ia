# Bitácora de Migración

---

## Fecha
2026-07-02

## Fase
Fase 00 — Diagnóstico completo del sistema PHP

## Archivos analizados
- `gym-system/public/index.php`
- `gym-system/public/.htaccess`
- `gym-system/app/config/Database.php`
- `gym-system/app/lib/Auth.php`
- 20 controladores en `gym-system/app/controllers/`
- 15 modelos en `gym-system/app/models/`
- 35 vistas en `gym-system/app/views/`
- `gym-system/app/lib/sri/` (5 archivos)
- `gym-system/bk_basededatos.sql`
- `gym-system/composer.json`

## Archivos modificados
- Ningún archivo PHP modificado
- Creada estructura `docs/` con documentación de diagnóstico

## Funcionalidades trabajadas
- Inventario completo de rutas, controladores, modelos y vistas
- Mapa de botones y acciones por pantalla
- Mapa de base de datos (17 tablas)
- Detección de 13 errores/inconsistencias (E01–E13)
- Plan de migración por fases
- Definición de módulos críticos y orden de migración

## Cambios realizados
- `docs/00-diagnostico-sistema-php.md` — diagnóstico técnico completo
- `docs/01-mapa-funcionalidades-actuales.md` — rutas, menú, botones, flujos
- `docs/02-mapa-base-datos-actual.md` — esquema BD y relaciones
- `docs/03-arquitectura-objetivo-nestjs-flutter.md` — borrador arquitectura
- `docs/04-plan-migracion-fases.md` — plan por fases
- `docs/fases/fase-00-diagnostico.md` — cierre de fase 00
- Plantillas para docs 05–12 y fases 01–12

## Problemas encontrados
- E01 (crítico): Caja calcula ventas con suscripciones, no tabla ventas
- E02: Asistencia se registra sin revalidar membresía
- E03: Stock puede quedar negativo en ajustes manuales
- E04–E13: ver `docs/00-diagnostico-sistema-php.md` sección 10

## Soluciones aplicadas
- Documentados para corregir en NestJS durante fases correspondientes
- No se corrigió PHP (fuera de alcance Fase 00)

## Pruebas realizadas
- Análisis estático de código fuente
- Revisión de esquema SQL vs modelos PHP
- Verificación de rutas vs controladores
- No se ejecutó el sistema en runtime (diagnóstico por código)

## Resultado
**Pendiente de aprobación** — Documentación Fase 00 completada

## Rollback
No aplica — no hubo cambios en código productivo PHP

## Próximo paso
Ejecutar y validar **Fase 01: Backend base NestJS** (sin tocar PHP). Luego pedir aprobación para Fase 02.

---

## Fecha
2026-07-02

## Fase
Fase 01 — Backend base NestJS

## Archivos analizados
- Referencia principal: documentación de Fase 00

## Archivos modificados
- `backend-nest/` (nuevo proyecto NestJS)
- `docs/fases/fase-01-backend-base.md`

## Funcionalidades trabajadas
- Base NestJS con:
  - Prefijo global `/api`
  - Swagger (`/api/docs`)
  - Health check (`/api/health`)
  - Validación global DTOs
  - Manejo global de errores y respuesta consistente
  - Prisma inicializado para MySQL

## Cambios realizados
- Creación de `backend-nest/` con dependencias: Config, Swagger, Helmet, Throttler, Terminus, Prisma
- `prisma/schema.prisma` configurado con `DATABASE_URL`
- `.env.example` creado (sin secretos)

## Problemas encontrados
- BD `ec_gym_system` no existía en MariaDB local (pool timeout en health)
- Backup SQL de MySQL 8 con collation incompatible en MariaDB 10.4
- Enum `configuracion_sri_ambiente` sin valores válidos tras `prisma db pull`

## Soluciones aplicadas
- Script `backend-nest/scripts/import-legacy-db.mjs` (adapta SQL + importa backup)
- Scripts npm: `db:import-legacy`, `db:pull`, `db:generate`
- Enum SRI corregido en `prisma/schema.prisma` con `@map`
- `prisma db pull` → 18 modelos; `prisma generate` + `npm run build` OK

## Pruebas realizadas
- `GET /api/health` → `database.status = "up"`
- `GET /api/docs` → HTTP 200
- Importación backup: 18 tablas en `ec_gym_system`

## Resultado
**Fase 01 completada y validada** — pendiente aprobación para Fase 02

## Rollback
Eliminar `backend-nest/` no afecta a `gym-system/`. BD local: `DROP DATABASE ec_gym_system` (solo dev).

## Próximo paso
Solicitar aprobación para iniciar **Fase 02: Auth JWT (Access/Refresh), usuarios y roles**.

---

## Fecha
2026-07-02

## Fase
Fase 02 — Auth JWT, usuarios y roles

## Archivos analizados
- `gym-system/app/controllers/AuthController.php`
- `gym-system/app/lib/Auth.php`
- `gym-system/app/models/Usuario.php`

## Archivos modificados
- `backend-nest/src/auth/**` (módulo completo)
- `backend-nest/src/users/**`
- `backend-nest/src/common/decorators/**`, `constants/roles.constant.ts`
- `backend-nest/prisma/schema.prisma` (tabla `auth_refresh_tokens`)
- `backend-nest/.env.example`, `backend-nest/package.json`
- `docs/fases/fase-02-auth-usuarios-roles.md`
- `docs/07-endpoints-api.md`, `docs/06-checklist-pruebas.md`

## Funcionalidades trabajadas
- JWT Access (15m) + Refresh (7d) con rotación y revocación
- Login/logout/me/refresh
- Guards globales + `@Roles`, `@Public`, `@CurrentUser`
- Listado usuarios staff (admin)
- Compatibilidad hashes bcrypt PHP (`$2y$`)

## Cambios realizados
- Tabla `auth_refresh_tokens` vía `prisma db push`
- Endpoints `/api/auth/*` y `GET /api/users`

## Problemas encontrados
- `prisma migrate dev` drift → reset prohibido
- Hash bcrypt backup no coincidía con password de prueba

## Soluciones aplicadas
- `prisma db push` sin pérdida de datos
- Normalización hash PHP en `UsersService`
- Password dev actualizado para `admin@gym.com`

## Pruebas realizadas
- Login, me, refresh, logout, users (admin), 401 sin token
- `npm run build` OK

## Resultado
**Fase 02 completada** — pendiente aprobación para Fase 03

## Rollback
`DROP TABLE auth_refresh_tokens`; eliminar módulos auth/users. PHP intacto.

## Próximo paso
Solicitar aprobación para **Fase 03: Socios y membresías**.

---

## Fecha
2026-07-02

## Fase
Fase 03 — Socios y membresías

## Archivos analizados
- `gym-system/app/controllers/SociosController.php`, `Socio.php`
- `gym-system/app/controllers/PlanesController.php`, `Plan.php`
- `gym-system/app/controllers/SuscripcionesController.php`, `Suscripcion.php`

## Archivos modificados
- `backend-nest/src/members/**`
- `backend-nest/src/plans/**`
- `backend-nest/src/subscriptions/**`
- `backend-nest/src/common/utils/membership-status.util.ts`
- `backend-nest/src/app.module.ts`
- `docs/fases/fase-03-socios-membresias.md`
- `docs/07-endpoints-api.md`, `docs/06-checklist-pruebas.md`

## Funcionalidades trabajadas
- CRUD socios (sin upload foto multipart)
- CRUD planes (admin) + lectura recepcionista
- Suscripciones: crear, listar, cancelar
- Cálculo `estadoEfectivo` y sync expiradas
- Permisos por rol alineados al menú PHP

## Cambios realizados
- 16 endpoints nuevos bajo `/api/members`, `/api/plans`, `/api/subscriptions`

## Problemas encontrados
- N/A

## Soluciones aplicadas
- Util compartido `membership-status.util.ts`
- Cancelación compatible PHP (`vencida`)

## Pruebas realizadas
- GET members, plans, subscriptions, membership
- `npm run build` OK

## Resultado
**Fase 03 completada** — pendiente aprobación para Fase 04

## Rollback
Eliminar módulos members/plans/subscriptions. Sin cambios destructivos en BD.

## Próximo paso
Solicitar aprobación para **Fase 04: Asistencias y QR**.

---

## Fecha
2026-07-02

## Fase
Auditoría de cierre — Fases 00, 01 y 02

## Archivos analizados/modificados
- Documentación `docs/00`–`11`, fases 00–02
- `backend-nest/src/users/**` (CRUD completado en auditoría)
- `backend-nest/scripts/audit-phase-02.mjs`

## Cambios realizados en auditoría
- Completado CRUD usuarios (`POST/PATCH /api/users/*`)
- Actualizados `docs/08`, `docs/10`, `docs/11`, `docs/06`
- Script auditoría runtime 20 pruebas OK

## Resultado
**Fases 00–02 listas para aprobación de Fase 03**

## Próximo paso
Esperar aprobación explícita del usuario para Fase 03.

---

## 2026-07-02 — Regla Cursor: nombres estandarizados

### Cambio realizado
Creación de regla Cursor `migracion-gym-nombres-estandar.mdc` con diccionario oficial, convenciones NestJS/Flutter/BD/endpoints y checklist de revisión. Documentación actualizada en `docs/10-decisiones-tecnicas.md`. Referencias cruzadas en reglas de backend y Flutter.

### Archivos modificados
- `docs/10-decisiones-tecnicas.md`
- `docs/05-bitacora-migracion.md`
- `.cursor/rules/migracion-gym-backend-nestjs.mdc`
- `.cursor/rules/migracion-gym-flutter.mdc`
- `backend-nest/README.md`

### Archivos creados
- `.cursor/rules/migracion-gym-nombres-estandar.mdc`

### Funcionalidad afectada
Convenciones de nomenclatura en todo el proyecto (código, endpoints, BD nueva, documentación técnica).

### Código reutilizado
Patrones ya definidos en reglas `migracion-gym-backend-nestjs.mdc` y `migracion-gym-flutter.mdc` (estructura de módulos y carpetas).

### Duplicados revisados
Regla global `alwaysApply: true`; reglas por stack mantienen solo referencia, sin duplicar el diccionario completo.

### Optimizaciones realizadas
Diccionario centralizado en un solo archivo de regla + sección en decisiones técnicas como fuente documental.

### Comentarios agregados en el código
N/A (solo reglas y documentación).

### Pruebas realizadas
- Verificación de frontmatter YAML en `.mdc`
- Coherencia con estructura de módulos en `backend-nest/src/`
- Revisión de que el diccionario coincide con documentación de fases

### Resultado
Aprobado — regla activa en todas las sesiones del proyecto.

### Riesgos detectados
Código legacy (PHP o NestJS Fase 03 con `/api/subscriptions`) puede divergir del diccionario; renombrar solo con plan documentado.

### Rollback
Eliminar `.cursor/rules/migracion-gym-nombres-estandar.mdc` y revertir entradas en `docs/10-decisiones-tecnicas.md` y esta bitácora.

### Próximo paso
Aplicar el diccionario en código nuevo de Fase 04+.

---

## 2026-07-02 — Alineación `subscriptions` → `memberships`

### Cambio realizado
Renombrado módulo NestJS `subscriptions` a `memberships`, rutas API, DTOs y campos de respuesta al diccionario oficial en inglés.

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/src/members/members.controller.ts`
- `backend-nest/src/members/members.service.ts`
- `docs/07-endpoints-api.md`
- `docs/fases/fase-03-socios-membresias.md`
- `docs/10-decisiones-tecnicas.md`
- `docs/05-bitacora-migracion.md`

### Archivos creados
- `backend-nest/src/memberships/memberships.module.ts`
- `backend-nest/src/memberships/memberships.controller.ts`
- `backend-nest/src/memberships/memberships.service.ts`
- `backend-nest/src/memberships/dto/create-membership.dto.ts`

### Archivos eliminados
- `backend-nest/src/subscriptions/**`

### Funcionalidad afectada
API de membresías (listar, crear, cancelar, historial por socio, resumen de membresía).

### Código reutilizado
- `membership-status.util.ts`
- Acceso Prisma a tabla legacy `suscripciones` (sin cambio de esquema)

### Duplicados revisados
Módulo `subscriptions` eliminado; sin duplicar lógica de cálculo de estado.

### Optimizaciones realizadas
Nombres de API alineados con diccionario (`memberId`, `effectiveStatus`, `startDate`, `endDate`).

### Comentarios agregados en el código
N/A — nombres autoexplicativos según estándar.

### Pruebas realizadas
- `npm run build` OK

### Resultado
Aprobado — breaking change documentado; sin consumidores Flutter aún.

### Riesgos detectados
Clientes externos que usaran `/api/subscriptions` deben migrar a `/api/memberships`.

### Rollback
Restaurar carpeta `subscriptions/` desde git, revertir `memberships/` y cambios en `members.service.ts`.

### Próximo paso
Probar endpoints con servidor en ejecución y continuar Fase 04 (Asistencias/QR).

---

## 2026-07-02 — Mitigación riesgos R07–R11 (cierre Fases 00–02)

### Fase
Fase 02 — cierre auditoría

### Archivos modificados
- `backend-nest/src/config/validate-env.ts` (nuevo)
- `backend-nest/src/config/throttle.config.ts` (nuevo)
- `backend-nest/src/main.ts`
- `backend-nest/src/app.module.ts`
- `backend-nest/src/auth/auth.controller.ts`
- `backend-nest/scripts/audit-phase-02.mjs`
- `backend-nest/scripts/db-push-legacy.mjs` (nuevo)
- `backend-nest/package.json`
- `backend-nest/.env.example`
- `docs/06-checklist-pruebas.md`
- `docs/08-guia-instalacion-backend.md`
- `docs/10-decisiones-tecnicas.md`
- `docs/11-riesgos-y-rollback.md`
- `docs/fases/fase-02-auth-usuarios-roles.md`
- `docs/fases/fase-03-socios-membresias.md`

### Funcionalidades
- Validación de secretos JWT en producción
- Rate limit configurable por variables de entorno
- Script seguro `db:push:legacy` para BD importada
- Prueba automatizada de access token expirado

### Cambios realizados
- R07: throttle login 30/min configurable
- R08: flujo `db:push:legacy` documentado
- R09: bloqueo arranque producción con secretos inseguros
- R10: JWT expirado en auditoría (21 pruebas)
- R11: Fase 03 marcada como pendiente aprobación formal

### Pruebas
- `npm run build` OK
- `npm run audit:phase-02` → **21/21 OK**

### Resultado
Riesgos R07–R11 mitigados o documentados. Fases 00–02 listas para aprobación formal.

### Rollback
Revertir commits de config/throttle/validate-env; restaurar throttle fijo 10/min si se desea.

### Próximo paso
Aprobación del usuario para avanzar a Fase 03 (formal) o Fase 04.

---

## 2026-07-02 — Fase 03 Socios/membresías — cierre formal

### Fase
Fase 03 — Socios, planes y membresías

### Archivos analizados (PHP legacy)
- `gym-system/app/controllers/SociosController.php`
- `gym-system/app/models/Socio.php`
- `gym-system/app/controllers/PlanesController.php`
- `gym-system/app/models/Plan.php`
- `gym-system/app/controllers/SuscripcionesController.php`
- `gym-system/app/models/Suscripcion.php`

### Archivos NestJS (ya implementados, validados)
- `backend-nest/src/members/**`
- `backend-nest/src/plans/**`
- `backend-nest/src/memberships/**`
- `backend-nest/src/common/utils/membership-status.util.ts`
- `backend-nest/scripts/audit-phase-03.mjs` (nuevo)

### Funcionalidades
- CRUD socios con DNI único y permisos por rol
- CRUD planes (admin) / lectura (recepcionista)
- Crear/cancelar membresías con cálculo `endDate` y `effectiveStatus`
- Sincronización automática `activa` → `vencida` si fecha pasada
- Entrenador: socios sí, membresías no (como PHP)

### Pruebas
- `npm run build` OK
- `npm run audit:phase-03` → **21/21 OK**

### Resultado
Fase 03 completada. API lista para Flutter. Pendientes documentados: upload foto, export Excel, login socio.

### Rollback
Eliminar módulos `members/`, `plans/`, `memberships/` del `app.module.ts`. PHP y BD intactos.

### Próximo paso
Aprobación para **Fase 04 — Asistencias/QR**.

---

## 2026-07-02 — Pendientes Fase 03 resueltos

### Funcionalidades
- Upload multipart foto socio (`POST /members/:id/photo`)
- Export Excel membresías (`GET /memberships/export/excel`)
- Login socio JWT (`POST /auth/member/login`, `userType: member`)
- Columna `socios.password` + `auth_refresh_tokens.memberId`

### Archivos principales
- `src/members/member-photo.service.ts`
- `src/memberships/memberships-export.service.ts`
- `src/auth/dto/member-login.dto.ts`
- `src/config/upload.config.ts`
- `src/auth/strategies/jwt-access.strategy.ts` (soporte member)
- `prisma/schema.prisma`

### Pruebas
- `npm run audit:phase-03` → **26/26 OK**
- `npm run audit:phase-02` → **21/21 OK**

### Resultado
Pendientes Fase 03 cerrados.

---

## 2026-07-02 — Fase 04 Asistencias y QR

### Cambio realizado
Implementación de módulos `attendance` y `qr-access` con validación de membresía en backend, anti-duplicado diario, reportes y script `audit:phase-04`. Corrección de fechas locales en membresías (`parseLocalDateString`).

### Archivos creados
- `backend-nest/src/attendance/**`
- `backend-nest/src/qr-access/**`
- `backend-nest/src/common/utils/date.util.ts`
- `backend-nest/scripts/audit-phase-04.mjs`

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/src/memberships/memberships.service.ts`
- `backend-nest/package.json`
- `docs/fases/fase-04-asistencias-qr.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/04-plan-migracion-fases.md`
- `docs/10-decisiones-tecnicas.md`
- `docs/05-bitacora-migracion.md`

### Funcionalidad afectada
Control de acceso, registro de asistencias, validación QR/carnet, reportes de visitas.

### Código reutilizado
- `MembersService.getMembershipSummary()`
- `membership-status.util.ts`
- `buildMemberPhotoUrl()`

### Duplicados revisados
Sin duplicar lógica de membresía; validación centralizada en `MembersService`.

### Optimizaciones realizadas
- Flujo validate/register separado (compatible PHP)
- Endpoint `scan` para QR/DNI en un paso
- `date.util.ts` compartido

### Comentarios agregados en el código
Reglas E02, anti-duplicado y payload QR (solo DNI) en servicios.

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-04` → **14/14 OK**

### Resultado
Fase 04 completada — pendiente aprobación para Fase 05.

### Riesgos detectados
`metodo_ingreso` no persistido en BD (pendiente migración columna).

### Rollback
Eliminar `attendance/` y `qr-access/`; revertir `app.module.ts`.

### Próximo paso
Aprobación para **Fase 05 — Progreso físico y rutinas**.

---

## 2026-07-02 — Fase 04 cierre: columna metodo_ingreso

### Cambio realizado
Columna `metodo_ingreso` en tabla `asistencias` (ENUM nullable). Persistencia real en `AttendanceService.register()`. Auditoría ampliada a 15 pruebas.

### Archivos modificados
- `backend-nest/prisma/schema.prisma`
- `backend-nest/src/attendance/attendance.service.ts`
- `backend-nest/scripts/audit-phase-04.mjs`
- `docs/fases/fase-04-asistencias-qr.md`
- `docs/02-mapa-base-datos-actual.md`
- `docs/06-checklist-pruebas.md`
- `docs/05-bitacora-migracion.md`

### Archivos creados
- `package.json` (raíz) — scripts `audit:phase-*` y `backend:*`

### Pruebas
- `npm run db:push:legacy` OK
- `npm run audit:phase-04` → **15/15 OK**

### Resultado
Fase 04 cerrada funcionalmente. Pendientes PDF diferidos a Fase 08/10.

### Rollback
`ALTER TABLE asistencias DROP COLUMN metodo_ingreso;` + revertir schema Prisma.

---

## 2026-07-02 — Fase 05 Progreso físico y rutinas

### Cambio realizado
Módulos `body-progress` y `workout-routines` con medidas corporales, gráficos, rutinas (historial por INSERT), permisos por rol y acceso socio restringido.

### Archivos creados
- `backend-nest/src/body-progress/**`
- `backend-nest/src/workout-routines/**`
- `backend-nest/src/common/utils/member-access.util.ts`
- `backend-nest/scripts/audit-phase-05.mjs`

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/package.json`
- `package.json` (raíz)
- `docs/fases/fase-05-progreso-rutinas.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/04-plan-migracion-fases.md`
- `docs/05-bitacora-migracion.md`

### Pruebas
- `npm run build` OK
- `npm run audit:phase-05` → **12/12 OK**

### Resultado
Fase 05 completada — pendiente aprobación para Fase 06.

### Rollback
Eliminar módulos `body-progress/` y `workout-routines/`.

### Próximo paso
Aprobación para **Fase 06 — Inventario y productos**.

---

## 2026-07-02 — Fase 06 Inventario y productos

### Cambio realizado
Módulos `categories`, `products` e `inventory` con CRUD de categorías/productos, upload de fotos, alerta stock bajo y ajustes manuales de stock sin permitir valores negativos (corrige E03 PHP).

### Archivos creados
- `backend-nest/src/categories/**`
- `backend-nest/src/products/**`
- `backend-nest/src/inventory/**`
- `backend-nest/scripts/audit-phase-06.mjs`

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/src/config/upload.config.ts`
- `backend-nest/src/main.ts`
- `backend-nest/.env.example`
- `backend-nest/package.json`
- `package.json` (raíz)
- `docs/fases/fase-06-inventario-productos.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/04-plan-migracion-fases.md`
- `docs/05-bitacora-migracion.md`

### Código reutilizado
- Patrón `PlansService` / `MemberPhotoService` para CRUD y fotos
- Guards JWT + `@Roles()` globales
- `ResponseTransformInterceptor` para respuestas `{ ok, data }`

### Duplicados revisados
No se duplicaron servicios de upload; `getProductUploadConfig()` extendió `upload.config.ts`.

### Optimizaciones realizadas
- Mapper `mapProduct` centralizado
- Separación inventario (`InventoryService`) vs catálogo (`ProductsService`)
- Query DTOs con `@Type(() => Number)` para parámetros numéricos

### Comentarios agregados en el código
- Regla stock no negativo en `InventoryService.adjustStock`
- Validación stock en create/update de productos

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-06` → **15/15 OK** (base); ampliación → **24/24 OK**

### Resultado
Fase 06 completada — pendiente aprobación para Fase 07.

### Riesgos detectados
~~Sin historial persistente~~ → resuelto con tabla `movimientos_inventario` (ver entrada ampliación).

### Rollback
Eliminar módulos `categories/`, `products/`, `inventory/`, `cash-registers/`, `sales/`; revertir tabla con `DROP TABLE movimientos_inventario`.

### Próximo paso
Aprobación para **Fase 07 — POS y caja**.

---

## 2026-07-02 (ampliación) — Fase 06 pendientes diferidos completados

### Cambio realizado
- Tabla `movimientos_inventario` con auditoría persistente de stock.
- `InventoryStockService` centraliza cambios y registra movimientos.
- Endpoints `GET /inventory/movements` y `GET /inventory/products/:id/movements`.
- Módulos `cash-registers` y `sales` con venta POS transaccional y descuento de stock auditado (tipo `sale`).
- Productos registran movimientos `product_create` y `product_update`.

### Archivos creados
- `backend-nest/src/inventory/inventory-stock.service.ts`
- `backend-nest/src/inventory/inventory-movements.service.ts`
- `backend-nest/src/inventory/inventory-movement.mapper.ts`
- `backend-nest/src/cash-registers/**`
- `backend-nest/src/sales/**`

### Archivos modificados
- `backend-nest/prisma/schema.prisma` (tabla + enum `movimientos_inventario_tipo`)
- `backend-nest/src/inventory/**`
- `backend-nest/src/products/products.service.ts`
- `backend-nest/src/app.module.ts`
- `backend-nest/scripts/audit-phase-06.mjs`
- `docs/02-mapa-base-datos-actual.md`
- `docs/fases/fase-06-inventario-productos.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`

### Pruebas realizadas
- `npm run db:push:legacy` OK
- `npm run audit:phase-06` → **24/24 OK**

### Pendiente Flutter
UI inventario en Flutter sigue en **Fase 10** (proyecto `frontend-flutter/` aún no iniciado). La API ya expone todos los endpoints necesarios.

### Resultado
Pendientes diferidos de Fase 06 completados en backend. Pendiente aprobación.

---

## 2026-07-02 — Fase 07 POS, ventas y caja

### Cambio realizado
Ampliación de `sales` y `cash-registers`: historial de ventas, detalle, ticket, resumen de caja con totales POS (corrige E01), cierre con diferencia e historial de cajas cerradas.

### Archivos creados
- `backend-nest/src/sales/dto/list-sales-query.dto.ts`
- `backend-nest/src/cash-registers/dto/list-cash-registers-query.dto.ts`
- `backend-nest/scripts/audit-phase-07.mjs`

### Archivos modificados
- `backend-nest/src/sales/sales.service.ts`
- `backend-nest/src/sales/sales.controller.ts`
- `backend-nest/src/sales/sale.mapper.ts`
- `backend-nest/src/sales/dto/create-sale.dto.ts`
- `backend-nest/src/cash-registers/cash-registers.service.ts`
- `backend-nest/src/cash-registers/cash-registers.controller.ts`
- `backend-nest/package.json`
- `package.json` (raíz)
- `docs/fases/fase-07-pos-ventas-caja.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/04-plan-migracion-fases.md`
- `docs/05-bitacora-migracion.md`
- `backend-nest/README.md`

### Código reutilizado
- `InventoryStockService`, `CashRegistersService` (Fase 06)
- `parseLocalDateString` para filtros de fechas

### Duplicados revisados
No se creó módulo `pos/` separado; carrito queda en cliente (Flutter). Ventas y caja centralizados en módulos existentes.

### Optimizaciones realizadas
- `calculateSessionTotals()` recalcula ventas desde tabla `ventas` al cerrar caja
- Mappers `mapSaleSummary` y `mapSaleTicket` separados

### Comentarios agregados en el código
- Corrección E01 documentada en `CashRegistersService.getCurrentSummary()`

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-07` → **16/16 OK**

### Resultado
Fase 07 completada — pendiente aprobación para Fase 08.

### Riesgos detectados
PDF ticket y datos de empresa para impresión diferidos a Fase 08.

### Rollback
Revertir cambios en `sales/` y `cash-registers/`; sin cambios destructivos en BD.

### Próximo paso
Aprobación para **Fase 08 — Reportes**.

---

## 2026-07-02 — Fase 08 Reportes y exportaciones

### Cambio realizado
Módulo `reports/` con KPIs financieros, movimientos y export Excel/PDF. Exportaciones PDF/Excel de asistencias y PDF ticket de venta POS (80mm). Utilidad compartida `pdf-buffer.util.ts`.

### Archivos creados
- `backend-nest/src/reports/` (controller, service, export, dto, module)
- `backend-nest/src/common/utils/pdf-buffer.util.ts`
- `backend-nest/src/attendance/attendance-export.service.ts`
- `backend-nest/src/sales/sales-ticket-export.service.ts`
- `backend-nest/scripts/audit-phase-08.mjs`

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/src/attendance/attendance.controller.ts`
- `backend-nest/src/attendance/attendance.module.ts`
- `backend-nest/src/sales/sales.controller.ts`
- `backend-nest/src/sales/sales.module.ts`
- `backend-nest/package.json`
- `package.json` (raíz)
- `docs/fases/fase-08-reportes.md`
- `docs/07-endpoints-api.md`
- `docs/06-checklist-pruebas.md`
- `docs/04-plan-migracion-fases.md`
- `docs/05-bitacora-migracion.md`
- `backend-nest/README.md`

### Funcionalidad afectada
Reportes financieros admin, export asistencias, ticket PDF ventas.

### Código reutilizado
- `date.util`, `exceljs` (memberships), `ReportsService.getCompanyConfig()` para PDFs

### Duplicados revisados
Un solo helper PDF (`pdf-buffer.util.ts`). Sin módulo reportes duplicado.

### Optimizaciones realizadas
- Gastos `anulado` excluidos del cálculo (mejora vs PHP)
- Consultas paralelas con `Promise.all` en summary

### Comentarios agregados en el código
- Reglas de negocio en servicios export y reports (roles, exclusiones)

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-08` → **11/11 OK**

### Resultado
Fase 08 **aprobada** el 2026-07-02.

### Riesgos detectados
Sin riesgos pendientes en exportaciones: PDF financiero incluye gráficos embebidos (barras ingresos/métodos de pago) y Excel con hoja Resumen + series.

### Rollback
Revertir módulo `reports/` y servicios export; sin cambios en BD.

### Próximo paso
Aprobación para **Fase 09 — Facturación SRI**.

---

## 2026-07-02 — Corrección riesgo Fase 08 (gráficos en PDF/Excel)

### Cambio realizado
PDF financiero con gráficos embebidos (ingresos por mes + métodos de pago POS), caja resumen tabular alineada al PHP y Excel con hoja `Resumen` + series.

### Archivos creados
- `backend-nest/src/common/utils/pdf-chart.util.ts`

### Archivos modificados
- `backend-nest/src/reports/reports-export.service.ts`
- `docs/fases/fase-08-reportes.md`
- `docs/05-bitacora-migracion.md`

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-08` → **11/11 OK** (PDF ~3740b con gráficos)

### Resultado
Riesgo pendiente de exportaciones resuelto.

---

## 2026-07-02 — Aprobación Fase 08 + inicio Fase 09 SRI (slice 1)

### Aprobación
Usuario aprobó Fase 08 para avanzar a Fase 09.

### Cambio realizado (Fase 09 slice 1)
Módulo `billing-sri/`: bandeja comprobantes, detalle, logs, descarga XML y config fiscal sin secretos.

### Archivos creados
- `backend-nest/src/billing-sri/` (controller, services, mapper, dto, module)
- `backend-nest/scripts/audit-phase-09.mjs`

### Archivos modificados
- `backend-nest/src/app.module.ts`
- `backend-nest/.env.example`
- `backend-nest/package.json`
- `package.json` (raíz)
- `docs/fases/fase-08-reportes.md`
- `docs/fases/fase-09-facturacion-sri.md`
- `docs/04-plan-migracion-fases.md`
- `docs/05-bitacora-migracion.md`
- `backend-nest/README.md`

### Próximo paso
Slice 2: portar emisión SOAP (XML, firma P12, recepción/autorización SRI).

---

## 2026-07-02 — Fix IDE TypeScript + Prisma Client

### Cambio realizado
Regeneración Prisma Client, `postinstall: prisma generate`, `tsconfig` raíz con referencia a `backend-nest`, `.vscode/settings.json` con TS SDK del backend.

### Archivos modificados
- `backend-nest/tsconfig.json`
- `backend-nest/package.json`
- `tsconfig.json` (raíz)
- `.vscode/settings.json`
- `backend-nest/src/auth/strategies/jwt-access.strategy.ts`
- `backend-nest/src/billing-sri/sri-config.service.ts`

### Resultado
`npx tsc --noEmit` OK. IDE debe reconocer enums y modelos Prisma tras recargar ventana.

### Pruebas realizadas (Fase 09 slice 1)
- `npm run build` OK
- `npm run audit:phase-09` → **10/10 OK**

### Estado Fase 09
Slice 1 operativo — emisión pendiente slice 2.

---

## 2026-07-02 — Fase 09 slice 2: emisión SRI + fix SOAP

### Cambio realizado
Port de emisión fiscal desde PHP: XML, IVA, firma P12, SOAP recepción/autorización, nota de crédito y reintento. Corrección enum ambiente Prisma (`pruebas`→`1`), SOAPAction vacío para JAX-WS SRI, simulación en pruebas sin certificado y detección de SOAP Fault.

### Archivos creados
- `backend-nest/src/billing-sri/sri-billing.service.ts`
- `backend-nest/src/billing-sri/services/sri-tax-calculator.service.ts`
- `backend-nest/src/billing-sri/services/sri-xml-builder.service.ts`
- `backend-nest/src/billing-sri/services/sri-xml-signer.service.ts`
- `backend-nest/src/billing-sri/services/sri-soap-client.service.ts`
- `backend-nest/src/billing-sri/services/sri-receipt-repository.service.ts`
- `backend-nest/src/billing-sri/utils/sri-access-key.util.ts`
- `backend-nest/src/billing-sri/utils/sri-environment.util.ts`
- `backend-nest/src/billing-sri/utils/sri-xml.util.ts`
- `backend-nest/src/billing-sri/types/sri-billing.types.ts`
- `backend-nest/src/billing-sri/dto/issue-credit-note.dto.ts`

### Archivos modificados
- `backend-nest/src/billing-sri/billing-sri.controller.ts`
- `backend-nest/src/billing-sri/billing-sri.module.ts`
- `backend-nest/scripts/audit-phase-09.mjs`
- `backend-nest/.env.example`
- `docs/fases/fase-09-facturacion-sri.md`
- `docs/06-checklist-pruebas.md`
- `docs/07-endpoints-api.md`
- `docs/05-bitacora-migracion.md`

### Funcionalidad afectada
Emisión facturas desde membresías y ventas POS; nota de crédito; reintento autorización.

### Código reutilizado
`PrismaService`, guards JWT/roles, patrón de auditoría de fases anteriores.

### Duplicados revisados
Sin servicios SRI duplicados; lógica fiscal centralizada en `billing-sri/`.

### Optimizaciones realizadas
Normalización ambiente SRI en util compartido; simulación explícita con `forceSimulate` en pruebas.

### Comentarios agregados en el código
Reglas de negocio en firma simulada, SOAPAction SRI y paridad PHP ambiente pruebas.

### Pruebas realizadas
- `npm run audit:phase-09` → **14/14 OK**
- Emisión membresía y venta → `AUTORIZADO` (modo simulación pruebas)

### Resultado
Pendiente de aprobación — slice 2 operativo; slice 3 (RIDE PDF / email) pendiente.

### Riesgos detectados
- Producción requiere certificado P12 válido y prueba real en celcer/cel.
- RIDE PDF y envío email no implementados aún.

### Rollback
Revertir módulo `billing-sri/` slice 2 o desactivar endpoints POST en controller.

### Próximo paso
Slice 3 (RIDE PDF, email) o aprobación para Fase 10 Flutter según decisión del usuario.

---

## 2026-07-02 — Fase 09 slice 3: RIDE PDF, email SMTP y readiness producción

### Cambio realizado
RIDE PDF con QR (clave acceso SRI Ecuador), envío real por SMTP (PDF + XML adjuntos), checklist de readiness en `/sri-config` y endpoints celcer/cel documentados.

### Archivos creados
- `backend-nest/src/billing-sri/services/sri-ride-export.service.ts`
- `backend-nest/src/billing-sri/services/sri-mail.service.ts`
- `backend-nest/src/billing-sri/dto/send-receipt-email.dto.ts`
- `backend-nest/src/billing-sri/utils/sri-document-labels.util.ts`

### Archivos modificados
- `backend-nest/src/billing-sri/electronic-receipts.controller.ts`
- `backend-nest/src/billing-sri/billing-sri.module.ts`
- `backend-nest/src/billing-sri/sri-config.service.ts`
- `backend-nest/scripts/audit-phase-09.mjs`
- `backend-nest/.env.example`
- `backend-nest/package.json`
- `docs/fases/fase-09-facturacion-sri.md`
- `docs/06-checklist-pruebas.md`
- `docs/07-endpoints-api.md`

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-09` → **17/17 OK**

### Resultado
Pendiente de aprobación — Fase 09 backend completa.

### Riesgos detectados
- Emisión real en producción requiere prueba manual con P12 en cel.sri.gob.ec (no automatizable sin certificado del contribuyente).

### Rollback
Revertir endpoints PDF/email y servicios slice 3 en `billing-sri/`.

### Próximo paso
Solicitar aprobación Fase 09 → Fase 10 Flutter.

---

## 2026-07-02 — Aprobación Fase 09 + SMTP operativo

### Aprobación
Usuario aprobó Fase 09 para avanzar a Fase 10 (Flutter).

### Cambio realizado
Sincronización `backend-nest/.env` con SMTP Gmail y variables SRI. Sanitización de credenciales en `.env.example` (solo placeholders).

### Pruebas realizadas
- `GET /sri-config` → `smtpConfigured: true`
- `POST /electronic-receipts/:id/send-email` → **201** `sent: true` (PDF + XML a info.gym.j@gmail.com)
- `npm run audit:phase-09` → **17/17 OK** (incluye envío SMTP real)

### Resultado
**Aprobado** — Fase 09 cerrada.

### Riesgos pendientes (operativos, no bloquean Fase 10)
- Prueba manual con certificado P12 real en ambiente producción cel.sri.gob.ec

### Próximo paso
**Fase 10 — Flutter** (app consumidor de API).

---

## 2026-07-02 — Fase 10 slice 2: asistencias socio + UI Flutter

### Cambio realizado
`GET /attendance/me` para historial del socio; API en `0.0.0.0` para LAN; Flutter con resumen de asistencias en Inicio e historial de membresías en Perfil.

### Archivos modificados
- `backend-nest/src/attendance/attendance.controller.ts`
- `backend-nest/src/main.ts`
- `backend-nest/scripts/audit-phase-10.mjs`
- `frontend-flutter/lib/features/home/home_page.dart`
- `frontend-flutter/lib/features/profile/profile_page.dart`
- `frontend-flutter/lib/services/attendance_service.dart`
- `frontend-flutter/lib/services/api_client.dart`
- `docs/fases/fase-10-flutter-app-cliente.md`
- `docs/06-checklist-pruebas.md`
- `docs/05-bitacora-migracion.md`

### Archivos creados
- `frontend-flutter/lib/core/models/attendance_report.dart`

### Pruebas realizadas
- `npm run audit:phase-10` → **13/13 OK**
- `flutter analyze` + `flutter test` OK

### Resultado
Pendiente de aprobación — slice 2 operativo.

### Próximo paso
Prueba manual en dispositivo + aprobación Fase 10 o slice 3 (push/offline).

---

## 2026-07-03 — Fase 10 slice 3: historial asistencias + AppBar

### Cambio realizado
Pantalla historial completo de asistencias, AppBar por tab, enlace desde Inicio y URL API visible en login.

### Archivos creados
- `frontend-flutter/lib/features/attendance/attendance_history_page.dart`

### Archivos modificados
- `frontend-flutter/lib/features/shell/app_shell.dart`
- `frontend-flutter/lib/features/home/home_page.dart`
- `frontend-flutter/lib/features/auth/login_page.dart`
- `frontend-flutter/lib/routes/app_router.dart`
- `docs/fases/fase-10-flutter-app-cliente.md`

### Pruebas realizadas
- `flutter analyze` OK
- `flutter test` 1/1 OK

### Resultado
Pendiente de aprobación — Fase 10 casi cerrada.

### Próximo paso
Aprobación Fase 10 → Fase 11 IA/WebSockets.

---

## 2026-07-02 — Auditoría técnica integral backend + correcciones

### Cambio realizado
Revisión profesional del backend NestJS (fases 01–09): compilación, lint, e2e, scripts `audit:phase-*` y corrección de bugs detectados en auth (staff inactivo en refresh/me), ventas POS (agregación de cantidades por producto duplicado), calidad de código (ESLint, e2e desactualizado, tipos SRI).

### Archivos modificados
- `backend-nest/src/auth/auth.service.ts`
- `backend-nest/src/auth/auth.controller.ts`
- `backend-nest/src/auth/dto/member-login.dto.ts`
- `backend-nest/src/auth/strategies/jwt-refresh.strategy.ts`
- `backend-nest/src/sales/sales.service.ts`
- `backend-nest/src/attendance/dto/scan-attendance.dto.ts`
- `backend-nest/src/users/dto/create-user.dto.ts`
- `backend-nest/src/members/members.service.ts`
- `backend-nest/src/memberships/memberships.service.ts`
- `backend-nest/src/billing-sri/sri-billing.service.ts`
- `backend-nest/src/billing-sri/utils/sri-environment.util.ts`
- `backend-nest/src/common/filters/http-exception.filter.ts`
- `backend-nest/src/common/interceptors/response-transform.interceptor.ts`
- `backend-nest/src/main.ts`
- `backend-nest/eslint.config.mjs`
- `backend-nest/test/app.e2e-spec.ts`

### Archivos creados
- `backend-nest/tsconfig.eslint.json`

### Funcionalidad afectada
Autenticación (refresh/me staff inactivo), ventas POS (validación stock con líneas duplicadas), facturación SRI (tipos ambiente), calidad estática y pruebas e2e.

### Código reutilizado
`assertMemberResourceAccess`, `InventoryStockService`, scripts de auditoría por fase existentes.

### Duplicados revisados
Sin duplicados nuevos; consolidación de cantidades por producto en `SalesService.create`.

### Optimizaciones realizadas
`mapMembership` sincrónico; `signXml` sincrónico; filtro HTTP sin `any`; ESLint con `tsconfig.eslint.json` incluyendo `test/`.

### Comentarios agregados en el código
Sin comentarios nuevos (cambios de tipado y reglas de negocio ya documentadas en servicios existentes).

### Pruebas realizadas
- `npm run build` OK
- `npm run lint` OK (0 errores)
- `npm test` OK (1 suite)
- `npm run test:e2e` OK (2 tests)
- `npm run audit:phase-02` → 22/22 OK
- `npm run audit:phase-03` a `08` → OK (ejecutados en sesión previa)
- `npm run audit:phase-06` → 24/24 OK
- `npm run audit:phase-09` → 17/17 OK

### Resultado
Pendiente de aprobación — backend estable para iniciar Fase 10 Flutter.

### Riesgos detectados
- Access token JWT sigue válido hasta expirar aunque el usuario sea desactivado (comportamiento estándar JWT; mitigado en `/me` y `/refresh`).
- Cobertura de tests unitarios mínima (solo `app.controller.spec.ts`).
- Flutter (`frontend-flutter/`) aún no existe.

### Rollback
Revertir commits de esta sesión en los archivos listados.

### Próximo paso
Fase 10 Flutter o ampliar tests de auth/ventas según prioridad del usuario.

---

## 2026-07-02 — Fase 10 slice 1: App Flutter socio + endpoints API

### Cambio realizado
Inicio Fase 10: proyecto `frontend-flutter/` con login JWT, navegación 5 tabs (inicio, carnet QR, progreso, rutina, perfil), servicios API con refresh automático y almacenamiento seguro. Backend: endpoints socio `GET /members/me/membership`, `GET /members/me/memberships`, `GET /qr-access/me/card`.

### Archivos modificados
- `backend-nest/src/members/members.module.ts`
- `backend-nest/src/qr-access/qr-access.controller.ts`
- `backend-nest/package.json`
- `README.md`
- `docs/07-endpoints-api.md`
- `docs/09-guia-instalacion-flutter.md`
- `docs/fases/fase-10-flutter-app-cliente.md`
- `docs/06-checklist-pruebas.md`

### Archivos creados
- `backend-nest/src/members/member-self.controller.ts`
- `backend-nest/scripts/audit-phase-10.mjs`
- `frontend-flutter/` (proyecto completo slice 1)
- `frontend-flutter/README.md`

### Funcionalidad afectada
App móvil socio; API complemento endpoints self-service.

### Código reutilizado
`MembersService`, `QrAccessService`, guards JWT/roles, patrones audit-phase-*.

### Duplicados revisados
Sin duplicación de lógica de membresía; Flutter solo consume servicios existentes.

### Optimizaciones realizadas
ApiClient con interceptor refresh; go_router con redirect por estado auth.

### Comentarios agregados en el código
Seguridad en `MemberSelfController` (memberId desde JWT).

### Pruebas realizadas
- `npm run build` OK
- `npm run audit:phase-10` → **11/11 OK**
- `flutter analyze` OK
- `flutter test` → 1/1 OK

### Resultado
Pendiente de aprobación — slice 1 operativo.

### Riesgos detectados
- Prueba manual UI pendiente en dispositivo físico
- HTTP cleartext en Android solo para desarrollo

### Rollback
Eliminar `frontend-flutter/` y revertir `member-self.controller.ts` + ruta qr `me/card`.

### Próximo paso
Prueba manual Flutter + aprobación slice 1; slice 2 (notificaciones, polish UI) según decisión.

---

## 2026-07-03 — Fase 10 cerrada (aprobación usuario)

### Cambio realizado
Usuario aprobó Fase 10 ("está correcto continua"). Estado documental actualizado a **Aprobada**.

### Resultado
Aprobado — avance a Fase 11.

### Próximo paso
Fase 11 slice 1: asistente IA REST + chat Flutter.

---

## 2026-07-03 — Fase 11 slice 1: Asistente IA REST + chat Flutter

### Cambio realizado
Módulo `ai-assistant` en NestJS con Gemini (`@google/generative-ai`), herramientas internas de contexto del socio, persistencia `ai_conversations`/`ai_messages`, endpoints `GET/POST /ai/*`. Flutter: 6ª pestaña Asistente con `AiChatPage` y `AiService`. Auditoría `audit:phase-11`.

### Archivos modificados
- `backend-nest/prisma/schema.prisma`
- `backend-nest/src/app.module.ts`
- `backend-nest/package.json`
- `backend-nest/.env.example`
- `package.json` (raíz)
- `frontend-flutter/lib/app.dart`
- `frontend-flutter/lib/routes/app_router.dart`
- `frontend-flutter/lib/features/shell/app_shell.dart`
- `docs/fases/fase-10-flutter-app-cliente.md`
- `docs/fases/fase-11-ia-websockets-gemini.md`
- `docs/06-checklist-pruebas.md`
- `docs/07-endpoints-api.md`

### Archivos creados
- `backend-nest/src/ai-assistant/*` (módulo completo)
- `backend-nest/scripts/audit-phase-11.mjs`
- `frontend-flutter/lib/features/ai/ai_chat_page.dart`
- `frontend-flutter/lib/services/ai_service.dart`

### Funcionalidad afectada
Asistente IA para socios; nueva pestaña en app Flutter.

### Código reutilizado
`MembersService`, `AttendanceService`, `BodyProgressService`, `WorkoutRoutinesService`, guards JWT/roles, `ApiClient`.

### Duplicados revisados
Sin duplicación de consultas de negocio; contexto consolidado en `AiToolsService`.

### Optimizaciones realizadas
Contexto del socio en un solo `buildMemberContext`; límite diario y throttle configurables.

### Comentarios agregados en el código
Reglas de seguridad en controller, tools y `GeminiService` (flujo Flutter→NestJS→Gemini).

### Pruebas realizadas
- `npm run db:push` OK
- `npm run build` OK
- `npm run audit:phase-11` → **6/6 OK**
- `flutter analyze` OK
- `flutter test` → 1/1 OK

### Resultado
Pendiente de aprobación — slice 1 operativo; requiere `GEMINI_API_KEY` para respuestas reales.

### Riesgos detectados
- Sin API key Gemini el chat responde 503 (comportamiento esperado)
- WebSockets y streaming pendientes (slice 2)
- Costo/uso de API Gemini según volumen de mensajes

### Rollback
Eliminar módulo `ai-assistant`, tablas AI y pestaña Flutter; revertir schema Prisma.

### Próximo paso
Configurar `GEMINI_API_KEY`, prueba manual chat; slice 2 WebSockets tras aprobación.

---

## 2026-07-03 — Fase 10 (UI): Sistema visual global Material 3

### Cambio realizado
Se modernizó el sistema visual global de la app Flutter centralizándolo en `AppTheme.light()` (Material 3): paleta derivada con `ColorScheme.fromSeed` manteniendo la marca (azul `#1B2A4A` + rojo `#E63946`), tipografía con jerarquía reforzada y estilos de componentes (AppBar, Card con borde sutil, inputs, botones `Filled`/`Outlined`/`Text`, `NavigationBar`, `Chip`, `ListTile`, `SnackBar`, `Dialog`, `BottomSheet`, `Divider`, `FAB`, `ProgressIndicator`). Documentación consultada con Context7 (`/flutter/website`, theming Material 3). Como las pantallas usan `Theme.of(context)`, el rediseño se propaga sin tocar su lógica.

### Archivos modificados
- `frontend-flutter/lib/core/theme/app_theme.dart` (reescrito, tema global completo)
- `frontend-flutter/lib/widgets/state_views.dart` (grises fijos → `onSurfaceVariant`)
- `frontend-flutter/lib/features/home/membership_status_card.dart` (gris fijo → `onSurfaceVariant`)
- `frontend-flutter/lib/features/auth/login_page.dart` (gris fijo → `onSurfaceVariant`)
- `docs/10-decisiones-tecnicas.md`, `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`

### Archivos creados
- Ninguno

### Funcionalidad afectada
Solo capa de presentación de toda la app de socios. Sin cambios en lógica, navegación, servicios ni contratos de API.

### Código reutilizado
`AppTheme` existente (misma firma `light()` usada por `app.dart`); tokens de `ColorScheme` en lugar de colores fijos.

### Duplicados revisados
Sin nuevos estilos por pantalla: todo el estilo vive en el tema. Se eliminaron colores grises repetidos en widgets compartidos.

### Optimizaciones realizadas
Radios y estilos de componentes centralizados; menos colores fijos; base preparada para un futuro modo oscuro.

### Comentarios agregados en el código
Explicación de la paleta de marca sobre `fromSeed`, propósito de cada tema de componente y nota de arquitectura (Flutter solo presentación).

### Pruebas realizadas
- `flutter analyze` → sin problemas nuevos en archivos modificados (14 avisos preexistentes en services/providers, no relacionados)
- Linter del editor sobre archivos modificados → sin errores
- Pendiente: verificación visual manual en Windows/Chrome

### Resultado
Pendiente de aprobación — cambio visual listo para probar.

### Riesgos detectados
- Cambio solo visual; bajo riesgo. Verde/naranja de estado y fondo blanco del QR se conservan a propósito (legibilidad/escáner).

### Rollback
Revertir `git checkout -- frontend-flutter/lib/core/theme/app_theme.dart` y los 3 widgets modificados.

### Próximo paso
Prueba visual manual del usuario; si se aprueba, opcionalmente habilitar modo oscuro (`AppTheme.dark()` + `themeMode`).

---

## 2026-07-04 — Fase 10 (UI): Modo oscuro (Material 3)

### Cambio realizado
Se habilitó el modo oscuro. `AppTheme` se refactorizó a un único generador `_buildTheme(colorScheme, scaffoldBackground)` que produce tanto `light()` como `dark()` sin duplicar estilos. Los primeros planos (texto/íconos de botones y listas) usan tokens adaptativos del `ColorScheme` (p. ej. `colorScheme.primary`), mientras que los colores de marca fijos (azul/rojo) se reservan a superficies siempre legibles (AppBar, botón primario, navegación seleccionada, SnackBar, FAB). La tipografía elige base clara/oscura según el brillo. En `app.dart` se añadió `darkTheme` + `themeMode: ThemeMode.system`.

### Archivos modificados
- `frontend-flutter/lib/core/theme/app_theme.dart` (refactor: `light()` + `dark()` con generador común)
- `frontend-flutter/lib/app.dart` (`darkTheme` + `themeMode: ThemeMode.system`)
- `frontend-flutter/lib/features/qr/qr_card_page.dart` (nuevo `_AccessChip` legible en ambos modos; QR se mantiene en blanco)
- `docs/10-decisiones-tecnicas.md`, `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`

### Archivos creados
- Ninguno (widget `_AccessChip` privado dentro de `qr_card_page.dart`)

### Funcionalidad afectada
Solo presentación. Sin cambios en lógica, navegación, servicios ni API. La app respeta el modo claro/oscuro del sistema operativo.

### Código reutilizado
Un solo `_buildTheme` compartido por ambos temas; tokens de `ColorScheme` en lugar de colores fijos.

### Duplicados revisados
Se evitó duplicar ~150 líneas de estilos entre claro y oscuro usando el generador común. El chip de acceso QR se extrajo a un widget reutilizable en lugar de repetir lógica de color.

### Optimizaciones realizadas
Generador de tema único; contraste garantizado por brillo; base preparada para un futuro selector manual de tema si se requiere.

### Comentarios agregados en el código
Explicación de qué colores son fijos de marca vs. adaptativos, motivo del fondo blanco del QR y del chip translúcido, y selección de tipografía por brillo.

### Pruebas realizadas
- `flutter analyze` (archivos modificados) → **No issues found**
- `flutter analyze` (proyecto) → solo 14 avisos preexistentes en services/providers (no relacionados)
- Pendiente: verificación visual manual alternando modo claro/oscuro del SO

### Resultado
Pendiente de aprobación — modo oscuro listo para probar.

### Riesgos detectados
- Cambio solo visual; bajo riesgo. Verificar contraste de números de ejes en gráficas `fl_chart` en modo oscuro (usan estilo por defecto; legibles pero mejorables).

### Rollback
Revertir `app_theme.dart`, `app.dart` y `qr_card_page.dart` con `git checkout --`. Para desactivar solo el oscuro sin revertir el tema: fijar `themeMode: ThemeMode.light` en `app.dart`.

### Próximo paso
Prueba visual del usuario alternando el tema del sistema; opcional: selector manual de tema en Perfil.

---

## 2026-07-04 — Fase 10 (UI): Selector manual de tema en Perfil

### Cambio realizado
Se agregó un selector de tema (Sistema / Claro / Oscuro) en la pantalla Perfil. Estado global en `ThemeProvider` (ChangeNotifier) y persistencia en `ThemeStorage` (reutiliza `flutter_secure_storage`, clave `app_theme_mode`). En `app.dart` se instancia el provider, se carga la preferencia guardada al inicio y `MaterialApp.router` se envuelve en `Consumer<ThemeProvider>` para aplicar `themeMode`. UI con `SegmentedButton` (Material 3, sin APIs deprecadas). Los temas claro/oscuro se cachean en `app.dart` para no reconstruirlos en cada rebuild.

### Archivos modificados
- `frontend-flutter/lib/app.dart` (provider de tema + `Consumer` + temas cacheados)
- `frontend-flutter/lib/features/profile/profile_page.dart` (tarjeta "Apariencia" con `_AppearanceCard`)
- `docs/10-decisiones-tecnicas.md`, `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`

### Archivos creados
- `frontend-flutter/lib/services/theme_storage.dart`
- `frontend-flutter/lib/providers/theme_provider.dart`

### Funcionalidad afectada
Preferencia de apariencia de la app. Sin cambios en lógica de negocio, navegación ni API. La selección persiste entre reinicios.

### Código reutilizado
Patrón de almacenamiento de `AuthStorage` (constructor con `?? default`), patrón de `ChangeNotifier` de `AuthProvider`, `MultiProvider` existente.

### Duplicados revisados
Sin duplicar lógica de tema: un único `AppTheme` genera claro/oscuro y un único `ThemeProvider` centraliza el estado. El control de selección se extrajo a `_AppearanceCard`.

### Optimizaciones realizadas
Temas construidos una sola vez (cacheados); `setThemeMode` evita trabajo si no hay cambio; persistencia con dependencia ya presente (sin añadir paquetes).

### Comentarios agregados en el código
Propósito de `ThemeStorage`/`ThemeProvider`, motivo de reutilizar `flutter_secure_storage`, y nota de que la UI solo lee/dispara (sin lógica).

### Pruebas realizadas
- `flutter analyze` (archivos nuevos y modificados) → **No issues found**
- Linter del editor → sin errores
- Pendiente: prueba manual (cambiar tema en Perfil y reiniciar la app para verificar persistencia)

### Resultado
Pendiente de aprobación — selector de tema listo para probar.

### Riesgos detectados
- Bajo riesgo (solo presentación). Posible parpadeo mínimo al iniciar mientras se carga la preferencia (arranca en "sistema").

### Rollback
Revertir `app.dart` y `profile_page.dart` y eliminar `theme_provider.dart` y `theme_storage.dart` con `git checkout --` / borrado. El resto del tema (claro/oscuro) sigue funcionando por SO.

### Próximo paso
Prueba manual del usuario; opcional: afinar ejes de gráficas `fl_chart` en modo oscuro.

---

## 2026-07-04 — Fase 10 (UI): Rediseño estilo fitness oscuro (acento naranja)

### Cambio realizado
A partir de referencias visuales del usuario (estilo FITFINITY), se rediseñó `AppTheme` a una estética fitness oscura: fondo casi negro neutro, tarjetas oscuras redondeadas, botones tipo píldora, AppBar integrada al fondo (sin barra de color), tipografía marcada y acento naranja/ámbar (`#F5A524`). El tema oscuro pasa a ser el principal (por defecto en el primer arranque y como valor inicial del provider), conservando el tema claro accesible desde el selector de Perfil. Ambos temas se generan con el mismo `_buildTheme` (sin duplicar). Se ajustaron los spinners dentro del botón naranja a un tono oscuro para contraste.

### Archivos modificados
- `frontend-flutter/lib/core/theme/app_theme.dart` (rediseño de paleta y componentes; light + dark)
- `frontend-flutter/lib/services/theme_storage.dart` (por defecto: oscuro)
- `frontend-flutter/lib/providers/theme_provider.dart` (inicial: oscuro)
- `frontend-flutter/lib/features/home/home_page.dart` y `.../auth/login_page.dart` (spinner `Colors.black87` sobre botón naranja)
- `docs/10-decisiones-tecnicas.md`, `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`

### Archivos creados
- Ninguno

### Funcionalidad afectada
Solo presentación (toda la app). Sin cambios en lógica, navegación, servicios ni API. El selector de tema sigue funcionando (Sistema/Claro/Oscuro).

### Código reutilizado
`_buildTheme` único para ambos temas; `ThemeProvider`/`ThemeStorage` existentes; tokens del `ColorScheme`. Sin nuevos widgets ni duplicados.

### Duplicados revisados
No se duplicó estilo entre claro y oscuro; no se crearon componentes repetidos. Se reutilizó el `_AccessChip` del carnet y el selector de Perfil.

### Optimizaciones realizadas
Estilos centralizados; acento y radios como constantes; contraste garantizado por brillo; sin dependencias nuevas.

### Comentarios agregados en el código
Motivo del acento naranja y del texto oscuro sobre él, superficies neutras en oscuro, y qué colores son fijos vs adaptativos.

### Pruebas realizadas
- `flutter analyze` (6 archivos modificados) → **No issues found**
- Pendiente: verificación visual manual (oscuro por defecto, cambio a claro/sistema, todas las pantallas)

### Resultado
Pendiente de aprobación — rediseño listo para probar.

### Riesgos detectados
- Bajo (solo presentación). Revisar en oscuro: ejes de gráficas `fl_chart` (estilo por defecto) y contraste general.

### Rollback
Revertir `app_theme.dart`, `theme_storage.dart`, `theme_provider.dart`, `home_page.dart`, `login_page.dart` con `git checkout --`.

### Próximo paso
Prueba visual del usuario; opcional: afinar ejes `fl_chart` en oscuro y aplicar acento naranja a las líneas de las gráficas.

---

## 2026-07-04 — Fase 10 (UI): Gráficas de progreso adaptadas al tema oscuro

### Cambio realizado
Se afinó `_MetricChart` en la pantalla de Progreso (`fl_chart`) para el estilo oscuro: rejilla, bordes y ejes usan tokens del tema (`outlineVariant`, `onSurfaceVariant`) y ahora son legibles en claro y oscuro. La línea de "Peso" usa el acento naranja (`AppTheme.accent`) y la de "% Grasa" un celeste (`#4FC3F7`) que contrasta en ambos temas; se añadió relleno sutil bajo la curva y puntos del mismo color.

### Archivos modificados
- `frontend-flutter/lib/features/body_progress/body_progress_page.dart`
- `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`

### Archivos creados
- Ninguno

### Funcionalidad afectada
Solo presentación de las gráficas de progreso. Sin cambios en datos, lógica ni API.

### Código reutilizado
`AppTheme.accent` (constante ya existente), tokens de `ColorScheme`, mismo widget `_MetricChart` (sin duplicar).

### Duplicados revisados
Un único estilo de eje/rejilla reutilizado en ambos ejes vía `axisLabelStyle`/`gridColor`.

### Optimizaciones realizadas
Estilos de eje calculados una vez por build; colores derivados del tema.

### Comentarios agregados en el código
Motivo del estilo de ejes adaptativo y del relleno bajo la curva.

### Pruebas realizadas
- `flutter analyze` (archivo modificado) → **No issues found**
- Pendiente: verificación visual manual de la pantalla Progreso en claro/oscuro

### Resultado
Pendiente de aprobación — gráficas listas para probar.

### Riesgos detectados
- Bajo (solo presentación).

### Rollback
Revertir `body_progress_page.dart` con `git checkout --`.

### Próximo paso
Prueba visual del usuario en la pantalla Progreso.

---

## 2026-07-04 — Fase 10 (UI): Icono y splash de marca (naranja/oscuro)

### Cambio realizado
Se creó la identidad visual de la app (mancuerna naranja `#F5A524` sobre fondo oscuro `#0E0F13`) y se generaron los recursos nativos por plataforma:
- **Icono de la app** con `flutter_launcher_icons`: Android (mipmaps `mdpi`–`xxxhdpi` + icono adaptativo con primer plano transparente y fondo `#0E0F13`), iOS (`AppIcon.appiconset` completo), Web y Windows.
- **Splash nativo** con `flutter_native_splash`: fondo `#0E0F13` con la marca centrada; incluye densidades, variante de modo noche y soporte Android 12+.
- Se reemplazó el ícono genérico del login (`Icons.fitness_center`) por el logo de marca (`assets/branding/logo_mark.png`) para coherencia con el splash.

### Archivos creados
- `frontend-flutter/assets/branding/icon.png` (icono completo, fondo oscuro)
- `frontend-flutter/assets/branding/logo_mark.png` (marca transparente)
- Recursos generados: `android/app/src/main/res/mipmap-*/ic_launcher.png`, `mipmap-anydpi-v26/ic_launcher.xml`, `values/colors.xml`, `drawable*/splash.png` y `android12splash.png` (incl. `-night-*`), `values-v31/`, `values-night-v31/`, `ios/Runner/Assets.xcassets/AppIcon.appiconset/*`, `LaunchScreen`/`Info.plist` actualizados, iconos Web y Windows.

### Archivos modificados
- `frontend-flutter/pubspec.yaml` (dev deps + config de icono y splash + assets de marca)
- `frontend-flutter/lib/features/auth/login_page.dart` (logo de marca)
- `docs/06-checklist-pruebas.md`, `docs/fases/fase-10-flutter-app-cliente.md`, `docs/10-decisiones-tecnicas.md`

### Funcionalidad afectada
Solo identidad visual (icono, splash, logo del login). Sin cambios en datos, lógica ni API.

### Código reutilizado
Paleta de marca ya definida en `app_theme.dart` (`#F5A524`, `#0E0F13`); generadores estándar en vez de editar recursos nativos a mano.

### Duplicados revisados
Una sola fuente de imágenes en `assets/branding/`; sin recursos nativos editados manualmente ni duplicados.

### Optimizaciones realizadas
Icono adaptativo (primer plano transparente + fondo de marca) para verse correcto en máscaras Android; `remove_alpha_ios` para cumplir requisitos de iOS.

### Comentarios agregados en el código
Configuración del `pubspec.yaml` comentada (rol de cada imagen y de los bloques de icono/splash).

### Pruebas realizadas
- `dart run flutter_launcher_icons` → **Successfully generated launcher icons**
- `dart run flutter_native_splash:create` → **Native splash complete**
- `flutter analyze` (login) → **No issues found**
- Verificación de archivos generados (Android mipmaps, iOS AppIcon set, splash en todas las densidades)
- Pendiente: verificación visual en dispositivo tras reinstalar la app

### Resultado
Pendiente de aprobación — icono y splash listos para probar (requieren rebuild/reinstalación).

### Riesgos detectados
- Bajo. El icono/splash solo se ve tras detener `flutter run` y reinstalar (no aparece con hot reload/restart).

### Rollback
Revertir `pubspec.yaml` y `login_page.dart`; eliminar `assets/branding/` y los recursos generados con `git checkout -- .` (los recursos nativos estaban versionados).

### Próximo paso
Reinstalar la app y validar icono en el launcher y splash al abrir. Cierre de Fase 10 a la espera de aprobación.

---

## 2026-07-04 — Fase 10 (UI, fix): Logo con transparencia real (splash profesional)

### Cambio realizado
El `logo_mark.png` inicial (generado por IA) tenía la cuadrícula de transparencia **pintada como píxeles reales**, por lo que el splash mostraba un recuadro con cuadros. Se reemplazó por un logo dibujado por código con **canal alfa real** (vectorial, simétrico y con anti-aliasing), y se regeneraron icono y splash.

### Archivos creados
- `frontend-flutter/tool/generate_branding.py` (generador reproducible del logo con Pillow)

### Archivos modificados
- `frontend-flutter/assets/branding/logo_mark.png` (ahora transparente de verdad)
- `frontend-flutter/assets/branding/icon.png` (redibujado, fondo oscuro opaco edge-to-edge)
- Recursos regenerados de icono y splash (Android/iOS/Web/Windows)
- `docs/10-decisiones-tecnicas.md`

### Funcionalidad afectada
Solo identidad visual (splash e icono adaptativo). Sin cambios de lógica ni API.

### Código reutilizado
Misma paleta de marca (`#F5A524`, `#0E0F13`) y los mismos generadores `flutter_launcher_icons` / `flutter_native_splash`.

### Duplicados revisados
Se sustituyeron las imágenes en su misma ruta; no se crearon variantes nuevas.

### Optimizaciones realizadas
Supersampling x4 + LANCZOS para bordes suaves; logo compacto dentro de la zona segura del icono adaptativo Android para evitar recortes.

### Comentarios agregados en el código
Script documentado (rol de cada imagen, zona segura, motivo del supersampling).

### Pruebas realizadas
- `python tool/generate_branding.py` → genera ambas imágenes
- Inspección de `logo_mark.png`/`icon.png` (transparencia real confirmada)
- `dart run flutter_launcher_icons` y `dart run flutter_native_splash:create` → OK
- Inspección de `drawable-xxxhdpi/splash.png` (mancuerna transparente, sin cuadrícula)
- Pendiente: verificación en dispositivo tras reinstalar

### Resultado
Pendiente de aprobación — splash e icono corregidos, listos para probar.

### Riesgos detectados
- Bajo. Requiere reinstalar la app para ver el cambio.

### Rollback
Revertir `assets/branding/*` y los recursos generados con `git checkout -- .`.

### Próximo paso
Reinstalar y validar splash (fondo oscuro + mancuerna naranja centrada, sin recuadro).

---

## 2026-07-04 — Fase 11 (Slice 2): WebSockets — streaming del chat IA

### Cambio realizado
Se agregó un gateway WebSocket (`namespace /ai`, socket.io) autenticado por JWT en el handshake para entregar la respuesta del asistente IA **en streaming** (token a token). El chat de Flutter ahora muestra la respuesta a medida que se genera, con **respaldo automático a REST** si el socket no conecta. La API key de Gemini sigue solo en el servidor.

### Archivos creados
- `backend-nest/src/websocket/websocket.module.ts`
- `backend-nest/src/websocket/ai-chat.gateway.ts`
- `backend-nest/src/websocket/ws-auth.service.ts`
- `frontend-flutter/lib/services/ai_socket_service.dart`

### Archivos modificados
- `backend-nest/src/ai-assistant/gemini.service.ts` (`generateReplyStream` + `createChatSession` reutilizable)
- `backend-nest/src/ai-assistant/ai-chat.service.ts` (`streamMessage` + `prepareTurn`/`persistAssistantReply` sin duplicar lógica)
- `backend-nest/src/app.module.ts` (registra `WebsocketModule`)
- `frontend-flutter/lib/features/ai/ai_chat_page.dart` (streaming + fallback REST, burbuja mutable)
- `frontend-flutter/lib/core/config/api_config.dart` (`socketBaseUrl`)
- `frontend-flutter/lib/app.dart` (provider `AiSocketService`)
- `backend-nest/package.json`, `frontend-flutter/pubspec.yaml` (dependencias)
- `docs/07-endpoints-api.md`, `docs/fases/fase-11-ia-websockets-gemini.md`, `docs/06-checklist-pruebas.md`

### Funcionalidad afectada
Chat del asistente IA (solo socio). REST se mantiene intacto como respaldo.

### Código reutilizado
`AiChatService` (límite diario, validación de propiedad, persistencia), `MembersService` (validación de socio), `AuthStorage` (token del socket), `SendAiChatDto` (misma forma de payload validada manualmente en el gateway).

### Duplicados revisados
La lógica de turno se factorizó (`prepareTurn`/`persistAssistantReply`) para que REST y WS compartan las mismas reglas; la creación de sesión Gemini se unificó en `createChatSession`.

### Optimizaciones realizadas
Streaming reduce la latencia percibida; conexión del socket en segundo plano al abrir el chat; timeout de turno para evitar estados colgados.

### Comentarios agregados en el código
Seguridad del handshake JWT, aislamiento por sala `member:{id}`, flujo Flutter→NestJS→Gemini, y motivo del respaldo REST.

### Pruebas realizadas
- Backend: `npm run build` → OK; lint de archivos WS → sin errores
- Flutter: `flutter analyze` (archivos modificados) → **No issues found**
- Pendiente: prueba manual en dispositivo con `GEMINI_API_KEY` (ver respuesta token a token) y prueba de rechazo con token inválido

### Resultado
Pendiente de aprobación — streaming implementado, listo para probar.

### Riesgos detectados
- Medio-bajo. Requiere backend con `@nestjs/platform-socket.io` activo y `GEMINI_API_KEY`. Si el socket no conecta, la app usa REST (sin degradar la funcionalidad).

### Rollback
1. Quitar `WebsocketModule` de `app.module.ts` y borrar `src/websocket/`.
2. En Flutter, revertir `ai_chat_page.dart`, quitar `ai_socket_service.dart` y su provider en `app.dart`.
3. El chat sigue funcionando por REST (`POST /api/ai/chat`).

### Próximo paso
Prueba manual del streaming; luego decidir Slice B (notificaciones en tiempo real) o cierre de fase.

---

## 2026-07-04 — Fase 11 (Slice 3): WebSockets — notificaciones en tiempo real al socio

### Cambio realizado
Se agregó un segundo gateway WebSocket (`namespace /events`, socket.io) autenticado por JWT en el handshake, que envía **notificaciones en tiempo real** al socio en su sala `member:{id}`. Hoy se emiten dos eventos de dominio reales: **asistencia registrada** (al hacer check-in) y **cambios de membresía** (activación y cancelación). La app Flutter muestra un aviso puntual (SnackBar) y una **campana con contador de no leídas** en el AppBar, con panel del historial reciente.

### Archivos creados
- `backend-nest/src/websocket/realtime.gateway.ts` (gateway `/events`)
- `backend-nest/src/websocket/realtime.service.ts` (API `notifyMember`, desacopla dominio de socket.io)
- `backend-nest/src/websocket/realtime.module.ts`
- `backend-nest/src/websocket/ws-auth.module.ts` (auth WS reutilizable)
- `backend-nest/src/websocket/ws-token.util.ts` (extracción de token + helpers tipados de `socket.data`)
- `backend-nest/src/websocket/types/realtime-notification.type.ts`
- `frontend-flutter/lib/core/models/app_notification.dart`
- `frontend-flutter/lib/services/realtime_notifications_service.dart`

### Archivos modificados
- `backend-nest/src/websocket/websocket.module.ts` (reutiliza `WsAuthModule`, ya no declara `WsAuthService`/`JwtModule`/`MembersModule`)
- `backend-nest/src/websocket/ai-chat.gateway.ts` (usa `extractHandshakeToken` + helpers tipados; se eliminó su `extractToken` privado duplicado)
- `backend-nest/src/attendance/attendance.module.ts` y `attendance.service.ts` (notifica al registrar asistencia)
- `backend-nest/src/memberships/memberships.module.ts` y `memberships.service.ts` (notifica al crear y cancelar)
- `backend-nest/src/app.module.ts` (registra `RealtimeModule`)
- `frontend-flutter/lib/app.dart` (provider `RealtimeNotificationsService`)
- `frontend-flutter/lib/features/shell/app_shell.dart` (ahora Stateful: conexión, SnackBar, campana con badge y panel)
- `docs/07-endpoints-api.md`, `docs/fases/fase-11-ia-websockets-gemini.md`, `docs/06-checklist-pruebas.md`

### Funcionalidad afectada
Área de socio (nueva capa de notificaciones). No cambia ninguna regla de negocio existente: la asistencia y la membresía se persisten igual; la notificación se emite **después** de guardar y nunca interrumpe el flujo.

### Código reutilizado
`WsAuthService` (mismo handshake JWT del chat IA, ahora en `WsAuthModule`), salas `member:{id}`, `AuthStorage` (token del socket en Flutter), patrón de `AiSocketService` para el cliente socket.io, `ApiConfig.socketBaseUrl`.

### Duplicados revisados
Se extrajo la extracción de token y el acceso a `socket.data` a `ws-token.util.ts` (compartido por ambos gateways); se eliminó el método duplicado en `ai-chat.gateway.ts`. `WsAuthService` quedó en un módulo reutilizable en lugar de duplicarse.

### Optimizaciones realizadas
`RealtimeService` desacopla los servicios de dominio del gateway (no conocen socket.io) y captura errores para no romper el negocio. Lista de notificaciones acotada a 30 en el cliente. `RealtimeModule` no importa módulos de dominio → sin dependencias circulares.

### Comentarios agregados en el código
Seguridad del handshake y aislamiento por sala, motivo de emitir después de persistir, semántica del namespace `/events` en `@WebSocketServer()` (Namespace, verificado con la doc oficial de NestJS).

### Pruebas realizadas
- Backend: `npm run build` → OK; `npm run lint` → **0 errores**
- Flutter: `flutter analyze` (archivos nuevos/modificados) → **No issues found** (los 14 avisos restantes son preexistentes en otros archivos)
- Pendiente: prueba manual en dispositivo (registrar asistencia del socio conectado y verificar el aviso; crear/cancelar membresía y verificar aviso)

### Resultado
Pendiente de aprobación — notificaciones en tiempo real implementadas, listas para probar.

### Riesgos detectados
- Bajo. Si el socket no conecta, la app funciona igual (sin avisos en vivo). Al cerrar sesión, el shell cierra el socket. Reutiliza `CORS_ORIGINS` y `JWT_ACCESS_SECRET`; sin nuevas variables de entorno ni cambios de BD.

### Rollback
1. Quitar `RealtimeModule` de `app.module.ts` y borrar `realtime.*.ts`, `types/realtime-notification.type.ts`.
2. Quitar `imports: [RealtimeModule]` y las llamadas `this.realtime.notifyMember(...)` en `attendance`/`memberships`.
3. En Flutter, revertir `app_shell.dart`, quitar el provider en `app.dart`, `realtime_notifications_service.dart` y `app_notification.dart`.
4. El chat IA (`/ai`) y el resto de la app siguen intactos.

### Próximo paso
Prueba manual de las notificaciones; con la aprobación, cerrar Fase 11 y preparar Fase 12 (cierre de migración).

---

## 2026-07-04 — Fase 12: Cierre de migración (consolidación documental)

### Cambio realizado
Cierre de la migración: consolidación documental y verificación técnica del sistema completo (NestJS + Flutter + IA/WebSockets). **No se modificó código** ni la base de datos; no se eliminó el legacy PHP. Se completó el documento de cierre, se actualizó el manual del sistema migrado, los README y el plan/riesgos.

### Archivos creados
- Ninguno (solo se completaron documentos existentes)

### Archivos modificados
- `docs/fases/fase-12-cierre-migracion.md` (documento de cierre completo)
- `docs/12-manual-funcionalidades.md` (ahora describe el sistema migrado)
- `docs/04-plan-migracion-fases.md` (estado global + marcas de fases)
- `docs/11-riesgos-y-rollback.md` (rollback consolidado 07–11 + riesgos tiempo real + cierre)
- `README.md`, `backend-nest/README.md`, `frontend-flutter/README.md` (estado final)
- `docs/06-checklist-pruebas.md` (verificación de cierre)

### Funcionalidad afectada
Ninguna en runtime. Solo documentación y verificación.

### Código reutilizado
No aplica (fase documental).

### Duplicados revisados
Se actualizaron los documentos oficiales existentes; no se crearon archivos nuevos ni duplicados.

### Optimizaciones realizadas
Documentación unificada y coherente con el código real; `.env.example` verificado (completo, sin secretos, sin variables nuevas).

### Comentarios agregados en el código
No aplica.

### Pruebas realizadas
- Backend: `npm run build` → OK; `npm run lint` → 0 errores
- Backend: `start:dev` levanta correctamente (grafo de módulos válido)
- Flutter: `flutter analyze` → sin issues nuevos; `flutter test` → 1/1 OK

### Resultado
Pendiente de aprobación — cierre documental y verificación completados.

### Riesgos detectados
- Bajo. Pendientes post-cierre documentados (prueba manual Fase 11, UI de staff, endurecimiento de producción, retiro de PHP tras operación en paralelo).

### Rollback
No aplica (sin cambios de código/BD). El rollback por fase sigue vigente en `docs/11-riesgos-y-rollback.md`.

### Próximo paso
Aprobación final del usuario. Post-cierre: prueba manual de IA/WebSockets con `GEMINI_API_KEY`, planificar UI de staff en Flutter y endurecimiento para producción.
