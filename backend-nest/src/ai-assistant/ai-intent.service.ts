import { Injectable, Logger } from '@nestjs/common';
import { GeminiService } from './gemini.service';
import type {
  AiIntentHistoryMessage,
  AiIntentResult,
  AiMemberIntent,
} from './types/ai-intent.types';

const VALID_INTENTS: AiMemberIntent[] = [
  'GREETING',
  'MEMBERSHIP',
  'ATTENDANCE',
  'WORKOUT',
  'BODY_PROGRESS',
  'GENERAL_FITNESS',
  'APP_HELP',
  'OTHER',
];

/**
 * Análisis de intención previo al turno de chat (patrón adaptado de src/ia).
 * Solo corre en el servidor; el resultado se puede emitir al cliente vía WebSocket
 * (`ai.intent`) para mejorar UX, sin cambiar reglas de negocio ni permisos.
 */
@Injectable()
export class AiIntentService {
  private readonly logger = new Logger(AiIntentService.name);
  private readonly enabled =
    (process.env.AI_INTENT_ENABLED ?? 'true').toLowerCase() !== 'false';

  constructor(private readonly gemini: GeminiService) {}

  isEnabled(): boolean {
    return this.enabled && this.gemini.isConfigured();
  }

  async analyzeIntent(
    message: string,
    history: AiIntentHistoryMessage[] = [],
  ): Promise<AiIntentResult> {
    if (!this.isEnabled()) {
      return { intent: 'OTHER' };
    }

    try {
      const raw = await this.gemini.analyzeMemberIntent(message, history);
      return this.normalizeResult({
        intent: raw.intent as AiMemberIntent,
        topic: raw.topic,
      });
    } catch (error) {
      this.logger.warn(
        `Intent analysis skipped: ${error instanceof Error ? error.message : error}`,
      );
      return { intent: 'OTHER' };
    }
  }

  /** Texto auxiliar inyectado al contexto del modelo según la intención. */
  buildIntentHint(result: AiIntentResult): string {
    const hints: Record<AiMemberIntent, string> = {
      GREETING:
        'El socio saluda. Responde breve y ofrece ayuda sobre membresía, asistencias, rutina o progreso.',
      MEMBERSHIP:
        'Prioriza estado de membresía, fechas y plan usando solo datos del contexto JSON.',
      ATTENDANCE: 'Prioriza resumen de asistencias recientes del socio.',
      WORKOUT: 'Prioriza la rutina asignada y ejercicios; no inventes rutinas.',
      BODY_PROGRESS:
        'Prioriza medidas corporales e historial de progreso registrado.',
      GENERAL_FITNESS:
        'Orientación general y motivacional; sin diagnósticos médicos.',
      APP_HELP:
        'Explica funciones de la app: carnet QR, progreso, rutina, asistente.',
      OTHER: 'Responde según el contexto real del socio.',
    };

    const base = hints[result.intent];
    return result.topic ? `${base} Tema detectado: ${result.topic}.` : base;
  }

  private normalizeResult(raw: AiIntentResult): AiIntentResult {
    const intent = VALID_INTENTS.includes(raw.intent) ? raw.intent : 'OTHER';
    const topic =
      typeof raw.topic === 'string' && raw.topic.trim().length > 0
        ? raw.topic.trim().slice(0, 120)
        : undefined;

    return { intent, topic };
  }
}
