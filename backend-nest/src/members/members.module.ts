import { Module } from '@nestjs/common';
import { MembersController } from './members.controller';
import { MemberSelfController } from './member-self.controller';
import { MembersService } from './members.service';
import { MemberPhotoService } from './member-photo.service';

@Module({
  controllers: [MemberSelfController, MembersController],
  providers: [MembersService, MemberPhotoService],
  exports: [MembersService],
})
export class MembersModule {}
