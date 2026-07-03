import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsNotEmpty, IsString, MinLength, ValidateIf } from 'class-validator';

export class MemberLoginDto {
  @ApiProperty({
    example: '1234567890',
    description: 'Email o DNI del socio',
  })
  @IsString()
  @IsNotEmpty()
  login: string;

  @ApiProperty({ example: 'MiClave123' })
  @IsString()
  @IsNotEmpty()
  @MinLength(4)
  password: string;
}
