# Checklist de Pruebas — Migración Gym System

## Fase 00 — Diagnóstico PHP

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Inventario controladores/modelos/vistas | Documentado | Sí | OK |
| Mapa BD 17+ tablas | Documentado | Sí | OK |
| Errores E01–E13 detectados | Documentado | Sí | OK |
| PHP sin modificaciones | Sin cambios en código | Sí | OK |

## Fase 01 — Backend base

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Servidor NestJS levanta | Sin errores | Sí | OK |
| Swagger `/api/docs` | HTTP 200 | Sí | OK |
| Health check | `database.status = up` | Sí | OK |
| ValidationPipe global | DTOs inválidos → 400 | Sí | OK |
| HttpExceptionFilter | `{ ok: false, error }` | Sí | OK |
| CORS / Helmet | Configurados en `main.ts` | Sí | OK |
| `.env.example` sin secretos reales | Placeholders | Sí | OK |
| `npm run build` | Compila sin errores | Sí | OK |
| `npm run lint` | 0 errores ESLint | Sí | OK (2026-07-02) |
| `npm run test:e2e` | 2 tests pasan | Sí | OK (2026-07-02) |

## Fase 02 — Auth JWT

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Login correcto | Tokens emitidos (201) | Sí | OK |
| Login incorrecto | 401 controlado | Sí | OK |
| Login usuario inactivo | 401 mensaje inhabilitada | Sí | OK |
| Access token válido `/auth/me` | 200 | Sí | OK |
| Token inválido | 401 | Sí | OK |
| Refresh token válido | Nuevo par (201) | Sí | OK |
| Refresh token inválido | 401 | Sí | OK |
| Logout | Refresh revocado | Sí | OK |
| Refresh tras logout | 401 | Sí | OK |
| Acceso sin token | 401 | Sí | OK |
| Admin en `GET /users` | 200 | Sí | OK |
| Recepcionista en `GET /users` | 403 | Sí | OK |
| Password oculto en respuestas | Sin campo `password` | Sí | OK |
| Refresh hasheado en BD | SHA-256 en `auth_refresh_tokens` | Sí | OK (código) |
| Crear usuario | 201 sin password en respuesta | Sí | OK |
| Editar usuario | 200 | Sí | OK |
| Cambiar estado | 200 | Sí | OK |
| Cambiar contraseña | 200 | Sí | OK |
| Access token expirado → refresh | 401 en `/me` | Sí | OK (JWT expirado en auditoría) |
| Staff inactivo en `/auth/refresh` | 401 cuenta inhabilitada | Sí | OK (corrección auditoría 2026-07-02) |
| Staff inactivo en `/auth/me` | 401 cuenta inhabilitada | Sí | OK (corrección auditoría 2026-07-02) |

### Script de auditoría

```bash
cd backend-nest
npm run audit:phase-02
```

Última ejecución auditoría: **21/21 OK** (`npm run audit:phase-02`).

## Fase 03 — Socios, planes y membresías

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Listar socios con paginación | 200 + `items`/`meta` | Sí | OK |
| Obtener socio por ID | 200 | Sí | OK |
| Estado membresía calculado | `effectiveStatus`, `isMembershipValid` | Sí | OK |
| Historial membresías socio | 200 array | Sí | OK |
| Crear socio | 201 | Sí | OK |
| DNI duplicado | 409 | Sí | OK |
| Editar socio | 200 | Sí | OK |
| Listar planes | 200 | Sí | OK |
| Crear plan (admin) | 201 | Sí | OK |
| Crear plan (recepcionista) | 403 | Sí | OK |
| Crear membresía | 201 + `endDate` calculada | Sí | OK |
| Cancelar membresía | 200, estado `vencida` | Sí | OK |
| Entrenador lista socios | 200 | Sí | OK |
| Entrenador en membresías | 403 | Sí | OK |
| Recepcionista crea socio | 201 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 03

```bash
cd backend-nest
npm run audit:phase-03
```

Última ejecución: **26/26 OK** (`npm run audit:phase-03`).

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Upload foto socio | 201 + `photoUrl` | Sí | OK |
| Export Excel membresías | .xlsx descargable | Sí | OK |
| Login socio app móvil | JWT `userType: member` | Sí | OK |
| `/auth/me` como socio | Perfil sin password | Sí | OK |
| Establecer password socio | PATCH `/members/:id/password` | Sí | OK |

