import { ventas } from '@prisma/client';

type SaleItemRow = {
  id: number;
  producto_id: number;
  cantidad: number;
  precio_unitario: ventas['total'];
  subtotal: ventas['total'];
  productos?: { nombre: string; codigo: string | null } | null;
};

type SaleRelations = {
  detalle_ventas?: SaleItemRow[];
  socios?: { id: number; nombre: string } | null;
  cajas?: {
    id: number;
    usuario_id: number;
    usuarios?: { id: number; nombre: string | null } | null;
  } | null;
};

export function mapSaleItem(item: SaleItemRow) {
  return {
    id: item.id,
    productId: item.producto_id,
    productName: item.productos?.nombre ?? null,
    productCode: item.productos?.codigo ?? null,
    quantity: item.cantidad,
    unitPrice: Number(item.precio_unitario),
    subtotal: Number(item.subtotal),
  };
}

export function mapSale(row: ventas & SaleRelations) {
  return {
    id: row.id,
    cashRegisterId: row.caja_id,
    memberId: row.socio_id,
    memberName: row.socios?.nombre ?? null,
    cashierId: row.cajas?.usuario_id ?? null,
    cashierName: row.cajas?.usuarios?.nombre ?? null,
    total: Number(row.total),
    discount: Number(row.descuento),
    paymentMethod: row.metodo_pago,
    receiptType: row.tipo_comprobante,
    clientDocumentType: row.cliente_tipo_doc,
    clientDocumentNumber: row.cliente_num_doc,
    clientName: row.cliente_razon,
    clientAddress: row.cliente_direccion,
    createdAt: row.fecha,
    items: (row.detalle_ventas ?? []).map(mapSaleItem),
  };
}

export function mapSaleSummary(row: ventas & SaleRelations) {
  return {
    id: row.id,
    total: Number(row.total),
    discount: Number(row.descuento),
    paymentMethod: row.metodo_pago,
    receiptType: row.tipo_comprobante,
    createdAt: row.fecha,
    memberId: row.socio_id,
    memberName: row.socios?.nombre ?? 'Cliente general',
    cashierName: row.cajas?.usuarios?.nombre ?? null,
  };
}

export function mapSaleTicket(row: ventas & SaleRelations) {
  const sale = mapSale(row);
  const grossTotal = sale.items.reduce((sum, item) => sum + item.subtotal, 0);

  return {
    ticketNumber: String(row.id).padStart(6, '0'),
    sale,
    summary: {
      grossTotal,
      discount: sale.discount,
      total: sale.total,
      paymentMethod: sale.paymentMethod,
      clientDisplayName: sale.memberName ?? sale.clientName ?? 'Cliente general',
      cashierName: sale.cashierName,
      issuedAt: sale.createdAt,
    },
  };
}
