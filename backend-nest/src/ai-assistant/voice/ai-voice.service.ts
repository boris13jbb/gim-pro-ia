import {
  BadRequestException,
  Injectable,
  ServiceUnavailableException,
} from '@nestjs/common';
import { AiChatService } from '../ai-chat.service';
import { AiModelService } from '../ai-model.service';
import { PiperTtsService } from './piper-tts.service';
import { WhisperSttService } from './whisper-stt.service';

export type AiVoiceStatus = {
  enabled: boolean;
  provider: string;
  ollamaModel: string;
  stt: { engine: string; ready: boolean };
  tts: { engine: string; ready: boolean };
};

/**
 * Orquesta el turno de voz completo en el servidor:
 * audio → Whisper → Ollama (vía AiChatService) → Piper → WAV base64.
 */
@Injectable()
export class AiVoiceService {
  constructor(
    private readonly whisper: WhisperSttService,
    private readonly piper: PiperTtsService,
    private readonly aiChat: AiChatService,
    private readonly aiModel: AiModelService,
  ) {}

  async getStatus(): Promise<AiVoiceStatus> {
    const [sttReady, ttsReady] = await Promise.all([
      this.whisper.isReady(),
      this.piper.isReady(),
    ]);

    return {
      enabled: this.whisper.isEnabled(),
      provider: this.aiModel.getProvider(),
      ollamaModel: this.aiModel.getActiveModelName(),
      stt: { engine: 'faster-whisper', ready: sttReady },
      tts: { engine: 'piper', ready: ttsReady },
    };
  }

  async processVoiceTurn(
    memberId: number,
    audio: Buffer,
    originalName: string,
    conversationId?: number,
  ) {
    if (!this.aiModel.isConfigured()) {
      throw new ServiceUnavailableException(
        'El asistente IA no está configurado. Usa Ollama con AI_PROVIDER=ollama.',
      );
    }

    const transcript = await this.whisper.transcribe(audio, originalName);
    if (!transcript.trim()) {
      throw new BadRequestException(
        'No se detectó voz en el audio. Habla más cerca del micrófono e intenta de nuevo.',
      );
    }

    const chat = await this.aiChat.sendMessage(memberId, {
      message: transcript,
      conversationId,
    });

    let audioBase64: string | null = null;
    let audioMimeType: string | null = null;

    if (await this.piper.isReady()) {
      const wav = await this.piper.synthesize(chat.reply);
      audioBase64 = wav.toString('base64');
      audioMimeType = 'audio/wav';
    }

    return {
      conversationId: chat.conversationId,
      transcript,
      reply: chat.reply,
      message: chat.message,
      audioBase64,
      audioMimeType,
      ttsEngine: audioBase64 ? 'piper' : null,
    };
  }
}
