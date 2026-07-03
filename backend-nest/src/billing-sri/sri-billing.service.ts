import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  comprobantes_electronicos_estado_sri,
  comprobantes_electronicos_origen_tipo,
  suscripciones_tipo_comprobante,
  ventas_tipo_comprobante,
} from '@prisma/client';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { PrismaService } from '../database/prisma.service';
import { SriReceiptRepositoryService } from './services/sri-receipt-repository.service';
import { SriSoapClientService } from './services/sri-soap-client.service';
import { SriTaxCalculatorService } from './services/sri-tax-calculator.service';
import { SriXmlBuilderService } from './services/sri-xml-builder.service';
import { SriXmlSignerService } from './services/sri-xml-signer.service';
import {
  SriEmitResult,
  SriReceiptHeaderInput,
} from './types/sri-billing.types';
import { IssueCreditNoteDto } from './dto/issue-credit-note.dto';
import {
  isSriTestEnvironment,
  normalizeSriEnvironment,
} from './utils/sri-environment.util';

@Injectable()
export class SriBillingService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly repository: SriReceiptRepositoryService,
    private readonly taxCalculator: SriTaxCalculatorService,
    private readonly xmlBuilder: SriXmlBuilderService,
    private readonly xmlSigner: SriXmlSignerService,
    private readonly soapClient: SriSoapClientService,
  ) {}

  async issueFromMembership(membershipId: number, userId: number) {
    const membership = await this.prisma.suscripciones.findUnique({
      where: { id: membershipId },
      include: {
        socios: true,
        planes: true,
      },
    });
    if (!membership?.planes || !membership.socios) {
      throw new NotFoundException('Suscripción no encontrada');
    }
    if (membership.comprobante_id) {
      throw new BadRequestException(
        'La suscripción ya tiene comprobante asociado',
      );
    }

    const member = membership.socios;
    const input: SriReceiptHeaderInput = {
      originType: comprobantes_electronicos_origen_tipo.suscripcion,
      originId: membership.id,
      documentType: '01',
      customerDocumentType: member.tipo_doc ?? '1',
      customerTaxId: member.dni || '9999999999999',
      customerName: member.nombre,
      customerAddress: member.direccion_fiscal ?? 'Quito, Ecuador',
      customerEmail: member.email,
      paymentForm: 'Contado',
      paymentMethod: 'efectivo',
      userId,
      items: [
        {
          code: `PLAN-${membership.plan_id}`,
          description: `Suscripción: ${membership.planes.nombre}`,
          unit: 'ZZ',
          quantity: 1,
          unitPrice: Number(membership.planes.precio),
          taxType: '10',
        },
      ],
    };

    const result = await this.emit(input);
    if (result.ok) {
      await this.prisma.suscripciones.update({
        where: { id: membershipId },
        data: {
          comprobante_id: result.receiptId,
          tipo_comprobante: suscripciones_tipo_comprobante.factura,
        },
      });
    }
    return result;
  }

  async issueFromSale(saleId: number, userId: number) {
    const sale = await this.prisma.ventas.findUnique({
      where: { id: saleId },
      include: {
        socios: true,
        detalle_ventas: {
          include: { productos: true },
        },
      },
    });
    if (!sale) {
      throw new NotFoundException('Venta no encontrada');
    }
    if (sale.comprobante_id) {
      throw new BadRequestException('La venta ya tiene comprobante asociado');
    }

    const items = sale.detalle_ventas.map((line) => ({
      code: line.productos.codigo ?? `P-${line.producto_id}`,
      description: line.productos.nombre,
      unit: 'NIU',
      quantity: line.cantidad,
      unitPrice: Number(line.precio_unitario),
      taxType: '10',
    }));

    const input: SriReceiptHeaderInput = {
      originType: comprobantes_electronicos_origen_tipo.venta,
      originId: sale.id,
      documentType: '01',
      customerDocumentType:
        sale.cliente_tipo_doc ?? sale.socios?.tipo_doc ?? '1',
      customerTaxId:
        sale.cliente_num_doc ?? sale.socios?.dni ?? '9999999999999',
      customerName:
        sale.cliente_razon ?? sale.socios?.nombre ?? 'CONSUMIDOR FINAL',
      customerAddress:
        sale.cliente_direccion ??
        sale.socios?.direccion_fiscal ??
        'Quito, Ecuador',
      paymentMethod: sale.metodo_pago ?? 'efectivo',
      discounts: Number(sale.descuento ?? 0),
      userId,
      items,
    };

    const result = await this.emit(input);
    if (result.ok) {
      await this.prisma.ventas.update({
        where: { id: saleId },
        data: {
          comprobante_id: result.receiptId,
          tipo_comprobante: ventas_tipo_comprobante.factura,
        },
      });
    }
    return result;
  }

  async issueCreditNote(
    receiptId: number,
    dto: IssueCreditNoteDto,
    userId: number,
  ) {
    const original = await this.repository.findReceiptWithLines(receiptId);
    if (original.tipo_doc !== '01') {
      throw new BadRequestException(
        'Solo se puede emitir nota de crédito sobre facturas',
      );
    }
    if (
      original.estado_sri !== comprobantes_electronicos_estado_sri.autorizado
    ) {
      throw new BadRequestException(
        'La factura original debe estar autorizada por el SRI',
      );
    }

    const input: SriReceiptHeaderInput = {
      originType: comprobantes_electronicos_origen_tipo.manual,
      originId: receiptId,
      documentType: '04',
      customerDocumentType: original.cliente_tipo_doc,
      customerTaxId: original.cliente_num_doc ?? '9999999999999',
      customerName: original.cliente_razon,
      customerAddress: original.cliente_direccion,
      customerEmail: original.cliente_email,
      referenceDocumentType: original.tipo_doc,
      referenceEstablishment: original.serie.slice(0, 3),
      referenceEmissionPoint: original.serie.slice(3, 6),
      referenceSequence: original.correlativo,
      referenceIssueDate: original.fecha_emision,
      creditNoteReasonCode: dto.reasonCode ?? '01',
      creditNoteReason: dto.reasonDescription ?? 'Anulación de la operación',
      userId,
      items: original.comprobantes_detalle.map((line) => ({
        code: line.codigo ?? undefined,
        description: line.descripcion,
        unit: line.unidad ?? 'NIU',
        quantity: Number(line.cantidad),
        unitPrice: Number(line.precio_unitario),
        taxType: line.tipo_afectacion ?? '10',
      })),
    };

    return this.emit(input);
  }

  async retryAuthorization(receiptId: number) {
    const receipt = await this.repository.findReceiptWithLines(receiptId);
    if (!receipt.clave_acceso) {
      throw new BadRequestException('El comprobante no tiene clave de acceso');
    }
    const company = await this.loadCompanyConfig();
    const authorization = await this.soapClient.authorizeReceipt(
      receipt.clave_acceso,
      company.sriAmbiente,
    );
    await this.repository.insertLog(
      receiptId,
      'consulta-autorizacionComprobante',
      receipt.clave_acceso,
      authorization.rawResponse,
      authorization.status,
      authorization.messages.join(' | ') || null,
    );

    if (authorization.ok) {
      await this.repository.updateShipment(receiptId, {
        status: comprobantes_electronicos_estado_sri.autorizado,
        code: 'AUTORIZADO',
        description: 'Autorizado por el SRI',
        signedXml: receipt.xml_firmado,
        authorizedXml: authorization.authorizedXml ?? receipt.xml_firmado,
        hash: receipt.xml_hash,
      });
      return {
        ok: true,
        receiptId,
        code: 'AUTORIZADO',
        description: 'Autorizado exitosamente por el SRI',
        simulated: authorization.simulated,
      } satisfies SriEmitResult;
    }

    const status =
      authorization.status === 'PROCESAMIENTO'
        ? comprobantes_electronicos_estado_sri.recibida
        : comprobantes_electronicos_estado_sri.no_autorizado;
    await this.repository.updateShipment(receiptId, {
      status,
      code: authorization.status,
      description: authorization.messages.join(' | '),
      signedXml: receipt.xml_firmado,
      hash: receipt.xml_hash,
      error: authorization.messages.join(' | '),
    });
    return {
      ok: false,
      receiptId,
      code: authorization.status,
      description: authorization.messages.join(' | ') || 'No autorizado',
      simulated: authorization.simulated,
    };
  }

  private async emit(input: SriReceiptHeaderInput): Promise<SriEmitResult> {
    const company = await this.loadCompanyConfig();
    if (!company.ruc) {
      throw new BadRequestException('RUC no configurado en el sistema');
    }

    const series =
      input.series ??
      `${company.sriEstablecimiento.padStart(3, '0')}${company.sriPuntoEmision.padStart(3, '0')}`;
    const correlativo = await this.repository.reserveSequence(
      input.documentType,
      series,
    );
    const { header, lines } = this.taxCalculator.calculate(
      input,
      company,
      correlativo,
      series,
    );
    const receiptId = await this.repository.saveReceipt(header, lines);

    try {
      const built =
        header.documentType === '04'
          ? this.xmlBuilder.buildCreditNote(header, lines)
          : this.xmlBuilder.buildInvoice(header, lines);

      const { signedXml, hash, simulatedSign } = this.signXml(
        built.xml,
        company,
      );
      this.persistXmlFile(header, signedXml);

      const reception = await this.soapClient.sendReceipt(
        signedXml,
        company.sriAmbiente,
        { forceSimulate: simulatedSign },
      );
      await this.repository.insertLog(
        receiptId,
        'validarComprobante',
        signedXml,
        reception.rawResponse,
        reception.status,
        reception.messages.join(' | ') || null,
      );

      if (!reception.ok) {
        const message =
          reception.messages.join(' | ') || 'Comprobante devuelto';
        await this.repository.updateShipment(receiptId, {
          status: comprobantes_electronicos_estado_sri.devuelta,
          code: 'DEVUELTA',
          description: message,
          signedXml,
          hash,
          error: message,
        });
        return {
          ok: false,
          receiptId,
          code: 'DEVUELTA',
          description: message,
          simulated: reception.simulated,
        };
      }

      const authorization = await this.soapClient.authorizeReceipt(
        header.claveAcceso,
        company.sriAmbiente,
        { forceSimulate: simulatedSign || reception.simulated },
      );
      await this.repository.insertLog(
        receiptId,
        'autorizacionComprobante',
        header.claveAcceso,
        authorization.rawResponse,
        authorization.status,
        authorization.messages.join(' | ') || null,
      );

      if (authorization.ok) {
        const authorizedXml = authorization.authorizedXml ?? signedXml;
        this.persistXmlFile(header, authorizedXml);
        await this.repository.updateShipment(receiptId, {
          status: comprobantes_electronicos_estado_sri.autorizado,
          code: 'AUTORIZADO',
          description: 'Autorizado por el SRI',
          signedXml,
          authorizedXml,
          hash,
        });
        return {
          ok: true,
          receiptId,
          code: 'AUTORIZADO',
          description: simulatedSign
            ? 'Autorizado (firma o SRI en modo pruebas/simulación)'
            : 'Autorizado por el SRI',
          simulated:
            reception.simulated || authorization.simulated || simulatedSign,
        };
      }

      const status =
        authorization.status === 'PROCESAMIENTO'
          ? comprobantes_electronicos_estado_sri.recibida
          : comprobantes_electronicos_estado_sri.no_autorizado;
      const message = authorization.messages.join(' | ') || 'No autorizado';
      await this.repository.updateShipment(receiptId, {
        status,
        code: authorization.status,
        description: message,
        signedXml,
        hash,
        error: message,
      });
      return {
        ok: false,
        receiptId,
        code: authorization.status,
        description: message,
        simulated: authorization.simulated,
      };
    } catch (error) {
      const message =
        error instanceof Error ? error.message : 'Error al emitir comprobante';
      await this.repository.updateShipment(receiptId, {
        status: comprobantes_electronicos_estado_sri.error,
        code: 'ERROR',
        description: message,
        signedXml: null,
        hash: null,
        error: message,
      });
      return {
        ok: false,
        receiptId,
        code: 'ERROR',
        description: message,
      };
    }
  }

  private signXml(
    xml: string,
    company: Awaited<ReturnType<SriBillingService['loadCompanyConfig']>>,
  ) {
    const certDir = process.env.SRI_CERT_DIR ?? 'cert';
    const certFile = company.certificateFile;
    const certPassword = company.certificatePassword;
    const certPath = certFile
      ? join(process.cwd(), 'public', certDir, certFile)
      : '';
    const certMissing = !certFile || !existsSync(certPath);

    // Regla de negocio (paridad PHP):
    // En ambiente de pruebas (1) se permite emitir sin P12 válido para no bloquear desarrollo local.
    if (isSriTestEnvironment(company.sriAmbiente) && certMissing) {
      return {
        signedXml: xml,
        hash: Buffer.from(xml).toString('base64').slice(0, 32),
        simulatedSign: true,
      };
    }

    if (certMissing) {
      throw new BadRequestException(
        'Certificado digital P12 no configurado o no encontrado',
      );
    }

    const signedXml = this.xmlSigner.sign(xml, certPath, certPassword ?? '');
    return {
      signedXml,
      hash: this.xmlSigner.extractDigestHash(signedXml),
      simulatedSign: false,
    };
  }

  private persistXmlFile(
    header: {
      emisorRuc: string;
      documentType: string;
      series: string;
      correlativo: number;
    },
    xml: string,
  ) {
    const xmlDir = join(
      process.cwd(),
      'public',
      process.env.SRI_XML_DIR ?? 'sri/xml',
    );
    if (!existsSync(xmlDir)) {
      mkdirSync(xmlDir, { recursive: true });
    }
    const ruc = header.emisorRuc.replace(/\D/g, '');
    const seq = String(header.correlativo).padStart(9, '0');
    const filename = `${ruc}-${header.documentType}-${header.series}-${seq}.xml`;
    writeFileSync(join(xmlDir, filename), xml, 'utf8');
  }

  private async loadCompanyConfig() {
    const config = await this.prisma.configuracion.findFirst();
    if (!config) {
      throw new BadRequestException('Configuración del sistema no encontrada');
    }
    return {
      ruc: config.ruc ?? '',
      razonSocial: config.razon_social ?? config.nombre_sistema ?? 'Gym System',
      nombreComercial:
        config.nombre_comercial ?? config.nombre_sistema ?? 'Gym System',
      direccion: config.direccion ?? 'Dirección Matriz',
      ivaTasa: Number(config.iva_tasa ?? 15),
      incluyeIva: config.incluye_iva ?? true,
      sriAmbiente: normalizeSriEnvironment(config.sri_ambiente),
      sriEstablecimiento: config.sri_establecimiento ?? '001',
      sriPuntoEmision: config.sri_punto_emision ?? '001',
      certificateFile: config.sri_certificado_p12 ?? '',
      certificatePassword: config.sri_certificado_clave ?? '',
    };
  }
}
