import {
  HttpException,
  HttpStatus,
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import {
  GoogleGenerativeAI,
  type Content,
} from '@google/generative-ai';

export type GeminiHistoryMessage = {
  role: 'user' | 'model';
  text: string;
};

const SYSTEM_INSTRUCTION = `Eres el asistente virtual del gimnasio Iron Gym para socios.
Responde en español, de forma clara, motivacional y profesional.

Reglas obligatorias:
- Usa SOLO los datos del socio proporcionados en el contexto JSON. No inventes membresías, asistencias, medidas ni rutinas.
- Si no hay datos suficientes, dilo con honestidad y sugiere registrar progreso o consultar en recepción.
- No des diagnósticos médicos, prescripciones de medicamentos ni planes de rehabilitación clínica.
- Orienta en hábitos generales, motivación y uso de las funciones de la app (carnet QR, progreso, rutina).
- No reveles datos de otros socios ni información interna del staff.
- Mantén respuestas concisas (máximo ~3 párrafos salvo que el socio pida detalle).`;

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

  async generateReply(params: {
    memberContext: string;
    history: GeminiHistoryMessage[];
    message: string;
  }): Promise<string> {
    if (!this.isConfigured()) {
      throw new ServiceUnavailableException(
        'El asistente IA no está configurado. Define GEMINI_API_KEY en el servidor.',
      );
    }

    const genAI = new GoogleGenerativeAI(this.apiKey);
    const model = genAI.getGenerativeModel({
      model: this.modelName,
      systemInstruction: `${SYSTEM_INSTRUCTION}\n\nContexto del socio (datos reales de la BD, no inventar):\n${params.memberContext}`,
    });

    const history: Content[] = this.normalizeHistory(params.history).map(
      (item) => ({
        role: item.role,
        parts: [{ text: item.text }],
      }),
    );

    try {
      const chat = model.startChat({ history });
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
   * Gemini exige alternancia user/model. Si hubo fallos previos pueden quedar
   * mensajes user huérfanos; se fusionan para no romper la conversación.
   */
  private normalizeHistory(
    history: GeminiHistoryMessage[],
  ): GeminiHistoryMessage[] {
    const normalized: GeminiHistoryMessage[] = [];

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
