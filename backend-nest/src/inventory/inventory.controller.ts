import { Body, Controller, Get, Param, ParseIntPipe, Post, Query } from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiTags } from '@nestjs/swagger';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { AdjustStockDto } from './dto/adjust-stock.dto';
import { ListInventoryMovementsQueryDto } from './dto/list-inventory-movements-query.dto';
import { InventoryMovementsService } from './inventory-movements.service';
import { InventoryService } from './inventory.service';

@ApiTags('inventory')
@ApiBearerAuth()
@Controller('inventory')
export class InventoryController {
  constructor(
    private readonly inventoryService: InventoryService,
    private readonly inventoryMovementsService: InventoryMovementsService,
  ) {}

  @Get('movements')
  @Roles('admin')
  @ApiOperation({ summary: 'Historial auditado de movimientos de inventario' })
  findMovements(@Query() query: ListInventoryMovementsQueryDto) {
    return this.inventoryMovementsService.findAll(query);
  }

  @Get('products/:productId/movements')
  @Roles('admin')
  @ApiOperation({ summary: 'Movimientos de inventario de un producto' })
  findProductMovements(
    @Param('productId', ParseIntPipe) productId: number,
    @Query() query: ListInventoryMovementsQueryDto,
  ) {
    return this.inventoryMovementsService.findByProduct(productId, query);
  }

  @Post('adjustments')
  @Roles('admin')
  @ApiOperation({
    summary: 'Ajuste manual de stock (sumar/restar). No permite stock negativo.',
  })
  adjustStock(
    @Body() dto: AdjustStockDto,
    @CurrentUser() user: JwtPayload,
  ) {
    return this.inventoryService.adjustStock(dto, user.sub);
  }
}
