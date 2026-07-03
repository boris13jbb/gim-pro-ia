import { ApiProperty } from '@nestjs/swagger';
import { IsNotEmpty, IsString, MaxLength } from 'class-validator';

export class ValidateAttendanceAccessDto {
  @ApiProperty({ example: '1234567890', description: 'DNI del socio' })
  @IsString()
  @IsNotEmpty()
  @MaxLength(20)
  dni: string;
}
