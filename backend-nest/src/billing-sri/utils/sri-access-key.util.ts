/** Clave de acceso SRI Ecuador (49 dígitos) — portado desde XmlBuilderSri.php */

export function calculateSriVerifierDigit(clave48: string): number {
  let pivot = 2;
  let sum = 0;
  for (let i = clave48.length - 1; i >= 0; i -= 1) {
    sum += Number(clave48[i]) * pivot;
    pivot += 1;
    if (pivot > 7) pivot = 2;
  }
  const remainder = sum % 11;
  let digit = 11 - remainder;
  if (digit === 11) digit = 0;
  else if (digit === 10) digit = 1;
  return digit;
}

export function generateSriAccessKey(params: {
  dateDmY: string;
  documentType: string;
  taxId: string;
  environment: string;
  establishment: string;
  emissionPoint: string;
  sequence: string;
  numericCode?: string;
}) {
  const date = params.dateDmY.replace(/[-/]/g, '').padStart(8, '0').slice(-8);
  const numericCode = (params.numericCode ?? params.sequence.slice(-8))
    .replace(/\D/g, '')
    .padStart(8, '0')
    .slice(-8);

  const clave48 =
    date +
    params.documentType.padStart(2, '0') +
    params.taxId.replace(/\D/g, '').padStart(13, '0').slice(-13) +
    (params.environment === '2' ? '2' : '1') +
    params.establishment.padStart(3, '0').slice(-3) +
    params.emissionPoint.padStart(3, '0').slice(-3) +
    params.sequence.padStart(9, '0').slice(-9) +
    numericCode +
    '1';

  return clave48 + String(calculateSriVerifierDigit(clave48));
}

export function formatDmY(date: Date) {
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}${month}${year}`;
}

export function formatSriDate(date: Date) {
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}/${month}/${year}`;
}
