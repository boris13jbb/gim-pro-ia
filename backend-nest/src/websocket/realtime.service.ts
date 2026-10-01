import { Injectable, Logger } from '@nestjs/common';
import { NotificationsService } from '../notifications/notifications.service';
import { RealtimeGateway } from './realtime.gateway';
import { RealtimeNotification } from './types/realtime-notification.type';

/**
 * API pública para enviar notificaciones en tiempo real al socio.
 *
 * Fase 16: cada notificación se persiste en BD antes de emitir por WebSocket,
 * de modo que el socio recupera el historial al abrir la app aunque no estuviera
 * conectado al momento del evento.
 */
@Injectable()
export class RealtimeService {
  private readonly logger = new Logger(RealtimeService.name);

  constructor(
    private readonly gateway: RealtimeGateway,
    private readonly notificationsService: NotificationsService,
  ) {}

  /**
   * Persiste + emite. Devuelve Promise para que jobs (p. ej. alertas de
   * membresía) puedan await y garantizar idempotencia antes del siguiente ciclo.
   * Callers que no necesiten esperar pueden ignorar el retorno (fire-and-forget).
   */
  notifyMember(
    memberId: number,
    notification: RealtimeNotification,
  ): Promise<void> {
    if (!memberId) return Promise.resolve();
    return this.deliver(memberId, notification);
  }

  private async deliver(
    memberId: number,
    notification: RealtimeNotification,
  ): Promise<void> {
    try {
      const saved = await this.notificationsService.createForMember(
        memberId,
        notification,
      );

      this.gateway.emitToMember(memberId, {
        id: saved.id,
        type: saved.type,
        title: saved.title,
        body: saved.body,
        data: saved.data ?? undefined,
        createdAt: saved.createdAt,
        isRead: false,
      });
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      this.logger.warn(`No se pudo notificar al socio ${memberId}: ${message}`);
    }
  }
}
