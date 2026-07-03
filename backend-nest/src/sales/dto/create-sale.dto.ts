import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { ventas_metodo_pago, ventas_tipo_comprobante } from '@prisma/client';
import { Type } from 'class-transformer';
import {
  ArrayMinSize,
  IsArray,
  IsEnum,
  IsInt,
  IsNumber,
  IsOptional,
  IsString,
  MaxLength,
  Min,
  ValidateNested,
} from 'class-validator';

export class CreateSaleItemDto {
  @ApiProperty({ example: 1 })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  productId!: number;

  @ApiProperty({ example: 2 })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  quantity!: number;
}

export class CreateSaleDto {
  @ApiProperty({ type: [CreateSaleItemDto] })
  @IsArray()
  @ArrayMinSize(1)
  @ValidateNested({ each: true })
  @Type(() => CreateSaleItemDto)
  items!: CreateSaleItemDto[];

  @ApiPropertyOptional({ example: 1 })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  memberId?: number;

  @ApiPropertyOptional({ example: 0, default: 0 })
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  discount?: number;

  @ApiPropertyOptional({ enum: ventas_metodo_pago, default: 'efectivo' })
  @IsOptional()
  @IsEnum(ventas_metodo_pago)
  paymentMethod?: ventas_metodo_pago;

  @ApiPropertyOptional({ enum: ventas_tipo_comprobante, default: 'boleta' })
  @IsOptional()
  @IsEnum(ventas_tipo_comprobante)
  receiptType?: ventas_tipo_comprobante;

  @ApiPropertyOptional({ example: '05' })
  @IsOptional()
  @IsString()
  @MaxLength(1)
  clientDocumentType?: string;

  @ApiPropertyOptional({ example: '1234567890' })
  @IsOptional()
  @IsString()
  @MaxLength(20)
  clientDocumentNumber?: string;

  @ApiPropertyOptional({ example: 'Consumidor final' })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  clientName?: string;

  @ApiPropertyOptional({ example: 'Av. Principal 123' })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  clientAddress?: string;
}
