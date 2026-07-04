import {
  Body,
  Controller,
  ForbiddenException,
  Get,
  HttpCode,
  HttpStatus,
  Param,
  ParseIntPipe,
  Post,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { AiChatService } from './ai-chat.service';
import { SendAiChatDto } from './dto/send-ai-chat.dto';

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
  @ApiOperation({ summary: 'Socio: listar conversaciones con el asistente IA' })
  listConversations(@CurrentUser() user: JwtPayload) {
    const memberId = this.resolveMemberId(user);
    return this.aiChatService.listConversations(memberId);
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
