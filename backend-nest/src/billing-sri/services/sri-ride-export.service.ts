import { Injectable, NotFoundException } from '@nestjs/common';
import { comprobantes_electronicos_estado_sri } from '@prisma/client';
import { existsSync } from 'node:fs';
import { join } from 'node:path';
import QRCode from 'qrcode';
import {
  createPdfBuffer,
  formatMoney,
  formatPdfDate,
} from '../../common/utils/pdf-buffer.util';
import { PrismaService } from '../../database/prisma.service';
import {
  getSriCustomerDocLabel,
  getSriDocumentTypeLabel,
} from '../utils/sri-document-labels.util';

@Injectable()
export class SriRideExportService {
  constructor(private readonly prisma: PrismaService) {}

  async buildRidePdf(receiptId: number) {
    const receipt = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id: receiptId },
      include: {
        comprobantes_detalle: { orderBy: { linea: 'asc' } },
      },
    });
    if (!receipt) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }

    const company = await this.prisma.configuracion.findFirst({
      select: {
        nombre_sistema: true,
        razon_social: true,
        ruc: true,
        direccion: true,
        telefono: true,
        email: true,
        moneda: true,
        logo: true,
        iva_tasa: true,
      },
    });

    const issuerName =
      company?.razon_social ?? company?.nombre_sistema ?? 'Gym System';
    const currency = receipt.moneda ?? company?.moneda ?? 'USD';
    const vatRate = company?.iva_tasa != null ? Number(company.iva_tasa) : 15;
    const documentLabel = getSriDocumentTypeLabel(receipt.tipo_doc);
    const sequence = String(receipt.correlativo).padStart(9, '0');
    const documentNumber = `${receipt.serie}-${sequence}`;
    const qrPayload =
      receipt.clave_acceso ??
      `${receipt.emisor_ruc}|${receipt.tipo_doc}|${receipt.serie}|${sequence}`;
    const qrBuffer = await QRCode.toBuffer(qrPayload, {
      errorCorrectionLevel: 'M',
      margin: 1,
      width: 180,
    });

    const logoPath =
      company?.logo &&
      existsSync(join(process.cwd(), 'public', 'img', company.logo))
        ? join(process.cwd(), 'public', 'img', company.logo)
        : null;

    const buffer = await createPdfBuffer((doc) => {
      const pageWidth =
        doc.page.width - doc.page.margins.left - doc.page.margins.right;
      const left = doc.page.margins.left;

      doc.save();
      doc.rect(0, 0, doc.page.width, 14).fill('#6366f1');
      doc.restore();
      doc.y = 24;

      let headerX = left;
      if (logoPath) {
        doc.image(logoPath, left, doc.y, { width: 52 });
        headerX = left + 58;
      }

      doc
        .font('Helvetica-Bold')
        .fontSize(14)
        .fillColor('#212529')
        .text(issuerName, headerX, doc.y, { width: pageWidth - 130 });
      doc
        .font('Helvetica')
        .fontSize(9)
        .fillColor('#666666')
        .text(
          `RUC: ${company?.ruc ?? receipt.emisor_ruc}  |  Tel: ${company?.telefono ?? 'N/A'}`,
          headerX,
        );
      doc.text(company?.direccion ?? '', headerX);

      const boxX = left + pageWidth - 125;
      doc.rect(boxX, 20, 125, 52).strokeColor('#6366f1').lineWidth(1).stroke();
      doc
        .font('Helvetica-Bold')
        .fontSize(8)
        .fillColor('#6366f1')
        .text(`RUC: ${receipt.emisor_ruc}`, boxX + 4, 24, {
          width: 117,
          align: 'center',
        });
      doc.fontSize(7).text(documentLabel, boxX + 4, 36, {
        width: 117,
        align: 'center',
      });
      doc
        .fontSize(11)
        .fillColor('#212529')
        .text(documentNumber, boxX + 4, 50, {
          width: 117,
          align: 'center',
        });

      doc.moveDown(3);
      doc
        .font('Helvetica-Bold')
        .fontSize(9)
        .fillColor('#212529')
        .rect(left, doc.y, pageWidth, 16)
        .fill('#f5f5f8');
      doc
        .fillColor('#212529')
        .text('  DATOS DEL CLIENTE', left + 4, doc.y - 12);

      doc.moveDown(0.4);
      const customerY = doc.y;
      doc
        .font('Helvetica-Bold')
        .fontSize(9)
        .text('Cliente:', left, customerY, { continued: true });
      doc.font('Helvetica').text(` ${receipt.cliente_razon}`, { width: 250 });
      doc
        .font('Helvetica-Bold')
        .text('Fecha:', left + 300, customerY, { continued: true });
      doc.font('Helvetica').text(` ${formatPdfDate(receipt.fecha_emision)}`);

      doc
        .font('Helvetica-Bold')
        .text(
          `${getSriCustomerDocLabel(receipt.cliente_tipo_doc)}:`,
          left,
          doc.y,
          { continued: true },
        );
      doc
        .font('Helvetica')
        .text(` ${receipt.cliente_num_doc ?? 'N/A'}`, { width: 250 });
      doc
        .font('Helvetica-Bold')
        .text('Moneda:', left + 300, doc.y - 12, { continued: true });
      doc.font('Helvetica').text(` ${currency}`);

      if (receipt.cliente_direccion) {
        doc
          .font('Helvetica-Bold')
          .text('Dirección:', left, doc.y, { continued: true });
        doc.font('Helvetica').text(` ${receipt.cliente_direccion}`);
      }

      if (receipt.ref_serie) {
        doc
          .font('Helvetica-Bold')
          .text('Modifica a:', left, doc.y, { continued: true });
        doc
          .font('Helvetica')
          .text(
            ` ${receipt.ref_serie}-${String(receipt.ref_correlativo ?? 0).padStart(9, '0')}`,
            { continued: true },
          );
        if (receipt.motivo_descripcion) {
          doc.font('Helvetica-Bold').text('  Motivo:', { continued: true });
          doc.font('Helvetica').text(` ${receipt.motivo_descripcion}`);
        } else {
          doc.text('');
        }
      }

      doc.moveDown(0.8);
      const tableTop = doc.y;
      const colWidths = [35, 40, 190, 55, 45, 55];
      const headers = ['CANT', 'UNID', 'DESCRIPCIÓN', 'V.UNIT', 'IVA', 'TOTAL'];
      doc.font('Helvetica-Bold').fontSize(8).fillColor('#ffffff');
      let x = left;
      doc.rect(left, tableTop, pageWidth, 16).fill('#6366f1');
      headers.forEach((header, index) => {
        doc.text(header, x + 2, tableTop + 4, {
          width: colWidths[index] - 4,
          align: index >= 3 ? 'right' : 'left',
        });
        x += colWidths[index];
      });

      doc.fillColor('#212529').font('Helvetica').fontSize(8);
      let rowY = tableTop + 18;
      for (const line of receipt.comprobantes_detalle) {
        x = left;
        const values = [
          Number(line.cantidad).toFixed(2),
          line.unidad ?? 'NIU',
          line.descripcion.slice(0, 70),
          Number(line.precio_unitario).toFixed(4),
          Number(line.igv_linea).toFixed(2),
          Number(line.total_linea).toFixed(2),
        ];
        values.forEach((value, index) => {
          doc.text(value, x + 2, rowY, {
            width: colWidths[index] - 4,
            align: index >= 3 ? 'right' : 'left',
          });
          x += colWidths[index];
        });
        rowY += 14;
        doc
          .moveTo(left, rowY - 2)
          .lineTo(left + pageWidth, rowY - 2)
          .strokeColor('#dddddd')
          .stroke();
      }
      doc.y = rowY + 4;

      const labelWidth = pageWidth - 70;
      const valueWidth = 70;
      const addTotalRow = (label: string, value: string, bold = false) => {
        doc.font(bold ? 'Helvetica-Bold' : 'Helvetica').fontSize(bold ? 11 : 9);
        doc.text(label, left + labelWidth - 120, doc.y, {
          width: 120,
          align: 'right',
        });
        doc.text(value, left + labelWidth, doc.y - (bold ? 13 : 11), {
          width: valueWidth,
          align: 'right',
        });
        doc.moveDown(0.2);
      };

      addTotalRow(
        'OP. GRAVADA:',
        formatMoney(Number(receipt.gravadas ?? 0), currency),
      );
      addTotalRow(
        `IVA (${vatRate}%):`,
        formatMoney(Number(receipt.igv ?? 0), currency),
      );
      if (Number(receipt.descuentos ?? 0) > 0) {
        addTotalRow(
          'DESCUENTO:',
          `-${formatMoney(Number(receipt.descuentos ?? 0), currency)}`,
        );
      }
      doc.fillColor('#6366f1');
      addTotalRow(
        'IMPORTE TOTAL:',
        formatMoney(Number(receipt.total), currency),
        true,
      );
      doc.fillColor('#212529');

      if (receipt.total_letras) {
        doc.moveDown(0.3);
        doc
          .font('Helvetica-Oblique')
          .fontSize(9)
          .text(`SON: ${receipt.total_letras}`);
      }

      doc.moveDown(0.8);
      const qrY = doc.y;
      doc.image(qrBuffer, left, qrY, { width: 78, height: 78 });
      doc
        .font('Helvetica-Bold')
        .fontSize(9)
        .text(
          'Representación impresa del Comprobante Electrónico (RIDE)',
          left + 88,
          qrY,
        );
      doc
        .font('Helvetica')
        .fontSize(8)
        .text(
          'Consulte en https://srienlinea.sri.gob.ec/ con la clave de acceso.',
          left + 88,
        );
      if (receipt.clave_acceso) {
        doc.text(`Clave de acceso: ${receipt.clave_acceso}`, left + 88);
      }

      const status =
        receipt.estado_sri ?? comprobantes_electronicos_estado_sri.pendiente;
      const statusColor =
        status === comprobantes_electronicos_estado_sri.autorizado
          ? '#10b981'
          : '#ef4444';
      doc
        .fillColor(statusColor)
        .font('Helvetica-Bold')
        .text(
          `Estado SRI: ${String(status).toUpperCase()}${receipt.cdr_codigo ? ` (${receipt.cdr_codigo})` : ''}`,
          left + 88,
        );
      if (receipt.cdr_descripcion) {
        doc
          .font('Helvetica')
          .fillColor('#212529')
          .text(receipt.cdr_descripcion, left + 88);
      }
      if (receipt.xml_hash) {
        doc.fontSize(7).text(`Hash: ${receipt.xml_hash}`, left + 88);
      }

      doc.moveDown(4);
      doc
        .font('Helvetica-Oblique')
        .fontSize(8)
        .fillColor('#666666')
        .text(
          `Documento generado el ${formatPdfDate(new Date())}. Contacto: ${company?.email ?? 'N/A'}`,
          { align: 'center' },
        );
    });

    const filename = `${receipt.emisor_ruc}-${receipt.tipo_doc}-${receipt.serie}-${sequence}.pdf`;
    return { buffer, filename };
  }
}
