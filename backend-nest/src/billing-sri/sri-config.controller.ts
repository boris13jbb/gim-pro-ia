import { Controller, Get } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { SriConfigService } from './sri-config.service';

@ApiTags('sri-config')
@ApiBearerAuth()
@Controller('sri-config')
@Roles('admin')
export class SriConfigController {
  constructor(private readonly sriConfigService: SriConfigService) {}

  @Get()
  @ApiOperation({
    summary: 'Configuración fiscal SRI (sin secretos ni claves de certificado)',
  })
  getConfig() {
    return this.sriConfigService.getPublicConfig();
  }
}
