import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { existsSync, mkdirSync, unlinkSync } from 'node:fs';
import { extname, join } from 'node:path';
import { getProductUploadConfig } from '../config/upload.config';
import { PrismaService } from '../database/prisma.service';

@Injectable()
export class ProductPhotoService {
  private readonly config = getProductUploadConfig();

  constructor(private readonly prisma: PrismaService) {
    if (!existsSync(this.config.productsDir)) {
      mkdirSync(this.config.productsDir, { recursive: true });
    }
  }

  async upload(productId: number, file: Express.Multer.File) {
    const product = await this.prisma.productos.findUnique({
      where: { id: productId },
    });
    if (!product) throw new NotFoundException('Producto no encontrado');

    this.validateFile(file);
    const filename = this.buildFilename(productId, file.originalname);
    const destination = join(this.config.productsDir, filename);

    const { writeFileSync } = await import('node:fs');
    writeFileSync(destination, file.buffer);

    if (product.foto && product.foto !== filename) {
      this.deleteFileIfExists(product.foto);
    }

    await this.prisma.productos.update({
      where: { id: productId },
      data: { foto: filename },
    });

    return {
      filename,
      imageUrl: `${this.config.publicPath}/${filename}`,
    };
  }

  async remove(productId: number) {
    const product = await this.prisma.productos.findUnique({
      where: { id: productId },
    });
    if (!product) throw new NotFoundException('Producto no encontrado');

    if (product.foto) {
      this.deleteFileIfExists(product.foto);
    }

    await this.prisma.productos.update({
      where: { id: productId },
      data: { foto: null },
    });

    return { message: 'Foto del producto eliminada correctamente' };
  }

  private validateFile(file: Express.Multer.File) {
    if (!file?.buffer?.length) {
      throw new BadRequestException('Archivo de imagen requerido');
    }
    if (file.size > this.config.maxFileSizeBytes) {
      throw new BadRequestException(
        `La imagen no puede superar ${this.config.maxFileSizeBytes / (1024 * 1024)} MB`,
      );
    }
    const ext = extname(file.originalname).replace('.', '').toLowerCase();
    if (!this.config.allowedExtensions.has(ext)) {
      throw new BadRequestException(
        'Formato no permitido. Use JPG, PNG o WEBP',
      );
    }
    if (
      !(this.config.allowedMimeTypes as readonly string[]).includes(
        file.mimetype,
      )
    ) {
      throw new BadRequestException('Tipo MIME de imagen no permitido');
    }
  }

  private buildFilename(productId: number, originalName: string) {
    const ext = extname(originalName).replace('.', '').toLowerCase() || 'jpg';
    return `product_${productId}_${Date.now()}.${ext}`;
  }

  private deleteFileIfExists(filename: string) {
    const path = join(this.config.productsDir, filename);
    if (existsSync(path)) {
      unlinkSync(path);
    }
  }
}
