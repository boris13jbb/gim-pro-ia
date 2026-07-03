import { Injectable, UnauthorizedException } from '@nestjs/common';
import { ConfigService } from '@nestjs/config';
import { PassportStrategy } from '@nestjs/passport';
import { ExtractJwt, Strategy } from 'passport-jwt';
import { socios_estado, usuarios_estado } from '@prisma/client';
import { JwtPayload } from '../types/jwt-payload.type';
import { UsersService } from '../../users/users.service';
import { MembersService } from '../../members/members.service';

@Injectable()
export class JwtAccessStrategy extends PassportStrategy(
  Strategy,
  'jwt-access',
) {
  constructor(
    config: ConfigService,
    private readonly usersService: UsersService,
    private readonly membersService: MembersService,
  ) {
    super({
      jwtFromRequest: ExtractJwt.fromAuthHeaderAsBearerToken(),
      ignoreExpiration: false,
      secretOrKey: config.getOrThrow<string>('JWT_ACCESS_SECRET'),
    });
  }

  async validate(payload: JwtPayload): Promise<JwtPayload> {
    if (payload.type && payload.type !== 'access') {
      throw new UnauthorizedException('Tipo de token inválido');
    }

    if (payload.userType === 'member') {
      const member = await this.membersService.findById(
        payload.memberId ?? payload.sub,
      );
      if (!member || member.estado === socios_estado.inactivo) {
        throw new UnauthorizedException('Socio inactivo o no encontrado');
      }

      return {
        sub: member.id,
        email: member.email ?? payload.email,
        role: 'socio',
        userType: 'member',
        memberId: member.id,
        type: 'access',
      };
    }

    const user = await this.usersService.findById(payload.sub);
    if (!user || user.estado === usuarios_estado.inactivo) {
      throw new UnauthorizedException('Usuario inactivo o no encontrado');
    }

    return {
      sub: user.id,
      email: user.email ?? payload.email,
      role: user.rol ?? payload.role,
      userType: 'staff',
      type: 'access',
    };
  }
}
