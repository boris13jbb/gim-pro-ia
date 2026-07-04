import { Module } from '@nestjs/common';
import { AiAssistantModule } from '../ai-assistant/ai-assistant.module';
import { AiChatGateway } from './ai-chat.gateway';
import { WsAuthModule } from './ws-auth.module';

/**
 * Módulo WebSocket del asistente IA.
 * Reutiliza:
 * - WsAuthService (validación del socio en el handshake), vía WsAuthModule.
 * - AiChatService (streaming del chat IA), exportado por AiAssistantModule.
 */
@Module({
  imports: [WsAuthModule, AiAssistantModule],
  providers: [AiChatGateway],
})
export class WebsocketModule {}
