import { NestFactory } from '@nestjs/core';

import { AppModule } from './app.module';

import { ValidationPipe } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { NestExpressApplication } from '@nestjs/platform-express';

import helmet from 'helmet';

import { SwaggerModule, DocumentBuilder } from '@nestjs/swagger';

import { HttpExceptionFilter } from './common/filters/http-exception.filter';

import { ResponseTransformInterceptor } from './common/interceptors/response-transform.interceptor';

import { validateEnvOnBootstrap } from './config/validate-env';
import { corsOriginCallback } from './config/cors.config';

import {
  getProductUploadConfig,
  getUploadConfig,
} from './config/upload.config';

async function bootstrap() {
  validateEnvOnBootstrap();

  const app = await NestFactory.create<NestExpressApplication>(AppModule);

  // Regla de arquitectura:

  // La API nueva no usa sesiones; todo endpoint privado debe autenticarse con JWT (Fase 02).

  app.setGlobalPrefix('api');

  const uploadConfig = getUploadConfig();
  const productUploadConfig = getProductUploadConfig();

  app.useStaticAssets(uploadConfig.membersDir, {
    prefix: uploadConfig.publicPath,
  });

  app.useStaticAssets(productUploadConfig.productsDir, {
    prefix: productUploadConfig.publicPath,
  });

  // Seguridad HTTP básica (permite cargar fotos desde el mismo origen/CORS)

  app.use(
    helmet({
      crossOriginResourcePolicy: { policy: 'cross-origin' },
    }),
  );

  // CORS controlado por env (string CSV).
  // Fase 18: flujo principal = LAN (localhost + IPv4 privadas en development).
  // ngrok sigue permitido solo en development como LEGACY/opcional.
  // Ver backend-nest/src/config/cors.config.ts
  const corsOrigins = (process.env.CORS_ORIGINS ?? '')
    .split(',')
    .map((s) => s.trim())
    .filter(Boolean);

  app.enableCors({
    origin: corsOrigins.length === 0 ? true : corsOriginCallback,
    credentials: false,
  });

  // Validación global de DTOs

  app.useGlobalPipes(
    new ValidationPipe({
      whitelist: true,

      forbidNonWhitelisted: true,

      transform: true,
    }),
  );

  // Manejo global de errores + respuesta consistente

  app.useGlobalFilters(new HttpExceptionFilter());

  app.useGlobalInterceptors(
    new ResponseTransformInterceptor(app.get(Reflector)),
  );

  // Swagger (solo para entornos donde esté habilitado)

  const swaggerEnabled =
    (process.env.SWAGGER_ENABLED ?? 'true').toLowerCase() === 'true';

  if (swaggerEnabled) {
    const config = new DocumentBuilder()

      .setTitle('Gym System API')

      .setDescription('API para migración progresiva PHP → NestJS + Flutter.')

      .setVersion('0.1.0')

      .addBearerAuth()

      .build();

    const document = SwaggerModule.createDocument(app, config);

    SwaggerModule.setup('api/docs', app, document, {
      swaggerOptions: { persistAuthorization: true },
    });
  }

  await app.listen(process.env.PORT ?? 3000, '0.0.0.0');
}

void bootstrap();
