import {
  Body,
  Controller,
  Get,
  Header,
  Param,
  ParseIntPipe,
  Post,
  Query,
  StreamableFile,
} from '@nestjs/common';
import { ApiBearerAuth, ApiOperation, ApiProduces, ApiTags } from '@nestjs/swagger';
import { RawResponse } from '../common/decorators/raw-response.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { CreateSaleDto } from './dto/create-sale.dto';
import { ListSalesQueryDto } from './dto/list-sales-query.dto';
import { SalesTicketExportService } from './sales-ticket-export.service';
import { SalesService } from './sales.service';

@ApiTags('sales')
@ApiBearerAuth()
@Controller('sales')
export class SalesController {
  constructor(
    private readonly salesService: SalesService,
    private readonly salesTicketExportService: SalesTicketExportService,
  ) {}

  @Get()
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Historial de ventas POS con filtros' })
  findAll(@Query() query: ListSalesQueryDto) {
    return this.salesService.findAll(query);
  }

  @Get(':id/ticket')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Datos de ticket para impresión o PDF (Fase 08)' })
  getTicket(@Param('id', ParseIntPipe) id: number) {
    return this.salesService.getTicket(id);
  }

  @Get(':id/ticket/export/pdf')
  @RawResponse()
  @ApiProduces('application/pdf')
  @Header('Content-Type', 'application/pdf')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Exportar ticket de venta a PDF (80mm)' })
  async exportTicketPdf(@Param('id', ParseIntPipe) id: number) {
    const file = await this.salesTicketExportService.buildTicketPdf(id);
    return new StreamableFile(file.buffer, {
      type: 'application/pdf',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }

  @Get(':id')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Detalle de venta con ítems' })
  findOne(@Param('id', ParseIntPipe) id: number) {
    return this.salesService.findOne(id);
  }

  @Post()
  @Roles('admin', 'recepcionista')
  @ApiOperation({
    summary:
      'Registrar venta POS con descuento de stock auditado (requiere caja abierta)',
  })
  create(@Body() dto: CreateSaleDto, @CurrentUser() user: JwtPayload) {
    return this.salesService.create(dto, user.sub);
  }
}
