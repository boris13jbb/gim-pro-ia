import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsEnum, IsOptional, IsString, MaxLength } from 'class-validator';
import { categorias_estado } from '@prisma/client';

export class UpdateCategoryDto {
  @ApiPropertyOptional({ example: 'Bebidas energéticas' })
  @IsOptional()
  @IsString()
  @MaxLength(100)
  name?: string;
}

export class UpdateCategoryStatusDto {
  @ApiPropertyOptional({ enum: categorias_estado, example: 'activo' })
  @IsEnum(categorias_estado)
  status!: categorias_estado;
}

export class ListCategoriesQueryDto {
  @ApiPropertyOptional({ enum: categorias_estado })
  @IsOptional()
  @IsEnum(categorias_estado)
  status?: categorias_estado;
}
