import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import {
  IsEmail,
  IsEnum,
  IsNotEmpty,
  IsOptional,
  IsString,
  MaxLength,
  MinLength,
} from 'class-validator';
import { socios_estado } from '@prisma/client';

export class CreateMemberDto {
  @ApiProperty({ example: 'Juan Pérez' })
  @IsString()
  @IsNotEmpty()
  @MaxLength(100)
  nombre: string;

  @ApiProperty({ example: '1234567890' })
  @IsString()
  @IsNotEmpty()
  @MaxLength(20)
  dni: string;

  @ApiPropertyOptional({ example: 'juan@email.com' })
  @IsOptional()
  @IsEmail()
  @MaxLength(100)
  email?: string;

  @ApiPropertyOptional({ example: '0999999999' })
  @IsOptional()
  @IsString()
  @MaxLength(20)
  telefono?: string;

  @ApiPropertyOptional({ enum: socios_estado, default: 'activo' })
  @IsOptional()
  @IsEnum(socios_estado)
  estado?: socios_estado;

  @ApiPropertyOptional({
    description: 'Nombre de archivo foto (sin upload en Fase 03)',
  })
  @IsOptional()
  @IsString()
  @MaxLength(255)
  foto?: string;

  @ApiPropertyOptional({
    description: 'Contraseña para app móvil del socio (opcional al crear)',
    minLength: 6,
  })
  @IsOptional()
  @IsString()
  @MinLength(6)
  password?: string;
}
