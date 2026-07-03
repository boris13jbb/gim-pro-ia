# Fase 08 — Reportes y exportaciones

**Estado:** Aprobada — 2026-07-02

## Objetivo de la fase

Migrar reportes financieros y exportaciones del PHP (`ReportesController`, `Reporte.php`) a NestJS, completar export PDF de asistencias (diferido Fase 04) y ticket PDF de ventas (diferido Fase 07).

## Archivos PHP analizados

| Archivo | Función |
|---------|---------|
| `ReportesController.php` | Dashboard financiero, movimientos, export Excel/PDF |
| `Reporte.php` | KPIs, ingresos membresías/POS, gastos, utilidad, gráficos |
| `AsistenciaController::exportarPDF` | Export asistencias (referencia Fase 04) |
| `TicketController.php` | Ticket PDF térmico (referencia Fase 07) |

## Tablas involucradas

| Tabla | Uso |
|-------|-----|
| `suscripciones` + `planes` + `socios` | Ingresos por membresías |
| `ventas` | Ingresos POS |
| `gastos` | Egresos (excluye `anulado`) |
| `socios` | Socios activos, altas por mes |
| `configuracion` | Datos empresa en PDF |
| `asistencias` | Reporte asistencias (export) |

## Reglas de negocio implementadas

| Regla | NestJS |
|-------|--------|
| Reportes financieros solo admin | `@Roles('admin')` en `/reports/*` |
| Gastos anulados no cuentan | `estado: { not: anulado }` |
| Utilidad = ingresos − gastos | `netProfit` en summary |
| Asistencias export: staff autorizado | `admin`, `recepcionista`, `entrenador` |
| Ticket PDF: recepción/admin | `@Roles('admin', 'recepcionista')` |
| Respuestas JSON estándar | `{ ok, data }`; exports con `@RawResponse()` |

## Módulos NestJS creados/ampliados

- `reports/` — summary, movements, export Excel/PDF financiero
- `attendance/attendance-export.service.ts` — Excel/PDF asistencias
- `sales/sales-ticket-export.service.ts` — PDF ticket 80mm
- `common/utils/pdf-buffer.util.ts` — utilidad PDF compartida (pdfkit)
- `common/utils/pdf-chart.util.ts` — gráficos embebidos en PDF (barras verticales/horizontales)

## Dependencias nuevas

- `pdfkit` + `@types/pdfkit`
- `exceljs` (ya usado en memberships)

## Endpoints creados

### Reportes financieros (`/api/reports`) — solo `admin`

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/financial/summary` | KPIs + series (`fromDate`, `toDate`) |
| GET | `/financial/movements` | Movimientos del período (`limit`) |
| GET | `/financial/export/excel` | Descarga `.xlsx` |
| GET | `/financial/export/pdf` | Descarga `.pdf` |

### Asistencias (ampliación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/attendance/report/export/excel` | Excel reporte (`from`, `to`) |
| GET | `/attendance/report/export/pdf` | PDF reporte |

### Ventas (ampliación)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/sales/:id/ticket/export/pdf` | PDF ticket térmico 80mm |

## Cambios de base de datos

Ninguno (consultas sobre tablas legacy existentes).

## Pruebas realizadas

- `npm run build` → OK
- `npm run audit:phase-08` → **11/11 OK**

## Botones probados (vía auditoría)

- Summary financiero admin
- Movimientos financieros
- Export Excel/PDF financiero
- Recepcionista bloqueado en `/reports/*` (403)
- Export PDF/Excel asistencias
- Export PDF ticket venta

## Errores encontrados

| Error | Solución |
|-------|----------|
| Import incorrecto en DTO reportes | Ruta `../../common/utils/date.util` |
| Tipos nullable Prisma en movimientos | Filtros + optional chaining |
| Auditoría asistencias 400 | Query `from`/`to` (no `fromDate`/`toDate`) |
| PDF sin gráficos (riesgo Fase 08) | `pdf-chart.util.ts` + resumen tabular en PDF/Excel |

## Pendientes

- UI Flutter reportes → Fase 10
- Reportes avanzados de inventario/gastos CRUD → fuera de alcance Fase 08

## Cómo hacer rollback

- Eliminar módulo `reports/` y revertir imports en `app.module.ts`, `attendance.module.ts`, `sales.module.ts`
- Quitar servicios export de attendance/sales
- Desinstalar `pdfkit` si no se usa en otra fase

## Estado final de la fase

Backend de reportes y exportaciones operativo. **Aprobada** el 2026-07-02.