## Fase 04 — Asistencias y QR

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Validar DNI con acceso | 200, `canAccess: true` | Sí | OK |
| Validar DNI inexistente | 200, `found: false` | Sí | OK |
| Registrar asistencia | 201 | Sí | OK |
| Sin membresía vigente | 400 | Sí | OK |
| Duplicado mismo día | 409 | Sí | OK |
| Listar asistencias hoy | 200 array | Sí | OK |
| Reporte por fechas | 200 + `totalVisits` | Sí | OK |
| Ranking top 5 | 200 + `leaders` | Sí | OK |
| Validar QR (DNI) | 200, `canAccess: true` | Sí | OK |
| Datos carnet digital | 200 + `qrPayload` | Sí | OK |
| Socio auto-registro duplicado | 409 | Sí | OK |
| Entrenador consulta hoy | 200 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 04

```bash
cd backend-nest
npm run audit:phase-04
```

Última ejecución: **15/15 OK** (`npm run audit:phase-04`).

## Fase 05 — Progreso físico y rutinas

| Escenario | Resultado esperado | Probado | Resultado |
|-----------|-------------------|---------|-----------|
| Crear medida corporal | 201 | Sí | OK |
| Listar medidas + chart | 200 + `chart.labels` | Sí | OK |
| Crear rutina | 201 | Sí | OK |
| Rutina actual + historial | 200 | Sí | OK |
| Eliminar medida | 200 | Sí | OK |
| Recepcionista crear medida | 403 | Sí | OK |
| Socio `/body-progress/me` | 200 | Sí | OK |
| Socio ve otro socio | 403 | Sí | OK |
| `npm run build` | Compila | Sí | OK |

### Script de auditoría Fase 05

```bash
npm run audit:phase-05
```

Última ejecución: **12/12 OK** (`npm run audit:phase-05`).

## Fase 06 — Inventario y productos

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 06 | Categorías | Crear | POST `/categories` | 201 | 201 | OK |
| 06 | Categorías | Listar | GET `/categories` | 200 | 200 | OK |
| 06 | Categorías | Listar activas | GET `/categories/active` | 200 | 200 | OK |
| 06 | Productos | Crear | POST `/products` | 201 + stock ≥ 0 | 201 stock=10 | OK |
| 06 | Productos | Detalle | GET `/products/:id` | 200 | 200 | OK |
| 06 | Productos | Activos POS | GET `/products/active` | 200 | 200 | OK |
| 06 | Inventario | Restar stock | POST `/inventory/adjustments` subtract | stock actualizado | 7 | OK |
| 06 | Inventario | Bloquear negativo | POST subtract excesivo | 400 | 400 | OK |
| 06 | Inventario | Sumar stock | POST `/inventory/adjustments` add | stock actualizado | 12 | OK |
| 06 | Productos | Stock bajo | GET `/products/low-stock` | 200 | 200 | OK |
| 06 | Productos | Desactivar | PATCH `/products/:id/status` | inactivo | inactivo | OK |
| 06 | Categorías | Recepcionista crear | POST `/categories` | 403 | 403 | OK |
| 06 | Inventario | Historial movimientos | GET `/inventory/movements` | 200 | 200 | OK |
| 06 | Inventario | Movimientos por producto | GET `/inventory/products/:id/movements` | ≥1 movimiento | 3+ | OK |
| 06 | Caja | Abrir caja | POST `/cash-registers/open` | 201 | 201 | OK |
| 06 | Ventas | Sin caja abierta | POST `/sales` | 400 | 400 | OK |
| 06 | Ventas | Registrar venta POS | POST `/sales` | 201 + stock | 201 stock=18 | OK |
| 06 | Ventas | Movimiento tipo sale | GET `/inventory/movements?saleId=` | sale auditado | OK | OK |
| 06 | Ventas | Stock insuficiente | POST `/sales` exceso | 400 | 400 | OK |
| 06 | Ventas | Producto duplicado en items | POST `/sales` misma línea 2× | 400 si stock total excede | OK (código) | OK |
| 06 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 06

```bash
npm run audit:phase-06
```

Última ejecución: **24/24 OK** (`npm run audit:phase-06`).

## Fase 07 — POS, ventas y caja

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 07 | Caja | Resumen sesión | GET `/cash-registers/current/summary` | 200 + expectedAmount | 200 | OK |
| 07 | POS | Registrar venta | POST `/sales` | 201 + items | 201 | OK |
| 07 | POS | Detalle venta | GET `/sales/:id` | 200 | 200 | OK |
| 07 | POS | Ticket | GET `/sales/:id/ticket` | 200 + ticketNumber | OK | OK |
| 07 | POS | Historial | GET `/sales?fromDate&toDate` | incluye venta | 200 | OK |
| 07 | Caja | Cierre con diferencia | POST `/cash-registers/close` | 201 | 201 | OK |
| 07 | Caja | Historial cajas | GET `/cash-registers/history` | 200 | 200 | OK |
| 07 | POS | Entrenador bloqueado | POST `/sales` | 403 | 403 | OK |
| 07 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 07

