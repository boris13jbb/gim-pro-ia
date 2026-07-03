import {
  BadRequestException,
  ConflictException,
  Injectable,
  NotFoundException,
} from '@nestjs/common';
import { PrismaService } from '../database/prisma.service';
import * as bcrypt from 'bcrypt';
import { usuarios_estado } from '@prisma/client';
import type { AppRole } from '../common/constants/roles.constant';
import { CreateUserDto } from './dto/create-user.dto';
import { UpdateUserDto } from './dto/update-user.dto';

@Injectable()
export class UsersService {
  constructor(private readonly prisma: PrismaService) {}

  findByEmail(email: string) {
    return this.prisma.usuarios.findUnique({ where: { email } });
  }

  findById(id: number) {
    return this.prisma.usuarios.findUnique({ where: { id } });
  }

  findAllStaff() {
    return this.prisma.usuarios.findMany({
      orderBy: { id: 'desc' },
      select: this.publicSelect(),
    });
  }

  async create(dto: CreateUserDto) {
    await this.ensureUniqueEmail(dto.email);
    const passwordHash = await bcrypt.hash(dto.password, 10);
    const user = await this.prisma.usuarios.create({
      data: {
        nombre: dto.nombre,
        email: dto.email,
        password: passwordHash,
        rol: dto.rol,
        estado: usuarios_estado.activo,
      },
    });
    return this.toPublicUser(user);
  }

  async update(id: number, dto: UpdateUserDto) {
    await this.findByIdOrThrow(id);
    if (dto.email) await this.ensureUniqueEmail(dto.email, id);

    const user = await this.prisma.usuarios.update({
      where: { id },
      data: {
        ...(dto.nombre !== undefined ? { nombre: dto.nombre } : {}),
        ...(dto.email !== undefined ? { email: dto.email } : {}),
        ...(dto.rol !== undefined ? { rol: dto.rol } : {}),
      },
    });
    return this.toPublicUser(user);
  }

  async updateStatus(id: number, estado: usuarios_estado, actorId: number) {
    if (id === actorId && estado === usuarios_estado.inactivo) {
      throw new BadRequestException(
        'No puedes desactivar tu propia cuenta mientras estás autenticado.',
      );
    }
    const user = await this.findByIdOrThrow(id);
    const updated = await this.prisma.usuarios.update({
      where: { id: user.id },
      data: { estado },
    });
    return this.toPublicUser(updated);
  }

  async updatePassword(id: number, password: string) {
    await this.findByIdOrThrow(id);
    const passwordHash = await bcrypt.hash(password, 10);
    const updated = await this.prisma.usuarios.update({
      where: { id },
      data: { password: passwordHash },
    });
    return this.toPublicUser(updated);
  }

  async validateStaffCredentials(email: string, password: string) {
    const user = await this.findByEmail(email);
    if (!user?.password || !user.email) {
      return { kind: 'invalid' as const };
    }

    const passwordHash = user.password.replace(/^\$2y\$/, '$2a$');
    const passwordOk = await bcrypt.compare(password, passwordHash);
    if (!passwordOk) {
      return { kind: 'invalid' as const };
    }

    if (user.estado === usuarios_estado.inactivo) {
      return { kind: 'inactive' as const };
    }

    return { kind: 'ok' as const, user };
  }

  toPublicUser(user: {
    id: number;
    nombre: string | null;
    email: string | null;
    rol: string | null;
    estado: string | null;
  }) {
    return {
      id: user.id,
      nombre: user.nombre ?? '',
      email: user.email ?? '',
      rol: (user.rol ?? 'recepcionista') as AppRole,
      estado: user.estado ?? 'activo',
    };
  }

  private publicSelect() {
    return {
      id: true,
      nombre: true,
      email: true,
      rol: true,
      estado: true,
    } as const;
  }

  private async findByIdOrThrow(id: number) {
    const user = await this.findById(id);
    if (!user) throw new NotFoundException('Usuario no encontrado');
    return user;
  }

  private async ensureUniqueEmail(email: string, excludeId?: number) {
    const existing = await this.findByEmail(email);
    if (existing && existing.id !== excludeId) {
      throw new ConflictException('Ya existe un usuario con ese email');
    }
  }
}
