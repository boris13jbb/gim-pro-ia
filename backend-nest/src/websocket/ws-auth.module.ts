import { Module } from '@nestjs/common';
import { JwtModule } from '@nestjs/jwt';
import { MembersModule } from '../members/members.module';
import { WsAuthService } from './ws-auth.service';

/**
 * Módulo compartido de autenticación WebSocket.
 *
 * Provee y exporta WsAuthService para reutilizarlo en TODOS los gateways
 * (chat IA y notificaciones en tiempo real) sin duplicar la validación del JWT
 * del handshake. JwtModule se registra aquí para verificar el access token del
 * socket con JWT_ACCESS_SECRET (mismo secreto que la API REST).
 */
@Module({
  imports: [JwtModule.register({}), MembersModule],
  providers: [WsAuthService],
  exports: [WsAuthService],
})
export class WsAuthModule {}
