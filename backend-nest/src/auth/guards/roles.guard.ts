import {
  CanActivate,
  ExecutionContext,
  ForbiddenException,
  Injectable,
} from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { ROLES_KEY } from '../../common/decorators/roles.decorator';
import { AppRole } from '../../common/constants/roles.constant';
import { JwtPayload } from '../types/jwt-payload.type';

/**
 * Protege rutas por rol según decorador @Roles().
 * Un socio no puede acceder a datos de otro socio: eso se valida por endpoint en fases posteriores.
 */
@Injectable()
export class RolesGuard implements CanActivate {
  constructor(private readonly reflector: Reflector) {}

  canActivate(context: ExecutionContext): boolean {
    const requiredRoles = this.reflector.getAllAndOverride<AppRole[]>(ROLES_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);
    if (!requiredRoles?.length) return true;

    const { user } = context.switchToHttp().getRequest<{ user: JwtPayload }>();
    if (!user?.role || !requiredRoles.includes(user.role)) {
      throw new ForbiddenException('No tienes permisos para acceder a esta sección');
    }
    return true;
  }
}
