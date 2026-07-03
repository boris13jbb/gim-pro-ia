import { ApiProperty } from '@nestjs/swagger';
import { IsEnum, IsNotEmpty, IsString, MaxLength } from 'class-validator';
import {
  ATTENDANCE_METHODS,
} from '../constants/attendance-method.constant';

const SCAN_METHODS = ['dni', 'qr'] as const;

export class ScanAttendanceDto {
  @ApiProperty({
    example: '1234567890',
    description: 'DNI leído manualmente o desde código QR del carnet',
  })
  @IsString()
  @IsNotEmpty()
  @MaxLength(20)
  dni: string;

  @ApiProperty({ enum: SCAN_METHODS, example: 'qr' })
  @IsEnum(SCAN_METHODS)
  method: (typeof SCAN_METHODS)[number];
}
