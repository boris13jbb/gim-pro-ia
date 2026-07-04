import { Logger } from '@nestjs/common';
import {
  OnGatewayConnection,
  OnGatewayDisconnect,
  SubscribeMessage,
  WebSocketGateway,
} from '@nestjs/websockets';
import type { Socket } from 'socket.io';
import { AiChatService } from '../ai-assistant/ai-chat.service';
import { WsAuthService } from './ws-auth.service';
import {
  extractHandshakeToken,
  getSocketMemberId,
  setSocketMemberId,
} from './ws-token.util';

// CORS del socket. Solo es relevante para Flutter Web; la app móvil no envía
// cabecera Origin. Se controla con la misma variable CORS_ORIGINS que la API REST.
const wsCorsOrigins = (process.env.CORS_ORIGINS ?? '')
  .split(',')
  .map((s) => s.trim())
  .filter(Boolean);

/**
 * Gateway del asistente IA en tiempo real (namespace `/ai`).
 *
 * Flujo obligatorio: Flutter → (socket con JWT) → NestJS → Gemini (streaming) → Flutter.
 * La API key de Gemini vive solo en el servidor; el socio recibe únicamente texto.
 *
 * Seguridad:
 * - El socio se autentica en el handshake (WsAuthService). Si falla, se desconecta.
 * - Cada socket entra a la sala `member:{memberId}`: nunca recibe datos de otro socio.
 *
 * Eventos:
 * - Entrante: `ai.message` `{ message, conversationId? }`
 * - Salientes: `ai.response.chunk` `{ delta }`,
 *              `ai.response.done` `{ conversationId, message }`,
 *              `ai.error` `{ message }`
 */
@WebSocketGateway({
  namespace: '/ai',
  cors: { origin: wsCorsOrigins.length > 0 ? wsCorsOrigins : true },
})
export class AiChatGateway implements OnGatewayConnection, OnGatewayDisconnect {
  private readonly logger = new Logger(AiChatGateway.name);

  constructor(
    private readonly wsAuth: WsAuthService,
    private readonly aiChatService: AiChatService,
  ) {}

  async handleConnection(client: Socket): Promise<void> {
    try {
      const token = extractHandshakeToken(client);
      const memberId = await this.wsAuth.authenticateMember(token);
      // El memberId validado se guarda en el socket; la sala aísla a cada socio.
      setSocketMemberId(client, memberId);
      await client.join(this.roomFor(memberId));
    } catch (error) {
      const message = error instanceof Error ? error.message : 'No autorizado';
      client.emit('ai.error', { message });
      client.disconnect(true);
    }
  }

  handleDisconnect(client: Socket): void {
    // socket.io limpia automáticamente las salas al desconectar.
    void client;
  }

  @SubscribeMessage('ai.message')
  async onMessage(client: Socket, payload: unknown): Promise<void> {
    const memberId = getSocketMemberId(client);
    if (!memberId) {
      client.emit('ai.error', { message: 'Sesión no autenticada' });
      return;
    }

    const parsed = this.parsePayload(payload);
    if (!parsed) {
      client.emit('ai.error', {
        message: 'Mensaje inválido (requerido, máx. 4000 caracteres)',
      });
      return;
    }

    try {
      // Reutiliza toda la lógica REST (límite diario, propiedad, persistencia),
      // pero entrega la respuesta por fragmentos vía `ai.response.chunk`.
      const result = await this.aiChatService.streamMessage(
        memberId,
        parsed,
        (delta) => client.emit('ai.response.chunk', { delta }),
      );

      client.emit('ai.response.done', {
        conversationId: result.conversationId,
        message: result.message,
      });
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : 'El asistente no está disponible.';
      this.logger.warn(`ai.message error (member ${memberId}): ${message}`);
      client.emit('ai.error', { message });
    }
  }

  /**
   * Valida el payload entrante (WebSockets no aplica el ValidationPipe global).
   * Mantiene las mismas restricciones que `SendAiChatDto`.
   */
  private parsePayload(
    payload: unknown,
  ): { message: string; conversationId?: number } | null {
    if (!payload || typeof payload !== 'object') return null;

    const data = payload as Record<string, unknown>;
    const message = typeof data.message === 'string' ? data.message.trim() : '';
    if (message.length === 0 || message.length > 4000) return null;

    let conversationId: number | undefined;
    if (data.conversationId !== undefined && data.conversationId !== null) {
      const raw = data.conversationId;
      const num =
        typeof raw === 'number'
          ? raw
          : typeof raw === 'string'
            ? Number(raw)
            : NaN;
      if (!Number.isInteger(num) || num <= 0) return null;
      conversationId = num;
    }

    return { message, conversationId };
  }

  private roomFor(memberId: number): string {
    return `member:${memberId}`;
  }
}
