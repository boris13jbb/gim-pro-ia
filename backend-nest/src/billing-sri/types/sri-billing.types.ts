import { comprobantes_electronicos_origen_tipo } from '@prisma/client';

export type SriIssueItemInput = {
  code?: string;
  description: string;
  unit?: string;
  quantity: number;
  unitPrice: number;
  taxType?: string;
};

export type SriCalculatedLine = {
  line: number;
  code: string | null;
  description: string;
  unit: string;
  quantity: number;
  unitValue: number;
  unitPrice: number;
  subtotal: number;
  taxLine: number;
  totalLine: number;
  taxType: string;
};

export type SriReceiptHeaderInput = {
  originType: comprobantes_electronicos_origen_tipo;
  originId?: number | null;
  documentType: string;
  series?: string | null;
  issueDate?: Date;
  customerDocumentType?: string;
  customerTaxId?: string;
  customerName?: string;
  customerAddress?: string | null;
  customerEmail?: string | null;
  paymentForm?: string;
  paymentMethod?: string;
  discounts?: number;
  referenceDocumentType?: string | null;
  referenceEstablishment?: string | null;
  referenceEmissionPoint?: string | null;
  referenceSequence?: number | null;
  referenceIssueDate?: Date | null;
  creditNoteReasonCode?: string | null;
  creditNoteReason?: string | null;
  userId?: number | null;
  items: SriIssueItemInput[];
};

export type SriReceiptHeaderCalculated = {
  originType: comprobantes_electronicos_origen_tipo;
  originId: number | null;
  documentType: string;
  series: string;
  correlativo: number;
  claveAcceso: string;
  fechaEmision: Date;
  emisorRuc: string;
  emisorRazon: string;
  clienteTipoDoc: string;
  clienteNumDoc: string;
  clienteRazon: string;
  clienteDireccion: string | null;
  clienteEmail: string | null;
  moneda: string;
  gravadas: number;
  inafectas: number;
  exoneradas: number;
  gratuitas: number;
  descuentos: number;
  igv: number;
  total: number;
  totalLetras: string;
  formaPago: 'Contado' | 'Credito';
  metodoPago: string;
  refTipoDoc: string | null;
  refSerie: string | null;
  refCorrelativo: number | null;
  motivoCodigo: string | null;
  motivoDescripcion: string | null;
  usuarioId: number | null;
  ivaTasa: number;
  sriAmbiente: string;
  sriEstablecimiento: string;
  sriPuntoEmision: string;
  emisorNombreComercial: string;
  emisorDireccion: string;
  refFechaEmision?: Date | null;
};

export type SriEmitResult = {
  ok: boolean;
  receiptId: number;
  code: string;
  description: string;
  simulated?: boolean;
};
