import { ApiProperty } from '@nestjs/swagger';
import { IsIn } from 'class-validator';

export class UpdateAiConversationStatusDto {
  @ApiProperty({ enum: ['active', 'archived'] })
  @IsIn(['active', 'archived'])
  status!: 'active' | 'archived';
}
