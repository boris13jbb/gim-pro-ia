import { ApiProperty } from '@nestjs/swagger';
import { IsEnum } from 'class-validator';
import { socios_estado } from '@prisma/client';

export class UpdateMemberStatusDto {
  @ApiProperty({ enum: socios_estado })
  @IsEnum(socios_estado)
  estado: socios_estado;
}
