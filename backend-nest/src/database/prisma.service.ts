import {
  Injectable,
  OnModuleDestroy,
  OnModuleInit,
} from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { PrismaMariaDb } from '@prisma/adapter-mariadb';
import { PrismaClient } from '@prisma/client';
import { parseMysqlDatabaseUrl } from './parse-mysql-database-url';

@Injectable()
export class PrismaService
  extends PrismaClient
  implements OnModuleInit, OnModuleDestroy
{
  constructor(private readonly config: ConfigService) {
    const databaseUrl = config.get<string>('DATABASE_URL');

    if (!databaseUrl) {
      throw new Error(
        'DATABASE_URL no está definida. Copia backend-nest/.env.example a .env y configura MySQL.',
      );
    }

    const { host, port, user, password, database } =
      parseMysqlDatabaseUrl(databaseUrl);

    // Prisma v7: conexión MySQL vía driver adapter (no motor Rust embebido).
    const adapter = new PrismaMariaDb({
      host,
      port,
      user,
      password,
      database,
      connectionLimit: 10,
      connectTimeout: 5_000,
      // MySQL/MariaDB 8+ con caching_sha2_password (común en Laragon/XAMPP).
      allowPublicKeyRetrieval: true,
    });

    super({ adapter });
  }

  async onModuleInit() {
    await this.$connect();
  }

  async onModuleDestroy() {
    await this.$disconnect();
  }
}
