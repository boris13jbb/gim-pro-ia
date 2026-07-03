import {
  Body,
  Controller,
  Get,
  Post,
  Req,
  UseGuards,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Throttle } from '@nestjs/throttler';
import type { Request } from 'express';
import { AuthService } from './auth.service';
import { LoginDto } from './dto/login.dto';
import { MemberLoginDto } from './dto/member-login.dto';
import { RefreshTokenDto } from './dto/refresh-token.dto';
import { Public } from '../common/decorators/public.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import { JwtRefreshGuard } from './guards/jwt-refresh.guard';
import type { JwtPayload } from './types/jwt-payload.type';
import type { AppRole } from '../common/constants/roles.constant';
import { getThrottleConfig } from '../config/throttle.config';

const loginThrottle = getThrottleConfig().login;

@ApiTags('auth')
@Controller('auth')
export class AuthController {
  constructor(private readonly authService: AuthService) {}

  @Public()
  @Post('login')
  @Throttle({ default: { limit: loginThrottle.limit, ttl: loginThrottle.ttl } })
  @ApiOperation({ summary: 'Iniciar sesión staff (email + contraseña)' })
  login(@Body() dto: LoginDto, @Req() req: Request) {
    return this.authService.login(dto, req);
  }

  @Public()
  @Post('member/login')
  @Throttle({ default: { limit: loginThrottle.limit, ttl: loginThrottle.ttl } })
  @ApiOperation({ summary: 'Iniciar sesión socio (email o DNI + contraseña)' })
  memberLogin(@Body() dto: MemberLoginDto, @Req() req: Request) {
    return this.authService.memberLogin(dto, req);
  }

  @Public()
  @UseGuards(JwtRefreshGuard)
  @Post('refresh')
  @ApiOperation({ summary: 'Renovar access token con refresh token (rotación)' })
  refresh(@CurrentUser() user: JwtPayload & { refreshToken: string }, @Req() req: Request) {
    return this.authService.refresh(user, req);
  }

  @Public()
  @UseGuards(JwtRefreshGuard)
  @Post('logout')
  @ApiOperation({ summary: 'Cerrar sesión e invalidar refresh token' })
  logout(@CurrentUser() user: JwtPayload & { refreshToken: string }, @Body() _dto: RefreshTokenDto) {
    return this.authService.logout(user);
  }

  @Get('me')
  @ApiBearerAuth()
  @ApiOperation({ summary: 'Perfil del usuario autenticado' })
  me(@CurrentUser() user: JwtPayload) {
    return this.authService.me(user);
  }
}
