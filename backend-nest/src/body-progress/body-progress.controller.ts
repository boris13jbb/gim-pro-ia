import {
  Body,
  Controller,
  Delete,
  Get,
  HttpCode,
  HttpStatus,
  Param,
  ParseIntPipe,
  Post,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { assertMemberResourceAccess } from '../common/utils/member-access.util';
import { BodyProgressService } from './body-progress.service';
import { CreateBodyMeasurementDto } from './dto/create-body-measurement.dto';

@ApiTags('body-progress')
@ApiBearerAuth()
@Controller('body-progress')
export class BodyProgressController {
  constructor(private readonly bodyProgressService: BodyProgressService) {}

  @Get('me')
  @Roles('socio')
  @ApiOperation({
    summary: 'Socio: historial y gráficos de su progreso físico',
  })
  getMyProgress(@CurrentUser() user: JwtPayload) {
    const memberId = user.memberId ?? user.sub;
    return this.bodyProgressService.listMemberMeasurements(memberId);
  }

  @Get('members/:memberId/measurements')
  @Roles('admin', 'recepcionista', 'entrenador', 'socio')
  @ApiOperation({
    summary: 'Historial de medidas corporales con datos para gráficos',
  })
  listMeasurements(
    @Param('memberId', ParseIntPipe) memberId: number,
    @CurrentUser() user: JwtPayload,
  ) {
    assertMemberResourceAccess(user, memberId);
    return this.bodyProgressService.listMemberMeasurements(memberId);
  }

  @Get('measurements/:id')
  @Roles('admin', 'recepcionista', 'entrenador', 'socio')
  @ApiOperation({ summary: 'Obtener medida corporal por ID' })
  async getMeasurement(
    @Param('id', ParseIntPipe) id: number,
    @CurrentUser() user: JwtPayload,
  ) {
    const measurement = await this.bodyProgressService.getMeasurement(id);
    assertMemberResourceAccess(user, measurement.memberId);
    return measurement;
  }

  @Post('members/:memberId/measurements')
  @HttpCode(HttpStatus.CREATED)
  @Roles('admin', 'entrenador')
  @ApiOperation({ summary: 'Registrar nueva medida corporal' })
  createMeasurement(
    @Param('memberId', ParseIntPipe) memberId: number,
    @Body() dto: CreateBodyMeasurementDto,
  ) {
    return this.bodyProgressService.createMeasurement(memberId, dto);
  }

  @Delete('measurements/:id')
  @Roles('admin', 'entrenador')
  @ApiOperation({
    summary:
      'Eliminar medida corporal (conserva historial en auditoría futura)',
  })
  async deleteMeasurement(@Param('id', ParseIntPipe) id: number) {
    return this.bodyProgressService.deleteMeasurement(id);
  }
}
