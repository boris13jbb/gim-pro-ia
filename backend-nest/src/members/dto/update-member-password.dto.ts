import { ApiProperty } from '@nestjs/swagger';
import { IsNotEmpty, IsString, MinLength } from 'class-validator';

export class UpdateMemberPasswordDto {
  @ApiProperty({ example: 'NuevaClave123' })
  @IsString()
  @IsNotEmpty()
  @MinLength(6)
  password: string;
}
