import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { categorias_estado, Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { CreateCategoryDto } from './dto/create-category.dto';
import {
  ListCategoriesQueryDto,
  UpdateCategoryDto,
} from './dto/update-category.dto';

function mapCategory(row: {
  id: number;
  nombre: string;
  estado: categorias_estado | null;
}) {
  return {
    id: row.id,
    name: row.nombre,
    status: row.estado ?? categorias_estado.activo,
  };
}

@Injectable()
export class CategoriesService {
  constructor(private readonly prisma: PrismaService) {}

  findAll(query: ListCategoriesQueryDto = {}) {
    const where: Prisma.categoriasWhereInput = {};
    if (query.status) where.estado = query.status;

    return this.prisma.categorias
      .findMany({
        where,
        orderBy: { nombre: 'asc' },
      })
      .then((rows) => rows.map(mapCategory));
  }

  findActive() {
    return this.findAll({ status: categorias_estado.activo });
  }

  async findOne(id: number) {
    const category = await this.prisma.categorias.findUnique({ where: { id } });
    if (!category) throw new NotFoundException('Categoría no encontrada');
    return mapCategory(category);
  }

  create(dto: CreateCategoryDto) {
    return this.prisma.categorias
      .create({
        data: {
          nombre: dto.name.trim(),
          estado: categorias_estado.activo,
        },
      })
      .then(mapCategory);
  }

  async update(id: number, dto: UpdateCategoryDto) {
    await this.findOne(id);
    if (dto.name !== undefined && !dto.name.trim()) {
      throw new BadRequestException(
        'El nombre de la categoría no puede estar vacío',
      );
    }

    return this.prisma.categorias
      .update({
        where: { id },
        data: {
          ...(dto.name !== undefined ? { nombre: dto.name.trim() } : {}),
        },
      })
      .then(mapCategory);
  }

  async updateStatus(id: number, status: categorias_estado) {
    await this.findOne(id);
    return this.prisma.categorias
      .update({
        where: { id },
        data: { estado: status },
      })
      .then(mapCategory);
  }
}
