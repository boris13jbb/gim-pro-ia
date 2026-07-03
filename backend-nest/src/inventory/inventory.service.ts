import { Injectable, NotFoundException } from '@nestjs/common';
import { movimientos_inventario_tipo, Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { mapProduct } from '../products/product.mapper';
import {
  AdjustStockDto,
  StockAdjustmentOperation,
} from './dto/adjust-stock.dto';
import { movementTypeFromAdjustment } from './inventory-movement.mapper';
import { InventoryStockService } from './inventory-stock.service';

@Injectable()
export class InventoryService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly inventoryStockService: InventoryStockService,
  ) {}

  private readonly productInclude = {
    categorias: { select: { id: true, nombre: true, estado: true } },
  } satisfies Prisma.productosInclude;

  private async assertProductExists(productId: number) {
    const product = await this.prisma.productos.findUnique({
      where: { id: productId },
    });
    if (!product) throw new NotFoundException('Producto no encontrado');
    return product;
  }

  /**
   * Regla de negocio (corrige E03 del PHP):
   * El stock nunca puede quedar negativo. Toda salida manual valida disponibilidad
   * y queda registrada en movimientos_inventario.
   */
  async adjustStock(dto: AdjustStockDto, userId: number) {
    await this.assertProductExists(dto.productId);

    const quantityDelta =
      dto.operation === StockAdjustmentOperation.ADD
        ? dto.quantity
        : -dto.quantity;

    const result = await this.inventoryStockService.applyStockChange({
      productId: dto.productId,
      quantityDelta,
      movementType: movementTypeFromAdjustment(dto.operation),
      userId,
      notes: 'Ajuste manual de inventario',
    });

    const updated = await this.prisma.productos.findUnique({
      where: { id: dto.productId },
      include: this.productInclude,
    });

    return {
      product: mapProduct(updated!),
      adjustment: {
        movementId: result.movement.id,
        productId: dto.productId,
        operation: dto.operation,
        quantity: dto.quantity,
        previousStock: result.movement.previousStock,
        newStock: result.movement.newStock,
        adjustedByUserId: userId,
      },
    };
  }

  async recordInitialStock(
    productId: number,
    initialStock: number,
    userId?: number,
  ) {
    if (initialStock <= 0) return null;

    return this.inventoryStockService.applyStockChange({
      productId,
      quantityDelta: initialStock,
      movementType: movimientos_inventario_tipo.product_create,
      userId,
      notes: 'Stock inicial al crear producto',
    });
  }

  async recordStockUpdate(
    productId: number,
    previousStock: number,
    newStock: number,
    userId?: number,
  ) {
    const delta = newStock - previousStock;
    if (delta === 0) return null;

    return this.inventoryStockService.applyStockChange({
      productId,
      quantityDelta: delta,
      movementType: movimientos_inventario_tipo.product_update,
      userId,
      notes: 'Actualización directa de stock en producto',
    });
  }
}
