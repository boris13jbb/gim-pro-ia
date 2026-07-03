import { Injectable } from '@nestjs/common';
import {
  createPdfBuffer,
  formatMoney,
  formatPdfDateTime,
} from '../common/utils/pdf-buffer.util';
import { ReportsService } from '../reports/reports.service';
import { SalesService } from './sales.service';

@Injectable()
export class SalesTicketExportService {
  constructor(
    private readonly salesService: SalesService,
    private readonly reportsService: ReportsService,
  ) {}

  async buildTicketPdf(saleId: number) {
    const [ticket, company] = await Promise.all([
      this.salesService.getTicket(saleId),
      this.reportsService.getCompanyConfig(),
    ]);

    const buffer = await createPdfBuffer(
      (doc) => {
        doc.fontSize(11).text(company.systemName, { align: 'center' });
        if (company.address) {
          doc.fontSize(8).text(company.address, { align: 'center' });
        }
        if (company.phone) {
          doc.fontSize(8).text(`Tel: ${company.phone}`, { align: 'center' });
        }
        doc.moveDown();
        doc
          .fontSize(9)
          .text(`Ticket N° ${ticket.ticketNumber}`, { align: 'left' });
        doc.text(
          `Fecha: ${formatPdfDateTime(ticket.summary.issuedAt)}`,
          { align: 'right' },
        );
        doc.text(`Cajero: ${ticket.summary.cashierName ?? 'N/A'}`);
        doc.text(`Cliente: ${ticket.summary.clientDisplayName}`);
        doc.moveDown();

        doc.fontSize(8).text('Producto | Cant | P.Unit | Subtotal', {
          underline: true,
        });
        doc.moveDown(0.2);

        for (const item of ticket.sale.items) {
          doc.text(
            `${item.productName ?? 'Producto'} | ${item.quantity} | ${formatMoney(item.unitPrice, company.currency)} | ${formatMoney(item.subtotal, company.currency)}`,
          );
        }

        doc.moveDown();
        doc.fontSize(10).text(
          `TOTAL: ${formatMoney(ticket.summary.total, company.currency)}`,
          { align: 'right' },
        );
        doc
          .fontSize(8)
          .text(`Pago: ${ticket.summary.paymentMethod ?? 'efectivo'}`, {
            align: 'center',
          });
        doc.moveDown();
        doc.fontSize(7).text('¡Gracias por tu preferencia!', { align: 'center' });
      },
      { size: [226.77, 566.93], margin: 12 },
    );

    return {
      buffer,
      filename: `Ticket_${ticket.ticketNumber}.pdf`,
    };
  }
}
