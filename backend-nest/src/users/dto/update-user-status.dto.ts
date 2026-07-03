import { ApiProperty } from '@nestjs/swagger';
import { IsEnum } from 'class-validator';
import { usuarios_estado } from '@prisma/client';

export class UpdateUserStatusDto {
  @ApiProperty({ enum: usuarios_estado })
  @IsEnum(usuarios_estado)
  estado: usuarios_estado;
}
