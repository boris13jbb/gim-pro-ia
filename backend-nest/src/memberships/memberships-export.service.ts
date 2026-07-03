import { Injectable } from '@nestjs/common';
import { Workbook } from 'exceljs';
import { PrismaService } from '../database/prisma.service';
import { computeMembershipEffectiveStatus } from '../common/utils/membership-status.util';

@Injectable()
export class MembershipsExportService {
  constructor(private readonly prisma: PrismaService) {}

  async buildExcelBuffer(): Promise<Buffer> {
    const [memberships, config] = await Promise.all([
      this.prisma.suscripciones.findMany({
        orderBy: { id: 'desc' },
        include: {
          socios: { select: { nombre: true } },
          planes: { select: { nombre: true, precio: true } },
        },
      }),
      this.prisma.configuracion.findFirst({ select: { moneda: true } }),
    ]);

    const currency = config?.moneda ?? '$';
    const workbook = new Workbook();
    const sheet = workbook.addWorksheet('Membresías');

    sheet.columns = [
      { header: 'ID', key: 'id', width: 8 },
      { header: 'SOCIO', key: 'memberName', width: 28 },
      { header: 'PLAN', key: 'planName', width: 22 },
      { header: `PRECIO (${currency})`, key: 'price', width: 16 },
      { header: 'INICIO', key: 'startDate', width: 14 },
      { header: 'FIN', key: 'endDate', width: 14 },
      { header: 'ESTADO', key: 'status', width: 14 },
      { header: 'ESTADO EFECTIVO', key: 'effectiveStatus', width: 18 },
    ];

    const headerRow = sheet.getRow(1);
    headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
    headerRow.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF2E7D32' },
    };

    for (const item of memberships) {
      const effectiveStatus = computeMembershipEffectiveStatus({
        estado: item.estado ?? undefined,
        fechaInicio: item.fecha_inicio,
        fechaFin: item.fecha_fin,
      });

      sheet.addRow({
        id: item.id,
        memberName: item.socios?.nombre ?? '',
        planName: item.planes?.nombre ?? '',
        price: Number(item.planes?.precio ?? 0),
        startDate: this.formatDate(item.fecha_inicio),
        endDate: this.formatDate(item.fecha_fin),
        status: String(item.estado ?? '').toUpperCase(),
        effectiveStatus: effectiveStatus.toUpperCase(),
      });
    }

    const buffer = await workbook.xlsx.writeBuffer();
    return Buffer.from(buffer);
  }

  buildFilename() {
    const date = new Date().toISOString().slice(0, 10);
    return `Reporte_Membresias_${date}.xlsx`;
  }

  private formatDate(value: Date | null | undefined) {
    if (!value) return '';
    const d = new Date(value);
    const day = String(d.getUTCDate()).padStart(2, '0');
    const month = String(d.getUTCMonth() + 1).padStart(2, '0');
    const year = d.getUTCFullYear();
    return `${day}/${month}/${year}`;
  }
}
