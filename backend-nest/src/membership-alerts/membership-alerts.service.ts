import { Injectable, Logger } from '@nestjs/common';
import { socios_estado, suscripciones_estado } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import {
  fromPrismaDate,
  localCalendarAsUtcDate,
  startOfDay,
  todayDateString,
} from '../common/utils/date.util';
import { NotificationsService } from '../notifications/notifications.service';
import { RealtimeService } from '../websocket/realtime.service';
import { getMembershipAlertsConfig } from './membership-alerts.config';

export type MembershipAlertsRunResult = {
  sent: number;
  skipped: number;
  expired: number;
};

/**
 * Regla de negocio:
 * Avisar al socio cuando su membresía activa está por vencer (7/3/1/0 días).
 * Cada combinación membresía + umbral de días se notifica como máximo una vez al día.
 */
@Injectable()
export class MembershipAlertsService {
  private readonly logger = new Logger(MembershipAlertsService.name);

  constructor(
    private readonly prisma: PrismaService,
    private readonly realtime: RealtimeService,
    private readonly notifications: NotificationsService,
  ) {}

  async runDailyAlerts(): Promise<MembershipAlertsRunResult> {
    const config = getMembershipAlertsConfig();
    if (!config.enabled) {
      this.logger.debug(
        'Alertas de membresía deshabilitadas (MEMBERSHIP_ALERTS_ENABLED=false)',
      );
      return { sent: 0, skipped: 0, expired: 0 };
    }

    const expired = await this.expireOverdueMemberships();
    const alertResult = await this.sendExpiringAlerts(config.alertDays);

    this.logger.log(
      `Alertas membresía: enviadas=${alertResult.sent}, omitidas=${alertResult.skipped}, vencidas=${expired}`,
    );

    return { ...alertResult, expired };
  }

  private async expireOverdueMemberships(): Promise<number> {
    // Regla de negocio / zona horaria:
    // `fecha_fin` es DATE. Comparar con medianoche UTC del día local evita
    // marcar como vencida una membresía que aún es válida “hoy” en Ecuador.
    const todayUtc = localCalendarAsUtcDate();
    const overdue = await this.prisma.suscripciones.findMany({
      where: {
        estado: suscripciones_estado.activa,
        fecha_fin: { lt: todayUtc },
        socio_id: { not: null },
      },
      include: {
        planes: { select: { nombre: true } },
      },
    });

    let expired = 0;
    for (const membership of overdue) {
      if (!membership.socio_id) continue;

      await this.prisma.suscripciones.update({
        where: { id: membership.id },
        data: { estado: suscripciones_estado.vencida },
      });
      expired += 1;

      const alertKey = `expired-${membership.id}-${todayDateString()}`;
      const alreadySent = await this.notifications.hasAlertKeyToday(
        membership.socio_id,
        alertKey,
      );
      if (alreadySent) continue;

      // Await: la idempotencia del job depende de que `alertKey` ya esté en BD.
      await this.realtime.notifyMember(membership.socio_id, {
        type: 'membership.updated',
        title: 'Membresía vencida',
        body: membership.planes?.nombre
          ? `Tu membresía del plan "${membership.planes.nombre}" ha vencido. Renueva en recepción.`
          : 'Tu membresía ha vencido. Acércate a recepción para renovar.',
        data: {
          membershipId: membership.id,
          status: suscripciones_estado.vencida,
          alertKey,
        },
      });
    }

    return expired;
  }

  private async sendExpiringAlerts(alertDays: number[]) {
    if (alertDays.length === 0) {
      return { sent: 0, skipped: 0 };
    }

    const todayLocal = startOfDay(new Date());
    const todayUtc = localCalendarAsUtcDate();
    const maxDays = Math.max(...alertDays);
    const latestEndUtc = localCalendarAsUtcDate(
      new Date(
        todayLocal.getFullYear(),
        todayLocal.getMonth(),
        todayLocal.getDate() + maxDays,
      ),
    );

    const memberships = await this.prisma.suscripciones.findMany({
      where: {
        estado: suscripciones_estado.activa,
        socio_id: { not: null },
        fecha_fin: { gte: todayUtc, lte: latestEndUtc },
      },
      include: {
        planes: { select: { nombre: true } },
        socios: { select: { estado: true } },
      },
    });

    let sent = 0;
    let skipped = 0;

    for (const membership of memberships) {
      if (!membership.socio_id || !membership.fecha_fin) continue;
      if (membership.socios?.estado === socios_estado.inactivo) {
        skipped += 1;
        continue;
      }

      const daysRemaining = this.daysUntil(membership.fecha_fin, todayLocal);
      if (!alertDays.includes(daysRemaining)) {
        continue;
      }

      const alertKey = `expiring-${membership.id}-${daysRemaining}-${todayDateString()}`;
      const alreadySent = await this.notifications.hasAlertKeyToday(
        membership.socio_id,
        alertKey,
      );
      if (alreadySent) {
        skipped += 1;
        continue;
      }

      const planName = membership.planes?.nombre ?? 'tu plan';
      const body = this.buildExpiringBody(planName, daysRemaining);

      // Await: evita carrera en ejecuciones repetidas del mismo día.
      await this.realtime.notifyMember(membership.socio_id, {
        type: 'membership.expiring',
        title: 'Membresía por vencer',
        body,
        data: {
          membershipId: membership.id,
          daysRemaining,
          endDate: membership.fecha_fin,
          alertKey,
        },
      });

      sent += 1;
    }

    return { sent, skipped };
  }

  /**
   * Días restantes entre hoy (calendario local) y `fecha_fin` (DATE de Prisma).
   * Usa `fromPrismaDate` para no desplazar el umbral 7/3/1/0 por UTC-5.
   */
  private daysUntil(endDate: Date, today: Date): number {
    const end = fromPrismaDate(endDate);
    const diffMs = end.getTime() - today.getTime();
    return Math.round(diffMs / (1000 * 60 * 60 * 24));
  }

  private buildExpiringBody(planName: string, daysRemaining: number): string {
    if (daysRemaining === 0) {
      return `Tu membresía del plan "${planName}" vence hoy. Renueva en recepción para seguir entrenando.`;
    }
    if (daysRemaining === 1) {
      return `Tu membresía del plan "${planName}" vence mañana. Renueva a tiempo en recepción.`;
    }
    return `Tu membresía del plan "${planName}" vence en ${daysRemaining} días. Acércate a recepción para renovar.`;
  }
}
