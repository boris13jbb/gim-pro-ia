import { Injectable } from '@nestjs/common';
import { GeminiService } from './gemini.service';
import { OllamaService } from './ollama.service';
import type { AiReplyParams } from './types/ai-history.types';

export type AiProvider = 'gemini' | 'ollama';

/**
 * Fachada del proveedor de IA según AI_PROVIDER en .env.
 * Producción: gemini (default). Desarrollo/pruebas locales: ollama.
 * Flutter y WebSockets solo hablan con NestJS; nunca con Gemini ni Ollama directo.
 */
@Injectable()
export class AiModelService {
  constructor(
    private readonly gemini: GeminiService,
    private readonly ollama: OllamaService,
  ) {}

  getProvider(): AiProvider {
    const configured = process.env.AI_PROVIDER?.trim().toLowerCase();
    return configured === 'ollama' ? 'ollama' : 'gemini';
  }

  isConfigured(): boolean {
    return this.getProvider() === 'ollama'
      ? this.ollama.isConfigured()
      : this.gemini.isConfigured();
  }

  getActiveModelName(): string {
    return this.getProvider() === 'ollama'
      ? this.ollama.getModelName()
      : this.gemini.getModelName();
  }

  generateReply(params: AiReplyParams): Promise<string> {
    return this.getProvider() === 'ollama'
      ? this.ollama.generateReply(params)
      : this.gemini.generateReply(params);
  }

  generateReplyStream(
    params: AiReplyParams,
    onChunk: (delta: string) => void,
  ): Promise<string> {
    return this.getProvider() === 'ollama'
      ? this.ollama.generateReplyStream(params, onChunk)
      : this.gemini.generateReplyStream(params, onChunk);
  }
}
