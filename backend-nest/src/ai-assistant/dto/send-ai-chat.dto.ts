import { ApiProperty, ApiPropertyOptional } from '@nestjs/swagger';
import { IsInt, IsOptional, IsString, MaxLength, MinLength } from 'class-validator';

export class SendAiChatDto {
  @ApiProperty({
    example: '¿Cuántas veces fui al gimnasio este mes?',
    description: 'Mensaje del socio al asistente',
  })
  @IsString()
  @MinLength(1)
  @MaxLength(4000)
  message!: string;

  @ApiPropertyOptional({
    example: 1,
    description: 'Conversación existente; si se omite se crea una nueva',
  })
  @IsOptional()
  @IsInt()
  conversationId?: number;
}
