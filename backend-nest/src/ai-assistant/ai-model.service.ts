import { Injectable } from '@nestjs/common';
import { GeminiService } from './gemini.service';
import { OllamaService } from './ollama.service';
import { ZaiService } from './zai.service';
import type { AiReplyParams } from './types/ai-history.types';

export type AiProvider = 'gemini' | 'ollama' | 'zai';

/**
 * Fachada del proveedor de IA según AI_PROVIDER en .env.
 * Producción: gemini o zai. Desarrollo/pruebas locales: ollama.
 * Flutter y WebSockets solo hablan con NestJS; nunca con el proveedor directo.
 */
@Injectable()
export class AiModelService {
  constructor(
    private readonly gemini: GeminiService,
    private readonly ollama: OllamaService,
    private readonly zai: ZaiService,
  ) {}

  getProvider(): AiProvider {
    const configured = process.env.AI_PROVIDER?.trim().toLowerCase();
    if (configured === 'ollama') return 'ollama';
    if (configured === 'zai') return 'zai';
    return 'gemini';
  }

  private getActiveProvider() {
    const provider = this.getProvider();
    if (provider === 'ollama') return this.ollama;
    if (provider === 'zai') return this.zai;
    return this.gemini;
  }

  isConfigured(): boolean {
    return this.getActiveProvider().isConfigured();
  }

  getActiveModelName(): string {
    return this.getActiveProvider().getModelName();
  }

  generateReply(params: AiReplyParams): Promise<string> {
    return this.getActiveProvider().generateReply(params);
  }

  generateReplyStream(
    params: AiReplyParams,
    onChunk: (delta: string) => void,
  ): Promise<string> {
    return this.getActiveProvider().generateReplyStream(params, onChunk);
  }
}
