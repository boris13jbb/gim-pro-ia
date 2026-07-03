import { Injectable, NotFoundException } from '@nestjs/common';
import { planes_estado, Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { CreatePlanDto } from './dto/create-plan.dto';
import { UpdatePlanDto } from './dto/update-plan.dto';
import { ListPlansQueryDto } from './dto/update-plan-status.dto';

@Injectable()
export class PlansService {
  constructor(private readonly prisma: PrismaService) {}

  findAll(query: ListPlansQueryDto = {}) {
    const where: Prisma.planesWhereInput = {};
    if (query.estado) where.estado = query.estado;

    return this.prisma.planes.findMany({
      where,
      orderBy: { precio: 'asc' },
    });
  }

  async findOne(id: number) {
    const plan = await this.prisma.planes.findUnique({ where: { id } });
    if (!plan) throw new NotFoundException('Plan no encontrado');
    return plan;
  }

  create(dto: CreatePlanDto) {
    return this.prisma.planes.create({
      data: {
        nombre: dto.nombre,
        precio: dto.precio,
        duracion_dias: dto.duracionDias,
        descripcion: dto.descripcion,
        estado: planes_estado.activo,
      },
    });
  }

  async update(id: number, dto: UpdatePlanDto) {
    await this.findOne(id);
    return this.prisma.planes.update({
      where: { id },
      data: {
        ...(dto.nombre !== undefined ? { nombre: dto.nombre } : {}),
        ...(dto.precio !== undefined ? { precio: dto.precio } : {}),
        ...(dto.duracionDias !== undefined
          ? { duracion_dias: dto.duracionDias }
          : {}),
        ...(dto.descripcion !== undefined
          ? { descripcion: dto.descripcion }
          : {}),
      },
    });
  }

  async updateStatus(id: number, estado: planes_estado) {
    await this.findOne(id);
    return this.prisma.planes.update({
      where: { id },
      data: { estado },
    });
  }
}
