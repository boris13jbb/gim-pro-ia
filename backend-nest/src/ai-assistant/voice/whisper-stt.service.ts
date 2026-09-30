import { execFile } from 'node:child_process';
import { access } from 'node:fs/promises';
import { constants } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { promisify } from 'node:util';
import {
  Injectable,
  Logger,
  ServiceUnavailableException,
} from '@nestjs/common';
import { writeFile, unlink } from 'node:fs/promises';

const execFileAsync = promisify(execFile);

/**
 * STT local con faster-whisper (script Python en scripts/voice/transcribe.py).
 * El audio nunca sale del servidor; Flutter solo sube el archivo de voz.
 */
@Injectable()
export class WhisperSttService {
  private readonly logger = new Logger(WhisperSttService.name);
  private readonly python = process.env.PYTHON_EXECUTABLE?.trim() || 'python';
  private readonly scriptPath = (() => {
    const configured = process.env.VOICE_TRANSCRIBE_SCRIPT?.trim();
    if (configured) {
      return join(process.cwd(), configured);
    }
    return join(process.cwd(), '..', 'scripts', 'voice', 'transcribe.py');
  })();
  private readonly model = process.env.FASTER_WHISPER_MODEL?.trim() || 'base';
  private readonly language = process.env.WHISPER_LANGUAGE?.trim() || 'es';

  isEnabled(): boolean {
    return (
      (process.env.VOICE_PIPELINE_ENABLED ?? 'true').toLowerCase() !== 'false'
    );
  }

  async isReady(): Promise<boolean> {
    if (!this.isEnabled()) return false;
    try {
      await access(this.scriptPath, constants.R_OK);
      await execFileAsync(this.python, ['--version']);
      return true;
    } catch {
      return false;
    }
  }

  async transcribe(audio: Buffer, originalName: string): Promise<string> {
    if (!this.isEnabled()) {
      throw new ServiceUnavailableException(
        'Pipeline de voz deshabilitado (VOICE_PIPELINE_ENABLED=false).',
      );
    }

    const ext = originalName.includes('.')
      ? originalName.slice(originalName.lastIndexOf('.'))
      : '.wav';
    const inputPath = join(tmpdir(), `gym-voice-in-${Date.now()}${ext}`);

    try {
      await writeFile(inputPath, audio);

      const { stdout, stderr } = await execFileAsync(
        this.python,
        [
          this.scriptPath,
          inputPath,
          '--language',
          this.language,
          '--model',
          this.model,
        ],
        {
          env: {
            ...process.env,
            FASTER_WHISPER_MODEL: this.model,
            WHISPER_LANGUAGE: this.language,
          },
          maxBuffer: 4 * 1024 * 1024,
          timeout: Number(process.env.VOICE_STT_TIMEOUT_MS ?? 120_000),
        },
      );

      if (stderr?.trim()) {
        this.logger.debug(`Whisper stderr: ${stderr.trim()}`);
      }

      return stdout.trim();
    } catch (error) {
      const msg = error instanceof Error ? error.message : String(error);
      this.logger.warn(`Whisper STT falló: ${msg}`);
      throw new ServiceUnavailableException(
        'No se pudo transcribir el audio. Instala faster-whisper ' +
          '(pip install -r scripts/voice/requirements-voice.txt) y verifica PYTHON_EXECUTABLE.',
      );
    } finally {
      await unlink(inputPath).catch(() => undefined);
    }
  }
}
