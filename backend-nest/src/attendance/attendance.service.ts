import {
  BadRequestException,
  ConflictException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { Prisma, socios_estado } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { MembersService } from '../members/members.service';
import { buildMemberPhotoUrl } from '../config/upload.config';
import { RegisterAttendanceDto } from './dto/register-attendance.dto';
import { ListAttendanceReportQueryDto } from './dto/list-attendance-report-query.dto';
import { AttendanceAccessPreview } from './types/attendance-access-preview.type';
import {
  getDateRange,
  getTodayRange,
  firstDayOfCurrentMonth,
  startOfDay,
  todayDateString,
} from '../common/utils/date.util';

@Injectable()
export class AttendanceService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly membersService: MembersService,
  ) {}

  /**
   * Regla de negocio (equivalente PHP validar):
   * Busca socio por DNI y calcula si puede ingresar sin persistir asistencia.
   */
  async validateAccessByDni(dni: string): Promise<AttendanceAccessPreview> {
    const member = await this.prisma.socios.findUnique({
      where: { dni: dni.trim() },
    });

    if (!member) {
      return {
        found: false,
        canAccess: false,
        reason: 'member_not_found',
        message: 'DNI no encontrado en la base de datos.',
      };
    }

    return this.buildAccessPreview(member);
  }

  /**
   * Regla de negocio (corrige E02 PHP):
   * Revalida membresía vigente y socio activo antes de guardar.
   */
  async register(dto: RegisterAttendanceDto) {
    const member = await this.prisma.socios.findUnique({
      where: { id: dto.memberId },
    });
    if (!member) {
      throw new NotFoundException('Socio no encontrado');
    }

    const preview = await this.buildAccessPreview(member);
    if (!preview.canAccess) {
      throw new BadRequestException(preview.message);
    }

    await this.ensureNoDuplicateToday(member.id);

    const record = await this.prisma.asistencias.create({
      data: {
        socio_id: member.id,
        metodo_ingreso: dto.method,
      },
      include: {
        socios: { select: { id: true, nombre: true, dni: true, foto: true } },
      },
    });

    return this.mapRecord(record);
  }

  /**
   * Flujo rápido para lectura DNI/QR: valida y registra en una sola operación.
   */
  async scanAndRegister(dni: string, method: 'dni' | 'qr') {
    const preview = await this.validateAccessByDni(dni);
    if (!preview.found || !preview.member) {
      throw new NotFoundException(preview.message);
    }
    if (!preview.canAccess) {
      throw new BadRequestException(preview.message);
    }

    return this.register({ memberId: preview.member.id, method });
  }

  async listToday() {
    const { start, end } = getTodayRange();
    const items = await this.prisma.asistencias.findMany({
      where: { fecha_hora: { gte: start, lte: end } },
      orderBy: { fecha_hora: 'desc' },
      include: {
        socios: { select: { id: true, nombre: true, dni: true, foto: true } },
      },
    });

    return items.map((item) => this.mapRecord(item));
  }

  async getReport(query: ListAttendanceReportQueryDto) {
    const from = query.from ?? firstDayOfCurrentMonth();
    const to = query.to ?? todayDateString();
    const { start, end } = getDateRange(from, to);

    const where: Prisma.asistenciasWhereInput = {
      fecha_hora: { gte: start, lte: end },
      ...(query.memberId ? { socio_id: query.memberId } : {}),
    };

    const items = await this.prisma.asistencias.findMany({
      where,
      orderBy: { fecha_hora: 'desc' },
      include: {
        socios: { select: { id: true, nombre: true, dni: true, foto: true } },
      },
    });

    const totalVisits = items.length;
    const daysInPeriod = Math.max(
      1,
      Math.floor((end.getTime() - start.getTime()) / 86_400_000) + 1,
    );

    return {
      from,
      to,
      memberId: query.memberId ?? null,
      totalVisits,
      averageDaily: Math.round((totalVisits / daysInPeriod) * 10) / 10,
      items: items.map((item) => this.mapRecord(item)),
    };
  }

  async getRanking(query: ListAttendanceReportQueryDto) {
    const from = query.from ?? firstDayOfCurrentMonth();
    const to = query.to ?? todayDateString();
    const { start, end } = getDateRange(from, to);

    const grouped = await this.prisma.asistencias.groupBy({
      by: ['socio_id'],
      where: {
        fecha_hora: { gte: start, lte: end },
        ...(query.memberId ? { socio_id: query.memberId } : {}),
      },
      _count: { _all: true },
    });

    const sorted = grouped
      .sort((a, b) => b._count._all - a._count._all)
      .slice(0, 5);

    if (sorted.length === 0) {
      return {
        from,
        to,
        leaders: [] as Array<{
          memberId: number;
          memberName: string;
          visits: number;
        }>,
      };
    }

    const memberIds = sorted.map((g) => g.socio_id);
    const members = await this.prisma.socios.findMany({
      where: { id: { in: memberIds } },
      select: { id: true, nombre: true },
    });
    const nameById = new Map(members.map((m) => [m.id, m.nombre]));

    return {
      from,
      to,
      leaders: sorted.map((g) => ({
        memberId: g.socio_id,
        memberName: nameById.get(g.socio_id) ?? 'N/A',
        visits: g._count._all,
      })),
    };
  }

  private async buildAccessPreview(member: {
    id: number;
    nombre: string;
    dni: string;
    estado: socios_estado | null;
    foto: string | null;
  }): Promise<AttendanceAccessPreview> {
    const membership = await this.membersService.getMembershipSummary(
      member.id,
    );
    const canAccess =
      member.estado === socios_estado.activo && membership.isMembershipValid;

    let message = 'Acceso permitido';
    if (member.estado !== socios_estado.activo) {
      message = 'Acceso denegado: socio inactivo';
    } else if (!membership.isMembershipValid) {
      message = 'Membresía vencida o no existente';
    }

    const endDate = membership.currentMembership?.endDate;
    const daysRemaining =
      endDate && membership.isMembershipValid
        ? Math.max(
            0,
            Math.round(
              (startOfDay(new Date(endDate)).getTime() -
                startOfDay(new Date()).getTime()) /
                86_400_000,
            ),
          )
        : 0;

    return {
      found: true,
      canAccess,
      reason: canAccess ? 'allowed' : 'denied',
      message,
      member: this.membersService.toPublicMember(member),
      memberStatus: member.estado,
      isMembershipValid: membership.isMembershipValid,
      effectiveStatus: membership.effectiveStatus,
      daysRemaining,
      endDate: endDate ?? null,
      currentMembership: membership.currentMembership,
    };
  }

  private async ensureNoDuplicateToday(memberId: number) {
    const { start, end } = getTodayRange();
    const existing = await this.prisma.asistencias.findFirst({
      where: {
        socio_id: memberId,
        fecha_hora: { gte: start, lte: end },
      },
    });

    if (existing) {
      throw new ConflictException(
        'El socio ya registró asistencia hoy. Evita duplicados en el mismo día.',
      );
    }
  }

  private mapRecord(
    record: Prisma.asistenciasGetPayload<{
      include: {
        socios: { select: { id: true; nombre: true; dni: true; foto: true } };
      };
    }>,
  ) {
    return {
      id: record.id,
      memberId: record.socio_id,
      memberName: record.socios?.nombre ?? null,
      memberDni: record.socios?.dni ?? null,
      checkedInAt: record.fecha_hora,
      method: record.metodo_ingreso ?? null,
      photoUrl: buildMemberPhotoUrl(record.socios?.foto ?? null),
    };
  }
}
