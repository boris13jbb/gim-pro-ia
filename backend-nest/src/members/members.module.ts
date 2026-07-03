import { Module } from '@nestjs/common';
import { MembersController } from './members.controller';
import { MembersService } from './members.service';
import { MemberPhotoService } from './member-photo.service';

@Module({
  controllers: [MembersController],
  providers: [MembersService, MemberPhotoService],
  exports: [MembersService],
})
export class MembersModule {}
