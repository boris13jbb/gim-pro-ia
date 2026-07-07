/**
 * Configuración de alertas proactivas de membresía por vencer (Fase 17).
 * Valores por defecto seguros para desarrollo; en producción ajustar CRON y días.
 */
export type MembershipAlertsConfig = {
  enabled: boolean;
  cronExpression: string;
  alertDays: number[];
};

function parseAlertDays(raw: string | undefined): number[] {
  if (!raw?.trim()) {
    return [7, 3, 1, 0];
  }

  const days = raw
    .split(',')
    .map((part) => Number(part.trim()))
    .filter((value) => Number.isInteger(value) && value >= 0);

  return days.length > 0
    ? [...new Set(days)].sort((a, b) => b - a)
    : [7, 3, 1, 0];
}

export function getMembershipAlertsConfig(): MembershipAlertsConfig {
  const enabled = process.env.MEMBERSHIP_ALERTS_ENABLED !== 'false';
  const cronExpression =
    process.env.MEMBERSHIP_ALERTS_CRON?.trim() || '0 8 * * *';

  return {
    enabled,
    cronExpression,
    alertDays: parseAlertDays(process.env.MEMBERSHIP_ALERT_DAYS),
  };
}
