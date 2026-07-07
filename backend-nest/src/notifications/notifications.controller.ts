import {
  Controller,
  ForbiddenException,
  Get,
  Param,
  ParseIntPipe,
  Patch,
  Query,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { ListNotificationsQueryDto } from './dto/list-notifications-query.dto';
import { NotificationsService } from './notifications.service';

/**
 * Seguridad:
 * Solo el socio autenticado accede a sus notificaciones.
 * El memberId proviene del JWT; no se aceptan IDs ajenos.
 */
@ApiTags('notifications')
@ApiBearerAuth()
@Controller('notifications')
@Roles('socio')
export class NotificationsController {
  constructor(private readonly notificationsService: NotificationsService) {}

  @Get()
  @ApiOperation({ summary: 'Socio: historial de notificaciones persistidas' })
  listMine(
    @CurrentUser() user: JwtPayload,
    @Query() query: ListNotificationsQueryDto,
  ) {
    const memberId = this.resolveMemberId(user);
    return this.notificationsService.listForMember(memberId, query.limit);
  }

  @Get('unread-count')
  @ApiOperation({ summary: 'Socio: contador de notificaciones no leídas' })
  async unreadCount(@CurrentUser() user: JwtPayload) {
    const memberId = this.resolveMemberId(user);
    const count = await this.notificationsService.countUnread(memberId);
    return { unreadCount: count };
  }

  @Patch('read-all')
  @ApiOperation({
    summary: 'Socio: marcar todas las notificaciones como leídas',
  })
  markAllRead(@CurrentUser() user: JwtPayload) {
    const memberId = this.resolveMemberId(user);
    return this.notificationsService.markAllAsRead(memberId);
  }

  @Patch(':id/read')
  @ApiOperation({ summary: 'Socio: marcar una notificación como leída' })
  markRead(
    @CurrentUser() user: JwtPayload,
    @Param('id', ParseIntPipe) id: number,
  ) {
    const memberId = this.resolveMemberId(user);
    return this.notificationsService.markAsRead(memberId, id);
  }

  private resolveMemberId(user: JwtPayload): number {
    const memberId = user.memberId ?? user.sub;
    if (!memberId) {
      throw new ForbiddenException('Token de socio inválido');
    }
    return memberId;
  }
}
