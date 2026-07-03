import { ApiPropertyOptional } from '@nestjs/swagger';
import { Type } from 'class-transformer';
import { IsDateString, IsEnum, IsInt, IsOptional, IsString, Min } from 'class-validator';
import { comprobantes_electronicos_estado_sri } from '@prisma/client';
import { firstDayOfCurrentMonth, todayDateString } from '../../common/utils/date.util';

export class ListElectronicReceiptsQueryDto {
  @ApiPropertyOptional({ example: firstDayOfCurrentMonth() })
  @IsOptional()
  @IsDateString()
  fromDate?: string;

  @ApiPropertyOptional({ example: todayDateString() })
  @IsOptional()
  @IsDateString()
  toDate?: string;

  @ApiPropertyOptional({ example: '01', description: '01 Factura, 04 Nota de crédito' })
  @IsOptional()
  @IsString()
  documentType?: string;

  @ApiPropertyOptional({ enum: comprobantes_electronicos_estado_sri })
  @IsOptional()
  @IsEnum(comprobantes_electronicos_estado_sri)
  status?: comprobantes_electronicos_estado_sri;

  @ApiPropertyOptional({ example: 100, default: 100 })
  @IsOptional()
  @Type(() => Number)
  @IsInt()
  @Min(1)
  limit?: number;
}
