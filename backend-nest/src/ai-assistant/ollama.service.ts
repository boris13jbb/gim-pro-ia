import {
  HttpException,
  HttpStatus,
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import { buildSystemPrompt } from './ai-system-instruction';
import type { AiHistoryMessage, AiReplyParams } from './types/ai-history.types';

type OllamaChatRole = 'system' | 'user' | 'assistant';

type OllamaChatMessage = {
  role: OllamaChatRole;
  content: string;
};

type OllamaChatChunk = {
  message?: { content?: string };
  error?: string;
};

/**
 * Proveedor local Ollama para desarrollo y pruebas.
 * Flutter sigue llamando solo a NestJS; Ollama nunca se expone al cliente.
 *
 * Requiere Ollama en ejecución: `ollama serve` y un modelo descargado
 * (ej. `ollama pull llama3.2`).
 */
@Injectable()
export class OllamaService {
  private readonly logger = new Logger(OllamaService.name);
  private readonly baseUrl = (
    process.env.OLLAMA_BASE_URL?.trim() || 'http://127.0.0.1:11434'
  ).replace(/\/$/, '');
  private readonly modelName = process.env.OLLAMA_MODEL?.trim() || 'llama3.2';

  isConfigured(): boolean {
    return this.modelName.length > 0;
  }

  getModelName(): string {
    return this.modelName;
  }

  async generateReply(params: AiReplyParams): Promise<string> {
    const messages = this.buildMessages(params);

    try {
      const response = await fetch(`${this.baseUrl}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          model: this.modelName,
          messages,
          stream: false,
        }),
      });

      const body = (await response.json()) as OllamaChatChunk & {
        message?: { content?: string };
      };

      if (!response.ok) {
        throw new Error(body.error ?? `Ollama respondió ${response.status}`);
      }

      const text = body.message?.content?.trim();
      if (!text) {
        throw new ServiceUnavailableException(
          'El asistente no generó una respuesta. Intenta de nuevo.',
        );
      }

      return text;
    } catch (error) {
      throw this.mapOllamaError(error);
    }
  }

  async generateReplyStream(
    params: AiReplyParams,
    onChunk: (delta: string) => void,
  ): Promise<string> {
    const messages = this.buildMessages(params);

    try {
      const response = await fetch(`${this.baseUrl}/api/chat`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          model: this.modelName,
          messages,
          stream: true,
        }),
      });

      if (!response.ok) {
        const body = (await response
          .json()
          .catch(() => ({}))) as OllamaChatChunk;
        throw new Error(body.error ?? `Ollama respondió ${response.status}`);
      }

      if (!response.body) {
        throw new ServiceUnavailableException(
          'Ollama no devolvió un stream de respuesta.',
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
          if (!trimmed) continue;

          const chunk = JSON.parse(trimmed) as OllamaChatChunk;
          const piece = chunk.message?.content ?? '';
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
      throw this.mapOllamaError(error);
    }
  }

  private buildMessages(params: AiReplyParams): OllamaChatMessage[] {
    const messages: OllamaChatMessage[] = [
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

  private mapOllamaError(error: unknown): HttpException {
    const raw =
      error instanceof Error ? error.message : 'Error desconocido con Ollama';
    this.logger.warn(`Ollama error: ${raw}`);

    const lower = raw.toLowerCase();

    if (
      lower.includes('econnrefused') ||
      lower.includes('fetch failed') ||
      lower.includes('network')
    ) {
      return new ServiceUnavailableException(
        `Ollama no está disponible en ${this.baseUrl}. ` +
          'Inicia el servicio (`ollama serve`) y descarga un modelo (`ollama pull llama3.2`).',
      );
    }

    if (lower.includes('not found') || lower.includes('404')) {
      return new HttpException(
        `El modelo Ollama "${this.modelName}" no está instalado. ` +
          `Ejecuta: ollama pull ${this.modelName}`,
        HttpStatus.BAD_REQUEST,
      );
    }

    if (error instanceof ServiceUnavailableException) {
      return error;
    }

    return new ServiceUnavailableException(
      'El asistente IA local no está disponible temporalmente. Intenta más tarde.',
    );
  }
}
