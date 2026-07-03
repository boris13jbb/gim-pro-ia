import { Body, Controller, Get, Post, Query } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { CashRegistersService } from './cash-registers.service';
import {
  CloseCashRegisterDto,
  OpenCashRegisterDto,
} from './dto/cash-register.dto';
import { ListCashRegistersQueryDto } from './dto/list-cash-registers-query.dto';

@ApiTags('cash-registers')
@ApiBearerAuth()
@Controller('cash-registers')
export class CashRegistersController {
  constructor(private readonly cashRegistersService: CashRegistersService) {}

  @Get('history')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Historial de cajas cerradas' })
  findHistory(@Query() query: ListCashRegistersQueryDto) {
    return this.cashRegistersService.findHistory(query);
  }

  @Get('current/summary')
  @Roles('admin', 'recepcionista')
  @ApiOperation({
    summary:
      'Resumen de caja abierta con totales POS (corrige E01: usa ventas, no suscripciones)',
  })
  getCurrentSummary(@CurrentUser() user: JwtPayload) {
    return this.cashRegistersService.getCurrentSummary(user.sub);
  }

  @Get('current')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Obtener caja abierta del usuario autenticado' })
  getCurrent(@CurrentUser() user: JwtPayload) {
    return this.cashRegistersService.getCurrentOpen(user.sub);
  }

  @Post('open')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Abrir caja (requerido antes de vender)' })
  open(@CurrentUser() user: JwtPayload, @Body() dto: OpenCashRegisterDto) {
    return this.cashRegistersService.open(user.sub, dto);
  }

  @Post('close')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Cerrar caja abierta con cuadre de diferencia' })
  close(@CurrentUser() user: JwtPayload, @Body() dto: CloseCashRegisterDto) {
    return this.cashRegistersService.close(user.sub, dto);
  }
}
