import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Type } from 'class-transformer';
import {
  IsDateString,
  IsNumber,
  IsOptional,
  Min,
} from 'class-validator';

export class CreateBodyMeasurementDto {
  @ApiProperty({ example: '2026-07-02' })
  @IsDateString()
  measuredAt: string;

  @ApiPropertyOptional({ example: 78.5 })
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  weight?: number;

  @ApiPropertyOptional({ example: 18.2, description: 'Porcentaje de grasa corporal' })
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  bodyFat?: number;

  @ApiPropertyOptional({ example: 82 })
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  waist?: number;

  @ApiPropertyOptional({ example: 34 })
  @IsOptional()
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  arm?: number;
}
