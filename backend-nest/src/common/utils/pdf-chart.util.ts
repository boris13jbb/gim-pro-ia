import type PDFKit from 'pdfkit';

export type PdfBarChartItem = {
  label: string;
  value: number;
};

export type PdfHorizontalBarItem = {
  label: string;
  value: number;
  color: string;
};

const CHART_COLORS = ['#6366F1', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];

/**
 * Dibuja un gráfico de barras verticales con pdfkit (sin dependencias de canvas).
 * Regla de negocio: los reportes PDF deben incluir series visuales, no solo tablas.
 */
export function drawVerticalBarChart(
  doc: PDFKit.PDFDocument,
  x: number,
  y: number,
  width: number,
  height: number,
  items: PdfBarChartItem[],
  options?: { barColor?: string; currency?: string },
) {
  const barColor = options?.barColor ?? '#6366F1';
  const currency = options?.currency ?? '$';
  const filtered = items.filter((item) => item.value > 0);

  doc.fontSize(10).fillColor('#111111').text('Ingresos por mes', x, y);
  const chartTop = y + 16;
  const chartHeight = height - 36;
  const chartWidth = width;

  if (filtered.length === 0) {
    doc
      .fontSize(9)
      .fillColor('#666666')
      .text('Sin ingresos en el período', x, chartTop + chartHeight / 2 - 6);
    return y + height;
  }

  const maxValue = Math.max(...filtered.map((item) => item.value), 1);
  const gap = 8;
  const barWidth = Math.min(
    48,
    (chartWidth - gap * (filtered.length + 1)) / filtered.length,
  );
  const totalBarsWidth =
    barWidth * filtered.length + gap * (filtered.length - 1);
  const startX = x + (chartWidth - totalBarsWidth) / 2;

  doc.save();
  doc.lineWidth(0.5).strokeColor('#E5E7EB');
  doc
    .moveTo(x, chartTop + chartHeight)
    .lineTo(x + chartWidth, chartTop + chartHeight)
    .stroke();
  doc.restore();

  filtered.forEach((item, index) => {
    const barHeight = Math.max(4, (item.value / maxValue) * (chartHeight - 20));
    const barX = startX + index * (barWidth + gap);
    const barY = chartTop + chartHeight - barHeight;

    doc.save();
    doc.roundedRect(barX, barY, barWidth, barHeight, 3).fill(barColor);
    doc.restore();

    doc
      .fontSize(7)
      .fillColor('#374151')
      .text(`${currency}${item.value.toFixed(0)}`, barX - 4, barY - 10, {
        width: barWidth + 8,
        align: 'center',
      });

    doc
      .fontSize(7)
      .fillColor('#6B7280')
      .text(
        formatMonthLabel(item.label),
        barX - 6,
        chartTop + chartHeight + 4,
        {
          width: barWidth + 12,
          align: 'center',
        },
      );
  });

  return y + height;
}

/**
 * Barras horizontales con leyenda de color — equivalente legible al doughnut del PHP web.
 */
export function drawHorizontalBarChart(
  doc: PDFKit.PDFDocument,
  x: number,
  y: number,
  width: number,
  items: PdfHorizontalBarItem[],
  options?: { title?: string; currency?: string },
) {
  const currency = options?.currency ?? '$';
  const title = options?.title ?? 'Distribución';
  const filtered = items.filter((item) => item.value > 0);
  const rowHeight = 22;
  const labelWidth = 72;
  const valueWidth = 64;
  const barAreaWidth = width - labelWidth - valueWidth - 12;

  doc.fontSize(10).fillColor('#111111').text(title, x, y);
  let cursorY = y + 16;

  if (filtered.length === 0) {
    doc
      .fontSize(9)
      .fillColor('#666666')
      .text('Sin datos en el período', x, cursorY);
    return cursorY + 20;
  }

  const maxValue = Math.max(...filtered.map((item) => item.value), 1);
  const total = filtered.reduce((sum, item) => sum + item.value, 0);

  filtered.forEach((item, index) => {
    const color = item.color || CHART_COLORS[index % CHART_COLORS.length];
    const barWidth = Math.max(6, (item.value / maxValue) * barAreaWidth);
    const percent = total > 0 ? ((item.value / total) * 100).toFixed(0) : '0';

    doc
      .fontSize(8)
      .fillColor('#374151')
      .text(capitalize(item.label), x, cursorY + 4, {
        width: labelWidth,
      });

    const barX = x + labelWidth;
    doc.save();
    doc.roundedRect(barX, cursorY + 2, barWidth, 12, 2).fill(color);
    doc.restore();

    doc
      .fontSize(8)
      .fillColor('#111111')
      .text(
        `${currency}${item.value.toFixed(2)} (${percent}%)`,
        barX + barAreaWidth + 8,
        cursorY + 4,
        { width: valueWidth, align: 'right' },
      );

    cursorY += rowHeight;
  });

  return cursorY + 8;
}

function formatMonthLabel(monthKey: string) {
  const [year, month] = monthKey.split('-').map(Number);
  if (!year || !month) return monthKey;
  const date = new Date(year, month - 1, 1);
  return date.toLocaleString('es', { month: 'short', year: '2-digit' });
}

function capitalize(value: string) {
  if (!value) return value;
  return value.charAt(0).toUpperCase() + value.slice(1);
}
