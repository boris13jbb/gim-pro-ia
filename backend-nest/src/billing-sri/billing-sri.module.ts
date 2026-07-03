import { Module } from '@nestjs/common';
import { ElectronicReceiptsController } from './electronic-receipts.controller';
import { ElectronicReceiptsService } from './electronic-receipts.service';
import { SriBillingService } from './sri-billing.service';
import { SriConfigController } from './sri-config.controller';
import { SriConfigService } from './sri-config.service';
import { SriReceiptRepositoryService } from './services/sri-receipt-repository.service';
import { SriRideExportService } from './services/sri-ride-export.service';
import { SriMailService } from './services/sri-mail.service';
import { SriSoapClientService } from './services/sri-soap-client.service';
import { SriTaxCalculatorService } from './services/sri-tax-calculator.service';
import { SriXmlBuilderService } from './services/sri-xml-builder.service';
import { SriXmlSignerService } from './services/sri-xml-signer.service';

@Module({
  controllers: [ElectronicReceiptsController, SriConfigController],
  providers: [
    ElectronicReceiptsService,
    SriConfigService,
    SriBillingService,
    SriReceiptRepositoryService,
    SriTaxCalculatorService,
    SriXmlBuilderService,
    SriXmlSignerService,
    SriSoapClientService,
    SriRideExportService,
    SriMailService,
  ],
  exports: [ElectronicReceiptsService, SriConfigService, SriBillingService],
})
export class BillingSriModule {}
