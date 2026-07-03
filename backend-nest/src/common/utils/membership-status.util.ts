export type MembershipStoredStatus = 'activa' | 'vencida';
export type MembershipEffectiveStatus =
  'activa' | 'vencida' | 'cancelada' | 'suspendida' | 'sin_membresia';

export interface MembershipStatusInput {
  estado: MembershipStoredStatus | null | undefined;
  fechaInicio: Date | null | undefined;
  fechaFin: Date | null | undefined;
}

/**
 * El backend calcula el estado efectivo de membresía.
 * La BD legacy solo guarda activa/vencida; cancelar en PHP marca vencida.
 */
export function computeMembershipEffectiveStatus(
  input: MembershipStatusInput | null | undefined,
): MembershipEffectiveStatus {
  if (!input?.fechaInicio || !input.fechaFin) {
    return 'sin_membresia';
  }

  const today = startOfDay(new Date());
  const end = startOfDay(input.fechaFin);

  if (input.estado === 'vencida') {
    return 'vencida';
  }

  if (end < today) {
    return 'vencida';
  }

  return 'activa';
}

export function isMembershipValidForAccess(
  input: MembershipStatusInput | null | undefined,
): boolean {
  return computeMembershipEffectiveStatus(input) === 'activa';
}

function startOfDay(date: Date): Date {
  const d = new Date(date);
  d.setHours(0, 0, 0, 0);
  return d;
}

export function addDays(date: Date, days: number): Date {
  const result = new Date(date);
  result.setDate(result.getDate() + days);
  return startOfDay(result);
}
