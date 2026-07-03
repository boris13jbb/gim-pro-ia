import { Controller, Get } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { MembersService } from './members.service';

/**
 * Seguridad:
 * Endpoints exclusivos del socio autenticado (app Flutter).
 * El memberId proviene del JWT; no se acepta ID por URL para evitar acceso cruzado.
 */
@ApiTags('members')
@ApiBearerAuth()
@Controller('members')
@Roles('socio')
export class MemberSelfController {
  constructor(private readonly membersService: MembersService) {}

  @Get('me/membership')
  @ApiOperation({
    summary: 'Socio: estado de membresía calculado en backend',
  })
  getMyMembership(@CurrentUser() user: JwtPayload) {
    const memberId = user.memberId ?? user.sub;
    return this.membersService.getMembershipSummary(memberId);
  }

  @Get('me/memberships')
  @ApiOperation({ summary: 'Socio: historial de membresías propias' })
  getMyMembershipHistory(@CurrentUser() user: JwtPayload) {
    const memberId = user.memberId ?? user.sub;
    return this.membersService.listMemberships(memberId);
  }
}
