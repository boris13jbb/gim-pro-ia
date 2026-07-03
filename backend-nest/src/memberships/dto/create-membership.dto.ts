import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { Type } from 'class-transformer';
import {
  IsDateString,
  IsEnum,
  IsInt,
  IsOptional,
  Min,
} from 'class-validator';
import { suscripciones_tipo_comprobante } from '@prisma/client';

export class CreateMembershipDto {
  @ApiProperty({ example: 1, description: 'ID del socio (member)' })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  memberId: number;

  @ApiProperty({ example: 1 })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  planId: number;

  @ApiProperty({ example: '2026-07-02' })
  @IsDateString()
  startDate: string;

  @ApiPropertyOptional({ enum: suscripciones_tipo_comprobante })
  @IsOptional()
  @IsEnum(suscripciones_tipo_comprobante)
  receiptType?: suscripciones_tipo_comprobante;
}
