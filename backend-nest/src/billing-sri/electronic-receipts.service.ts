import { Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import {
  firstDayOfCurrentMonth,
  getDateRange,
  todayDateString,
} from '../common/utils/date.util';
import { PrismaService } from '../database/prisma.service';
import { ListElectronicReceiptsQueryDto } from './dto/list-electronic-receipts-query.dto';
import {
  mapElectronicReceiptDetail,
  mapElectronicReceiptSummary,
  mapSriLog,
} from './electronic-receipt.mapper';

@Injectable()
export class ElectronicReceiptsService {
  constructor(private readonly prisma: PrismaService) {}

  async findAll(query: ListElectronicReceiptsQueryDto = {}) {
    const fromDate = query.fromDate ?? firstDayOfCurrentMonth();
    const toDate = query.toDate ?? todayDateString();
    const { start, end } = getDateRange(fromDate, toDate);

    const where: Prisma.comprobantes_electronicosWhereInput = {
      fecha_emision: { gte: start, lte: end },
    };

    if (query.documentType) {
      where.tipo_doc = query.documentType;
    }
    if (query.status) {
      where.estado_sri = query.status;
    }

    const rows = await this.prisma.comprobantes_electronicos.findMany({
      where,
      orderBy: [{ fecha_emision: 'desc' }, { id: 'desc' }],
      take: query.limit ?? 100,
    });

    return {
      fromDate,
      toDate,
      items: rows.map(mapElectronicReceiptSummary),
    };
  }

  async findOne(id: number) {
    const row = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id },
      include: {
        comprobantes_detalle: { orderBy: { linea: 'asc' } },
      },
    });
    if (!row) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }
    return mapElectronicReceiptDetail(row);
  }

  async getLogs(id: number) {
    await this.ensureExists(id);
    const logs = await this.prisma.sri_log.findMany({
      where: { comprobante_id: id },
      orderBy: { id: 'desc' },
    });
    return logs.map(mapSriLog);
  }

  async getXmlDownload(id: number) {
    const row = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id },
      select: {
        id: true,
        emisor_ruc: true,
        tipo_doc: true,
        serie: true,
        correlativo: true,
        xml_firmado: true,
        sri_authorization_xml: true,
      },
    });
    if (!row) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }

    const xml = row.sri_authorization_xml ?? row.xml_firmado;
    if (!xml) {
      throw new NotFoundException('El comprobante no tiene XML disponible');
    }

    const sequence = String(row.correlativo).padStart(9, '0');
    const filename = `${row.emisor_ruc}-${row.tipo_doc}-${row.serie}-${sequence}.xml`;

    return { xml, filename };
  }

  private async ensureExists(id: number) {
    const exists = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id },
      select: { id: true },
    });
    if (!exists) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }
  }
}
