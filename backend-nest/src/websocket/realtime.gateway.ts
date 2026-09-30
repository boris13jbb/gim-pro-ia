import { Logger } from '@nestjs/common';
import {
  OnGatewayConnection,
  WebSocketGateway,
  WebSocketServer,
} from '@nestjs/websockets';
import type { Namespace, Socket } from 'socket.io';
import { WsAuthService } from './ws-auth.service';
import { extractHandshakeToken, setSocketMemberId } from './ws-token.util';

import { getWebSocketCorsConfig } from '../config/cors.config';

/**
 * Gateway de notificaciones en tiempo real para el socio (namespace `/events`).
 *
 * Seguridad (idéntica al gateway del chat IA):
 * - El socio se autentica en el handshake con su access token JWT (WsAuthService).
 *   Un token ausente, vencido, de staff o de un socio inactivo hace que la
 *   conexión se rechace (equivalente al 401 de la API REST).
 * - Cada socket entra a la sala `member:{memberId}`. Las notificaciones se emiten
 *   solo a esa sala, por lo que un socio NUNCA recibe eventos de otro socio.
 *
 * Este gateway solo acepta conexiones; los eventos los publica RealtimeService a
 * partir de acciones de dominio (asistencia registrada, cambios de membresía).
 */
@WebSocketGateway({
  namespace: '/events',
  cors: getWebSocketCorsConfig(),
})
export class RealtimeGateway implements OnGatewayConnection {
  private readonly logger = new Logger(RealtimeGateway.name);

  // Con la opción `namespace`, @WebSocketServer() inyecta el Namespace `/events`
  // (no el Server raíz). Así `server.to(room).emit()` solo alcanza a clientes de
  // ESTE namespace y sala. Confirmado en la documentación de NestJS.
  @WebSocketServer()
  private readonly server?: Namespace;

  constructor(private readonly wsAuth: WsAuthService) {}

  async handleConnection(client: Socket): Promise<void> {
    try {
      const token = extractHandshakeToken(client);
      const memberId = await this.wsAuth.authenticateMember(token);
      // El memberId validado proviene del token firmado, nunca del cliente.
      setSocketMemberId(client, memberId);
      await client.join(this.roomFor(memberId));
    } catch (error) {
      const message = error instanceof Error ? error.message : 'No autorizado';
      client.emit('notification.error', { message });
      client.disconnect(true);
    }
  }

  /**
   * Emite una notificación únicamente a la sala del socio indicado.
   * Si el socio no tiene sockets conectados, socket.io simplemente no entrega
   * el evento (no es error). Se omite en silencio si el namespace aún no inició.
   */
  emitToMember(memberId: number, payload: unknown): void {
    if (!this.server) {
      this.logger.warn(
        'Namespace /events no inicializado; notificación omitida',
      );
      return;
    }
    this.server.to(this.roomFor(memberId)).emit('notification', payload);
  }

  private roomFor(memberId: number): string {
    return `member:${memberId}`;
  }
}
