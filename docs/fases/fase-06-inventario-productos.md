# Fase 06 — Inventario y productos

**Estado:** Completada (incluye pendientes diferidos) — pendiente aprobación

## Objetivo de la fase

Migrar categorías, productos, ajustes de stock y auditoría de inventario del PHP a NestJS, corrigiendo **E03** (stock negativo) e integrando descuento de stock en ventas POS con trazabilidad completa.

## Archivos PHP analizados

| Archivo | Función |
|---------|---------|
| `gym-system/app/controllers/InventarioController.php` | CRUD categorías/productos, ajuste stock |
| `gym-system/app/models/Producto.php` | CRUD productos, stock sin validación negativa |
| `gym-system/app/models/Categoria.php` | CRUD categorías |
| `gym-system/app/controllers/PosController.php` | POS, carrito, checkout |
| `gym-system/app/models/Venta.php` | Venta transaccional + descuento stock |
| `gym-system/app/models/Caja.php` | Apertura/cierre caja |

## Tablas involucradas

| Tabla | Uso |
|-------|-----|
| `categorias` | Catálogo |
| `productos` | Stock y precios |
| `movimientos_inventario` | **Nueva** — auditoría de entradas/salidas |
| `cajas` | Sesión de caja (apertura requerida para vender) |
| `ventas`, `detalle_ventas` | Ventas POS |

## Reglas de negocio implementadas

| Regla | NestJS |
|-------|--------|
| Stock nunca negativo | `InventoryStockService` |
| Toda entrada/salida auditada | Tabla `movimientos_inventario` |
| No vender sin caja abierta | `SalesService` + `CashRegistersService` |
| Venta transaccional | Prisma `$transaction` |
| Descuento ≤ total | Validado en `SalesService` |
| Productos con historial no se borran | `PATCH .../status` activo/inactivo |

## Módulos NestJS

```
categories/          — CRUD categorías
products/              — CRUD productos + fotos
inventory/             — Ajustes + historial movimientos
cash-registers/        — Apertura/cierre/consulta caja
sales/                 — POST venta POS con descuento stock
```

## Endpoints principales

### Inventario / auditoría

| Método | Ruta | Rol |
|--------|------|-----|
| GET | `/inventory/movements` | admin |
| GET | `/inventory/products/:productId/movements` | admin |
| POST | `/inventory/adjustments` | admin |

Filtros movimientos: `productId`, `saleId`, `type`, `fromDate`, `toDate`, `limit`.

Tipos de movimiento: `manual_add`, `manual_subtract`, `sale`, `product_create`, `product_update`.

### Caja (base POS)

| Método | Ruta | Rol |
|--------|------|-----|
| GET | `/cash-registers/current` | admin, recepcionista |
| POST | `/cash-registers/open` | admin, recepcionista |
| POST | `/cash-registers/close` | admin, recepcionista |

### Ventas

| Método | Ruta | Rol |
|--------|------|-----|
| POST | `/sales` | admin, recepcionista |

Body venta:
```json
{
  "items": [{ "productId": 1, "quantity": 2 }],
  "memberId": 5,
  "discount": 0,
  "paymentMethod": "efectivo"
}
```

## Cambios de base de datos

Nueva tabla `movimientos_inventario` + enum `movimientos_inventario_tipo`.

Aplicar con:
```bash
cd backend-nest
npm run db:push:legacy
npm run db:generate
```

Rollback:
```sql
DROP TABLE IF EXISTS movimientos_inventario;
```

## Pruebas realizadas

- `npm run build` → OK
- `npm run db:push:legacy` → OK
- `npm run audit:phase-06` → **24/24 OK**

## Pendientes restantes

| Item | Estado |
|------|--------|
| Historial movimientos inventario | ✅ Completado |
| Descuento stock en ventas | ✅ Completado (`POST /sales`) |
| UI Flutter inventario | ⏳ **Fase 10** — proyecto Flutter aún no iniciado; API lista |

## Estado final

Backend de inventario + auditoría + venta POS con stock completado. **Flutter UI queda para Fase 10** cuando exista `frontend-flutter/`.

**Requiere aprobación** antes de continuar Fase 07 (POS/caja ampliado: historial ventas, tickets, etc.).
