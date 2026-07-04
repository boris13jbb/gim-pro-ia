import { Module } from '@nestjs/common';
import { WsAuthModule } from './ws-auth.module';
import { RealtimeGateway } from './realtime.gateway';
import { RealtimeService } from './realtime.service';

/**
 * Módulo de tiempo real (notificaciones al socio).
 *
 * Exporta RealtimeService para que los módulos de dominio (asistencia,
 * membresías) emitan notificaciones sin acoplarse al gateway ni a socket.io.
 * No importa módulos de dominio, por lo que no genera dependencias circulares.
 */
@Module({
  imports: [WsAuthModule],
  providers: [RealtimeGateway, RealtimeService],
  exports: [RealtimeService],
})
export class RealtimeModule {}
