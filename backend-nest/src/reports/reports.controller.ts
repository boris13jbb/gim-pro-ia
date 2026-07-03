import { Controller, Get, Header, Query, StreamableFile } from '@nestjs/common';
import {
  ApiBearerAuth,
  ApiOperation,
  ApiProduces,
  ApiTags,
} from '@nestjs/swagger';
import { RawResponse } from '../common/decorators/raw-response.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import { ReportDateRangeQueryDto } from './dto/report-date-range-query.dto';
import { ReportsExportService } from './reports-export.service';
import { ReportsService } from './reports.service';

@ApiTags('reports')
@ApiBearerAuth()
@Controller('reports')
@Roles('admin')
export class ReportsController {
  constructor(
    private readonly reportsService: ReportsService,
    private readonly reportsExportService: ReportsExportService,
  ) {}

  @Get('financial/summary')
  @ApiOperation({ summary: 'KPIs y series para dashboard financiero' })
  getFinancialSummary(@Query() query: ReportDateRangeQueryDto) {
    return this.reportsService.getFinancialSummary(query);
  }

  @Get('financial/movements')
  @ApiOperation({ summary: 'Movimientos financieros del período' })
  getFinancialMovements(@Query() query: ReportDateRangeQueryDto) {
    return this.reportsService.getFinancialMovements(query);
  }

  @Get('financial/export/excel')
  @RawResponse()
  @ApiProduces(
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  )
  @Header(
    'Content-Type',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  )
  @ApiOperation({ summary: 'Exportar reporte financiero a Excel (.xlsx)' })
  async exportFinancialExcel(@Query() query: ReportDateRangeQueryDto) {
    const file = await this.reportsExportService.buildFinancialExcel(query);
    return new StreamableFile(file.buffer, {
      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }

  @Get('financial/export/pdf')
  @RawResponse()
  @ApiProduces('application/pdf')
  @Header('Content-Type', 'application/pdf')
  @ApiOperation({ summary: 'Exportar reporte financiero a PDF' })
  async exportFinancialPdf(@Query() query: ReportDateRangeQueryDto) {
    const file = await this.reportsExportService.buildFinancialPdf(query);
    return new StreamableFile(file.buffer, {
      type: 'application/pdf',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }
}
