import { Controller, Post } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { MembershipAlertsService } from './membership-alerts.service';

/**
 * Endpoint manual para probar alertas sin esperar al cron (solo admin).
 */
@ApiTags('membership-alerts')
@ApiBearerAuth()
@Controller('membership-alerts')
export class MembershipAlertsController {
  constructor(private readonly alertsService: MembershipAlertsService) {}

  @Post('run')
  @Roles('admin')
  @ApiOperation({
    summary: 'Ejecutar alertas de membresía por vencer (manual, admin)',
  })
  async runNow() {
    return this.alertsService.runDailyAlerts();
  }
}
