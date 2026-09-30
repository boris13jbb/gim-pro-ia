import {
  HttpException,
  HttpStatus,
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import { GoogleGenerativeAI, type Content } from '@google/generative-ai';
import { buildSystemPrompt } from './ai-system-instruction';
import type { AiHistoryMessage, AiReplyParams } from './types/ai-history.types';

export type GeminiHistoryMessage = AiHistoryMessage;

/**
 * Integración con Gemini vía SDK oficial.
 * La API key vive solo en el servidor (GEMINI_API_KEY); Flutter nunca la ve.
 */
@Injectable()
export class GeminiService {
  private readonly logger = new Logger(GeminiService.name);
  private readonly apiKey = process.env.GEMINI_API_KEY?.trim() ?? '';
  private readonly modelName =
    process.env.GEMINI_MODEL?.trim() || 'gemini-2.0-flash';

  isConfigured(): boolean {
    return this.apiKey.length > 0;
  }

  getModelName(): string {
    return this.modelName;
  }

  async generateReply(params: AiReplyParams): Promise<string> {
    const chat = this.createChatSession(params);

    try {
      const result = await chat.sendMessage(params.message);
      const text = result.response.text()?.trim();

      if (!text) {
        throw new ServiceUnavailableException(
          'El asistente no generó una respuesta. Intenta de nuevo.',
        );
      }

      return text;
    } catch (error) {
      throw this.mapGeminiError(error);
    }
  }

  /**
   * Streaming (WebSockets): entrega la respuesta por fragmentos vía onChunk y
   * devuelve el texto completo para persistirlo. La API key sigue solo en el
   * servidor; el socio recibe únicamente texto generado, nunca claves.
   */
  async generateReplyStream(
    params: AiReplyParams,
    onChunk: (delta: string) => void,
  ): Promise<string> {
    const chat = this.createChatSession(params);

    try {
      const result = await chat.sendMessageStream(params.message);

      let full = '';
      for await (const chunk of result.stream) {
        const piece = chunk.text();
        if (piece) {
          full += piece;
          onChunk(piece);
        }
      }

      const finalText = (await result.response).text()?.trim() || full.trim();

      if (!finalText) {
        throw new ServiceUnavailableException(
          'El asistente no generó una respuesta. Intenta de nuevo.',
        );
      }

      return finalText;
    } catch (error) {
      throw this.mapGeminiError(error);
    }
  }

  /**
   * Crea la sesión de chat con la instrucción de sistema y el contexto real del
   * socio. Reutilizado por la respuesta REST (completa) y por el streaming (WS).
   */
  /**
   * Clasifica la intención del mensaje del socio (JSON). Usado antes del streaming WS.
   */
  async analyzeMemberIntent(
    message: string,
    history: Array<{ role: 'user' | 'assistant'; content: string }> = [],
  ): Promise<{ intent: string; topic?: string }> {
    if (!this.isConfigured()) {
      return { intent: 'OTHER' };
    }

    const historyBlock =
      history.length > 0
        ? `\nHistorial reciente:\n${history
            .slice(-6)
            .map((h) => `${h.role}: ${h.content}`)
            .join('\n')}`
        : '';

    const prompt = `Analiza el último mensaje de un socio del gimnasio Iron Gym.
Devuelve SOLO JSON válido:
{
  "intent": "GREETING" | "MEMBERSHIP" | "ATTENDANCE" | "WORKOUT" | "BODY_PROGRESS" | "GENERAL_FITNESS" | "APP_HELP" | "OTHER",
  "topic": "tema breve en español o null"
}
${historyBlock}

Último mensaje del socio: ${message}`;

    const genAI = new GoogleGenerativeAI(this.apiKey);
    const model = genAI.getGenerativeModel({
      model: this.modelName,
      generationConfig: {
        responseMimeType: 'application/json',
        temperature: 0.1,
      },
    });

    const result = await model.generateContent(prompt);
    const text = result.response.text()?.trim() || '{}';
    const parsed = JSON.parse(text) as {
      intent?: string;
      topic?: string | null;
    };

    return {
      intent: parsed.intent ?? 'OTHER',
      topic: parsed.topic ?? undefined,
    };
  }

  private createChatSession(params: {
    memberContext: string;
    history: AiHistoryMessage[];
  }) {
    if (!this.isConfigured()) {
      throw new ServiceUnavailableException(
        'El asistente IA no está configurado. Define GEMINI_API_KEY en el servidor.',
      );
    }

    const genAI = new GoogleGenerativeAI(this.apiKey);
    const model = genAI.getGenerativeModel({
      model: this.modelName,
      systemInstruction: buildSystemPrompt(params.memberContext),
    });

    const history: Content[] = this.normalizeHistory(params.history).map(
      (item) => ({
        role: item.role,
        parts: [{ text: item.text }],
      }),
    );

    return model.startChat({ history });
  }

  /**
   * Gemini exige alternancia user/model. Si hubo fallos previos pueden quedar
   * mensajes user huérfanos; se fusionan para no romper la conversación.
   */
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

  private mapGeminiError(error: unknown): HttpException {
    const raw =
      error instanceof Error ? error.message : 'Error desconocido con Gemini';
    this.logger.warn(`Gemini error: ${raw}`);

    const lower = raw.toLowerCase();

    if (
      lower.includes('429') ||
      lower.includes('too many requests') ||
      lower.includes('quota') ||
      lower.includes('credits are depleted') ||
      lower.includes('resource exhausted')
    ) {
      return new HttpException(
        'El asistente IA no tiene créditos disponibles en Google AI Studio. ' +
          'Activa facturación o recarga créditos en https://aistudio.google.com/apikey',
        HttpStatus.TOO_MANY_REQUESTS,
      );
    }

    if (
      lower.includes('api key') ||
      lower.includes('apikey') ||
      lower.includes('permission denied') ||
      lower.includes('403')
    ) {
      return new HttpException(
        'La API key de Gemini es inválida o no tiene permisos.',
        HttpStatus.UNAUTHORIZED,
      );
    }

    if (lower.includes('not found') || lower.includes('404')) {
      return new HttpException(
        `El modelo Gemini configurado no está disponible (${this.modelName}). ` +
          'Prueba GEMINI_MODEL=gemini-1.5-flash en .env',
        HttpStatus.BAD_REQUEST,
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
