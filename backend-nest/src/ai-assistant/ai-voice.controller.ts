import {
  BadRequestException,
  Body,
  Controller,
  ForbiddenException,
  Get,
  HttpCode,
  HttpStatus,
  Post,
  UploadedFile,
  UseInterceptors,
} from '@nestjs/common';
import { FileInterceptor } from '@nestjs/platform-express';
import {
  ApiBearerAuth,
  ApiBody,
  ApiConsumes,
  ApiOperation,
  ApiTags,
} from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import { memoryStorage } from 'multer';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { AiVoiceTurnBodyDto } from './dto/ai-voice-turn-body.dto';
import { AiVoiceService } from './voice/ai-voice.service';

const aiThrottleLimit = Number(process.env.AI_THROTTLE_LIMIT ?? 20);
const aiThrottleTtl = Number(process.env.AI_THROTTLE_TTL_MS ?? 60_000);
const maxVoiceMb = Number(process.env.VOICE_MAX_AUDIO_MB ?? 10);

/**
 * Voz en tiempo real: Flutter sube audio → Whisper → Ollama → Piper.
 * La app móvil nunca llama a Ollama/Whisper/Piper directamente.
 */
@ApiTags('ai')
@ApiBearerAuth()
@Controller('ai/voice')
@Roles('socio')
export class AiVoiceController {
  constructor(private readonly aiVoice: AiVoiceService) {}

  @Get('status')
  @ApiOperation({ summary: 'Socio: estado del pipeline de voz local' })
  getStatus() {
    return this.aiVoice.getStatus();
  }

  @Post('turn')
  @HttpCode(HttpStatus.OK)
  @Throttle({ default: { limit: aiThrottleLimit, ttl: aiThrottleTtl } })
  @UseInterceptors(
    FileInterceptor('audio', {
      storage: memoryStorage(),
      limits: { fileSize: maxVoiceMb * 1024 * 1024 },
    }),
  )
  @ApiConsumes('multipart/form-data')
  @ApiBody({
    schema: {
      type: 'object',
      properties: {
        audio: { type: 'string', format: 'binary' },
        conversationId: { type: 'integer' },
      },
      required: ['audio'],
    },
  })
  @ApiOperation({
    summary: 'Socio: turno de voz (STT + Ollama + TTS)',
  })
  async voiceTurn(
    @CurrentUser() user: JwtPayload,
    @UploadedFile() file: Express.Multer.File | undefined,
    @Body() body: AiVoiceTurnBodyDto,
  ) {
    const memberId = this.resolveMemberId(user);
    if (!file?.buffer?.length) {
      throw new BadRequestException(
        'Archivo de audio requerido (campo "audio").',
      );
    }

    return this.aiVoice.processVoiceTurn(
      memberId,
      file.buffer,
      file.originalname || 'voice.wav',
      body.conversationId,
    );
  }

  private resolveMemberId(user: JwtPayload): number {
    if (!user.memberId || user.memberId !== user.sub) {
      throw new ForbiddenException('Token de socio inválido');
    }
    return user.memberId;
  }
}
