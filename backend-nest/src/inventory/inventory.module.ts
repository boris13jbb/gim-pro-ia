import { Module } from '@nestjs/common';
import { InventoryController } from './inventory.controller';
import { InventoryMovementsService } from './inventory-movements.service';
import { InventoryService } from './inventory.service';
import { InventoryStockService } from './inventory-stock.service';

@Module({
  controllers: [InventoryController],
  providers: [
    InventoryService,
    InventoryStockService,
    InventoryMovementsService,
  ],
  exports: [InventoryService, InventoryStockService, InventoryMovementsService],
})
export class InventoryModule {}
