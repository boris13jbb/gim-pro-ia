import { Module } from '@nestjs/common';
import { RealtimeModule } from '../websocket/realtime.module';
import { MembershipsController } from './memberships.controller';
import { MembershipsService } from './memberships.service';
import { MembershipsExportService } from './memberships-export.service';

@Module({
  imports: [RealtimeModule],
  controllers: [MembershipsController],
  providers: [MembershipsService, MembershipsExportService],
  exports: [MembershipsService],
})
export class MembershipsModule {}
