import { BadRequestException, Injectable } from '@nestjs/common';
import {
  movimientos_inventario_tipo,
  Prisma,
  productos,
} from '@prisma/client';
import { PrismaService } from '../database/prisma.service';

export type InventoryStockChangeInput = {
  productId: number;
  quantityDelta: number;
  movementType: movimientos_inventario_tipo;
  userId?: number;
  saleId?: number;
  notes?: string;
};

export type InventoryStockChangeResult = {
  product: productos;
  movement: {
    id: number;
    productId: number;
    type: movimientos_inventario_tipo;
    quantity: number;
    previousStock: number;
    newStock: number;
    saleId: number | null;
    userId: number | null;
    notes: string | null;
    createdAt: Date;
  };
};

type PrismaTx = Prisma.TransactionClient;

@Injectable()
export class InventoryStockService {
  constructor(private readonly prisma: PrismaService) {}

  /**
   * Regla de negocio:
   * Toda modificación de stock pasa por aquí para validar no-negativo
   * y registrar movimiento auditado en movimientos_inventario.
   */
  applyStockChange(
    input: InventoryStockChangeInput,
    tx?: PrismaTx,
  ): Promise<InventoryStockChangeResult> {
    if (input.quantityDelta === 0) {
      throw new BadRequestException('La cantidad del movimiento debe ser distinta de cero');
    }
    if (tx) {
      return this.applyStockChangeInternal(tx, input);
    }
    return this.prisma.$transaction((transaction) =>
      this.applyStockChangeInternal(transaction, input),
    );
  }

  private async applyStockChangeInternal(
    db: PrismaTx,
    input: InventoryStockChangeInput,
  ): Promise<InventoryStockChangeResult> {
    const product = await db.productos.findUnique({
      where: { id: input.productId },
    });
    if (!product) {
      throw new BadRequestException(`Producto ${input.productId} no encontrado`);
    }

    const previousStock = product.stock ?? 0;
    const newStock = previousStock + input.quantityDelta;
    if (newStock < 0) {
      throw new BadRequestException(
        `Stock insuficiente para producto ${product.nombre}. Disponible: ${previousStock}, solicitado: ${Math.abs(input.quantityDelta)}`,
      );
    }

    const updatedProduct = await db.productos.update({
      where: { id: input.productId },
      data: { stock: newStock },
    });

    const movement = await db.movimientos_inventario.create({
      data: {
        producto_id: input.productId,
        tipo: input.movementType,
        cantidad: Math.abs(input.quantityDelta),
        stock_anterior: previousStock,
        stock_nuevo: newStock,
        venta_id: input.saleId ?? null,
        usuario_id: input.userId ?? null,
        notas: input.notes ?? null,
      },
    });

    return {
      product: updatedProduct,
      movement: {
        id: movement.id,
        productId: movement.producto_id,
        type: movement.tipo,
        quantity: movement.cantidad,
        previousStock: movement.stock_anterior,
        newStock: movement.stock_nuevo,
        saleId: movement.venta_id,
        userId: movement.usuario_id,
        notes: movement.notas,
        createdAt: movement.created_at,
      },
    };
  }
}
