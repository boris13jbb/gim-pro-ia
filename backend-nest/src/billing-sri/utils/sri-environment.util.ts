import { configuracion_sri_ambiente } from '@prisma/client';

type SriEnvironmentInput =
  configuracion_sri_ambiente | '1' | '2' | null | undefined;

/** Mapea enum Prisma (`pruebas`/`produccion`) o código SRI (`1`/`2`) al código SRI. */
export function normalizeSriEnvironment(
  value?: SriEnvironmentInput,
): '1' | '2' {
  if (value === configuracion_sri_ambiente.produccion || value === '2') {
    return '2';
  }
  return '1';
}

export function isSriTestEnvironment(value?: SriEnvironmentInput) {
  return normalizeSriEnvironment(value) === '1';
}
