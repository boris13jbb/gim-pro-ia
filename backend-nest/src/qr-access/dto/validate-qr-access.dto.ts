import { ApiProperty } from '@nestjs/swagger';
import { IsNotEmpty, IsString, MaxLength } from 'class-validator';

export class ValidateQrAccessDto {
  @ApiProperty({
    example: '1234567890',
    description: 'Contenido del QR del carnet (en PHP legacy es el DNI)',
  })
  @IsString()
  @IsNotEmpty()
  @MaxLength(64)
  qrPayload: string;
}
