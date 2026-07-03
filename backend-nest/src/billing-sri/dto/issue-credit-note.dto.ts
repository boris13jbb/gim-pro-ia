import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsOptional, IsString, MaxLength } from 'class-validator';

export class IssueCreditNoteDto {
  @ApiPropertyOptional({ example: '01', default: '01' })
  @IsOptional()
  @IsString()
  @MaxLength(2)
  reasonCode?: string;

  @ApiPropertyOptional({ example: 'Anulación de la operación' })
  @IsOptional()
  @IsString()
  @MaxLength(250)
  reasonDescription?: string;
}
