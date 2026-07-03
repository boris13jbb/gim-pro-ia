import { Module } from '@nestjs/common';
import { AttendanceModule } from '../attendance/attendance.module';
import { MembersModule } from '../members/members.module';
import { QrAccessController } from './qr-access.controller';
import { QrAccessService } from './qr-access.service';

@Module({
  imports: [AttendanceModule, MembersModule],
  controllers: [QrAccessController],
  providers: [QrAccessService],
})
export class QrAccessModule {}
