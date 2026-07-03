import { Injectable } from '@nestjs/common';
import {
  SriCalculatedLine,
  SriReceiptHeaderCalculated,
} from '../types/sri-billing.types';
import {
  escapeSriXml,
  formatMoneySri,
  resolveBuyerIdType,
  vatPercentageCode,
} from '../utils/sri-xml.util';
import { formatSriDate } from '../utils/sri-access-key.util';

@Injectable()
export class SriXmlBuilderService {
  buildInvoice(header: SriReceiptHeaderCalculated, lines: SriCalculatedLine[]) {
    const xml = this.buildInvoiceXml(header, lines);
    return { xml, accessKey: header.claveAcceso };
  }

  buildCreditNote(header: SriReceiptHeaderCalculated, lines: SriCalculatedLine[]) {
    const xml = this.buildCreditNoteXml(header, lines);
    return { xml, accessKey: header.claveAcceso };
  }

  private buildInvoiceXml(
    cab: SriReceiptHeaderCalculated,
    items: SriCalculatedLine[],
  ) {
    const buyer = resolveBuyerIdType(cab.clienteNumDoc);
    const vatCode = vatPercentageCode(cab.ivaTasa);
    const paymentCode = cab.metodoPago === 'efectivo' ? '01' : '20';

    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<factura id="comprobante" version="1.1.0">\n';
    xml += this.tributaryInfo(cab, '01');
    xml += '  <infoFactura>\n';
    xml += `    <fechaEmision>${formatSriDate(cab.fechaEmision)}</fechaEmision>\n`;
    xml += `    <dirEstablecimiento>${escapeSriXml(cab.emisorDireccion)}</dirEstablecimiento>\n`;
    xml += '    <obligadoContabilidad>NO</obligadoContabilidad>\n';
    xml += `    <tipoIdentificacionComprador>${buyer.type}</tipoIdentificacionComprador>\n`;
    xml += `    <razonSocialComprador>${escapeSriXml(cab.clienteRazon)}</razonSocialComprador>\n`;
    xml += `    <identificacionComprador>${buyer.doc}</identificacionComprador>\n`;
    xml += `    <totalSinImpuestos>${formatMoneySri(cab.gravadas)}</totalSinImpuestos>\n`;
    xml += `    <totalDescuento>${formatMoneySri(cab.descuentos)}</totalDescuento>\n`;
    xml += this.taxTotals(cab, vatCode);
    xml += '    <propina>0.00</propina>\n';
    xml += `    <importeTotal>${formatMoneySri(cab.total)}</importeTotal>\n`;
    xml += '    <moneda>DOLAR</moneda>\n';
    xml += '    <pagos>\n      <pago>\n';
    xml += `        <formaPago>${paymentCode}</formaPago>\n`;
    xml += `        <total>${formatMoneySri(cab.total)}</total>\n`;
    xml += '      </pago>\n    </pagos>\n  </infoFactura>\n';
    xml += this.detailLines(items, cab.ivaTasa, vatCode, 'codigoPrincipal');
    xml += '</factura>';
    return xml;
  }

  private buildCreditNoteXml(
    cab: SriReceiptHeaderCalculated,
    items: SriCalculatedLine[],
  ) {
    const buyer = resolveBuyerIdType(cab.clienteNumDoc);
    const vatCode = vatPercentageCode(cab.ivaTasa);
    const refEst = cab.refSerie?.slice(0, 3) ?? '001';
    const refPto = cab.refSerie?.slice(3, 6) ?? '001';
    const refSeq = String(cab.refCorrelativo ?? 0).padStart(9, '0');
    const modifiedDoc = `${refEst}-${refPto}-${refSeq}`;
    const refDate = cab.refFechaEmision
      ? formatSriDate(cab.refFechaEmision)
      : formatSriDate(cab.fechaEmision);

    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<notaCredito id="comprobante" version="1.0.0">\n';
    xml += this.tributaryInfo(cab, '04');
    xml += '  <infoNotaCredito>\n';
    xml += `    <fechaEmision>${formatSriDate(cab.fechaEmision)}</fechaEmision>\n`;
    xml += `    <dirEstablecimiento>${escapeSriXml(cab.emisorDireccion)}</dirEstablecimiento>\n`;
    xml += `    <tipoIdentificacionComprador>${buyer.type}</tipoIdentificacionComprador>\n`;
    xml += `    <razonSocialComprador>${escapeSriXml(cab.clienteRazon)}</razonSocialComprador>\n`;
    xml += `    <identificacionComprador>${buyer.doc}</identificacionComprador>\n`;
    xml += '    <obligadoContabilidad>NO</obligadoContabilidad>\n';
    xml += '    <codDocModificado>01</codDocModificado>\n';
    xml += `    <numDocModificado>${modifiedDoc}</numDocModificado>\n`;
    xml += `    <fechaEmisionDocSustento>${refDate}</fechaEmisionDocSustento>\n`;
    xml += `    <totalSinImpuestos>${formatMoneySri(cab.gravadas)}</totalSinImpuestos>\n`;
    xml += `    <valorModificacion>${formatMoneySri(cab.total)}</valorModificacion>\n`;
    xml += '    <moneda>DOLAR</moneda>\n';
    xml += this.taxTotals(cab, vatCode);
    xml += `    <motivo>${escapeSriXml(cab.motivoDescripcion ?? 'Anulación de la operación')}</motivo>\n`;
    xml += '  </infoNotaCredito>\n';
    xml += this.detailLines(items, cab.ivaTasa, vatCode, 'codigoInterno');
    xml += '</notaCredito>';
    return xml;
  }

