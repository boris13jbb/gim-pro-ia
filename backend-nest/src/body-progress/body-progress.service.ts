import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { MembersService } from '../members/members.service';
import { parseLocalDateString } from '../common/utils/date.util';
import { CreateBodyMeasurementDto } from './dto/create-body-measurement.dto';

@Injectable()
export class BodyProgressService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly membersService: MembersService,
  ) {}

  async listMemberMeasurements(memberId: number) {
    await this.membersService.findOne(memberId);

    const items = await this.prisma.medidas.findMany({
      where: { socio_id: memberId },
      orderBy: { fecha: 'asc' },
    });

    const mapped = items.map((item) => this.mapMeasurement(item));

    return {
      memberId,
      items: mapped,
      chart: this.buildChartSeries(mapped),
    };
  }

  async getMeasurement(id: number) {
    const record = await this.prisma.medidas.findUnique({ where: { id } });
    if (!record) {
      throw new NotFoundException('Medida corporal no encontrada');
    }
    return this.mapMeasurement(record);
  }

  async createMeasurement(memberId: number, dto: CreateBodyMeasurementDto) {
    await this.membersService.findOne(memberId);
    this.ensureAtLeastOneMetric(dto);

    const created = await this.prisma.medidas.create({
      data: {
        socio_id: memberId,
        fecha: parseLocalDateString(dto.measuredAt),
        peso: dto.weight,
        grasa: dto.bodyFat,
        cintura: dto.waist,
        brazo: dto.arm,
      },
    });

    return this.mapMeasurement(created);
  }

  async deleteMeasurement(id: number) {
    const record = await this.prisma.medidas.findUnique({ where: { id } });
    if (!record) {
      throw new NotFoundException('Medida corporal no encontrada');
    }

    await this.prisma.medidas.delete({ where: { id } });
    return { id, memberId: record.socio_id, deleted: true };
  }

  private ensureAtLeastOneMetric(dto: CreateBodyMeasurementDto) {
    const hasValue =
      dto.weight != null ||
      dto.bodyFat != null ||
      dto.waist != null ||
      dto.arm != null;

    if (!hasValue) {
      throw new BadRequestException(
        'Debe registrar al menos una métrica (weight, bodyFat, waist o arm)',
      );
    }
  }

  private mapMeasurement(record: {
    id: number;
    socio_id: number;
    peso: Prisma.Decimal | null;
    grasa: Prisma.Decimal | null;
    cintura: Prisma.Decimal | null;
    brazo: Prisma.Decimal | null;
    fecha: Date | null;
  }) {
    return {
      id: record.id,
      memberId: record.socio_id,
      weight: decimalToNumber(record.peso),
      bodyFat: decimalToNumber(record.grasa),
      waist: decimalToNumber(record.cintura),
      arm: decimalToNumber(record.brazo),
      measuredAt: record.fecha,
    };
  }

  private buildChartSeries(
    items: Array<{
      measuredAt: Date | null;
      weight: number | null;
      bodyFat: number | null;
      waist: number | null;
      arm: number | null;
    }>,
  ) {
    return {
      labels: items.map((item) => formatChartLabel(item.measuredAt)),
      weight: items.map((item) => item.weight),
      bodyFat: items.map((item) => item.bodyFat),
      waist: items.map((item) => item.waist),
      arm: items.map((item) => item.arm),
    };
  }
}

function decimalToNumber(
  value: Prisma.Decimal | null | undefined,
): number | null {
  if (value == null) return null;
  return Number(value);
}

function formatChartLabel(date: Date | null): string {
  if (!date) return '';
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  return `${day}/${month}`;
}
