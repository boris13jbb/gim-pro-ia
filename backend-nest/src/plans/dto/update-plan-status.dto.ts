import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsEnum, IsOptional } from 'class-validator';
import { planes_estado } from '@prisma/client';

export class UpdatePlanStatusDto {
  @ApiProperty({ enum: planes_estado })
  @IsEnum(planes_estado)
  estado: planes_estado;
}

export class ListPlansQueryDto {
  @ApiPropertyOptional({ enum: planes_estado })
  @IsOptional()
  @IsEnum(planes_estado)
  estado?: planes_estado;
}
