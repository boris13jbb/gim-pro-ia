import { spawn } from 'node:child_process';
import { access } from 'node:fs/promises';
import { constants } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { readFile, unlink } from 'node:fs/promises';
import {
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';

/**
 * TTS local con Piper CLI. Genera WAV para que Flutter lo reproduzca.
 * Orpheus/Coqui pueden añadirse como proveedores alternativos en el futuro.
 */
@Injectable()
export class PiperTtsService {
  private readonly logger = new Logger(PiperTtsService.name);
  private readonly executable = process.env.PIPER_EXECUTABLE?.trim() || 'piper';
  private readonly modelPath = process.env.PIPER_MODEL_PATH?.trim() ?? '';
  private readonly configPath = process.env.PIPER_CONFIG_PATH?.trim() ?? '';

  isConfigured(): boolean {
    return this.modelPath.length > 0;
  }

  async isReady(): Promise<boolean> {
    if (!this.isConfigured()) return false;
    try {
      await access(this.modelPath, constants.R_OK);
      return true;
    } catch {
      return false;
    }
  }

  async synthesize(text: string): Promise<Buffer> {
    const trimmed = text.trim();
    if (!trimmed) {
      throw new ServiceUnavailableException('Texto vacío para TTS.');
    }
    if (!this.isConfigured()) {
      throw new ServiceUnavailableException(
        'Piper no configurado. Define PIPER_MODEL_PATH y PIPER_EXECUTABLE en .env',
      );
    }

    const outputPath = join(tmpdir(), `gym-voice-out-${Date.now()}.wav`);

    try {
      await this.runPiper(trimmed, outputPath);
      return await readFile(outputPath);
    } catch (error) {
      const msg = error instanceof Error ? error.message : String(error);
      this.logger.warn(`Piper TTS falló: ${msg}`);
      throw new ServiceUnavailableException(
        'No se pudo sintetizar voz con Piper. Revisa PIPER_EXECUTABLE y el modelo ONNX.',
      );
    } finally {
      await unlink(outputPath).catch(() => undefined);
    }
  }

  private runPiper(text: string, outputPath: string): Promise<void> {
    const args = ['--model', this.modelPath, '--output_file', outputPath];
    if (this.configPath) {
      args.push('--config', this.configPath);
    }

    return new Promise((resolve, reject) => {
      const proc = spawn(this.executable, args, {
        stdio: ['pipe', 'pipe', 'pipe'],
        windowsHide: true,
      });

      let stderr = '';
      proc.stderr.on('data', (chunk: Buffer) => {
        stderr += chunk.toString();
      });

      proc.on('error', (err) => reject(err));
      proc.on('close', (code) => {
        if (code === 0) {
          resolve();
          return;
        }
        reject(
          new Error(
            stderr.trim() || `Piper salió con código ${code ?? 'desconocido'}`,
          ),
        );
      });

      proc.stdin.write(text);
      proc.stdin.end();
    });
  }
}
