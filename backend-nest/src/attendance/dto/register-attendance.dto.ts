import { ApiProperty } from '@nestjs/swagger';
import { Type } from 'class-transformer';
import { IsEnum, IsInt, Min } from 'class-validator';
import { ATTENDANCE_METHODS } from '../constants/attendance-method.constant';

export class RegisterAttendanceDto {
  @ApiProperty({ example: 3 })
  @Type(() => Number)
  @IsInt()
  @Min(1)
  memberId: number;

  @ApiProperty({
    enum: ATTENDANCE_METHODS,
    example: 'manual',
    description: 'Método de registro: manual, dni, qr o app',
  })
  @IsEnum(ATTENDANCE_METHODS)
  method: (typeof ATTENDANCE_METHODS)[number];
}
