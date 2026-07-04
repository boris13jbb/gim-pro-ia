import { Injectable, Logger } from '@nestjs/common';
import { RealtimeGateway } from './realtime.gateway';
import { RealtimeNotification } from './types/realtime-notification.type';

/**
 * API pública para enviar notificaciones en tiempo real al socio.
 *
 * Los servicios de dominio (asistencia, membresías) dependen de este servicio,
 * no del gateway, para mantener el desacople (no conocen socket.io).
 *
 * Regla de negocio: notificar NUNCA debe romper el flujo que lo origina. Si el
 * envío falla, se registra y se ignora, porque la asistencia/membresía ya quedó
 * persistida antes de intentar la notificación.
 */
@Injectable()
export class RealtimeService {
  private readonly logger = new Logger(RealtimeService.name);

  constructor(private readonly gateway: RealtimeGateway) {}

  notifyMember(memberId: number, notification: RealtimeNotification): void {
    if (!memberId) return;
    try {
      this.gateway.emitToMember(memberId, {
        ...notification,
        createdAt: new Date().toISOString(),
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.warn(`No se pudo notificar al socio ${memberId}: ${message}`);
    }
  }
}
