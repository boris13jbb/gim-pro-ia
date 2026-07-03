import {
  ArgumentsHost,
  Catch,
  ExceptionFilter,
  HttpException,
  HttpStatus,
} from '@nestjs/common';
import { Request, Response } from 'express';

/**
 * Fase 01 (base):
 * Respuesta de error consistente sin exponer stack traces internos.
 * En Fase 02+ se ampliará para mapear errores de auth/validación con detalle controlado.
 */
@Catch()
export class HttpExceptionFilter implements ExceptionFilter {
  catch(exception: unknown, host: ArgumentsHost) {
    const ctx = host.switchToHttp();
    const response = ctx.getResponse<Response>();
    const request = ctx.getRequest<Request>();

    const isHttpException = exception instanceof HttpException;
    const status = isHttpException
      ? exception.getStatus()
      : HttpStatus.INTERNAL_SERVER_ERROR;

    const errorResponse = isHttpException
      ? exception.getResponse()
      : { message: 'Internal server error' };

    const message = this.extractErrorMessage(errorResponse);

    response.status(status).json({
      ok: false,
      error: {
        statusCode: status,
        message,
        path: request.url,
        timestamp: new Date().toISOString(),
      },
    });
  }

  private extractErrorMessage(errorResponse: string | object): string {
    if (typeof errorResponse === 'string') {
      return errorResponse;
    }

    const payload = errorResponse as { message?: string | string[] };
    if (Array.isArray(payload.message)) {
      return payload.message.join(', ');
    }
    if (typeof payload.message === 'string') {
      return payload.message;
    }

    return 'Error';
  }
}
