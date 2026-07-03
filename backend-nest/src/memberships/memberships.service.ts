import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  planes_estado,
  socios_estado,
  suscripciones_estado,
  Prisma,
} from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { CreateMembershipDto } from './dto/create-membership.dto';
import {
  addDays,
  computeMembershipEffectiveStatus,
} from '../common/utils/membership-status.util';
import { parseLocalDateString, startOfDay } from '../common/utils/date.util';

@Injectable()
export class MembershipsService {
  constructor(private readonly prisma: PrismaService) {}

  async findAll() {
    const items = await this.prisma.suscripciones.findMany({
      orderBy: { id: 'desc' },
      include: {
        socios: { select: { id: true, nombre: true, dni: true } },
        planes: { select: { id: true, nombre: true, precio: true } },
      },
    });

    await Promise.all(items.map((m) => this.syncExpiredMembership(m)));

    const refreshed = await this.prisma.suscripciones.findMany({
      orderBy: { id: 'desc' },
      include: {
        socios: { select: { id: true, nombre: true, dni: true } },
        planes: { select: { id: true, nombre: true, precio: true } },
      },
    });

    return refreshed.map((m) => this.mapDetail(m));
  }

  async findOne(id: number) {
    const membership = await this.prisma.suscripciones.findUnique({
      where: { id },
      include: {
        socios: true,
        planes: true,
      },
    });
    if (!membership) throw new NotFoundException('Membresía no encontrada');

    await this.syncExpiredMembership(membership);
    const refreshed = await this.prisma.suscripciones.findUnique({
      where: { id },
      include: { socios: true, planes: true },
    });

    return this.mapDetail(refreshed!);
  }

  async create(dto: CreateMembershipDto) {
    const member = await this.prisma.socios.findUnique({
      where: { id: dto.memberId },
    });
    if (!member) throw new NotFoundException('Socio no encontrado');
    if (member.estado === socios_estado.inactivo) {
      throw new BadRequestException('No se puede asignar membresía a socio inactivo');
    }

    const plan = await this.prisma.planes.findUnique({
      where: { id: dto.planId },
    });
    if (!plan) throw new NotFoundException('Plan no encontrado');
    if (plan.estado === planes_estado.inactivo) {
      throw new BadRequestException('El plan seleccionado está inactivo');
    }

    const fechaInicio = parseLocalDateString(dto.startDate);
    const fechaFin = addDays(fechaInicio, plan.duracion_dias);

    const created = await this.prisma.suscripciones.create({
      data: {
        socio_id: dto.memberId,
        plan_id: dto.planId,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        estado: suscripciones_estado.activa,
        tipo_comprobante: dto.receiptType,
      },
      include: {
        socios: { select: { id: true, nombre: true, dni: true } },
        planes: { select: { id: true, nombre: true, precio: true } },
      },
    });

    return this.mapDetail(created);
  }

  async cancel(id: number) {
    const membership = await this.prisma.suscripciones.findUnique({
      where: { id },
    });
    if (!membership) throw new NotFoundException('Membresía no encontrada');

    if (membership.estado === suscripciones_estado.vencida) {
      throw new BadRequestException('La membresía ya está vencida');
    }

    const updated = await this.prisma.suscripciones.update({
      where: { id },
      data: { estado: suscripciones_estado.vencida },
      include: {
        socios: { select: { id: true, nombre: true, dni: true } },
        planes: { select: { id: true, nombre: true, precio: true } },
      },
    });

    return this.mapDetail(updated);
  }

  private async syncExpiredMembership(membership: {
    id: number;
    estado: suscripciones_estado | null;
    fecha_fin: Date | null;
  }) {
    if (!membership.fecha_fin || membership.estado !== suscripciones_estado.activa) {
      return;
    }

    const today = startOfDay(new Date());
    const end = startOfDay(membership.fecha_fin);
    if (end < today) {
      await this.prisma.suscripciones.update({
        where: { id: membership.id },
        data: { estado: suscripciones_estado.vencida },
      });
    }
  }

  private mapDetail(membership: {
    id: number;
    socio_id: number | null;
    plan_id: number | null;
    fecha_inicio: Date | null;
    fecha_fin: Date | null;
    estado: suscripciones_estado | null;
    tipo_comprobante: string | null;
    comprobante_id: number | null;
    socios?: {
      id: number;
      nombre: string;
      dni: string;
      email?: string | null;
      telefono?: string | null;
      estado?: string | null;
    } | null;
    planes?: {
      id: number;
      nombre: string;
      precio: unknown;
      duracion_dias?: number;
      descripcion?: string | null;
      estado?: string | null;
    } | null;
  }) {
    const effectiveStatus = computeMembershipEffectiveStatus({
      estado: membership.estado ?? undefined,
      fechaInicio: membership.fecha_inicio,
      fechaFin: membership.fecha_fin,
    });

    return {
      id: membership.id,
      memberId: membership.socio_id,
      memberName: membership.socios?.nombre ?? null,
      memberDni: membership.socios?.dni ?? null,
      planId: membership.plan_id,
      planName: membership.planes?.nombre ?? null,
      planPrice: membership.planes?.precio ?? null,
      startDate: membership.fecha_inicio,
      endDate: membership.fecha_fin,
      status: membership.estado,
      effectiveStatus,
      receiptType: membership.tipo_comprobante,
      receiptId: membership.comprobante_id,
      member: membership.socios ?? null,
      plan: membership.planes ?? null,
    };
  }
}
