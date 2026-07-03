import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  movimientos_inventario_tipo,
  Prisma,
  productos_estado,
  ventas_metodo_pago,
  ventas_tipo_comprobante,
} from '@prisma/client';
import { parseLocalDateString } from '../common/utils/date.util';
import { CashRegistersService } from '../cash-registers/cash-registers.service';
import { PrismaService } from '../database/prisma.service';
import { InventoryStockService } from '../inventory/inventory-stock.service';
import { CreateSaleDto } from './dto/create-sale.dto';
import { ListSalesQueryDto } from './dto/list-sales-query.dto';
import { mapSale, mapSaleSummary, mapSaleTicket } from './sale.mapper';

const saleInclude = {
  detalle_ventas: {
    include: {
      productos: { select: { nombre: true, codigo: true } },
    },
  },
  socios: { select: { id: true, nombre: true } },
  cajas: {
    select: {
      id: true,
      usuario_id: true,
      usuarios: { select: { id: true, nombre: true } },
    },
  },
} satisfies Prisma.ventasInclude;

@Injectable()
export class SalesService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly cashRegistersService: CashRegistersService,
    private readonly inventoryStockService: InventoryStockService,
  ) {}

  findAll(query: ListSalesQueryDto = {}) {
    const where: Prisma.ventasWhereInput = {};

    if (query.fromDate || query.toDate) {
      where.fecha = {};
      if (query.fromDate) {
        where.fecha.gte = parseLocalDateString(query.fromDate);
      }
      if (query.toDate) {
        const end = parseLocalDateString(query.toDate);
        end.setHours(23, 59, 59, 999);
        where.fecha.lte = end;
      }
    }

    const search = query.search?.trim();
    if (search) {
      where.OR = [
        { socios: { nombre: { contains: search } } },
        { cajas: { usuarios: { nombre: { contains: search } } } },
        { cliente_razon: { contains: search } },
      ];
    }

    return this.prisma.ventas
      .findMany({
        where,
        include: saleInclude,
        orderBy: { fecha: 'desc' },
        take: query.limit ?? 50,
      })
      .then((rows) => rows.map(mapSaleSummary));
  }

  async findOne(id: number) {
    const sale = await this.prisma.ventas.findUnique({
      where: { id },
      include: saleInclude,
    });
    if (!sale) throw new NotFoundException('Venta no encontrada');
    return mapSale(sale);
  }

  async getTicket(id: number) {
    const sale = await this.prisma.ventas.findUnique({
      where: { id },
      include: saleInclude,
    });
    if (!sale) throw new NotFoundException('Venta no encontrada');
    return mapSaleTicket(sale);
  }

  /**
   * Regla de negocio (POS):
   * - Venta transaccional: cabecera, detalle, descuento de stock y total en caja.
   * - No vender sin caja abierta ni sin stock suficiente.
   * - Stock nunca negativo (validado vía InventoryStockService + movimiento tipo sale).
   */
  async create(dto: CreateSaleDto, userId: number) {
    const openRegister =
      await this.cashRegistersService.requireOpenRegister(userId);

    const productIds = [...new Set(dto.items.map((item) => item.productId))];
    const products = await this.prisma.productos.findMany({
      where: { id: { in: productIds } },
      include: { categorias: true },
    });
    const productMap = new Map(
      products.map((product) => [product.id, product]),
    );

    const quantityByProduct = new Map<number, number>();
    for (const item of dto.items) {
      quantityByProduct.set(
        item.productId,
        (quantityByProduct.get(item.productId) ?? 0) + item.quantity,
      );
    }

    let grossTotal = 0;
    const lineItems: Array<{
      productId: number;
      quantity: number;
      unitPrice: number;
      subtotal: number;
    }> = [];

    for (const [productId, totalQuantity] of quantityByProduct) {
      const product = productMap.get(productId);
      if (!product) {
        throw new NotFoundException(`Producto ${productId} no encontrado`);
      }
      if (product.estado !== productos_estado.activo) {
        throw new BadRequestException(
          `Producto ${product.nombre} está inactivo`,
        );
      }
      if (product.categorias?.estado !== 'activo') {
        throw new BadRequestException(
          `La categoría del producto ${product.nombre} está inactiva`,
        );
      }
      const available = product.stock ?? 0;
      if (totalQuantity > available) {
        throw new BadRequestException(
          `Stock insuficiente para ${product.nombre}. Disponible: ${available}`,
        );
      }

      const unitPrice = Number(product.precio_venta);
      const subtotal = unitPrice * totalQuantity;
      grossTotal += subtotal;
      lineItems.push({
        productId,
        quantity: totalQuantity,
        unitPrice,
        subtotal,
      });
    }

    const discount = dto.discount ?? 0;
    if (discount > grossTotal) {
      throw new BadRequestException(
        'El descuento no puede superar el total de la venta',
      );
    }
    const total = Math.max(0, grossTotal - discount);

    const sale = await this.prisma.$transaction(async (tx) => {
      const createdSale = await tx.ventas.create({
        data: {
          caja_id: openRegister.id,
          socio_id: dto.memberId ?? null,
          total,
          descuento: discount,
          metodo_pago: dto.paymentMethod ?? ventas_metodo_pago.efectivo,
          tipo_comprobante: dto.receiptType ?? ventas_tipo_comprobante.boleta,
          cliente_tipo_doc: dto.clientDocumentType ?? null,
          cliente_num_doc: dto.clientDocumentNumber ?? null,
          cliente_razon: dto.clientName ?? null,
          cliente_direccion: dto.clientAddress ?? null,
        },
      });

      for (const line of lineItems) {
        await tx.detalle_ventas.create({
          data: {
            venta_id: createdSale.id,
            producto_id: line.productId,
            cantidad: line.quantity,
            precio_unitario: line.unitPrice,
            subtotal: line.subtotal,
          },
        });

        await this.inventoryStockService.applyStockChange(
          {
            productId: line.productId,
            quantityDelta: -line.quantity,
            movementType: movimientos_inventario_tipo.sale,
            userId,
            saleId: createdSale.id,
            notes: `Venta POS #${createdSale.id}`,
          },
          tx,
        );
      }

      await tx.cajas.update({
        where: { id: openRegister.id },
        data: {
          total_ventas: {
            increment: total,
          },
        },
      });

      return tx.ventas.findUnique({
        where: { id: createdSale.id },
        include: saleInclude,
      });
    });

    return mapSale(sale!);
  }
}
