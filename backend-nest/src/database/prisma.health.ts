import { Injectable } from '@nestjs/common';
import { HealthIndicator, HealthIndicatorResult } from '@nestjs/terminus';
import { PrismaService } from './prisma.service';

@Injectable()
export class PrismaHealthIndicator extends HealthIndicator {
  constructor(private readonly prisma: PrismaService) {
    super();
  }

  async isHealthy(key: string): Promise<HealthIndicatorResult> {
    try {
      // Ping liviano para validar conexión sin depender de modelos generados.
      await this.prisma.$queryRaw`SELECT 1`;
      return this.getStatus(key, true);
    } catch (error) {
      const raw = (error as Error).message ?? 'Database unavailable';
      const hint =
        raw.includes('ER_BAD_DB_ERROR') || raw.includes('Unknown database')
          ? 'La base ec_gym_system no existe. Importa gym-system/bk_basededatos.sql o créala en MySQL.'
          : raw.includes('pool timeout') || raw.includes('45028')
            ? 'MySQL no responde. Verifica que el servicio esté activo y DATABASE_URL en .env.'
            : undefined;

      return this.getStatus(key, false, {
        message: raw,
        ...(hint ? { hint } : {}),
      });
    }
  }
}
