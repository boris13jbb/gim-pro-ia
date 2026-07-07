import { Module } from '@nestjs/common';
import { NotificationsModule } from '../notifications/notifications.module';
import { RealtimeModule } from '../websocket/realtime.module';
import { MembershipAlertsScheduler } from './membership-alerts.scheduler';
import { MembershipAlertsService } from './membership-alerts.service';
import { MembershipAlertsController } from './membership-alerts.controller';

/**
 * Alertas proactivas de membresía por vencer (Fase 17).
 * Usa RealtimeService para persistir + WebSocket al socio.
 */
@Module({
  imports: [RealtimeModule, NotificationsModule],
  controllers: [MembershipAlertsController],
  providers: [MembershipAlertsService, MembershipAlertsScheduler],
  exports: [MembershipAlertsService],
})
export class MembershipAlertsModule {}
