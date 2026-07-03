import {

  Body,

  Controller,

  Delete,

  Get,

  Param,

  ParseIntPipe,

  Patch,

  Post,

  Query,

  UploadedFile,

  UseInterceptors,

} from '@nestjs/common';

import {

  ApiBearerAuth,

  ApiBody,

  ApiConsumes,

  ApiOperation,

  ApiTags,

} from '@nestjs/swagger';

import { FileInterceptor } from '@nestjs/platform-express';

import { memoryStorage } from 'multer';

import { Roles } from '../common/decorators/roles.decorator';

import { MembersService } from './members.service';

import { MemberPhotoService } from './member-photo.service';

import { ListMembersQueryDto } from './dto/list-members-query.dto';

import { CreateMemberDto } from './dto/create-member.dto';

import { UpdateMemberDto } from './dto/update-member.dto';

import { UpdateMemberStatusDto } from './dto/update-member-status.dto';

import { UpdateMemberPasswordDto } from './dto/update-member-password.dto';

import { getUploadConfig } from '../config/upload.config';



const uploadConfig = getUploadConfig();



@ApiTags('members')

@ApiBearerAuth()

@Controller('members')

@Roles('admin', 'recepcionista', 'entrenador')

export class MembersController {

  constructor(

    private readonly membersService: MembersService,

    private readonly memberPhotoService: MemberPhotoService,

  ) {}



  @Get()

  @ApiOperation({ summary: 'Listar socios con paginación y búsqueda' })

  findAll(@Query() query: ListMembersQueryDto) {

    return this.membersService.findAll(query);

  }



  @Get(':id/membership')

  @ApiOperation({ summary: 'Estado de membresía calculado en backend' })

  getMembership(@Param('id', ParseIntPipe) id: number) {

    return this.membersService.getMembershipSummary(id);

  }



  @Get(':id/memberships')

  @ApiOperation({ summary: 'Historial de membresías del socio' })

  listMemberships(@Param('id', ParseIntPipe) id: number) {

    return this.membersService.listMemberships(id);

  }



  @Get(':id')

  @ApiOperation({ summary: 'Obtener socio por ID' })

  findOne(@Param('id', ParseIntPipe) id: number) {

    return this.membersService.findOne(id);

  }



  @Post()

  @Roles('admin', 'recepcionista')

  @ApiOperation({ summary: 'Crear socio' })

  create(@Body() dto: CreateMemberDto) {

    return this.membersService.create(dto);

  }



  @Patch(':id')

  @Roles('admin', 'recepcionista')

  @ApiOperation({ summary: 'Actualizar datos del socio' })

  update(

    @Param('id', ParseIntPipe) id: number,

    @Body() dto: UpdateMemberDto,

  ) {

    return this.membersService.update(id, dto);

  }



  @Patch(':id/status')

  @Roles('admin', 'recepcionista')

  @ApiOperation({ summary: 'Activar/inactivar socio' })

  updateStatus(

    @Param('id', ParseIntPipe) id: number,

    @Body() dto: UpdateMemberStatusDto,

  ) {

    return this.membersService.updateStatus(id, dto.estado);

  }



  @Patch(':id/password')

  @Roles('admin', 'recepcionista')

  @ApiOperation({ summary: 'Establecer contraseña de acceso app móvil del socio' })

  updatePassword(

    @Param('id', ParseIntPipe) id: number,

    @Body() dto: UpdateMemberPasswordDto,

  ) {

    return this.membersService.updatePassword(id, dto.password);

  }



  @Post(':id/photo')

  @Roles('admin', 'recepcionista')

  @UseInterceptors(

    FileInterceptor('photo', {

      storage: memoryStorage(),

      limits: { fileSize: uploadConfig.maxFileSizeBytes },

    }),

  )

  @ApiConsumes('multipart/form-data')

  @ApiBody({

    schema: {

      type: 'object',

      properties: {

        photo: { type: 'string', format: 'binary' },

      },

    },

  })

  @ApiOperation({ summary: 'Subir foto del socio (JPG, PNG, WEBP)' })

  uploadPhoto(

    @Param('id', ParseIntPipe) id: number,

    @UploadedFile() file: Express.Multer.File,

  ) {

    return this.memberPhotoService.upload(id, file);

  }



  @Delete(':id/photo')

  @Roles('admin', 'recepcionista')

  @ApiOperation({ summary: 'Eliminar foto del socio' })

  removePhoto(@Param('id', ParseIntPipe) id: number) {

    return this.memberPhotoService.remove(id);

  }

}

