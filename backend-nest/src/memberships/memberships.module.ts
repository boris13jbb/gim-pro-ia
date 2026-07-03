import { Module } from '@nestjs/common';
import { MembershipsController } from './memberships.controller';
import { MembershipsService } from './memberships.service';
import { MembershipsExportService } from './memberships-export.service';

@Module({
  controllers: [MembershipsController],
  providers: [MembershipsService, MembershipsExportService],
  exports: [MembershipsService],
})
export class MembershipsModule {}
