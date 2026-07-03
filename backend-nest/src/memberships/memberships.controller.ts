import {

  Body,

  Controller,

  Get,

  Header,

  Param,

  ParseIntPipe,

  Patch,

  Post,

  StreamableFile,

} from '@nestjs/common';

import { ApiBearerAuth, ApiOperation, ApiProduces, ApiTags } from '@nestjs/swagger';

import { Roles } from '../common/decorators/roles.decorator';

import { RawResponse } from '../common/decorators/raw-response.decorator';

import { MembershipsService } from './memberships.service';

import { MembershipsExportService } from './memberships-export.service';

import { CreateMembershipDto } from './dto/create-membership.dto';



@ApiTags('memberships')

@ApiBearerAuth()

@Controller('memberships')

@Roles('admin', 'recepcionista')

export class MembershipsController {

  constructor(

    private readonly membershipsService: MembershipsService,

    private readonly membershipsExportService: MembershipsExportService,

  ) {}



  @Get('export/excel')

  @RawResponse()

  @ApiProduces(

    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

  )

  @Header(

    'Content-Type',

    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

  )

  @ApiOperation({ summary: 'Exportar membresías a Excel (.xlsx)' })

  async exportExcel() {

    const buffer = await this.membershipsExportService.buildExcelBuffer();

    const filename = this.membershipsExportService.buildFilename();

    return new StreamableFile(buffer, {

      type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

      disposition: `attachment; filename="${filename}"`,

    });

  }



  @Get()

  @ApiOperation({ summary: 'Listar membresías' })

  findAll() {

    return this.membershipsService.findAll();

  }



  @Get(':id')

  @ApiOperation({ summary: 'Obtener membresía por ID' })

  findOne(@Param('id', ParseIntPipe) id: number) {

    return this.membershipsService.findOne(id);

  }



  @Post()

  @ApiOperation({ summary: 'Registrar nueva membresía (calcula fecha fin por plan)' })

  create(@Body() dto: CreateMembershipDto) {

    return this.membershipsService.create(dto);

  }



  @Patch(':id/cancel')

  @ApiOperation({ summary: 'Cancelar membresía (marca como vencida, compatible PHP)' })

  cancel(@Param('id', ParseIntPipe) id: number) {

    return this.membershipsService.cancel(id);

  }

}

