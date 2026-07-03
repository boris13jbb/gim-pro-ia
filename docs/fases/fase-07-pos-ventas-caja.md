# Fase 07 — POS, ventas y caja

**Estado:** Completada — pendiente aprobación

## Objetivo de la fase

Completar el flujo POS/caja en NestJS: historial de ventas, detalle, ticket, resumen y cierre de caja con cuadre correcto. Corrige **E01** (PHP sumaba suscripciones en lugar de ventas POS).

## Archivos PHP analizados

| Archivo | Función |
|---------|---------|
| `PosController.php` | POS, carrito sesión, checkout, historial |
| `Venta.php` | Venta transaccional + stock |
| `CajaController.php` | Apertura/cierre caja |
| `Caja.php` | ⚠️ E01 en `obtenerTotalesSesion()` |
| `TicketController.php` | Vista/PDF ticket |

## Tablas involucradas

| Tabla | Uso |
|-------|-----|
| `cajas` | Sesiones de caja |
| `ventas` | Cabecera ventas POS |
| `detalle_ventas` | Líneas de venta |
| `gastos` | Gastos del período (cierre) |
| `movimientos_inventario` | Stock por venta (Fase 06) |

## Reglas de negocio implementadas

| Regla | NestJS |
|-------|--------|
| No vender sin caja abierta | `CashRegistersService.requireOpenRegister()` |
| Venta transaccional | Prisma `$transaction` |
| Descuento ≤ total | Validado |
| Stock no negativo en venta | `InventoryStockService` |
| Cierre con diferencia | `POST /cash-registers/close` |
| Totales caja desde ventas POS | `calculateSessionTotals()` — **corrige E01** |
| Entrenador sin acceso POS | `@Roles('admin', 'recepcionista')` |

## Módulos NestJS (ampliados desde Fase 06)

- `cash-registers/` — apertura, cierre, resumen, historial
- `sales/` — crear, listar, detalle, ticket

## Endpoints creados/ampliados

### Caja (`/api/cash-registers`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/current` | Caja abierta del usuario |
| GET | `/current/summary` | Resumen con `expectedAmount` (E01 corregido) |
| GET | `/history` | Cajas cerradas |
| POST | `/open` | Abrir caja |
| POST | `/close` | Cerrar con cuadre |

### Ventas (`/api/sales`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/` | Historial (`fromDate`, `toDate`, `search`) |
| GET | `/:id` | Detalle con ítems |
| GET | `/:id/ticket` | Datos ticket (PDF en Fase 08) |
| POST | `/` | Registrar venta POS |

## Cambios de base de datos

Ninguno adicional (reutiliza tablas legacy).

## Pruebas realizadas

- `npm run build` → OK
- `npm run audit:phase-07` → **16/16 OK**

## Pendientes diferidos

- PDF ticket térmico → Fase 08 (reportes)
- Carrito en sesión PHP → Flutter manejará carrito en cliente (Fase 10)
- Facturación electrónica SRI en venta → Fase 09

## Rollback

Revertir cambios en `sales/` y `cash-registers/`; no hay migración destructiva.

## Estado final

Fase 07 completada en backend. **Requiere aprobación** antes de Fase 08.
