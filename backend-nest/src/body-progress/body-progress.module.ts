import { Module } from '@nestjs/common';
import { MembersModule } from '../members/members.module';
import { BodyProgressController } from './body-progress.controller';
import { BodyProgressService } from './body-progress.service';

@Module({
  imports: [MembersModule],
  controllers: [BodyProgressController],
  providers: [BodyProgressService],
  exports: [BodyProgressService],
})
export class BodyProgressModule {}
