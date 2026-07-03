const DOCUMENT_TYPE_LABELS: Record<string, string> = {
  '01': 'FACTURA ELECTRÓNICA',
  '04': 'NOTA DE CRÉDITO ELECTRÓNICA',
  '05': 'NOTA DE DÉBITO ELECTRÓNICA',
  '06': 'GUÍA DE REMISIÓN ELECTRÓNICA',
  '07': 'COMPROBANTE DE RETENCIÓN ELECTRÓNICO',
};

const CUSTOMER_DOC_LABELS: Record<string, string> = {
  '04': 'RUC',
  '05': 'Cédula',
  '06': 'Pasaporte',
  '07': 'Consumidor final',
  '08': 'Identificación del exterior',
  '1': 'Cédula',
  '6': 'RUC',
};

export function getSriDocumentTypeLabel(type: string) {
  return DOCUMENT_TYPE_LABELS[type] ?? 'COMPROBANTE ELECTRÓNICO';
}

export function getSriCustomerDocLabel(type: string) {
  return CUSTOMER_DOC_LABELS[type] ?? 'Documento';
}
