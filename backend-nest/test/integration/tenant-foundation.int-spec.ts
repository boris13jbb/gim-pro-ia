import { ConfigModule } from '@nestjs/config';
import { Test, TestingModule } from '@nestjs/testing';
import { DatabaseModule } from '../../src/database/database.module';
import { PrismaService } from '../../src/database/prisma.service';

/**
 * SAAS-03 — Integridad de la fundación multi-tenant sobre la base gim_test_* que
 * global-setup.mjs migró desde cero (0001 + 0002).
 *
 * Los specs comparten base y su orden no está garantizado: cada prueba crea sus propios
 * tenants y pasa tenant_id explícito en las raíces. El modo legado (un único tenant) se
 * ensaya en scripts/tenant-migration-rehearsal.mjs, donde el número de tenants es fijo.
 */
describe('Fundación multi-tenant (integración MySQL)', () => {
  let moduleRef: TestingModule;
  let prisma: PrismaService;
  let sequence = 0;
  const databaseName = process.env.INTEGRATION_DATABASE_NAME ?? '';
  const runId = String(Date.now());

  /** public_id de prueba: 26 dígitos, válido para el CHECK ULID (Crockford). */
  const createTenant = (label: string) => {
    sequence += 1;
    return prisma.tenants.create({
      data: {
        public_id: `${runId.padStart(13, '0')}${String(sequence).padStart(13, '0')}`,
        slug: `it-${label}-${runId}-${sequence}`,
        name: `Gym integración ${label}`,
      },
    });
  };

  const tenantScopedTables = async () =>
    (
      await prisma.$queryRaw<{ table_name: string }[]>`
        SELECT TABLE_NAME AS table_name FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'tenant_id'
          AND TABLE_NAME <> 'tenant_memberships'
        ORDER BY TABLE_NAME`
    ).map((row) => row.table_name);

  beforeAll(async () => {
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
  });

  afterAll(async () => {
    await moduleRef?.close();
  });

  it('1. crea un tenant con public_id ULID y slug kebab-case validados por la base', async () => {
    const tenant = await createTenant('crear');
    expect(tenant.status).toBe('active');
    expect(tenant.country).toBe('EC');

    await expect(
      prisma.tenants.create({
        data: {
          public_id: 'invalid-public-id-lower-x',
          slug: `bad-${runId}`,
          name: 'X',
        },
      }),
    ).rejects.toThrow();
    await expect(
      prisma.tenants.create({
        data: { public_id: '0'.repeat(26), slug: 'Slug_Invalido', name: 'X' },
      }),
    ).rejects.toThrow();
  });

  it('2. una membership pertenece a un tenant y es única por (tenant, usuario)', async () => {
    const tenantA = await createTenant('mem-a');
    const tenantB = await createTenant('mem-b');
    const user = await prisma.usuarios.create({
      data: {
        nombre: 'Staff integración',
        email: `staff.${runId}@gim-test.local`,
        password: 'hash-no-valido',
      },
    });

    const membership = await prisma.tenant_memberships.create({
      data: { tenant_id: tenantA.id, user_id: user.id, role: 'admin' },
    });
    expect(membership.tenant_id).toBe(tenantA.id);
    expect(membership.status).toBe('active');

    await expect(
      prisma.tenant_memberships.create({
        data: { tenant_id: tenantA.id, user_id: user.id, role: 'trainer' },
      }),
    ).rejects.toThrow();
    // El mismo usuario puede pertenecer a otro gimnasio.
    await expect(
      prisma.tenant_memberships.create({
        data: { tenant_id: tenantB.id, user_id: user.id, role: 'reception' },
      }),
    ).resolves.toMatchObject({ tenant_id: tenantB.id });
  });

  it('3. un socio pertenece a un tenant', async () => {
    const tenant = await createTenant('socio');
    const member = await prisma.socios.create({
      data: {
        tenant_id: tenant.id,
        nombre: 'Socio tenant',
        dni: `S3-${runId}`,
      },
    });
    expect(member.tenant_id).toBe(tenant.id);
  });

  it('4. un hijo sin tenant_id hereda el tenant de su padre', async () => {
    const tenant = await createTenant('hijo');
    const member = await prisma.socios.create({
      data: { tenant_id: tenant.id, nombre: 'Socio padre', dni: `S4-${runId}` },
    });
    const attendance = await prisma.asistencias.create({
      data: { socio_id: member.id, metodo_ingreso: 'manual' },
    });
    expect(attendance.tenant_id).toBe(tenant.id);

    const category = await prisma.categorias.create({
      data: { tenant_id: tenant.id, nombre: 'Categoría padre' },
    });
    const product = await prisma.productos.create({
      data: {
        categoria_id: category.id,
        nombre: 'Producto hijo',
        precio_compra: 1,
        precio_venta: 2,
      },
    });
    expect(product.tenant_id).toBe(tenant.id);
  });

  it('5. una referencia cruzada entre tenants se rechaza (FK compuesta y trigger)', async () => {
    const tenantA = await createTenant('cruce-a');
    const tenantB = await createTenant('cruce-b');
    const memberA = await prisma.socios.create({
      data: { tenant_id: tenantA.id, nombre: 'Socio A', dni: `S5-${runId}` },
    });
    const categoryA = await prisma.categorias.create({
      data: { tenant_id: tenantA.id, nombre: 'Categoría A' },
    });
    const planB = await prisma.planes.create({
      data: {
        tenant_id: tenantB.id,
        nombre: 'Plan B',
        precio: 10,
        duracion_dias: 30,
      },
    });

    // FK compuesta (socio_id, tenant_id) → socios(id, tenant_id).
    await expect(
      prisma.asistencias.create({
        data: {
          socio_id: memberA.id,
          tenant_id: tenantB.id,
          metodo_ingreso: 'manual',
        },
      }),
    ).rejects.toThrow();
    await expect(
      prisma.productos.create({
        data: {
          categoria_id: categoryA.id,
          tenant_id: tenantB.id,
          nombre: 'Producto cruzado',
          precio_compra: 1,
          precio_venta: 2,
        },
      }),
    ).rejects.toThrow();

    // Relaciones opcionales: las valida el trigger (SQLSTATE 45000).
    await expect(
      prisma.suscripciones.create({
        data: {
          socio_id: memberA.id,
          plan_id: planB.id,
          fecha_inicio: new Date('2026-01-01'),
          fecha_fin: new Date('2026-01-31'),
        },
      }),
    ).rejects.toThrow(/cross-tenant reference rejected/);
  });

  it('6. un duplicado dentro del mismo tenant se rechaza (dni, configuracion)', async () => {
    const tenant = await createTenant('dup');
    await prisma.socios.create({
      data: {
        tenant_id: tenant.id,
        nombre: 'Socio original',
        dni: `S6-${runId}`,
      },
    });
    await expect(
      prisma.socios.create({
        data: {
          tenant_id: tenant.id,
          nombre: 'Socio duplicado',
          dni: `S6-${runId}`,
        },
      }),
    ).rejects.toThrow();

    await prisma.configuracion.create({
      data: { tenant_id: tenant.id, nombre_sistema: 'Config 1' },
    });
    await expect(
      prisma.configuracion.create({
        data: { tenant_id: tenant.id, nombre_sistema: 'Config 2' },
      }),
    ).rejects.toThrow();
  });

  it('7. el mismo dni se permite en tenants distintos (prueba A/B)', async () => {
    const tenantA = await createTenant('ab-a');
    const tenantB = await createTenant('ab-b');
    const dni = `S7-${runId}`;
    const memberA = await prisma.socios.create({
      data: { tenant_id: tenantA.id, nombre: 'Socio A', dni },
    });
    const memberB = await prisma.socios.create({
      data: { tenant_id: tenantB.id, nombre: 'Socio B', dni },
    });
    expect(memberA.id).not.toBe(memberB.id);
    expect([memberA.tenant_id, memberB.tenant_id]).toEqual([
      tenantA.id,
      tenantB.id,
    ]);
  });

  it('8. ninguna tabla tenant admite tenant_id NULL', async () => {
    const tables = await tenantScopedTables();
    expect(tables).toHaveLength(21);
    const nullable = await prisma.$queryRaw<{ table_name: string }[]>`
      SELECT TABLE_NAME AS table_name FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'tenant_id' AND IS_NULLABLE = 'YES'`;
    expect(nullable).toEqual([]);
  });

  it('9. no existen referencias a tenants inexistentes', async () => {
    for (const table of await tenantScopedTables()) {
      const [row] = await prisma.$queryRawUnsafe<{ total: bigint }[]>(
        `SELECT COUNT(*) AS total FROM \`${table}\` x LEFT JOIN tenants t ON t.id = x.tenant_id WHERE t.id IS NULL`,
      );
      expect({ table, invalid: Number(row.total) }).toEqual({
        table,
        invalid: 0,
      });
    }
  });

  it('10. la migración completa desde el baseline quedó aplicada (0001 + 0002)', async () => {
    const rows = await prisma.$queryRaw<
      { migration_name: string; finished_at: Date | null }[]
    >`
      SELECT migration_name, finished_at FROM _prisma_migrations ORDER BY migration_name`;
    expect(rows.map((row) => row.migration_name)).toEqual([
      '0001_baseline_current_schema',
      '0002_multi_tenant_foundation',
    ]);
    expect(rows.every((row) => row.finished_at !== null)).toBe(true);
  });

  it('con varios tenants, una raíz sin tenant_id se rechaza (no se adivina el gimnasio)', async () => {
    await createTenant('ambiguo-a');
    await createTenant('ambiguo-b');
    await expect(
      prisma.socios.create({
        data: { nombre: 'Socio sin tenant', dni: `SX-${runId}` },
      }),
    ).rejects.toThrow();
  });

  it('un tenant con datos no puede eliminarse (ON DELETE RESTRICT)', async () => {
    const tenant = await createTenant('borrar');
    await prisma.planes.create({
      data: {
        tenant_id: tenant.id,
        nombre: 'Plan protegido',
        precio: 10,
        duracion_dias: 30,
      },
    });
    await expect(
      prisma.tenants.delete({ where: { id: tenant.id } }),
    ).rejects.toThrow();
  });

  it('un tenant_id inexistente se rechaza en raíces e hijos (FK a tenants)', async () => {
    const missingTenantId = 2_000_000_000;
    await expect(
      prisma.planes.create({
        data: {
          tenant_id: missingTenantId,
          nombre: 'Plan huérfano',
          precio: 10,
          duracion_dias: 30,
        },
      }),
    ).rejects.toThrow();

    const tenant = await createTenant('inexistente');
    const member = await prisma.socios.create({
      data: { tenant_id: tenant.id, nombre: 'Socio real', dni: `SN-${runId}` },
    });
    await expect(
      prisma.asistencias.create({
        data: {
          socio_id: member.id,
          tenant_id: missingTenantId,
          metodo_ingreso: 'manual',
        },
      }),
    ).rejects.toThrow();
  });

  describe('tenant_id es inmutable', () => {
    type TenantGraph = Record<string, number>;

    /** Un registro por cada una de las 21 tablas tenant, todos en el mismo tenant. */
    const seedTenantGraph = async (tenantId: number): Promise<TenantGraph> => {
      sequence += 1;
      const tag = `${runId}-${sequence}`;
      const tenant_id = tenantId;
      const user = await prisma.usuarios.create({
        data: {
          nombre: 'Cajero integración',
          email: `cajero.${tag}@gim-test.local`,
          password: 'hash-no-valido',
        },
      });
      const socio = await prisma.socios.create({
        data: { tenant_id, nombre: 'Socio grafo', dni: `G-${tag}` },
      });
      const plan = await prisma.planes.create({
        data: { tenant_id, nombre: 'Plan grafo', precio: 10, duracion_dias: 30 },
      });
      const suscripcion = await prisma.suscripciones.create({
        data: {
          tenant_id,
          socio_id: socio.id,
          plan_id: plan.id,
          fecha_inicio: new Date('2026-01-01'),
          fecha_fin: new Date('2026-01-31'),
        },
      });
      const categoria = await prisma.categorias.create({
        data: { tenant_id, nombre: 'Categoría grafo' },
      });
      const producto = await prisma.productos.create({
        data: {
          tenant_id,
          categoria_id: categoria.id,
          nombre: 'Producto grafo',
          precio_compra: 1,
          precio_venta: 2,
        },
      });
      const caja = await prisma.cajas.create({
        data: { tenant_id, usuario_id: user.id, monto_inicial: 10 },
      });
      const venta = await prisma.ventas.create({
        data: { tenant_id, caja_id: caja.id, socio_id: socio.id, total: 2 },
      });
      const detalleVenta = await prisma.detalle_ventas.create({
        data: {
          tenant_id,
          venta_id: venta.id,
          producto_id: producto.id,
          cantidad: 1,
          precio_unitario: 2,
          subtotal: 2,
        },
      });
      const movimiento = await prisma.movimientos_inventario.create({
        data: {
          tenant_id,
          producto_id: producto.id,
          venta_id: venta.id,
          tipo: 'sale',
          cantidad: 1,
          stock_anterior: 5,
          stock_nuevo: 4,
        },
      });
      const asistencia = await prisma.asistencias.create({
        data: { tenant_id, socio_id: socio.id, metodo_ingreso: 'manual' },
      });
      const medida = await prisma.medidas.create({
        data: { tenant_id, socio_id: socio.id },
      });
      const rutina = await prisma.rutinas.create({
        data: { tenant_id, socio_id: socio.id },
      });
      const notification = await prisma.notifications.create({
        data: {
          tenant_id,
          member_id: socio.id,
          type: 'test',
          title: 'Aviso',
          body: 'Cuerpo',
        },
      });
      const conversation = await prisma.ai_conversations.create({
        data: { tenant_id, member_id: socio.id },
      });
      const message = await prisma.ai_messages.create({
        data: {
          tenant_id,
          conversation_id: conversation.id,
          role: 'user',
          content: 'Hola',
        },
      });
      const gasto = await prisma.gastos.create({
        data: {
          tenant_id,
          descripcion: 'Gasto grafo',
          monto: 3,
          fecha: new Date('2026-01-02'),
        },
      });
      const configuracion = await prisma.configuracion.create({
        data: { tenant_id, nombre_sistema: 'Config grafo' },
      });
      const serie = await prisma.sri_series.create({
        data: { tenant_id, tipo_doc: '01', serie: '001001' },
      });
      const comprobante = await prisma.comprobantes_electronicos.create({
        data: {
          tenant_id,
          origen_tipo: 'venta',
          tipo_doc: '01',
          serie: '001001',
          correlativo: 1,
          fecha_emision: new Date('2026-01-02'),
          emisor_ruc: '0999999999001',
          emisor_razon: 'Emisor grafo',
          cliente_tipo_doc: '07',
          cliente_razon: 'Consumidor final',
          total: 2,
        },
      });
      const comprobanteDetalle = await prisma.comprobantes_detalle.create({
        data: {
          tenant_id,
          comprobante_id: comprobante.id,
          descripcion: 'Línea grafo',
          valor_unitario: 2,
          precio_unitario: 2,
          subtotal: 2,
          total_linea: 2,
        },
      });
      const sriLog = await prisma.sri_log.create({
        data: { tenant_id, comprobante_id: comprobante.id, accion: 'test' },
      });

      return {
        socios: socio.id,
        planes: plan.id,
        suscripciones: suscripcion.id,
        categorias: categoria.id,
        productos: producto.id,
        cajas: caja.id,
        ventas: venta.id,
        detalle_ventas: detalleVenta.id,
        movimientos_inventario: movimiento.id,
        asistencias: asistencia.id,
        medidas: medida.id,
        rutinas: rutina.id,
        notifications: notification.id,
        ai_conversations: conversation.id,
        ai_messages: message.id,
        gastos: gasto.id,
        configuracion: configuracion.id,
        sri_series: serie.id,
        comprobantes_electronicos: comprobante.id,
        comprobantes_detalle: comprobanteDetalle.id,
        sri_log: sriLog.id,
      };
    };

    const tenantOf = async (table: string, id: number) => {
      const [row] = await prisma.$queryRawUnsafe<{ tenant_id: number }[]>(
        `SELECT tenant_id FROM \`${table}\` WHERE id = ?`,
        id,
      );
      return Number(row.tenant_id);
    };

    const moveTenant = (table: string, id: number, tenantId: number) =>
      prisma.$executeRawUnsafe(
        `UPDATE \`${table}\` SET tenant_id = ? WHERE id = ?`,
        tenantId,
        id,
      );

    let tenantA: number;
    let tenantB: number;
    let graph: TenantGraph;

    beforeAll(async () => {
      tenantA = (await createTenant('inmutable-a')).id;
      tenantB = (await createTenant('inmutable-b')).id;
      graph = await seedTenantGraph(tenantA);
    });

    /** El intento A → B falla y tanto la fila como sus relacionadas siguen en A. */
    const expectMoveRejected = async (
      table: string,
      related: [string, number][],
    ) => {
      await expect(moveTenant(table, graph[table], tenantB)).rejects.toThrow(
        new RegExp(`tenant_id is immutable: ${table}`),
      );
      for (const [relatedTable, id] of [
        [table, graph[table]] as [string, number],
        ...related,
      ]) {
        expect({
          table: relatedTable,
          tenant: await tenantOf(relatedTable, id),
        }).toEqual({ table: relatedTable, tenant: tenantA });
      }
    };

    it('A. un plan referenciado solo por una suscripción opcional no cambia de tenant', async () => {
      await expectMoveRejected('planes', [
        ['suscripciones', graph.suscripciones],
      ]);
      await expect(
        prisma.planes.update({
          where: { id: graph.planes },
          data: { tenant_id: tenantB },
        }),
      ).rejects.toThrow(/tenant_id is immutable/);
    });

    it('B. un socio con suscripción no cambia de tenant', async () => {
      await expectMoveRejected('socios', [
        ['suscripciones', graph.suscripciones],
      ]);
    });

    it('C. un socio referenciado por ventas no cambia de tenant', async () => {
      await expectMoveRejected('socios', [['ventas', graph.ventas]]);
    });

    it('D. una venta con movimientos de inventario no cambia de tenant', async () => {
      await expectMoveRejected('ventas', [
        ['movimientos_inventario', graph.movimientos_inventario],
      ]);
    });

    it('E. un comprobante con sri_log no cambia de tenant', async () => {
      await expectMoveRejected('comprobantes_electronicos', [
        ['sri_log', graph.sri_log],
      ]);
    });

    it('F. ninguna de las 21 tablas tenant permite cambiar tenant_id', async () => {
      const tables = await tenantScopedTables();
      expect(Object.keys(graph).sort()).toEqual([...tables].sort());
      for (const table of tables) {
        await expectMoveRejected(table, []);
      }
    });

    it('un UPDATE que conserva el mismo tenant_id está permitido', async () => {
      for (const table of await tenantScopedTables()) {
        await expect(
          moveTenant(table, graph[table], tenantA),
        ).resolves.toBeGreaterThanOrEqual(0);
      }
    });

    it('los UPDATE normales de otras columnas siguen funcionando', async () => {
      await expect(
        prisma.socios.update({
          where: { id: graph.socios },
          data: { telefono: '0999999999' },
        }),
      ).resolves.toMatchObject({ telefono: '0999999999', tenant_id: tenantA });
      await expect(
        prisma.planes.update({
          where: { id: graph.planes },
          data: { nombre: 'Plan renombrado' },
        }),
      ).resolves.toMatchObject({ nombre: 'Plan renombrado' });
      const producto = await prisma.productos.update({
        where: { id: graph.productos },
        data: { precio_venta: 3.5 },
      });
      expect(Number(producto.precio_venta)).toBe(3.5);
      const venta = await prisma.ventas.update({
        where: { id: graph.ventas },
        data: { total: 4 },
      });
      expect(Number(venta.total)).toBe(4);
      await expect(
        prisma.comprobantes_electronicos.update({
          where: { id: graph.comprobantes_electronicos },
          data: { cliente_razon: 'Cliente actualizado' },
        }),
      ).resolves.toMatchObject({ cliente_razon: 'Cliente actualizado' });
    });
  });
});
