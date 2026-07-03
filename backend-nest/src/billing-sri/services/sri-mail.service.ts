import {
  BadRequestException,
  Injectable,
  NotFoundException,
  ServiceUnavailableException,
} from '@nestjs/common';
import nodemailer from 'nodemailer';
import { PrismaService } from '../../database/prisma.service';
import { SriRideExportService } from './sri-ride-export.service';
import { ElectronicReceiptsService } from '../electronic-receipts.service';

export type SendReceiptEmailResult = {
  sent: boolean;
  recipient: string;
  messageId: string | null;
  attachments: string[];
};

@Injectable()
export class SriMailService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly rideExport: SriRideExportService,
    private readonly receiptsService: ElectronicReceiptsService,
  ) {}

  /**
   * Regla de negocio:
   * El envío al cliente adjunta RIDE (PDF) y XML autorizado/firmado.
   * Requiere SMTP real configurado; no se simula éxito sin envío efectivo.
   */
  async sendReceiptToCustomer(
    receiptId: number,
    overrideEmail?: string,
  ): Promise<SendReceiptEmailResult> {
    const transporter = this.createTransporter();
    if (!transporter) {
      throw new ServiceUnavailableException(
        'Servicio de correo no configurado. Defina SMTP_HOST, SMTP_USER y SMTP_PASS en el servidor.',
      );
    }

    const receipt = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id: receiptId },
      select: {
        id: true,
        cliente_email: true,
        cliente_razon: true,
        tipo_doc: true,
        serie: true,
        correlativo: true,
        clave_acceso: true,
      },
    });
    if (!receipt) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }

    const recipient = (overrideEmail ?? receipt.cliente_email ?? '').trim();
    if (!recipient) {
      throw new BadRequestException(
        'El cliente no tiene correo electrónico configurado',
      );
    }

    const [ride, xmlFile, company] = await Promise.all([
      this.rideExport.buildRidePdf(receiptId),
      this.receiptsService.getXmlDownload(receiptId),
      this.prisma.configuracion.findFirst({
        select: { nombre_sistema: true, email: true },
      }),
    ]);

    const sequence = String(receipt.correlativo).padStart(9, '0');
    const docNumber = `${receipt.serie}-${sequence}`;
    const systemName = company?.nombre_sistema ?? 'Gym System';
    const from = process.env.SMTP_FROM ?? company?.email ?? process.env.SMTP_USER;

    const info = await transporter.sendMail({
      from,
      to: recipient,
      subject: `${systemName} — Comprobante electrónico ${docNumber}`,
      text: [
        `Estimado/a ${receipt.cliente_razon},`,
        '',
        `Adjuntamos su comprobante electrónico ${docNumber}.`,
        receipt.clave_acceso ? `Clave de acceso: ${receipt.clave_acceso}` : '',
        '',
        'Puede verificar el documento en https://srienlinea.sri.gob.ec/',
        '',
        `Atentamente,`,
        systemName,
      ]
        .filter(Boolean)
        .join('\n'),
      attachments: [
        {
          filename: ride.filename,
          content: ride.buffer,
          contentType: 'application/pdf',
        },
        {
          filename: xmlFile.filename,
          content: xmlFile.xml,
          contentType: 'application/xml',
        },
      ],
    });

    return {
      sent: true,
      recipient,
      messageId: info.messageId ?? null,
      attachments: [ride.filename, xmlFile.filename],
    };
  }

  isSmtpConfigured() {
    return !!this.createTransporter();
  }

  private createTransporter() {
    const host = process.env.SMTP_HOST?.trim();
    const user = process.env.SMTP_USER?.trim();
    const pass = process.env.SMTP_PASS?.trim();
    if (!host || !user || !pass) {
      return null;
    }

    const port = Number(process.env.SMTP_PORT ?? 587);
    const secure =
      process.env.SMTP_SECURE === 'true' || String(port) === '465';

    return nodemailer.createTransport({
      host,
      port,
      secure,
      auth: { user, pass },
    });
  }
}
