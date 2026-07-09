import { ApiPropertyOptional } from '@nestjs/swagger';
import { IsIn, IsOptional } from 'class-validator';

export class ListAiConversationsQueryDto {
  @ApiPropertyOptional({
    enum: ['active', 'archived'],
    default: 'active',
    description: 'Filtra conversaciones activas o archivadas',
  })
  @IsOptional()
  @IsIn(['active', 'archived'])
  status?: 'active' | 'archived' = 'active';
}
