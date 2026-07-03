import {
  Body,
  Controller,
  Get,
  HttpCode,
  HttpStatus,
  Param,
  ParseIntPipe,
  Post,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { QrAccessService } from './qr-access.service';
import { ValidateQrAccessDto } from './dto/validate-qr-access.dto';

@ApiTags('qr-access')
@ApiBearerAuth()
@Controller('qr-access')
export class QrAccessController {
  constructor(private readonly qrAccessService: QrAccessService) {}

  @Post('validate')
  @HttpCode(HttpStatus.OK)
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({
    summary: 'Validar acceso leyendo payload QR (DNI del carnet)',
  })
  validate(@Body() dto: ValidateQrAccessDto) {
    return this.qrAccessService.validateQrPayload(dto.qrPayload);
  }

  @Get('members/:id/card')
  @Roles('admin', 'recepcionista', 'entrenador')
  @ApiOperation({
    summary: 'Datos para generar carnet digital con QR (sin PDF en API)',
  })
  getMemberCard(@Param('id', ParseIntPipe) id: number) {
    return this.qrAccessService.getMemberCardData(id);
  }
}
