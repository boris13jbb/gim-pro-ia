import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import {
  comprobantes_electronicos_estado_sri,
  comprobantes_electronicos_estado_sunat,
} from '@prisma/client';
import { PrismaService } from '../../database/prisma.service';
import {
  SriCalculatedLine,
  SriReceiptHeaderCalculated,
} from '../types/sri-billing.types';

@Injectable()
export class SriReceiptRepositoryService {
  constructor(private readonly prisma: PrismaService) {}

  async reserveSequence(documentType: string, series: string) {
    return this.prisma.$transaction(async (tx) => {
      const rows = await tx.$queryRaw<Array<{ correlativo: number }>>`
        SELECT correlativo FROM sri_series
        WHERE tipo_doc = ${documentType} AND serie = ${series}
        FOR UPDATE`;
      const current = rows[0];
      if (!current) {
        throw new BadRequestException(
          `Serie SRI no configurada: ${documentType} - ${series}`,
        );
      }
      const next = Number(current.correlativo) + 1;
      await tx.$executeRaw`
        UPDATE sri_series SET correlativo = ${next}
        WHERE tipo_doc = ${documentType} AND serie = ${series}`;
      return next;
    });
  }

  async saveReceipt(header: SriReceiptHeaderCalculated, lines: SriCalculatedLine[]) {
    return this.prisma.$transaction(async (tx) => {
      const receipt = await tx.comprobantes_electronicos.create({
        data: {
          origen_tipo: header.originType,
          origen_id: header.originId,
          tipo_doc: header.documentType,
          serie: header.series,
          correlativo: header.correlativo,
          clave_acceso: header.claveAcceso,
          fecha_emision: header.fechaEmision,
          emisor_ruc: header.emisorRuc,
          emisor_razon: header.emisorRazon,
          cliente_tipo_doc: header.clienteTipoDoc,
          cliente_num_doc: header.clienteNumDoc,
          cliente_razon: header.clienteRazon,
          cliente_direccion: header.clienteDireccion,
          cliente_email: header.clienteEmail,
          moneda: header.moneda,
          gravadas: header.gravadas,
          inafectas: header.inafectas,
          exoneradas: header.exoneradas,
          gratuitas: header.gratuitas,
          descuentos: header.descuentos,
          igv: header.igv,
          total: header.total,
          total_letras: header.totalLetras,
          forma_pago: header.formaPago,
          metodo_pago: header.metodoPago,
          ref_tipo_doc: header.refTipoDoc,
          ref_serie: header.refSerie,
          ref_correlativo: header.refCorrelativo,
          motivo_codigo: header.motivoCodigo,
          motivo_descripcion: header.motivoDescripcion,
          estado_sri: comprobantes_electronicos_estado_sri.pendiente,
          estado_sunat: comprobantes_electronicos_estado_sunat.pendiente,
          usuario_id: header.usuarioId,
          comprobantes_detalle: {
            create: lines.map((line) => ({
              linea: line.line,
              codigo: line.code,
              descripcion: line.description,
              unidad: line.unit,
              cantidad: line.quantity,
              valor_unitario: line.unitValue,
              precio_unitario: line.unitPrice,
              subtotal: line.subtotal,
              igv_linea: line.taxLine,
              total_linea: line.totalLine,
              tipo_afectacion: line.taxType,
            })),
          },
        },
      });
      return receipt.id;
    });
  }

  async updateShipment(
    receiptId: number,
    params: {
      status: comprobantes_electronicos_estado_sri;
      code: string | null;
      description: string | null;
      signedXml: string | null;
      authorizedXml?: string | null;
      hash: string | null;
      error?: string | null;
    },
  ) {
    const legacyStatus = this.mapLegacyStatus(params.status);
    await this.prisma.comprobantes_electronicos.update({
      where: { id: receiptId },
      data: {
        estado_sri: params.status,
        estado_sunat: legacyStatus,
        cdr_codigo: params.code,
        cdr_descripcion: params.description,
        xml_firmado: params.signedXml,
        sri_authorization_xml: params.authorizedXml ?? params.signedXml,
        xml_hash: params.hash,
        mensaje_error: params.error ?? null,
      },
    });
  }

  async insertLog(
    receiptId: number,
    action: string,
    request: string,
    response: string,
    code?: string | null,
    message?: string | null,
  ) {
    await this.prisma.sri_log.create({
      data: {
        comprobante_id: receiptId,
        accion: action,
        request_xml: request,
        response_xml: response,
        codigo: code ?? null,
        mensaje: message ?? null,
      },
    });
  }

  async findReceiptWithLines(receiptId: number) {
    const receipt = await this.prisma.comprobantes_electronicos.findUnique({
      where: { id: receiptId },
      include: {
        comprobantes_detalle: { orderBy: { linea: 'asc' } },
      },
    });
    if (!receipt) {
      throw new NotFoundException('Comprobante electrónico no encontrado');
    }
    return receipt;
  }

  private mapLegacyStatus(status: comprobantes_electronicos_estado_sri) {
    switch (status) {
      case comprobantes_electronicos_estado_sri.autorizado:
        return comprobantes_electronicos_estado_sunat.aceptado;
      case comprobantes_electronicos_estado_sri.no_autorizado:
      case comprobantes_electronicos_estado_sri.devuelta:
        return comprobantes_electronicos_estado_sunat.rechazado;
      case comprobantes_electronicos_estado_sri.anulado:
        return comprobantes_electronicos_estado_sunat.anulado;
      case comprobantes_electronicos_estado_sri.error:
        return comprobantes_electronicos_estado_sunat.error;
      case comprobantes_electronicos_estado_sri.recibida:
        return comprobantes_electronicos_estado_sunat.enviando;
      default:
        return comprobantes_electronicos_estado_sunat.pendiente;
    }
  }
}
