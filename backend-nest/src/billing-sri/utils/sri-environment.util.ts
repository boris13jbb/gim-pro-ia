import { configuracion_sri_ambiente } from '@prisma/client';

/** Mapea enum Prisma (`pruebas`/`produccion`) al código SRI (`1`/`2`). */
export function normalizeSriEnvironment(
  value?: configuracion_sri_ambiente | string | null,
): '1' | '2' {
  if (value === configuracion_sri_ambiente.produccion || value === '2' || value === 'produccion') {
    return '2';
  }
  return '1';
}

export function isSriTestEnvironment(
  value?: configuracion_sri_ambiente | string | null,
) {
  return normalizeSriEnvironment(value) === '1';
}
