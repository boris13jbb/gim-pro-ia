import {
  BadRequestException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { existsSync, mkdirSync, unlinkSync } from 'node:fs';
import { extname, join } from 'node:path';
import { getUploadConfig } from '../config/upload.config';
import { PrismaService } from '../database/prisma.service';

@Injectable()
export class MemberPhotoService {
  private readonly config = getUploadConfig();

  constructor(private readonly prisma: PrismaService) {
    if (!existsSync(this.config.membersDir)) {
      mkdirSync(this.config.membersDir, { recursive: true });
    }
  }

  async upload(memberId: number, file: Express.Multer.File) {
    const member = await this.prisma.socios.findUnique({ where: { id: memberId } });
    if (!member) throw new NotFoundException('Socio no encontrado');

    this.validateFile(file);
    const filename = this.buildFilename(memberId, file.originalname);
    const destination = join(this.config.membersDir, filename);

    const { writeFileSync } = await import('node:fs');
    writeFileSync(destination, file.buffer);

    if (member.foto && member.foto !== filename) {
      this.deleteFileIfExists(member.foto);
    }

    await this.prisma.socios.update({
      where: { id: memberId },
      data: { foto: filename },
    });

    return { filename, photoUrl: `${this.config.publicPath}/${filename}` };
  }

  async remove(memberId: number) {
    const member = await this.prisma.socios.findUnique({ where: { id: memberId } });
    if (!member) throw new NotFoundException('Socio no encontrado');

    if (member.foto) {
      this.deleteFileIfExists(member.foto);
    }

    await this.prisma.socios.update({
      where: { id: memberId },
      data: { foto: null },
    });

    return { message: 'Foto eliminada correctamente' };
  }

  private validateFile(file: Express.Multer.File) {
    if (!file?.buffer?.length) {
      throw new BadRequestException('Archivo de foto requerido');
    }
    if (file.size > this.config.maxFileSizeBytes) {
      throw new BadRequestException(
        `La foto no puede superar ${this.config.maxFileSizeBytes / (1024 * 1024)} MB`,
      );
    }
    const ext = extname(file.originalname).replace('.', '').toLowerCase();
    if (!this.config.allowedExtensions.has(ext)) {
      throw new BadRequestException('Formato no permitido. Use JPG, PNG o WEBP');
    }
    if (!(this.config.allowedMimeTypes as readonly string[]).includes(file.mimetype)) {
      throw new BadRequestException('Tipo MIME de imagen no permitido');
    }
  }

  private buildFilename(memberId: number, originalName: string) {
    const ext = extname(originalName).replace('.', '').toLowerCase() || 'jpg';
    return `member_${memberId}_${Date.now()}.${ext}`;
  }

  private deleteFileIfExists(filename: string) {
    const path = join(this.config.membersDir, filename);
    if (existsSync(path)) {
      unlinkSync(path);
    }
  }
}
