import { ApiProperty } from '@nestjs/swagger';
import { IsEnum, IsInt, Min } from 'class-validator';

export enum StockAdjustmentOperation {
  ADD = 'add',
  SUBTRACT = 'subtract',
}

export class AdjustStockDto {
  @ApiProperty({ example: 1 })
  @IsInt()
  @Min(1)
  productId!: number;

  @ApiProperty({ example: 5, description: 'Cantidad entera mayor a 0' })
  @IsInt()
  @Min(1)
  quantity!: number;

  @ApiProperty({ enum: StockAdjustmentOperation, example: 'add' })
  @IsEnum(StockAdjustmentOperation)
  operation!: StockAdjustmentOperation;
}
