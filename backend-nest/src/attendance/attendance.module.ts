import { Module } from '@nestjs/common';
import { MembersModule } from '../members/members.module';
import { ReportsModule } from '../reports/reports.module';
import { AttendanceController } from './attendance.controller';
import { AttendanceExportService } from './attendance-export.service';
import { AttendanceService } from './attendance.service';

@Module({
  imports: [MembersModule, ReportsModule],
  controllers: [AttendanceController],
  providers: [AttendanceService, AttendanceExportService],
  exports: [AttendanceService],
})
export class AttendanceModule {}
