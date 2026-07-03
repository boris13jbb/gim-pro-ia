import {
  movimientos_inventario,
  movimientos_inventario_tipo,
} from '@prisma/client';

export function mapInventoryMovement(
  row: movimientos_inventario & {
    productos?: { id: number; nombre: string; codigo: string | null } | null;
    usuarios?: { id: number; nombre: string | null } | null;
  },
) {
  return {
    id: row.id,
    productId: row.producto_id,
    productName: row.productos?.nombre ?? null,
    productCode: row.productos?.codigo ?? null,
    type: row.tipo,
    quantity: row.cantidad,
    previousStock: row.stock_anterior,
    newStock: row.stock_nuevo,
    saleId: row.venta_id,
    userId: row.usuario_id,
    userName: row.usuarios?.nombre ?? null,
    notes: row.notas,
    createdAt: row.created_at,
  };
}

export function movementTypeFromAdjustment(
  operation: 'add' | 'subtract',
): movimientos_inventario_tipo {
  return operation === 'add'
    ? movimientos_inventario_tipo.manual_add
    : movimientos_inventario_tipo.manual_subtract;
}
