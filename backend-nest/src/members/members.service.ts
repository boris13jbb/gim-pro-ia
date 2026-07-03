import {
  ConflictException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { Prisma, socios_estado } from '@prisma/client';
import * as bcrypt from 'bcrypt';
import { PrismaService } from '../database/prisma.service';
import { buildMemberPhotoUrl } from '../config/upload.config';
import { CreateMemberDto } from './dto/create-member.dto';
import { ListMembersQueryDto } from './dto/list-members-query.dto';
import { UpdateMemberDto } from './dto/update-member.dto';
import {
  computeMembershipEffectiveStatus,
  isMembershipValidForAccess,
} from '../common/utils/membership-status.util';

@Injectable()
export class MembersService {
  constructor(private readonly prisma: PrismaService) {}

  async findAll(query: ListMembersQueryDto) {
    const page = query.page ?? 1;
    const limit = query.limit ?? 20;
    const skip = (page - 1) * limit;

    const where: Prisma.sociosWhereInput = {};
    if (query.estado) where.estado = query.estado;
    if (query.search?.trim()) {
      const term = query.search.trim();
      where.OR = [
        { nombre: { contains: term } },
        { dni: { contains: term } },
        { email: { contains: term } },
      ];
    }

    const [items, total] = await Promise.all([
      this.prisma.socios.findMany({
        where,
        orderBy: { id: 'desc' },
        skip,
        take: limit,
      }),
      this.prisma.socios.count({ where }),
    ]);

    return {
      items: items.map((m) => this.toPublicMember(m)),
      meta: { page, limit, total, totalPages: Math.ceil(total / limit) },
    };
  }

  async findOne(id: number) {
    const member = await this.prisma.socios.findUnique({ where: { id } });
    if (!member) throw new NotFoundException('Socio no encontrado');
    return this.toPublicMember(member);
  }

  findById(id: number) {
    return this.prisma.socios.findUnique({ where: { id } });
  }

  async create(dto: CreateMemberDto) {
    await this.ensureUniqueDni(dto.dni);
    const passwordHash = dto.password
      ? await bcrypt.hash(dto.password, 10)
      : undefined;

    const created = await this.prisma.socios.create({
      data: {
        nombre: dto.nombre,
        dni: dto.dni,
        email: dto.email,
        telefono: dto.telefono,
        estado: dto.estado ?? socios_estado.activo,
        foto: dto.foto,
        password: passwordHash,
      },
    });
    return this.toPublicMember(created);
  }

  async update(id: number, dto: UpdateMemberDto) {
    await this.findByIdOrThrow(id);
    if (dto.dni) await this.ensureUniqueDni(dto.dni, id);

    return this.prisma.socios.update({
      where: { id },
      data: {
        ...(dto.nombre !== undefined ? { nombre: dto.nombre } : {}),
        ...(dto.dni !== undefined ? { dni: dto.dni } : {}),
        ...(dto.email !== undefined ? { email: dto.email } : {}),
        ...(dto.telefono !== undefined ? { telefono: dto.telefono } : {}),
        ...(dto.estado !== undefined ? { estado: dto.estado } : {}),
        ...(dto.foto !== undefined ? { foto: dto.foto } : {}),
      },
    }).then((m) => this.toPublicMember(m));
  }

  async updateStatus(id: number, estado: socios_estado) {
    await this.findByIdOrThrow(id);
    const updated = await this.prisma.socios.update({
      where: { id },
      data: { estado },
    });
    return this.toPublicMember(updated);
  }

  async updatePassword(id: number, password: string) {
    await this.findByIdOrThrow(id);
    const passwordHash = await bcrypt.hash(password, 10);
    const updated = await this.prisma.socios.update({
      where: { id },
      data: { password: passwordHash },
    });
    return this.toPublicMember(updated);
  }

  async validateMemberCredentials(login: string, password: string) {
    const normalized = login.trim();
    const member = normalized.includes('@')
      ? await this.prisma.socios.findFirst({ where: { email: normalized } })
      : await this.prisma.socios.findUnique({ where: { dni: normalized } });

    if (!member?.password) {
      return { kind: 'invalid' as const };
    }

    const passwordHash = member.password.replace(/^\$2y\$/, '$2a$');
    const passwordOk = await bcrypt.compare(password, passwordHash);
    if (!passwordOk) {
      return { kind: 'invalid' as const };
    }

    if (member.estado === socios_estado.inactivo) {
      return { kind: 'inactive' as const };
    }

    return { kind: 'ok' as const, member };
  }

  toPublicMember(member: {
    id: number;
    nombre: string;
    dni: string;
    email?: string | null;
    telefono?: string | null;
    estado?: socios_estado | null;
    foto?: string | null;
    fecha_registro?: Date | null;
  }) {
    return {
      id: member.id,
      nombre: member.nombre,
      dni: member.dni,
      email: member.email ?? null,
      telefono: member.telefono ?? null,
      estado: member.estado ?? socios_estado.activo,
      foto: member.foto ?? null,
      photoUrl: buildMemberPhotoUrl(member.foto),
      fechaRegistro: member.fecha_registro ?? null,
    };
  }

  async getMembershipSummary(memberId: number) {
    const member = await this.findByIdOrThrow(memberId);
    const latest = await this.getLatestMembership(memberId);
    await this.syncExpiredMembership(latest);

    const refreshed = latest
      ? await this.prisma.suscripciones.findUnique({
          where: { id: latest.id },
          include: { planes: true },
        })
      : null;

    const effectiveStatus = computeMembershipEffectiveStatus(
      refreshed
        ? {
            estado: refreshed.estado ?? undefined,
            fechaInicio: refreshed.fecha_inicio,
            fechaFin: refreshed.fecha_fin,
          }
        : null,
    );

    return {
      memberId: member.id,
      memberStatus: member.estado,
      isMembershipValid:
        member.estado === socios_estado.activo &&
        isMembershipValidForAccess(
          refreshed
            ? {
                estado: refreshed.estado ?? undefined,
                fechaInicio: refreshed.fecha_inicio,
                fechaFin: refreshed.fecha_fin,
              }
            : null,
        ),
      effectiveStatus,
      currentMembership: refreshed
        ? await this.mapMembership(refreshed)
        : null,
    };
  }

  async listMemberships(memberId: number) {
    await this.findByIdOrThrow(memberId);
    const items = await this.prisma.suscripciones.findMany({
      where: { socio_id: memberId },
      orderBy: { id: 'desc' },
      include: { planes: true },
    });

    await Promise.all(items.map((m) => this.syncExpiredMembership(m)));

    const refreshed = await this.prisma.suscripciones.findMany({
      where: { socio_id: memberId },
      orderBy: { id: 'desc' },
      include: { planes: true },
    });

    return Promise.all(refreshed.map((m) => this.mapMembership(m)));
  }

  private async getLatestMembership(memberId: number) {
    return this.prisma.suscripciones.findFirst({
      where: { socio_id: memberId },
      orderBy: [{ fecha_fin: 'desc' }, { id: 'desc' }],
      include: { planes: true },
    });
  }

  private async syncExpiredMembership(
    membership: { id: number; estado: string | null; fecha_fin: Date | null } | null,
  ) {
    if (!membership?.fecha_fin || membership.estado !== 'activa') return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const end = new Date(membership.fecha_fin);
    end.setHours(0, 0, 0, 0);

    if (end < today) {
      await this.prisma.suscripciones.update({
        where: { id: membership.id },
        data: { estado: 'vencida' },
      });
    }
  }

  private async mapMembership(
    membership: Prisma.suscripcionesGetPayload<{ include: { planes: true } }>,
  ) {
    const effectiveStatus = computeMembershipEffectiveStatus({
      estado: membership.estado ?? undefined,
      fechaInicio: membership.fecha_inicio,
      fechaFin: membership.fecha_fin,
    });

    return {
      id: membership.id,
      memberId: membership.socio_id,
      planId: membership.plan_id,
      planName: membership.planes?.nombre ?? null,
      planPrice: membership.planes?.precio ?? null,
      startDate: membership.fecha_inicio,
      endDate: membership.fecha_fin,
      status: membership.estado,
      effectiveStatus,
      receiptType: membership.tipo_comprobante,
      receiptId: membership.comprobante_id,
    };
  }

  private async findByIdOrThrow(id: number) {
    const member = await this.prisma.socios.findUnique({ where: { id } });
    if (!member) throw new NotFoundException('Socio no encontrado');
    return member;
  }

  private async ensureUniqueDni(dni: string, excludeId?: number) {
    const existing = await this.prisma.socios.findUnique({ where: { dni } });
    if (existing && existing.id !== excludeId) {
      throw new ConflictException('Ya existe un socio con ese DNI');
    }
  }
}
