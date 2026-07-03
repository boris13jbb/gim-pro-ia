import { ForbiddenException } from '@nestjs/common';
import { JwtPayload } from '../../auth/types/jwt-payload.type';

/**
 * Seguridad:
 * Un socio autenticado solo puede acceder a su propio memberId.
 * Staff (admin, recepcionista, entrenador) accede según @Roles del endpoint.
 */
export function assertMemberResourceAccess(
  user: JwtPayload,
  memberId: number,
): void {
  if (user.userType === 'member' && user.memberId !== memberId) {
    throw new ForbiddenException('No puede acceder a datos de otro socio');
  }
}
