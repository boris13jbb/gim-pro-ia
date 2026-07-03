import {

  Injectable,

  UnauthorizedException,

} from '@nestjs/common';

import { ConfigService } from '@nestjs/config';

import { JwtService } from '@nestjs/jwt';

import type { Request } from 'express';

import { socios_estado } from '@prisma/client';

import { UsersService } from '../users/users.service';

import { MembersService } from '../members/members.service';

import { RefreshTokenService } from './services/refresh-token.service';

import { LoginDto } from './dto/login.dto';

import { MemberLoginDto } from './dto/member-login.dto';

import type { JwtPayload } from './types/jwt-payload.type';

import { TokenPairResponse } from './types/auth-response.type';

import { createJti } from './utils/token.util';

import type { AppRole } from '../common/constants/roles.constant';



@Injectable()

export class AuthService {

  constructor(

    private readonly usersService: UsersService,

    private readonly membersService: MembersService,

    private readonly jwtService: JwtService,

    private readonly refreshTokenService: RefreshTokenService,

    private readonly config: ConfigService,

  ) {}



  async login(dto: LoginDto, req: Request): Promise<TokenPairResponse> {

    const result = await this.usersService.validateStaffCredentials(

      dto.email,

      dto.password,

    );



    if (result.kind === 'inactive') {

      throw new UnauthorizedException(

        'Cuenta inhabilitada. Contacte al administrador.',

      );

    }

    if (result.kind === 'invalid') {

      throw new UnauthorizedException('Correo o contraseña incorrectos.');

    }



    return this.issueStaffTokenPair(result.user, req);

  }



  async memberLogin(dto: MemberLoginDto, req: Request): Promise<TokenPairResponse> {

    const result = await this.membersService.validateMemberCredentials(

      dto.login,

      dto.password,

    );



    if (result.kind === 'inactive') {

      throw new UnauthorizedException(

        'Cuenta inhabilitada. Contacte al administrador.',

      );

    }

    if (result.kind === 'invalid') {

      throw new UnauthorizedException('Credenciales incorrectas.');

    }



    return this.issueMemberTokenPair(result.member, req);

  }



  async refresh(

    user: JwtPayload & { refreshToken: string },

    req: Request,

  ): Promise<TokenPairResponse> {

    const record = await this.refreshTokenService.validateToken(

      user.jti!,

      user.refreshToken,

    );

    if (!record) {

      throw new UnauthorizedException('Refresh token revocado o inválido');

    }



    if (user.userType === 'member') {

      const member = await this.membersService.findById(user.memberId ?? user.sub);

      if (!member) {

        throw new UnauthorizedException('Socio no encontrado');

      }

      if (member.estado === socios_estado.inactivo) {

        throw new UnauthorizedException('Cuenta inhabilitada.');

      }



      await this.refreshTokenService.revokeByJti(user.jti!);

      return this.issueMemberTokenPair(member, req);

    }



    const dbUser = await this.usersService.findById(user.sub);

    if (!dbUser) {

      throw new UnauthorizedException('Usuario no encontrado');

    }



    await this.refreshTokenService.revokeByJti(user.jti!);

    return this.issueStaffTokenPair(dbUser, req);

  }



  async logout(user: JwtPayload & { refreshToken?: string }) {

    if (user.jti && user.refreshToken) {

      await this.refreshTokenService.validateToken(user.jti, user.refreshToken);

      await this.refreshTokenService.revokeByJti(user.jti);

    }

    return { message: 'Sesión cerrada correctamente' };

  }



  async me(user: JwtPayload) {

    if (user.userType === 'member') {

      const member = await this.membersService.findById(user.memberId ?? user.sub);

      if (!member) {

        throw new UnauthorizedException('Socio no encontrado');

      }

      return this.membersService.toPublicMember(member);

    }



    const dbUser = await this.usersService.findById(user.sub);

    if (!dbUser) {

      throw new UnauthorizedException('Usuario no encontrado');

    }

    return this.usersService.toPublicUser(dbUser);

  }



