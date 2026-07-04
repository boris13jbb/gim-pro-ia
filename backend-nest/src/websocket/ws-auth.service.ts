import { Injectable, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { JwtService } from '@nestjs/jwt';
import { socios_estado } from '@prisma/client';
import { JwtPayload } from '../auth/types/jwt-payload.type';
import { MembersService } from '../members/members.service';

/**
 * Seguridad WebSocket:
 * Valida el JWT que Flutter envía en el handshake del socket (auth.token).
 * Reutiliza el MISMO secreto del access token (JWT_ACCESS_SECRET) que la API REST,
 * de modo que no exista una segunda forma de autenticación.
 *
 * Regla: solo se aceptan sockets de socios ACTIVOS. Un token ausente, vencido,
 * inválido, de staff o de un socio inactivo hace que la conexión se rechace
 * (equivalente al 401 de la API REST).
 */
@Injectable()
export class WsAuthService {
  constructor(
    private readonly jwtService: JwtService,
    private readonly config: ConfigService,
    private readonly membersService: MembersService,
  ) {}

  /**
   * Autentica un socket de socio a partir del token del handshake y devuelve el
   * memberId validado. Nunca confía en un id enviado por el cliente: el memberId
   * proviene del token firmado por el servidor.
   */
  async authenticateMember(token: string | undefined): Promise<number> {
    if (!token) {
      throw new UnauthorizedException('Token ausente');
    }

    let payload: JwtPayload;
    try {
      payload = await this.jwtService.verifyAsync<JwtPayload>(token, {
        secret: this.config.getOrThrow<string>('JWT_ACCESS_SECRET'),
      });
    } catch {
      throw new UnauthorizedException('Token inválido o vencido');
    }

    if (payload.type && payload.type !== 'access') {
      throw new UnauthorizedException('Tipo de token inválido');
    }

    if (payload.userType !== 'member') {
      throw new UnauthorizedException('El asistente IA es solo para socios');
    }

    const memberId = payload.memberId ?? payload.sub;
    const member = await this.membersService.findById(memberId);
    if (!member || member.estado === socios_estado.inactivo) {
      throw new UnauthorizedException('Socio inactivo o no encontrado');
    }

    return member.id;
  }
}
