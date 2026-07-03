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
import {
  ApiBearerAuth,
  ApiOperation,
  ApiProduces,
  ApiTags,
} from '@nestjs/swagger';
import { RawResponse } from '../common/decorators/raw-response.decorator';
import { CurrentUser } from '../common/decorators/current-user.decorator';
import { Roles } from '../common/decorators/roles.decorator';
import type { JwtPayload } from '../auth/types/jwt-payload.type';
import { IssueCreditNoteDto } from './dto/issue-credit-note.dto';
import { ListElectronicReceiptsQueryDto } from './dto/list-electronic-receipts-query.dto';
import { SendReceiptEmailDto } from './dto/send-receipt-email.dto';
import { ElectronicReceiptsService } from './electronic-receipts.service';
import { SriBillingService } from './sri-billing.service';
import { SriMailService } from './services/sri-mail.service';
import { SriRideExportService } from './services/sri-ride-export.service';

@ApiTags('electronic-receipts')
@ApiBearerAuth()
@Controller('electronic-receipts')
@Roles('admin', 'recepcionista')
export class ElectronicReceiptsController {
  constructor(
    private readonly electronicReceiptsService: ElectronicReceiptsService,
    private readonly sriBillingService: SriBillingService,
    private readonly rideExportService: SriRideExportService,
    private readonly mailService: SriMailService,
  ) {}

  @Post('issue/membership/:membershipId')
  @ApiOperation({ summary: 'Emitir factura SRI desde una suscripción' })
  issueFromMembership(
    @Param('membershipId', ParseIntPipe) membershipId: number,
    @CurrentUser() user: JwtPayload,
  ) {
    return this.sriBillingService.issueFromMembership(membershipId, user.sub);
  }

  @Post('issue/sale/:saleId')
  @ApiOperation({ summary: 'Emitir factura SRI desde una venta POS' })
  issueFromSale(
    @Param('saleId', ParseIntPipe) saleId: number,
    @CurrentUser() user: JwtPayload,
  ) {
    return this.sriBillingService.issueFromSale(saleId, user.sub);
  }

  @Get()
  @ApiOperation({ summary: 'Bandeja de comprobantes electrónicos SRI' })
  findAll(@Query() query: ListElectronicReceiptsQueryDto) {
    return this.electronicReceiptsService.findAll(query);
  }

  @Post(':id/credit-note')
  @ApiOperation({ summary: 'Emitir nota de crédito sobre factura autorizada' })
  issueCreditNote(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: IssueCreditNoteDto,
    @CurrentUser() user: JwtPayload,
  ) {
    return this.sriBillingService.issueCreditNote(id, dto, user.sub);
  }

  @Post(':id/retry')
  @ApiOperation({ summary: 'Reconsultar autorización SRI del comprobante' })
  retryAuthorization(@Param('id', ParseIntPipe) id: number) {
    return this.sriBillingService.retryAuthorization(id);
  }

  @Get(':id/xml')
  @RawResponse()
  @ApiProduces('application/xml')
  @Header('Content-Type', 'application/xml')
  @ApiOperation({
    summary: 'Descargar XML firmado o autorizado del comprobante',
  })
  async downloadXml(@Param('id', ParseIntPipe) id: number) {
    const file = await this.electronicReceiptsService.getXmlDownload(id);
    return new StreamableFile(Buffer.from(file.xml, 'utf8'), {
      type: 'application/xml',
      disposition: `attachment; filename="${file.filename}"`,
    });
  }

  @Get(':id/pdf')
  @RawResponse()
  @ApiProduces('application/pdf')
  @Header('Content-Type', 'application/pdf')
  @ApiOperation({ summary: 'Descargar RIDE (PDF) del comprobante electrónico' })
  async downloadPdf(@Param('id', ParseIntPipe) id: number) {
    const file = await this.rideExportService.buildRidePdf(id);
    return new StreamableFile(file.buffer, {
      type: 'application/pdf',
      disposition: `inline; filename="${file.filename}"`,
    });
  }

  @Post(':id/send-email')
  @ApiOperation({ summary: 'Enviar RIDE (PDF) y XML al correo del cliente' })
  sendEmail(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: SendReceiptEmailDto,
  ) {
    return this.mailService.sendReceiptToCustomer(id, dto.email);
  }

  @Get(':id/logs')
  @ApiOperation({ summary: 'Logs técnicos SRI del comprobante' })
  getLogs(@Param('id', ParseIntPipe) id: number) {
    return this.electronicReceiptsService.getLogs(id);
  }

  @Get(':id')
  @ApiOperation({ summary: 'Detalle de comprobante con líneas' })
  findOne(@Param('id', ParseIntPipe) id: number) {
    return this.electronicReceiptsService.findOne(id);
  }
}
