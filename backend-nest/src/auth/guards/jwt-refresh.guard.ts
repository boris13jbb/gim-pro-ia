import { Injectable, UnauthorizedException } from '@nestjs/common';
import { AuthGuard } from '@nestjs/passport';

/**
 * Valida el refresh token JWT enviado en el body.
 * La revocación y rotación se completan en AuthService.refresh().
 */
@Injectable()
export class JwtRefreshGuard extends AuthGuard('jwt-refresh') {
  handleRequest<TUser>(err: Error | null, user: TUser): TUser {
    if (err || !user) {
      throw (
        err ?? new UnauthorizedException('Refresh token inválido o expirado')
      );
    }
    return user;
  }
}
