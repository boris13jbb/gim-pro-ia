# Asistencias y QR

**Estado:** Completada — pendiente aprobación para Fase 05

## Objetivo de la fase

Migrar control de acceso, registro de asistencias y validación QR del PHP legacy a NestJS, corrigiendo **E02** (registrar sin revalidar membresía) y evitando duplicados el mismo día.

## Archivos PHP analizados

- `gym-system/app/controllers/AsistenciaController.php`
- `gym-system/app/models/Asistencia.php`
- `gym-system/app/views/asistencia/index.php`
- `gym-system/app/views/asistencia/reporte.php`
- `gym-system/app/controllers/CarnetController.php`

## Tablas involucradas

- `asistencias` (legacy, sin cambio de esquema en esta fase)
- Lectura: `socios`, `suscripciones`, `planes`

## Reglas de negocio detectadas

- Flujo PHP en dos pasos: **validar** (DNI) → **registrar** (socio_id)
- QR del carnet codifica solo el **DNI** del socio
- Acceso permitido si: socio `activo` + membresía vigente (`activa` + `fecha_fin >= hoy`)
- PHP **no** evita duplicados el mismo día → NestJS **sí** (409 Conflict)
- PHP **no** revalida al registrar → NestJS **sí** (corrige E02)
- Reporte por rango de fechas + ranking top 5 socios
- Entrenador puede consultar asistencias; recepción/admin registran ingresos
- Socio puede registrar asistencia propia vía app (`method: app`)

## Nuevos módulos NestJS creados

- `AttendanceModule` — validar, registrar, escanear, reportes
- `QrAccessModule` — validar payload QR y datos de carnet digital
- `common/utils/date.util.ts` — fechas locales (`parseLocalDateString`) usado también en membresías

## Nuevas pantallas o funcionalidades Flutter

Ninguna (API lista para Fase 10).

## Endpoints creados

| Método | Ruta | Roles |
|--------|------|-------|
| GET | `/api/attendance/today` | admin, recepcionista, entrenador |
| GET | `/api/attendance/report` | admin, recepcionista, entrenador |
| GET | `/api/attendance/report/ranking` | admin, recepcionista, entrenador |
| POST | `/api/attendance/validate` | admin, recepcionista, entrenador |
| POST | `/api/attendance/register` | admin, recepcionista, entrenador |
| POST | `/api/attendance/scan` | admin, recepcionista, entrenador |
| POST | `/api/attendance/self` | socio |
| POST | `/api/qr-access/validate` | admin, recepcionista, entrenador |
| GET | `/api/qr-access/members/:id/card` | admin, recepcionista, entrenador |

## Cambios de base de datos

Columna nueva en `asistencias` (nullable, compatible con registros legacy):

| Campo | Tipo | Valores |
|---|---|---|
| `metodo_ingreso` | ENUM nullable | `manual`, `dni`, `qr`, `app` |

Aplicar con `npm run db:push:legacy` en `backend-nest/`.

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| `npm run build` | OK |
| `npm run audit:phase-04` | 15/15 OK |
| Validar DNI con membresía activa | OK — `canAccess: true` |
| Validar DNI inexistente | OK — `found: false` |
| Registrar asistencia | OK — 201 |
| Duplicado mismo día | OK — 409 |
| Reporte y ranking | OK |
| QR validate + card data | OK |
| Socio `POST /attendance/self` duplicado | OK — 409 |

## Botones probados

N/A (API). Equivalente PHP: Validar DNI, Registrar ingreso, reporte asistencias.

## Errores encontrados

- Desfase UTC al parsear `startDate` en membresías afectaba validación de acceso → corregido con `parseLocalDateString`

## Soluciones aplicadas

- Reutilización de `MembersService.getMembershipSummary()` y `membership-status.util.ts`
- Anti-duplicado por socio y día calendario local
- `@HttpCode` explícito en endpoints de validación (200) y registro (201)
- Export PDF de reporte diferido a Fase 08 (reportes)

## Pendientes

- Generación PDF carnet en API o Flutter (Fase 10)
- Export PDF asistencias (PHP `exportarPDF`) → Fase 08 reportes
- Columna `usuario_id` en asistencias (staff que registró) — futura mejora

## Cómo hacer rollback

Eliminar carpetas `attendance/` y `qr-access/` del backend y revertir `app.module.ts`. Tabla `asistencias` intacta.

## Estado final de la fase

API de asistencias y QR operativa con validación de membresía en backend, roles correctos y pruebas runtime documentadas.

### Guía de prueba (Fase 04)

1. Login admin:
```bash
POST /api/auth/login
{ "email": "admin@gym.com", "password": "123456" }
```

2. Validar acceso por DNI (sin guardar):
```bash
POST /api/attendance/validate
{ "dni": "1234567890" }
Authorization: Bearer <token>
```

3. Registrar ingreso:
```bash
POST /api/attendance/register
{ "memberId": 3, "method": "manual" }
```

4. Escanear QR (DNI en payload):
```bash
POST /api/attendance/scan
{ "dni": "1234567890", "method": "qr" }
```

5. Ver asistencias de hoy:
```bash
GET /api/attendance/today
```

6. **Esperado:** sin membresía vigente → 400 al registrar; segundo registro mismo día → 409.

7. Auditoría automática (desde raíz o `backend-nest/`):
```bash
npm run audit:phase-04
```

8. Siguiente paso: aprobar **Fase 05 (Progreso y rutinas)**.
