import {
  HttpException,
  HttpStatus,
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import { buildSystemPrompt } from './ai-system-instruction';
import type { AiHistoryMessage, AiReplyParams } from './types/ai-history.types';

type ZaiChatRole = 'system' | 'user' | 'assistant';

type ZaiChatMessage = {
  role: ZaiChatRole;
  content: string;
};

type ZaiChatChoice = {
  message?: { content?: string };
  delta?: { content?: string; reasoning_content?: string };
};

type ZaiChatResponse = {
  choices?: ZaiChatChoice[];
  error?: { message?: string; code?: string };
};

/**
 * Proveedor Z.AI (GLM-5.2 y modelos compatibles) vía API OpenAI-compatible.
 * La API key vive solo en el servidor (ZAI_API_KEY); Flutter nunca la ve.
 *
 * Documentación: https://docs.z.ai/guides/llm/glm-5.2
 */
@Injectable()
export class ZaiService {
  private readonly logger = new Logger(ZaiService.name);
  private readonly apiKey = process.env.ZAI_API_KEY?.trim() ?? '';
  private readonly baseUrl = (
    process.env.ZAI_BASE_URL?.trim() || 'https://api.z.ai/api/paas/v4'
  ).replace(/\/$/, '');
  private readonly modelName = process.env.ZAI_MODEL?.trim() || 'glm-5.2';

  isConfigured(): boolean {
    return this.apiKey.length > 0;
  }

  getModelName(): string {
    return this.modelName;
  }

  async generateReply(params: AiReplyParams): Promise<string> {
    const messages = this.buildMessages(params);

    try {
      const response = await fetch(`${this.baseUrl}/chat/completions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${this.apiKey}`,
        },
        body: JSON.stringify({
          model: this.modelName,
          messages,
          stream: false,
          thinking: { type: 'disabled' },
          max_tokens: 4096,
          temperature: 0.7,
        }),
      });

      const body = (await response.json()) as ZaiChatResponse;

      if (!response.ok) {
        throw new Error(
          body.error?.message ?? `Z.AI respondió ${response.status}`,
        );
      }

      const text = body.choices?.[0]?.message?.content?.trim();
      if (!text) {
        throw new ServiceUnavailableException(
          'El asistente no generó una respuesta. Intenta de nuevo.',
        );
      }

      return text;
    } catch (error) {
      throw this.mapZaiError(error);
    }
  }

  async generateReplyStream(
    params: AiReplyParams,
    onChunk: (delta: string) => void,
  ): Promise<string> {
    const messages = this.buildMessages(params);

    try {
      const response = await fetch(`${this.baseUrl}/chat/completions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${this.apiKey}`,
        },
        body: JSON.stringify({
          model: this.modelName,
          messages,
          stream: true,
          thinking: { type: 'disabled' },
          max_tokens: 4096,
          temperature: 0.7,
        }),
      });

      if (!response.ok) {
        const body = (await response
          .json()
          .catch(() => ({}))) as ZaiChatResponse;
        throw new Error(
          body.error?.message ?? `Z.AI respondió ${response.status}`,
        );
      }

      if (!response.body) {
        throw new ServiceUnavailableException(
          'Z.AI no devolvió un stream de respuesta.',
        );
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffer = '';
      let full = '';

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split('\n');
        buffer = lines.pop() ?? '';

        for (const line of lines) {
          const trimmed = line.trim();
          if (!trimmed || !trimmed.startsWith('data:')) continue;

          const payload = trimmed.slice(5).trim();
          if (!payload || payload === '[DONE]') continue;

          const chunk = JSON.parse(payload) as ZaiChatResponse;
          const piece = chunk.choices?.[0]?.delta?.content ?? '';
          if (piece) {
            full += piece;
            onChunk(piece);
          }
        }
      }

      const finalText = full.trim();
      if (!finalText) {
        throw new ServiceUnavailableException(
          'El asistente no generó una respuesta. Intenta de nuevo.',
        );
      }

      return finalText;
    } catch (error) {
      throw this.mapZaiError(error);
    }
  }

  private buildMessages(params: AiReplyParams): ZaiChatMessage[] {
    const messages: ZaiChatMessage[] = [
      {
        role: 'system',
        content: buildSystemPrompt(params.memberContext),
      },
    ];

    for (const item of this.normalizeHistory(params.history)) {
      messages.push({
        role: item.role === 'model' ? 'assistant' : 'user',
        content: item.text,
      });
    }

    messages.push({ role: 'user', content: params.message });
    return messages;
  }

  private normalizeHistory(history: AiHistoryMessage[]): AiHistoryMessage[] {
    const normalized: AiHistoryMessage[] = [];

    for (const item of history) {
      const last = normalized[normalized.length - 1];
      if (last && last.role === item.role) {
        last.text = `${last.text}\n${item.text}`;
        continue;
      }
      normalized.push({ ...item });
    }

    return normalized;
  }

  private mapZaiError(error: unknown): HttpException {
    const raw =
      error instanceof Error ? error.message : 'Error desconocido con Z.AI';
    this.logger.warn(`Z.AI error: ${raw}`);

    if (!this.isConfigured()) {
      return new ServiceUnavailableException(
        'El asistente IA no está configurado. Define ZAI_API_KEY en el servidor.',
      );
    }

    const lower = raw.toLowerCase();

    if (
      lower.includes('429') ||
      lower.includes('too many requests') ||
      lower.includes('rate limit') ||
      lower.includes('quota') ||
      lower.includes('insufficient balance') ||
      lower.includes('no resource package') ||
      lower.includes('recharge')
    ) {
      return new HttpException(
        'El asistente IA no tiene créditos disponibles en Z.AI. Recarga tu plan en https://z.ai',
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }

    if (
      lower.includes('api key') ||
      lower.includes('unauthorized') ||
      lower.includes('invalid') ||
      lower.includes('401') ||
      lower.includes('403')
    ) {
      return new HttpException(
        'La API key de Z.AI es inválida o no tiene permisos.',
        HttpStatus.UNAUTHORIZED,
      );
    }

    if (lower.includes('not found') || lower.includes('404')) {
      return new HttpException(
        `El modelo Z.AI configurado no está disponible (${this.modelName}). ` +
          'Verifica ZAI_MODEL en .env',
        HttpStatus.BAD_REQUEST,
      );
    }

    if (
      lower.includes('econnrefused') ||
      lower.includes('fetch failed') ||
      lower.includes('network')
    ) {
      return new ServiceUnavailableException(
        'No se pudo conectar con la API de Z.AI. Revisa tu conexión a internet.',
      );
    }

    if (error instanceof ServiceUnavailableException) {
      return error;
    }

    return new ServiceUnavailableException(
      'El asistente IA no está disponible temporalmente. Intenta más tarde.',
    );
  }
}