  private tributaryInfo(cab: SriReceiptHeaderCalculated, codDoc: string) {
    const ruc = cab.emisorRuc.replace(/\D/g, '');
    const seq = String(cab.correlativo).padStart(9, '0');
    let xml = '  <infoTributaria>\n';
    xml += `    <ambiente>${cab.sriAmbiente}</ambiente>\n`;
    xml += '    <tipoEmision>1</tipoEmision>\n';
    xml += `    <razonSocial>${escapeSriXml(cab.emisorRazon)}</razonSocial>\n`;
    xml += `    <nombreComercial>${escapeSriXml(cab.emisorNombreComercial)}</nombreComercial>\n`;
    xml += `    <ruc>${ruc}</ruc>\n`;
    xml += `    <claveAcceso>${cab.claveAcceso}</claveAcceso>\n`;
    xml += `    <codDoc>${codDoc}</codDoc>\n`;
    xml += `    <estab>${cab.sriEstablecimiento}</estab>\n`;
    xml += `    <ptoEmi>${cab.sriPuntoEmision}</ptoEmi>\n`;
    xml += `    <secuencial>${seq}</secuencial>\n`;
    xml += `    <dirMatriz>${escapeSriXml(cab.emisorDireccion)}</dirMatriz>\n`;
    xml += '  </infoTributaria>\n';
    return xml;
  }

  private taxTotals(cab: SriReceiptHeaderCalculated, vatCode: string) {
    let xml = '    <totalConImpuestos>\n      <totalImpuesto>\n';
    xml += '        <codigo>2</codigo>\n';
    xml += `        <codigoPorcentaje>${vatCode}</codigoPorcentaje>\n`;
    xml += `        <baseImponible>${formatMoneySri(cab.gravadas)}</baseImponible>\n`;
    xml += `        <tarifa>${formatMoneySri(cab.ivaTasa)}</tarifa>\n`;
    xml += `        <valor>${formatMoneySri(cab.igv)}</valor>\n`;
    xml += '      </totalImpuesto>\n    </totalConImpuestos>\n';
    return xml;
  }

  private detailLines(
    items: SriCalculatedLine[],
    vatRate: number,
    vatCode: string,
    codeTag: 'codigoPrincipal' | 'codigoInterno',
  ) {
    let xml = '  <detalles>\n';
    for (const item of items) {
      xml += '    <detalle>\n';
      xml += `      <${codeTag}>${escapeSriXml(item.code ?? 'S/C')}</${codeTag}>\n`;
      xml += `      <descripcion>${escapeSriXml(item.description)}</descripcion>\n`;
      xml += `      <cantidad>${formatMoneySri(item.quantity)}</cantidad>\n`;
      xml += `      <precioUnitario>${formatMoneySri(item.unitValue, 4)}</precioUnitario>\n`;
      xml += '      <descuento>0.00</descuento>\n';
      xml += `      <precioTotalSinImpuesto>${formatMoneySri(item.subtotal)}</precioTotalSinImpuesto>\n`;
      xml += '      <impuestos>\n        <impuesto>\n';
      xml += '          <codigo>2</codigo>\n';
      xml += `          <codigoPorcentaje>${vatCode}</codigoPorcentaje>\n`;
      xml += `          <tarifa>${formatMoneySri(vatRate)}</tarifa>\n`;
      xml += `          <baseImponible>${formatMoneySri(item.subtotal)}</baseImponible>\n`;
      xml += `          <valor>${formatMoneySri(item.taxLine)}</valor>\n`;
      xml += '        </impuesto>\n      </impuestos>\n';
      xml += '    </detalle>\n';
    }
    xml += '  </detalles>\n';
    return xml;
  }
}
