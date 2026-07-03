import { Injectable } from '@nestjs/common';
import { Workbook } from 'exceljs';
import type PDFKit from 'pdfkit';
import {
  drawHorizontalBarChart,
  drawVerticalBarChart,
} from '../common/utils/pdf-chart.util';
import {
  createPdfBuffer,
  formatMoney,
  formatPdfDate,
} from '../common/utils/pdf-buffer.util';
import { ReportDateRangeQueryDto } from './dto/report-date-range-query.dto';
import { ReportsService } from './reports.service';

@Injectable()
export class ReportsExportService {
  constructor(private readonly reportsService: ReportsService) {}

  async buildFinancialExcel(query: ReportDateRangeQueryDto) {
    const [summary, movements, company] = await Promise.all([
      this.reportsService.getFinancialSummary(query),
      this.reportsService.getFinancialMovements(query),
      this.reportsService.getCompanyConfig(),
    ]);

    const workbook = new Workbook();

    const summarySheet = workbook.addWorksheet('Resumen');
    summarySheet.columns = [
      { header: 'Indicador', key: 'label', width: 32 },
      { header: `Valor (${company.currency})`, key: 'value', width: 18 },
    ];
    const summaryHeader = summarySheet.getRow(1);
    summaryHeader.font = { bold: true, color: { argb: 'FFFFFFFF' } };
    summaryHeader.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF6366F1' },
    };

    summarySheet.addRows([
      { label: 'Ingresos Membresías', value: summary.membershipIncome },
      { label: 'Ventas POS', value: summary.posIncome },
      { label: 'Total Ingresos', value: summary.totalIncome },
      { label: 'Total Gastos', value: summary.totalExpenses },
      { label: 'Utilidad Neta', value: summary.netProfit },
      { label: 'Socios Activos', value: summary.activeMembers },
    ]);

    summarySheet.addRow({});
    summarySheet.addRow({ label: 'Ingresos por mes' });
    for (const row of summary.charts.incomeByMonth) {
      summarySheet.addRow({ label: row.month, value: row.total });
    }

    summarySheet.addRow({});
    summarySheet.addRow({ label: 'Ventas POS por método de pago' });
    for (const row of summary.charts.salesByPaymentMethod) {
      summarySheet.addRow({
        label: row.paymentMethod,
        value: row.total,
      });
    }

    const sheet = workbook.addWorksheet('Movimientos');
    sheet.columns = [
      { header: 'Fecha', key: 'date', width: 14 },
      { header: 'Descripción', key: 'description', width: 48 },
      { header: `Monto (${company.currency})`, key: 'amount', width: 16 },
      { header: 'Tipo', key: 'type', width: 12 },
    ];

    const headerRow = sheet.getRow(1);
    headerRow.font = { bold: true, color: { argb: 'FFFFFFFF' } };
    headerRow.fill = {
      type: 'pattern',
      pattern: 'solid',
      fgColor: { argb: 'FF6366F1' },
    };

    for (const movement of movements.items) {
      sheet.addRow({
        date: formatPdfDate(movement.date),
        description: movement.description,
        amount: movement.amount,
        type: movement.type,
      });
    }

    const buffer = await workbook.xlsx.writeBuffer();
    return {
      buffer: Buffer.from(buffer),
      filename: `Reporte_Finanzas_${summary.fromDate.replace(/-/g, '')}_${summary.toDate.replace(/-/g, '')}.xlsx`,
    };
  }

  async buildFinancialPdf(query: ReportDateRangeQueryDto) {
    const [summary, movements, company] = await Promise.all([
      this.reportsService.getFinancialSummary(query),
      this.reportsService.getFinancialMovements(query),
      this.reportsService.getCompanyConfig(),
    ]);

    const buffer = await createPdfBuffer((doc) => {
      const pageWidth =
        doc.page.width - doc.page.margins.left - doc.page.margins.right;
      const left = doc.page.margins.left;

      doc
        .fontSize(16)
        .fillColor('#111111')
        .text(company.systemName, { align: 'center' });
      doc
        .fontSize(10)
        .text(
          `Reporte Financiero: ${formatPdfDate(parseDate(summary.fromDate))} al ${formatPdfDate(parseDate(summary.toDate))}`,
          { align: 'center' },
        );
      doc.moveDown(0.8);

      drawSummaryBox(doc, left, doc.y, pageWidth, summary, company.currency);
      doc.y += 78;

      const chartStartY = doc.y;
      drawVerticalBarChart(
        doc,
        left,
        chartStartY,
        pageWidth * 0.58,
        130,
        summary.charts.incomeByMonth.map((row) => ({
          label: row.month,
          value: row.total,
        })),
        { barColor: '#6366F1', currency: company.currency },
      );

      drawHorizontalBarChart(
        doc,
        left + pageWidth * 0.6,
        chartStartY,
        pageWidth * 0.4,
        summary.charts.salesByPaymentMethod.map((row, index) => ({
          label: row.paymentMethod,
          value: row.total,
          color: ['#6366F1', '#10B981', '#F59E0B', '#EF4444'][index % 4],
        })),
        {
          title: 'Métodos de pago (POS)',
          currency: company.currency,
        },
      );

      doc.y = chartStartY + 140;
      doc.moveDown();

      drawMovementsTable(
        doc,
        left,
        doc.y,
        pageWidth,
        movements.items.slice(0, 60),
        company.currency,
      );

      doc.moveDown();
      doc
        .fontSize(8)
        .fillColor('#666666')
        .text(
          `Generado el ${formatPdfDate(new Date())} — ${company.systemName}`,
          { align: 'center' },
        );
      doc.fillColor('#000000');
    });

    return {
      buffer,
      filename: `Reporte_Finanzas_${summary.fromDate}_${summary.toDate}.pdf`,
    };
  }
}

