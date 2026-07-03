export function escapeSriXml(value: string) {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

export function formatMoneySri(value: number, decimals = 2) {
  return value.toFixed(decimals);
}

export function resolveBuyerIdType(documentNumber: string) {
  const doc = documentNumber.replace(/[^0-9a-zA-Z]/g, '');
  if (doc === '9999999999999') return { doc, type: '07' };
  if (doc.length === 10) return { doc, type: '05' };
  if (doc.length === 13) return { doc, type: '04' };
  return { doc, type: '06' };
}

export function vatPercentageCode(vatRate: number) {
  return vatRate >= 14 ? '4' : '2';
}

export function amountToWords(amount: number) {
  return `SON ${formatMoneySri(amount)} DOLARES`;
}
