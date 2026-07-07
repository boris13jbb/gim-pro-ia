import { Module } from '@nestjs/common';
import { NotificationsModule } from '../notifications/notifications.module';
import { WsAuthModule } from './ws-auth.module';
import { RealtimeGateway } from './realtime.gateway';
import { RealtimeService } from './realtime.service';

/**
 * Módulo de tiempo real (notificaciones al socio).
 *
 * Exporta RealtimeService para que los módulos de dominio (asistencia,
 * membresías) emitan notificaciones sin acoplarse al gateway ni a socket.io.
 * Persiste cada notificación vía NotificationsModule (Fase 16).
 */
@Module({
  imports: [WsAuthModule, NotificationsModule],
  providers: [RealtimeGateway, RealtimeService],
  exports: [RealtimeService],
})
export class RealtimeModule {}