```bash
npm run audit:phase-07
```

Última ejecución: **16/16 OK** (`npm run audit:phase-07`).

## Fase 08 — Reportes y exportaciones

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 08 | Reportes | Summary financiero | GET `/reports/financial/summary` | 200 + KPIs | 200 | OK |
| 08 | Reportes | Movimientos | GET `/reports/financial/movements` | 200 + items | 200 | OK |
| 08 | Reportes | Export Excel | GET `/reports/financial/export/excel` | xlsx | 7134b | OK |
| 08 | Reportes | Export PDF | GET `/reports/financial/export/pdf` | %PDF | 1971b | OK |
| 08 | Reportes | Recepcionista bloqueado | GET `/reports/financial/summary` | 403 | 403 | OK |
| 08 | Asistencias | Export PDF | GET `/attendance/report/export/pdf` | %PDF | 1664b | OK |
| 08 | Asistencias | Export Excel | GET `/attendance/report/export/excel` | xlsx | 6830b | OK |
| 08 | POS | Ticket PDF | GET `/sales/:id/ticket/export/pdf` | %PDF | 1717b | OK |
| 08 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 08

```bash
npm run audit:phase-08
```

Última ejecución: **11/11 OK** (`npm run audit:phase-08`).

## Fase 09 — Facturación SRI (slice 1 consulta + slice 2 emisión)

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 09 | SRI | Bandeja comprobantes | GET `/electronic-receipts` | 200 + items | 200 | OK |
| 09 | SRI | Config fiscal | GET `/sri-config` | 200 sin secretos | 200 | OK |
| 09 | SRI | Recepcionista bandeja | GET `/electronic-receipts` | 200 | 200 | OK |
| 09 | SRI | Recepcionista bloqueado config | GET `/sri-config` | 403 | 403 | OK |
| 09 | SRI | Entrenador bloqueado | GET `/electronic-receipts` | 403 | 403 | OK |
| 09 | SRI | Emitir desde membresía | POST `/electronic-receipts/issue/membership/:id` | AUTORIZADO | AUTORIZADO | OK |
| 09 | SRI | Emitir desde venta | POST `/electronic-receipts/issue/sale/:id` | AUTORIZADO | AUTORIZADO | OK |
| 09 | SRI | Membresía inexistente | POST issue/membership/99999999 | 404 | 404 | OK |
| 09 | SRI | RIDE PDF | GET `/electronic-receipts/:id/pdf` | 200 PDF | 200 | OK |
| 09 | SRI | Envío email SMTP | POST `.../send-email` | 201 sent:true | 201 | OK |
| 09 | SRI | Readiness producción | GET `/sri-config` | checks[] | 7 checks | OK |
| 09 | Build | Compilar | `npm run build` | OK | OK | OK |

### Script de auditoría Fase 09

```bash
npm run audit:phase-09
```

Última ejecución: **17/17 OK** (`npm run audit:phase-09`).

## Fase 10 — Flutter app socio

| Fase | Pantalla/Módulo | Botón/Acción | Endpoint | Resultado esperado | Resultado obtenido | Estado |
|------|-----------------|--------------|----------|-------------------|-------------------|--------|
| 10 | API | GET membresía propia | GET `/members/me/membership` | 200 socio | activa | OK |
| 10 | API | GET historial membresías | GET `/members/me/memberships` | 200 array | 1 | OK |
| 10 | API | GET carnet QR propio | GET `/qr-access/me/card` | 200 + qrPayload | DNI | OK |
| 10 | API | Staff bloqueado me/membership | GET con admin | 403 | 403 | OK |
| 10 | Flutter | Análisis estático | `flutter analyze` | sin errores | OK | OK |
| 10 | Flutter | Test widget | `flutter test` | 1/1 | 1/1 | OK |
| 10 | Flutter | Login UI | Pantalla login | navega a home | OK (Chrome) | OK |
| 10 | Flutter | CORS web :8080 | Login desde Chrome | 201 | corregido CORS | OK |
| 10 | Flutter | Carnet QR | Tab Carnet | muestra QR | Pendiente manual | Pendiente |
| 10 | Flutter | Registrar asistencia | Botón inicio | 201 o 409 | Pendiente manual | Pendiente |

### Script de auditoría Fase 10 (API)

```bash
cd backend-nest
npm run audit:phase-10
```

Última ejecución: **11/11 OK** (`npm run audit:phase-10`).
