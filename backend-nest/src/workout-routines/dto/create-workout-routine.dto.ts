import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsString, MaxLength } from 'class-validator';

export class CreateWorkoutRoutineDto {
  @ApiPropertyOptional({ example: 'Press banca 4x12, fondos 3x10' })
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day1?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day2?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day3?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day4?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day5?: string;

  @ApiPropertyOptional()
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  day6?: string;

  @ApiPropertyOptional({ example: 'Enfocarse en técnica y descanso 90s' })
  @IsOptional()
  @IsString()
  @MaxLength(5000)
  notes?: string;
}