function drawSummaryBox(
  doc: PDFKit.PDFDocument,
  x: number,
  y: number,
  width: number,
  summary: Awaited<ReturnType<ReportsService['getFinancialSummary']>>,
  currency: string,
) {
  const colWidth = width / 2;
  const rowHeight = 22;
  const profitColor = summary.netProfit >= 0 ? '#059669' : '#DC2626';

  doc.save();
  doc.fontSize(11).fillColor('#FFFFFF');
  doc.rect(x, y, width, 20).fill('#6366F1');
  doc.text('  Resumen del Período', x + 4, y + 5);
  doc.restore();

  const rows = [
    [
      `Ingresos Membresías: ${formatMoney(summary.membershipIncome, currency)}`,
      `Ventas POS: ${formatMoney(summary.posIncome, currency)}`,
    ],
    [
      `Total Gastos: ${formatMoney(summary.totalExpenses, currency)}`,
      `Socios Activos: ${summary.activeMembers}`,
    ],
    [
      `Utilidad Neta: ${formatMoney(summary.netProfit, currency)}`,
      `Total Ingresos: ${formatMoney(summary.totalIncome, currency)}`,
    ],
  ];

  let rowY = y + 20;
  rows.forEach((pair, rowIndex) => {
    pair.forEach((text, colIndex) => {
      const cellX = x + colIndex * colWidth;
      const isProfitCell = rowIndex === 2 && colIndex === 0;

      doc.save();
      if (isProfitCell) {
        doc.rect(cellX, rowY, colWidth, rowHeight).fill(profitColor);
        doc
          .fontSize(9)
          .fillColor('#FFFFFF')
          .text(text, cellX + 6, rowY + 6, {
            width: colWidth - 12,
          });
      } else {
        doc.rect(cellX, rowY, colWidth, rowHeight).stroke('#E5E7EB');
        doc
          .fontSize(9)
          .fillColor('#111111')
          .text(text, cellX + 6, rowY + 6, {
            width: colWidth - 12,
          });
      }
      doc.restore();
    });
    rowY += rowHeight;
  });
}

function drawMovementsTable(
  doc: PDFKit.PDFDocument,
  x: number,
  y: number,
  width: number,
  items: Awaited<ReturnType<ReportsService['buildMovements']>>,
  currency: string,
) {
  const colDate = 58;
  const colAmount = 62;
  const colType = 48;
  const colDesc = width - colDate - colAmount - colType;
  const rowHeight = 16;

  doc.save();
  doc.fontSize(11).fillColor('#FFFFFF');
  doc.rect(x, y, width, 18).fill('#6366F1');
  doc.text('  Detalle de Movimientos', x + 4, y + 4);
  doc.restore();

  let rowY = y + 18;
  const headers = ['Fecha', 'Descripción', 'Monto', 'Tipo'];
  const colWidths = [colDate, colDesc, colAmount, colType];

  doc.fontSize(8).fillColor('#374151');
  let headerX = x;
  headers.forEach((header, index) => {
    doc.rect(headerX, rowY, colWidths[index], rowHeight).fill('#EBEBF5');
    doc.fillColor('#111111').text(header, headerX + 4, rowY + 4, {
      width: colWidths[index] - 8,
    });
    headerX += colWidths[index];
  });
  rowY += rowHeight;

  for (const movement of items) {
    if (rowY > doc.page.height - doc.page.margins.bottom - 40) {
      doc.addPage();
      rowY = doc.page.margins.top;
    }

    const isExpense = movement.type === 'gasto';
    const fill = isExpense ? '#FEE2E2' : '#F0FDF4';
    const values = [
      formatPdfDate(movement.date),
      truncate(movement.description, 52),
      formatMoney(movement.amount, currency),
      movement.type.toUpperCase(),
    ];

    let cellX = x;
    values.forEach((value, index) => {
      doc.save();
      doc.rect(cellX, rowY, colWidths[index], rowHeight).fill(fill);
      doc.rect(cellX, rowY, colWidths[index], rowHeight).stroke('#E5E7EB');
      doc
        .fontSize(7)
        .fillColor(isExpense && index === 2 ? '#991B1B' : '#111111')
        .text(value, cellX + 3, rowY + 4, {
          width: colWidths[index] - 6,
          align: index === 2 ? 'right' : 'left',
        });
      doc.restore();
      cellX += colWidths[index];
    });
    rowY += rowHeight;
  }
}

function parseDate(value: string) {
  const [year, month, day] = value.split('-').map(Number);
  return new Date(year, month - 1, day);
}

function truncate(value: string, max: number) {
  return value.length > max ? `${value.slice(0, max - 3)}...` : value;
}