  private async issueStaffTokenPair(

    user: {

      id: number;

      nombre: string | null;

      email: string | null;

      rol: string | null;

      estado: string | null;

    },

    req: Request,

  ): Promise<TokenPairResponse> {

    const payload: JwtPayload = {

      sub: user.id,

      email: user.email ?? '',

      role: (user.rol ?? 'recepcionista') as AppRole,

      userType: 'staff',

      type: 'access',

    };



    const accessExpiresIn =

      this.config.get<string>('JWT_ACCESS_EXPIRES_IN') ?? '15m';

    const accessToken = this.jwtService.sign(payload, {

      secret: this.config.getOrThrow<string>('JWT_ACCESS_SECRET'),

      expiresIn: accessExpiresIn as `${number}${'s' | 'm' | 'h' | 'd'}`,

    });



    const jti = createJti();

    const refreshExpiresIn =

      this.config.get<string>('JWT_REFRESH_EXPIRES_IN') ?? '7d';

    const refreshToken = this.jwtService.sign(

      {

        sub: user.id,

        email: user.email ?? '',

        role: (user.rol ?? 'recepcionista') as AppRole,

        userType: 'staff',

        type: 'refresh',

        jti,

      } satisfies JwtPayload,

      {

        secret: this.config.getOrThrow<string>('JWT_REFRESH_SECRET'),

        expiresIn: refreshExpiresIn as `${number}${'s' | 'm' | 'h' | 'd'}`,

      },

    );



    const expiresAt = new Date(

      Date.now() + this.parseDurationToMs(refreshExpiresIn),

    );



    await this.refreshTokenService.store(

      {

        userType: 'staff',

        userId: user.id,

        refreshToken,

        expiresAt,

        userAgent: req.headers['user-agent'],

        ip: req.ip,

      },

      jti,

    );



    return {

      accessToken,

      refreshToken,

      user: this.usersService.toPublicUser(user),

    };

  }



  private async issueMemberTokenPair(

    member: {

      id: number;

      nombre: string;

      email?: string | null;

      dni: string;

      telefono?: string | null;

      estado?: socios_estado | null;

      foto?: string | null;

      fecha_registro?: Date | null;

    },

    req: Request,

  ): Promise<TokenPairResponse> {

    const payload: JwtPayload = {

      sub: member.id,

      email: member.email ?? '',

      role: 'socio',

      userType: 'member',

      memberId: member.id,

      type: 'access',

    };



    const accessExpiresIn =

      this.config.get<string>('JWT_ACCESS_EXPIRES_IN') ?? '15m';

    const accessToken = this.jwtService.sign(payload, {

      secret: this.config.getOrThrow<string>('JWT_ACCESS_SECRET'),

      expiresIn: accessExpiresIn as `${number}${'s' | 'm' | 'h' | 'd'}`,

    });



    const jti = createJti();

    const refreshExpiresIn =

      this.config.get<string>('JWT_REFRESH_EXPIRES_IN') ?? '7d';

    const refreshToken = this.jwtService.sign(

      {

        sub: member.id,

        email: member.email ?? '',

        role: 'socio',

        userType: 'member',

        memberId: member.id,

        type: 'refresh',

        jti,

      } satisfies JwtPayload,

      {

        secret: this.config.getOrThrow<string>('JWT_REFRESH_SECRET'),

        expiresIn: refreshExpiresIn as `${number}${'s' | 'm' | 'h' | 'd'}`,

      },

    );



    const expiresAt = new Date(

      Date.now() + this.parseDurationToMs(refreshExpiresIn),

    );



    await this.refreshTokenService.store(

      {

        userType: 'member',

        memberId: member.id,

        refreshToken,

        expiresAt,

        userAgent: req.headers['user-agent'],

        ip: req.ip,

      },

      jti,

    );



    return {

      accessToken,

      refreshToken,

      user: this.membersService.toPublicMember(member),

    };

  }



  private parseDurationToMs(value: string): number {

    const match = /^(\d+)([smhd])$/.exec(value.trim());

    if (!match) return 7 * 24 * 60 * 60 * 1000;

    const amount = Number(match[1]);

    const unit = match[2];

    const multipliers: Record<string, number> = {

      s: 1000,

      m: 60_000,

      h: 3_600_000,

      d: 86_400_000,

    };

    return amount * (multipliers[unit] ?? 86_400_000);

  }

}

