import { Module } from '@nestjs/common';
import { CashRegistersModule } from '../cash-registers/cash-registers.module';
import { InventoryModule } from '../inventory/inventory.module';
import { ReportsModule } from '../reports/reports.module';
import { SalesController } from './sales.controller';
import { SalesTicketExportService } from './sales-ticket-export.service';
import { SalesService } from './sales.service';

@Module({
  imports: [CashRegistersModule, InventoryModule, ReportsModule],
  controllers: [SalesController],
  providers: [SalesService, SalesTicketExportService],
  exports: [SalesService],
})
export class SalesModule {}
