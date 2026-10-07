import { ConfigModule } from '@nestjs/config';
import { Test, TestingModule } from '@nestjs/testing';
import { DatabaseModule } from '../../src/database/database.module';
import { PrismaHealthIndicator } from '../../src/database/prisma.health';
import { PrismaService } from '../../src/database/prisma.service';

/**
 * SAAS-02/03 — Prueba de migración desde cero.
 * global-setup.mjs ya creó una base gim_test_* vacía y aplicó las migraciones;
 * aquí se verifica que la aplicación (DatabaseModule real) opera sobre ella.
 * La base nueva no tiene tenants: el spec crea el suyo y lo pasa explícito.
 */
describe('Baseline de base de datos (integración MySQL)', () => {
  let moduleRef: TestingModule;
  let prisma: PrismaService;
  let tenantId: number;
  const databaseName = process.env.INTEGRATION_DATABASE_NAME ?? '';

  beforeAll(async () => {
    // Seguridad: abortar si el harness no apuntó a una base desechable.
    if (!databaseName.startsWith('gim_test_')) {
      throw new Error('El harness no configuró una base desechable gim_test_*');
    }

    moduleRef = await Test.createTestingModule({
      imports: [
        ConfigModule.forRoot({ isGlobal: true, ignoreEnvFile: true }),
        DatabaseModule,
      ],
    }).compile();
    await moduleRef.init();
    prisma = moduleRef.get(PrismaService);
    const tenant = await prisma.tenants.create({
      data: {
        public_id: String(Date.now()).padStart(26, '0'),
        slug: `baseline-${Date.now()}`,
        name: 'Gym baseline',
      },
    });
    tenantId = tenant.id;
  });

  afterAll(async () => {
    await moduleRef?.close();
  });

  it('conecta a la base desechable y no a la base de la aplicación', async () => {
    const [row] = await prisma.$queryRaw<
      { db: string }[]
    >`SELECT DATABASE() AS db`;
    expect(row.db).toBe(databaseName);
  });

  it('el health indicator de la aplicación reporta la base como disponible', async () => {
    const result = await moduleRef
      .get(PrismaHealthIndicator)
      .isHealthy('database');
    expect(result.database.status).toBe('up');
  });

  it('el historial de Prisma contiene baseline + multi-tenant, aplicados completos', async () => {
    const rows = await prisma.$queryRaw<
      { migration_name: string; finished_at: Date | null }[]
    >`SELECT migration_name, finished_at FROM _prisma_migrations ORDER BY migration_name`;
    expect(rows.map((row) => row.migration_name)).toEqual([
      '0001_baseline_current_schema',
      '0002_multi_tenant_foundation',
    ]);
    expect(rows.every((row) => row.finished_at !== null)).toBe(true);
  });

  it('los modelos Prisma operan y las foreign keys se aplican', async () => {
    const category = await prisma.categorias.create({
      data: { tenant_id: tenantId, nombre: 'Categoría integración' },
    });
    const product = await prisma.productos.create({
      data: {
        categoria_id: category.id,
        nombre: 'Producto integración',
        precio_compra: 1,
        precio_venta: 2,
      },
    });
    expect(product.stock).toBe(0);

    await expect(
      prisma.productos.create({
        data: {
          categoria_id: category.id + 100_000,
          nombre: 'Producto huérfano',
          precio_compra: 1,
          precio_venta: 2,
        },
      }),
    ).rejects.toThrow();
  });

  it('socios.dni es único dentro del tenant', async () => {
    await prisma.socios.create({
      data: {
        tenant_id: tenantId,
        nombre: 'Socio integración',
        dni: 'INT-0001',
      },
    });
    await expect(
      prisma.socios.create({
        data: {
          tenant_id: tenantId,
          nombre: 'Socio duplicado',
          dni: 'INT-0001',
        },
      }),
    ).rejects.toThrow();
  });
});
