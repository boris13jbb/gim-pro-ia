import {
  Body,
  Controller,
  Delete,
  ForbiddenException,
  Get,
  HttpCode,
  HttpStatus,
  Param,
  ParseIntPipe,
  Patch,
  Post,
  Query,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import { ai_conversation_status } from '@prisma/client';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { AiChatService } from './ai-chat.service';
import { ListAiConversationsQueryDto } from './dto/list-ai-conversations-query.dto';
import { SendAiChatDto } from './dto/send-ai-chat.dto';
import { UpdateAiConversationStatusDto } from './dto/update-ai-conversation-status.dto';

const aiThrottleLimit = Number(process.env.AI_THROTTLE_LIMIT ?? 20);
const aiThrottleTtl = Number(process.env.AI_THROTTLE_TTL_MS ?? 60_000);

/**
 * Seguridad:
 * Solo el rol socio accede al asistente IA.
 * El memberId se toma del JWT; no se acepta ID ajeno en la URL.
 * Gemini se invoca únicamente en el servidor (Flutter → NestJS → Gemini).
 */
@ApiTags('ai')
@ApiBearerAuth()
@Controller('ai')
@Roles('socio')
export class AiChatController {
  constructor(private readonly aiChatService: AiChatService) {}

  @Get('conversations')
  @ApiOperation({
    summary: 'Socio: listar conversaciones activas o archivadas',
  })
  listConversations(
    @CurrentUser() user: JwtPayload,
    @Query() query: ListAiConversationsQueryDto,
  ) {
    const memberId = this.resolveMemberId(user);
    const status =
      query.status === 'archived'
        ? ai_conversation_status.archived
        : ai_conversation_status.active;
    return this.aiChatService.listConversations(memberId, status);
  }

  @Get('conversations/:id')
  @ApiOperation({ summary: 'Socio: detalle de una conversación con mensajes' })
  getConversation(
    @CurrentUser() user: JwtPayload,
    @Param('id', ParseIntPipe) id: number,
  ) {
    const memberId = this.resolveMemberId(user);
    return this.aiChatService.getConversation(memberId, id);
  }

  @Patch('conversations/:id/status')
  @ApiOperation({
    summary: 'Socio: archivar o restaurar una conversación',
  })
  updateConversationStatus(
    @CurrentUser() user: JwtPayload,
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: UpdateAiConversationStatusDto,
  ) {
    const memberId = this.resolveMemberId(user);
    const status =
      dto.status === 'archived'
        ? ai_conversation_status.archived
        : ai_conversation_status.active;
    return this.aiChatService.updateConversationStatus(memberId, id, status);
  }

  @Delete('conversations/:id')
  @HttpCode(HttpStatus.OK)
  @ApiOperation({ summary: 'Socio: eliminar una conversación y sus mensajes' })
  deleteConversation(
    @CurrentUser() user: JwtPayload,
    @Param('id', ParseIntPipe) id: number,
  ) {
    const memberId = this.resolveMemberId(user);
    return this.aiChatService.deleteConversation(memberId, id);
  }

  @Post('chat')
  @HttpCode(HttpStatus.OK)
  @Throttle({ default: { limit: aiThrottleLimit, ttl: aiThrottleTtl } })
  @ApiOperation({
    summary:
      'Socio: enviar mensaje al asistente IA (REST; streaming en slice WS)',
  })
  sendMessage(@CurrentUser() user: JwtPayload, @Body() dto: SendAiChatDto) {
    const memberId = this.resolveMemberId(user);
    return this.aiChatService.sendMessage(memberId, dto);
  }

  private resolveMemberId(user: JwtPayload): number {
    if (!user.memberId || user.memberId !== user.sub) {
      throw new ForbiddenException('Token de socio inválido');
    }
    return user.memberId;
  }
}
