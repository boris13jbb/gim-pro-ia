import { Injectable, Logger } from '@nestjs/common';
import { Cron } from '@nestjs/schedule';
import { MembershipAlertsService } from './membership-alerts.service';

/**
 * Job diario de alertas de membresía.
 * Horario por defecto: 08:00 (servidor). Configurable con MEMBERSHIP_ALERTS_CRON.
 */
@Injectable()
export class MembershipAlertsScheduler {
  private readonly logger = new Logger(MembershipAlertsScheduler.name);

  constructor(private readonly alertsService: MembershipAlertsService) {}

  @Cron(process.env.MEMBERSHIP_ALERTS_CRON?.trim() || '0 8 * * *')
  async handleMembershipAlerts(): Promise<void> {
    if (process.env.MEMBERSHIP_ALERTS_ENABLED === 'false') {
      return;
    }

    try {
      await this.alertsService.runDailyAlerts();
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.error(`Error en job de alertas de membresía: ${message}`);
    }
  }
}
