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
import { FileInterceptor } from '@nestjs/platform-express';
import {
  ApiBearerAuth,
  ApiBody,
  ApiConsumes,
  ApiOperation,
  ApiTags,
} from '@nestjs/swagger';
import { Roles } from '../common/decorators/roles.decorator';
import { CreateProductDto } from './dto/create-product.dto';
import {
  ListProductsQueryDto,
  LowStockQueryDto,
  UpdateProductDto,
  UpdateProductStatusDto,
} from './dto/update-product.dto';
import { ProductPhotoService } from './product-photo.service';
import { ProductsService } from './products.service';

@ApiTags('products')
@ApiBearerAuth()
@Controller('products')
export class ProductsController {
  constructor(
    private readonly productsService: ProductsService,
    private readonly productPhotoService: ProductPhotoService,
  ) {}

  @Get()
  @Roles('admin')
  @ApiOperation({ summary: 'Listar productos (admin)' })
  findAll(@Query() query: ListProductsQueryDto) {
    return this.productsService.findAll(query);
  }

  @Get('active')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Listar productos activos con categoría activa' })
  findActive() {
    return this.productsService.findActive();
  }

  @Get('low-stock')
  @Roles('admin')
  @ApiOperation({ summary: 'Productos activos con stock bajo o igual al umbral' })
  findLowStock(@Query() query: LowStockQueryDto) {
    return this.productsService.findLowStock(query);
  }

  @Get(':id')
  @Roles('admin', 'recepcionista')
  @ApiOperation({ summary: 'Obtener producto por ID' })
  findOne(@Param('id', ParseIntPipe) id: number) {
    return this.productsService.findOne(id);
  }

  @Post()
  @Roles('admin')
  @ApiOperation({ summary: 'Crear producto (solo admin)' })
  create(@Body() dto: CreateProductDto) {
    return this.productsService.create(dto);
  }

  @Patch(':id')
  @Roles('admin')
  @ApiOperation({ summary: 'Actualizar producto (solo admin)' })
  update(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: UpdateProductDto,
  ) {
    return this.productsService.update(id, dto);
  }

  @Patch(':id/status')
  @Roles('admin')
  @ApiOperation({ summary: 'Activar/inactivar producto (solo admin)' })
  updateStatus(
    @Param('id', ParseIntPipe) id: number,
    @Body() dto: UpdateProductStatusDto,
  ) {
    return this.productsService.updateStatus(id, dto.status);
  }

  @Post(':id/photo')
  @Roles('admin')
  @UseInterceptors(FileInterceptor('photo'))
  @ApiConsumes('multipart/form-data')
  @ApiBody({
    schema: {
      type: 'object',
      properties: { photo: { type: 'string', format: 'binary' } },
    },
  })
  @ApiOperation({ summary: 'Subir foto de producto (solo admin)' })
  uploadPhoto(
    @Param('id', ParseIntPipe) id: number,
    @UploadedFile() file: Express.Multer.File,
  ) {
    return this.productPhotoService.upload(id, file);
  }

  @Delete(':id/photo')
  @Roles('admin')
  @ApiOperation({ summary: 'Eliminar foto de producto (solo admin)' })
  removePhoto(@Param('id', ParseIntPipe) id: number) {
    return this.productPhotoService.remove(id);
  }
}
