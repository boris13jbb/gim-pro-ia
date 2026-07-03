import { Injectable } from '@nestjs/common';
import {
  SriCalculatedLine,
  SriReceiptHeaderCalculated,
  SriReceiptHeaderInput,
} from '../types/sri-billing.types';
import { formatDmY, generateSriAccessKey } from '../utils/sri-access-key.util';
import { amountToWords } from '../utils/sri-xml.util';

type CompanyConfig = {
  ruc: string;
  razonSocial: string;
  nombreComercial: string;
  direccion: string;
  ivaTasa: number;
  incluyeIva: boolean;
  sriAmbiente: string;
  sriEstablecimiento: string;
  sriPuntoEmision: string;
};

@Injectable()
export class SriTaxCalculatorService {
  calculate(
    input: SriReceiptHeaderInput,
    company: CompanyConfig,
    correlativo: number,
    series: string,
  ): { header: SriReceiptHeaderCalculated; lines: SriCalculatedLine[] } {
    const vatRate = company.ivaTasa;
    const includesVat = company.incluyeIva;
    const lines: SriCalculatedLine[] = [];
    let taxable = 0;
    let vat = 0;
    let totalLines = 0;
    let lineNo = 1;

    for (const item of input.items) {
      const quantity = Number(item.quantity);
      const price = Number(item.unitPrice);
      const unitValue = includesVat
        ? Math.round((price / (1 + vatRate / 100)) * 10000) / 10000
        : Math.round(price * 10000) / 10000;
      const unitPrice = includesVat
        ? Math.round(price * 10000) / 10000
        : Math.round(price * (1 + vatRate / 100) * 10000) / 10000;
      const subtotal = Math.round(quantity * unitValue * 100) / 100;
      const taxLine = Math.round(subtotal * (vatRate / 100) * 100) / 100;
      const totalLine = Math.round((subtotal + taxLine) * 100) / 100;

      lines.push({
        line: lineNo,
        code: item.code ?? null,
        description: item.description,
        unit: item.unit ?? 'NIU',
        quantity,
        unitValue,
        unitPrice,
        subtotal,
        taxLine,
        totalLine,
        taxType: item.taxType ?? '10',
      });
      lineNo += 1;
      taxable += subtotal;
      vat += taxLine;
      totalLines += totalLine;
    }

    taxable = Math.round(taxable * 100) / 100;
    vat = Math.round(vat * 100) / 100;
    const total = Math.round(totalLines * 100) / 100;
    const issueDate = input.issueDate ?? new Date();
    const estab = company.sriEstablecimiento.padStart(3, '0');
    const pto = company.sriPuntoEmision.padStart(3, '0');
    const sequence = String(correlativo).padStart(9, '0');
    const accessKey = generateSriAccessKey({
      dateDmY: formatDmY(issueDate),
      documentType: input.documentType,
      taxId: company.ruc,
      environment: company.sriAmbiente,
      establishment: estab,
      emissionPoint: pto,
      sequence,
    });

    const refEst = input.referenceEstablishment?.padStart(3, '0') ?? null;
    const refPto = input.referenceEmissionPoint?.padStart(3, '0') ?? null;

    return {
      header: {
        originType: input.originType,
        originId: input.originId ?? null,
        documentType: input.documentType,
        series,
        correlativo,
        claveAcceso: accessKey,
        fechaEmision: issueDate,
        emisorRuc: company.ruc,
        emisorRazon: company.razonSocial,
        clienteTipoDoc: input.customerDocumentType ?? '1',
        clienteNumDoc: input.customerTaxId ?? '9999999999999',
        clienteRazon: input.customerName ?? 'CONSUMIDOR FINAL',
        clienteDireccion: input.customerAddress ?? null,
        clienteEmail: input.customerEmail ?? null,
        moneda: '$',
        gravadas: taxable,
        inafectas: 0,
        exoneradas: 0,
        gratuitas: 0,
        descuentos: input.discounts ?? 0,
        igv: vat,
        total,
        totalLetras: amountToWords(total),
        formaPago: input.paymentForm === 'Credito' ? 'Credito' : 'Contado',
        metodoPago: input.paymentMethod ?? 'efectivo',
        refTipoDoc: input.referenceDocumentType ?? null,
        refSerie: refEst && refPto ? `${refEst}${refPto}` : null,
        refCorrelativo: input.referenceSequence ?? null,
        motivoCodigo: input.creditNoteReasonCode ?? null,
        motivoDescripcion: input.creditNoteReason ?? null,
        usuarioId: input.userId ?? null,
        ivaTasa: vatRate,
        sriAmbiente: company.sriAmbiente,
        sriEstablecimiento: estab,
        sriPuntoEmision: pto,
        emisorNombreComercial: company.nombreComercial,
        emisorDireccion: company.direccion,
        refFechaEmision: input.referenceIssueDate ?? null,
      },
      lines,
    };
  }
}
