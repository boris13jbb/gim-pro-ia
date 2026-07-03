import { Injectable } from '@nestjs/common';
import { Workbook } from 'exceljs';
import {
  createPdfBuffer,
  formatPdfDate,
  formatPdfDateTime,
} from '../common/utils/pdf-buffer.util';
import { ReportsService } from '../reports/reports.service';
import { AttendanceService } from './attendance.service';
import { ListAttendanceReportQueryDto } from './dto/list-attendance-report-query.dto';

@Injectable()
export class AttendanceExportService {
  constructor(
    private readonly attendanceService: AttendanceService,
    private readonly reportsService: ReportsService,
  ) {}

  async buildExcel(query: ListAttendanceReportQueryDto) {
    const report = await this.attendanceService.getReport(query);
    const workbook = new Workbook();
    const sheet = workbook.addWorksheet('Asistencias');

    sheet.columns = [
      { header: 'Fecha y Hora', key: 'dateTime', width: 20 },
      { header: 'Socio', key: 'memberName', width: 30 },
      { header: 'DNI', key: 'dni', width: 16 },
      { header: 'Método', key: 'method', width: 12 },
    ];

    const headerRow = sheet.getRow(1);
    headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
    headerRow.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF6366F1' },
    };

    for (const item of report.items) {
      sheet.addRow({
        dateTime: formatPdfDateTime(item.checkedInAt),
        memberName: item.memberName,
        dni: item.memberDni,
        method: item.method,
      });
    }

    const buffer = await workbook.xlsx.writeBuffer();
    return {
      buffer: Buffer.from(buffer),
      filename: `Reporte_Asistencias_${report.from}_${report.to}.xlsx`,
    };
  }

  async buildPdf(query: ListAttendanceReportQueryDto) {
    const [report, company] = await Promise.all([
      this.attendanceService.getReport(query),
      this.reportsService.getCompanyConfig(),
    ]);

    const buffer = await createPdfBuffer((doc) => {
      doc.fontSize(16).text(company.systemName, { align: 'center' });
      doc
        .fontSize(10)
        .text(
          `Reporte de Asistencias: ${formatPdfDate(parseDate(report.from))} al ${formatPdfDate(parseDate(report.to))}`,
          { align: 'center' },
        );
      doc.moveDown();
      doc.fontSize(10).text(`Total visitas: ${report.totalVisits}`);
      doc.text(`Promedio diario: ${report.averageDaily}`);
      doc.moveDown();

      doc
        .fontSize(9)
        .text('Fecha/Hora | Socio | DNI | Método', { underline: true });
      doc.moveDown(0.3);

      for (const item of report.items) {
        doc.text(
          `${formatPdfDateTime(item.checkedInAt)} | ${item.memberName} | ${item.memberDni} | ${item.method}`,
        );
      }
    });

    return {
      buffer,
      filename: `Reporte_Asistencias_${report.from}_${report.to}.pdf`,
    };
  }
}

function parseDate(value: string) {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day);
}
