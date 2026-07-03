import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { Prisma, productos_estado } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { InventoryService } from '../inventory/inventory.service';
import { CreateProductDto } from './dto/create-product.dto';
import {
  ListProductsQueryDto,
  LowStockQueryDto,
  UpdateProductDto,
} from './dto/update-product.dto';
import { mapProduct } from './product.mapper';

const productInclude = {
  categorias: {
    select: { id: true, nombre: true, estado: true },
  },
} satisfies Prisma.productosInclude;

@Injectable()
export class ProductsService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly inventoryService: InventoryService,
  ) {}

  findAll(query: ListProductsQueryDto = {}) {
    const where: Prisma.productosWhereInput = {};
    if (query.status) where.estado = query.status;
    if (query.categoryId) where.categoria_id = query.categoryId;

    return this.prisma.productos
      .findMany({
        where,
        include: productInclude,
        orderBy: { nombre: 'asc' },
      })
      .then((rows) => rows.map(mapProduct));
  }

  findActive() {
    return this.prisma.productos
      .findMany({
        where: {
          estado: productos_estado.activo,
          categorias: { estado: 'activo' },
        },
        include: productInclude,
        orderBy: { nombre: 'asc' },
      })
      .then((rows) => rows.map(mapProduct));
  }

  findLowStock(query: LowStockQueryDto = {}) {
    const threshold = query.threshold ?? 5;
    return this.prisma.productos
      .findMany({
        where: {
          estado: productos_estado.activo,
          stock: { lte: threshold },
        },
        include: productInclude,
        orderBy: { stock: 'asc' },
      })
      .then((rows) => rows.map(mapProduct));
  }

  async findOne(id: number) {
    const product = await this.prisma.productos.findUnique({
      where: { id },
      include: productInclude,
    });
    if (!product) throw new NotFoundException('Producto no encontrado');
    return mapProduct(product);
  }

  async create(dto: CreateProductDto) {
    await this.assertCategoryExists(dto.categoryId);
    const initialStock = dto.stock ?? 0;
    this.assertNonNegativeStock(initialStock);

    const product = await this.prisma.productos.create({
      data: {
        categoria_id: dto.categoryId,
        codigo: dto.code?.trim() || null,
        nombre: dto.name.trim(),
        precio_compra: dto.purchasePrice,
        precio_venta: dto.salePrice,
        stock: 0,
        estado: productos_estado.activo,
      },
    });

    if (initialStock > 0) {
      await this.inventoryService.recordInitialStock(product.id, initialStock);
    }

    return this.findOne(product.id);
  }

  async update(id: number, dto: UpdateProductDto) {
    const current = await this.getRawProduct(id);
    if (dto.categoryId !== undefined) {
      await this.assertCategoryExists(dto.categoryId);
    }
    if (dto.stock !== undefined) {
      this.assertNonNegativeStock(dto.stock);
    }
    if (dto.name !== undefined && !dto.name.trim()) {
      throw new BadRequestException(
        'El nombre del producto no puede estar vacío',
      );
    }

    const stockWillChange =
      dto.stock !== undefined && dto.stock !== (current.stock ?? 0);

    await this.prisma.productos.update({
      where: { id },
      data: {
        ...(dto.categoryId !== undefined
          ? { categoria_id: dto.categoryId }
          : {}),
        ...(dto.code !== undefined ? { codigo: dto.code?.trim() || null } : {}),
        ...(dto.name !== undefined ? { nombre: dto.name.trim() } : {}),
        ...(dto.purchasePrice !== undefined
          ? { precio_compra: dto.purchasePrice }
          : {}),
        ...(dto.salePrice !== undefined ? { precio_venta: dto.salePrice } : {}),
      },
    });

    if (stockWillChange) {
      await this.inventoryService.recordStockUpdate(
        id,
        current.stock ?? 0,
        dto.stock!,
      );
    }

    return this.findOne(id);
  }

  async updateStatus(id: number, status: productos_estado) {
    await this.findOne(id);
    return this.prisma.productos
      .update({
        where: { id },
        data: { estado: status },
        include: productInclude,
      })
      .then(mapProduct);
  }

  async getRawProduct(id: number) {
    const product = await this.prisma.productos.findUnique({ where: { id } });
    if (!product) throw new NotFoundException('Producto no encontrado');
    return product;
  }

  private async assertCategoryExists(categoryId: number) {
    const category = await this.prisma.categorias.findUnique({
      where: { id: categoryId },
    });
    if (!category) {
      throw new BadRequestException('La categoría indicada no existe');
    }
  }

  private assertNonNegativeStock(stock: number) {
    if (stock < 0) {
      throw new BadRequestException('El stock no puede ser negativo');
    }
  }
}
