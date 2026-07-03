import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsEmail, IsOptional } from 'class-validator';

export class SendReceiptEmailDto {
  @ApiPropertyOptional({
    description: 'Correo alternativo si el cliente no tiene email en BD',
    example: 'cliente@ejemplo.com',
  })
  @IsOptional()
  @IsEmail()
  email?: string;
}
