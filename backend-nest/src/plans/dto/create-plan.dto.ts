import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Type } from 'class-transformer';
import {
  IsInt,
  IsNotEmpty,
  IsNumber,
  IsOptional,
  IsString,
  MaxLength,
  Min,
} from 'class-validator';

export class CreatePlanDto {
  @ApiProperty({ example: 'Mensual' })
  @IsString()
  @IsNotEmpty()
  @MaxLength(50)
  nombre: string;

  @ApiProperty({ example: 35.5 })
  @Type(() => Number)
  @IsNumber({ maxDecimalPlaces: 2 })
  @Min(0)
  precio: number;

  @ApiProperty({ example: 30, description: 'Duración en días' })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  duracionDias: number;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  descripcion?: string;
}
