import {
  BadRequestException,
  ForbiddenException,
  HttpException,
  HttpStatus,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { ai_conversation_status, ai_message_role, Prisma } from '@prisma/client';
import { PrismaService } from '../database/prisma.service';
import { AiToolsService } from './ai-tools.service';
import { AiModelService } from './ai-model.service';
import { SendAiChatDto } from './dto/send-ai-chat.dto';

@Injectable()
export class AiChatService {
  private readonly maxHistoryMessages = Number(
    process.env.AI_MAX_HISTORY_MESSAGES ?? 12,
  );
  private readonly dailyMessageLimit = Number(
    process.env.AI_DAILY_MESSAGE_LIMIT ?? 50,
  );

  constructor(
    private readonly prisma: PrismaService,
    private readonly aiTools: AiToolsService,
    private readonly aiModel: AiModelService,
  ) {}

  async listConversations(
    memberId: number,
    status: ai_conversation_status = ai_conversation_status.active,
  ) {
    const items = await this.prisma.ai_conversations.findMany({
      where: { member_id: memberId, status },
      orderBy: { actualizado_en: 'desc' },
      include: {
        ai_messages: {
          orderBy: { creado_en: 'desc' },
          take: 1,
        },
      },
    });

    return items.map((conv) => this.mapConversationSummary(conv));
  }

  async updateConversationStatus(
    memberId: number,
    conversationId: number,
    status: ai_conversation_status,
  ) {
    const conversation = await this.findOwnedConversation(
      memberId,
      conversationId,
    );

    const updated = await this.prisma.ai_conversations.update({
      where: { id: conversation.id },
      data: { status },
      include: {
        ai_messages: {
          orderBy: { creado_en: 'desc' },
          take: 1,
        },
      },
    });

    return this.mapConversationSummary(updated);
  }

  async deleteConversation(memberId: number, conversationId: number) {
    const conversation = await this.findOwnedConversation(
      memberId,
      conversationId,
    );

    await this.prisma.ai_conversations.delete({
      where: { id: conversation.id },
    });

    return { deleted: true, id: conversationId };
  }

  async getConversation(memberId: number, conversationId: number) {
    const conversation = await this.findOwnedConversation(
      memberId,
      conversationId,
    );

    const messages = await this.prisma.ai_messages.findMany({
      where: { conversation_id: conversation.id },
      orderBy: { creado_en: 'asc' },
    });

    return {
      id: conversation.id,
      title: conversation.titulo,
      status: conversation.status,
      createdAt: conversation.creado_en,
      updatedAt: conversation.actualizado_en,
      messages: messages.map((msg) => this.mapMessage(msg)),
    };
  }

  async sendMessage(memberId: number, dto: SendAiChatDto) {
    const turn = await this.prepareTurn(memberId, dto);

    try {
      const reply = await this.aiModel.generateReply(turn.modelInput);
      const assistantMessage = await this.persistAssistantReply(
        turn.conversation.id,
        reply,
      );

      return {
        conversationId: turn.conversation.id,
        reply: assistantMessage.content,
        message: this.mapMessage(assistantMessage),
      };
    } catch (error) {
      // Si Gemini falla, no dejar mensajes user huérfanos que rompen el historial.
      await this.prisma.ai_messages.delete({
        where: { id: turn.userMessage.id },
      });
      throw error;
    }
  }

  /**
   * Variante en streaming para WebSockets: entrega la respuesta por fragmentos
   * (onChunk) y persiste el mensaje final. Reutiliza exactamente la misma
   * preparación, límites diarios, validación de propiedad y persistencia que el
   * flujo REST, de modo que las reglas de negocio no se dupliquen ni difieran.
   */
  async streamMessage(
    memberId: number,
    dto: SendAiChatDto,
    onChunk: (delta: string) => void,
  ) {
    const turn = await this.prepareTurn(memberId, dto);

    try {
      const reply = await this.aiModel.generateReplyStream(
        turn.modelInput,
        onChunk,
      );
      const assistantMessage = await this.persistAssistantReply(
        turn.conversation.id,
        reply,
      );

      return {
        conversationId: turn.conversation.id,
        message: this.mapMessage(assistantMessage),
      };
    } catch (error) {
      await this.prisma.ai_messages.delete({
        where: { id: turn.userMessage.id },
      });
      throw error;
    }
  }

  /**
   * Prepara un turno de conversación: valida límite diario, resuelve/crea la
   * conversación (solo del socio), guarda el mensaje del usuario y arma el
   * contexto real + historial para el proveedor de IA activo (Gemini, Z.AI u Ollama).
   */
  private async prepareTurn(memberId: number, dto: SendAiChatDto) {
    await this.ensureDailyLimit(memberId);

    const conversation = dto.conversationId
      ? await this.findOwnedConversation(memberId, dto.conversationId, true)
      : await this.prisma.ai_conversations.create({
          data: {
            member_id: memberId,
            titulo: this.buildConversationTitle(dto.message),
          },
        });

    const priorMessages = await this.prisma.ai_messages.findMany({
      where: { conversation_id: conversation.id },
      orderBy: { creado_en: 'asc' },
      take: this.maxHistoryMessages,
    });

    const userMessage = await this.prisma.ai_messages.create({
      data: {
        conversation_id: conversation.id,
        role: ai_message_role.user,
        content: dto.message.trim(),
      },
    });

    const memberContext = await this.aiTools.buildMemberContext(memberId);
    const { history, message } = this.buildModelInput(
      priorMessages,
      dto.message.trim(),
    );

    return {
      conversation,
      userMessage,
      modelInput: { memberContext, history, message },
    };
  }

  /**
   * Persiste la respuesta del asistente y actualiza la marca de tiempo de la
   * conversación. Compartido por REST y streaming.
   */
  private async persistAssistantReply(conversationId: number, reply: string) {
    const assistantMessage = await this.prisma.ai_messages.create({
      data: {
        conversation_id: conversationId,
        role: ai_message_role.assistant,
        content: reply,
        metadata: {
          provider: this.aiModel.getProvider(),
          model: this.aiModel.getActiveModelName(),
          toolsUsed: [
            'getMemberProfile',
            'getMembershipStatus',
            'getAttendanceSummary',
            'getBodyProgress',
            'getCurrentWorkoutRoutine',
          ],
        },
      },
    });

    await this.prisma.ai_conversations.update({
      where: { id: conversationId },
      data: { actualizado_en: new Date() },
    });

    return assistantMessage;
  }

  private buildModelInput(
    priorMessages: Array<{ role: ai_message_role; content: string }>,
    currentMessage: string,
  ): {
    history: Array<{ role: 'user' | 'model'; text: string }>;
    message: string;
  } {
    const mapped = priorMessages
      .filter((msg) => msg.role !== ai_message_role.system)
      .map((msg) => ({
        role:
          msg.role === ai_message_role.user
            ? ('user' as const)
            : ('model' as const),
        text: msg.content,
      }));

    const trailingUserParts: string[] = [];
    while (mapped.length > 0 && mapped[mapped.length - 1].role === 'user') {
      trailingUserParts.unshift(mapped.pop()!.text);
    }

    const message = [...trailingUserParts, currentMessage].join('\n').trim();

    return { history: mapped, message };
  }

  private async findOwnedConversation(
    memberId: number,
    conversationId: number,
    requireActive = false,
  ) {
    const conversation = await this.prisma.ai_conversations.findUnique({
      where: { id: conversationId },
    });

    if (!conversation) {
      throw new NotFoundException('Conversación no encontrada');
    }

    if (conversation.member_id !== memberId) {
      throw new ForbiddenException(
        'No puedes acceder a conversaciones de otro socio',
      );
    }

    if (
      requireActive &&
      conversation.status === ai_conversation_status.archived
    ) {
      throw new BadRequestException(
        'Esta conversación está archivada. Restáurala antes de continuar.',
      );
    }

    return conversation;
  }

  private async ensureDailyLimit(memberId: number) {
    const startOfDay = new Date();
    startOfDay.setHours(0, 0, 0, 0);

    const count = await this.prisma.ai_messages.count({
      where: {
        role: ai_message_role.user,
        creado_en: { gte: startOfDay },
        ai_conversations: { member_id: memberId },
      },
    });

    if (count >= this.dailyMessageLimit) {
      throw new HttpException(
        `Límite diario de mensajes al asistente (${this.dailyMessageLimit}). Intenta mañana.`,
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }
  }

  private buildConversationTitle(message: string): string {
    const trimmed = message.trim().replace(/\s+/g, ' ');
    if (trimmed.length <= 60) return trimmed;
    return `${trimmed.slice(0, 57)}...`;
  }

  private mapConversationSummary(conv: {
    id: number;
    titulo: string | null;
    status: ai_conversation_status;
    creado_en: Date;
    actualizado_en: Date;
    ai_messages: Array<{
      id: number;
      role: ai_message_role;
      content: string;
      creado_en: Date;
    }>;
  }) {
    return {
      id: conv.id,
      title: conv.titulo,
      status: conv.status,
      createdAt: conv.creado_en,
      updatedAt: conv.actualizado_en,
      lastMessage: conv.ai_messages[0]
        ? {
            id: conv.ai_messages[0].id,
            role: conv.ai_messages[0].role,
            content: conv.ai_messages[0].content,
            createdAt: conv.ai_messages[0].creado_en,
          }
        : null,
    };
  }

  private mapMessage(message: {
    id: number;
    role: ai_message_role;
    content: string;
    metadata: Prisma.JsonValue | null;
    creado_en: Date;
  }) {
    return {
      id: message.id,
      role: message.role,
      content: message.content,
      metadata: message.metadata,
      createdAt: message.creado_en,
    };
  }
}
