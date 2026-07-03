import type {
  comprobantes_detalle,
  comprobantes_electronicos,
  sri_log,
} from '@prisma/client';

type ReceiptWithLines = comprobantes_electronicos & {
  comprobantes_detalle: comprobantes_detalle[];
};

export function mapElectronicReceiptSummary(row: comprobantes_electronicos) {
  return {
    id: row.id,
    originType: row.origen_tipo,
    originId: row.origen_id,
    documentType: row.tipo_doc,
    series: row.serie,
    sequence: row.correlativo,
    accessKey: row.clave_acceso,
    issueDate: row.fecha_emision,
    issuerTaxId: row.emisor_ruc,
    issuerName: row.emisor_razon,
    customerTaxId: row.cliente_num_doc,
    customerName: row.cliente_razon,
    total: Number(row.total),
    taxAmount: Number(row.igv ?? 0),
    status: row.estado_sri,
    legacyStatus: row.estado_sunat,
    errorMessage: row.mensaje_error,
    createdAt: row.creado_en,
  };
}

export function mapElectronicReceiptDetail(row: ReceiptWithLines) {
  return {
    ...mapElectronicReceiptSummary(row),
    customerDocumentType: row.cliente_tipo_doc,
    customerAddress: row.cliente_direccion,
    customerEmail: row.cliente_email,
    currency: row.moneda,
    taxableBase: Number(row.gravadas ?? 0),
    exemptAmount: Number(row.exoneradas ?? 0),
    discountAmount: Number(row.descuentos ?? 0),
    totalInWords: row.total_letras,
    paymentForm: row.forma_pago,
    paymentMethod: row.metodo_pago,
    referenceDocumentType: row.ref_tipo_doc,
    referenceSeries: row.ref_serie,
    referenceSequence: row.ref_correlativo,
    creditNoteReasonCode: row.motivo_codigo,
    creditNoteReason: row.motivo_descripcion,
    authorizationCode: row.cdr_codigo,
    authorizationMessage: row.cdr_descripcion,
    hasSignedXml: !!row.xml_firmado,
    hasAuthorizedXml: !!row.sri_authorization_xml,
    items: row.comprobantes_detalle.map(mapReceiptLine),
  };
}

export function mapReceiptLine(row: comprobantes_detalle) {
  return {
    line: row.linea,
    code: row.codigo,
    description: row.descripcion,
    unit: row.unidad,
    quantity: Number(row.cantidad),
    unitValue: Number(row.valor_unitario),
    unitPrice: Number(row.precio_unitario),
    subtotal: Number(row.subtotal),
    taxLine: Number(row.igv_linea),
    totalLine: Number(row.total_linea),
    taxType: row.tipo_afectacion,
  };
}

export function mapSriLog(row: sri_log) {
  return {
    id: row.id,
    receiptId: row.comprobante_id,
    action: row.accion,
    code: row.codigo,
    message: row.mensaje,
    createdAt: row.creado_en,
  };
}
