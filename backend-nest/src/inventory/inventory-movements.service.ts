import { Injectable } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { ListInventoryMovementsQueryDto } from './dto/list-inventory-movements-query.dto';
import { mapInventoryMovement } from './inventory-movement.mapper';

const movementInclude = {
  productos: { select: { id: true, nombre: true, codigo: true } },
  usuarios: { select: { id: true, nombre: true } },
} satisfies Prisma.movimientos_inventarioInclude;

@Injectable()
export class InventoryMovementsService {
  constructor(private readonly prisma: PrismaService) {}

  findAll(query: ListInventoryMovementsQueryDto = {}) {
    const where: Prisma.movimientos_inventarioWhereInput = {};

    if (query.productId) where.producto_id = query.productId;
    if (query.saleId) where.venta_id = query.saleId;
    if (query.type) where.tipo = query.type;
    if (query.fromDate || query.toDate) {
      where.created_at = {};
      if (query.fromDate) {
        where.created_at.gte = new Date(`${query.fromDate}T00:00:00`);
      }
      if (query.toDate) {
        where.created_at.lte = new Date(`${query.toDate}T23:59:59`);
      }
    }

    return this.prisma.movimientos_inventario
      .findMany({
        where,
        include: movementInclude,
        orderBy: { created_at: 'desc' },
        take: query.limit ?? 50,
      })
      .then((rows) => rows.map(mapInventoryMovement));
  }

  findByProduct(productId: number, query: ListInventoryMovementsQueryDto = {}) {
    return this.findAll({ ...query, productId });
  }
}
