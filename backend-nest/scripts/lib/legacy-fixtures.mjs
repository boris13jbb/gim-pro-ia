/**
 * Fixtures de ENSAYO (no datos reales) que imitan una instalación single-gym.
 *
 * Los INSERT no mencionan `tenant_id`, igual que el código actual de la aplicación:
 * - sobre el esquema baseline (pre SAAS-03) simulan los datos legados a migrar;
 * - sobre el esquema multi-tenant con exactamente un tenant, los triggers de 0002
 *   resuelven el tenant (modo legado).
 * Recorren todas las tablas tenant-scoped y sus FK. Solo se usan en bases gim_test_*.
 */
const FIXTURE_STATEMENTS = [
  "INSERT INTO configuracion (id, nombre_sistema, nombre_comercial, moneda) VALUES (1, 'Ensayo', 'Gym Ensayo', '$')",
  `INSERT INTO usuarios (id, nombre, email, password, rol, estado) VALUES
     (1, 'Staff Ensayo', 'staff.ensayo@gim-test.local', 'hash-no-valido', 'admin', 'activo'),
     (2, 'Entrenador Ensayo', 'trainer.ensayo@gim-test.local', 'hash-no-valido', 'entrenador', 'inactivo')`,
  `INSERT INTO socios (id, nombre, dni, email, estado) VALUES
     (1, 'Socio Uno', 'T0000001', 'uno@gim-test.local', 'activo'),
     (2, 'Socio Dos', 'T0000002', NULL, 'activo')`,
  "INSERT INTO planes (id, nombre, precio, duracion_dias, estado) VALUES (1, 'Mensual ensayo', 30.00, 30, 'activo')",
  `INSERT INTO suscripciones (id, socio_id, plan_id, fecha_inicio, fecha_fin, estado) VALUES
     (1, 1, 1, '2026-01-01', '2026-01-31', 'vencida'),
     (2, NULL, 1, '2026-02-01', '2026-02-28', 'vencida')`,
  "INSERT INTO categorias (id, nombre, estado) VALUES (1, 'Bebidas', 'activo')",
  "INSERT INTO productos (id, categoria_id, nombre, precio_compra, precio_venta, stock, estado) VALUES (1, 1, 'Agua ensayo', 0.50, 1.00, 10, 'activo')",
  "INSERT INTO cajas (id, usuario_id, monto_inicial, estado) VALUES (1, 1, 20.00, 'abierta')",
  'INSERT INTO ventas (id, caja_id, socio_id, total) VALUES (1, 1, 1, 2.00), (2, 1, NULL, 1.00)',
  'INSERT INTO detalle_ventas (id, venta_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (1, 1, 1, 2, 1.00, 2.00)',
  `INSERT INTO movimientos_inventario (id, producto_id, tipo, cantidad, stock_anterior, stock_nuevo, venta_id, usuario_id)
     VALUES (1, 1, 'sale', 2, 12, 10, 1, 1)`,
  "INSERT INTO asistencias (id, socio_id, metodo_ingreso) VALUES (1, 1, 'manual')",
  "INSERT INTO medidas (id, socio_id, peso, fecha) VALUES (1, 1, 80.50, '2026-01-15')",
  "INSERT INTO rutinas (id, socio_id, dia1) VALUES (1, 1, 'Rutina de ensayo')",
  `INSERT INTO notifications (id, member_id, type, title, body, updated_at)
     VALUES (1, 1, 'rehearsal', 'Ensayo', 'Notificación de ensayo', NOW())`,
  "INSERT INTO ai_conversations (id, member_id, titulo, actualizado_en) VALUES (1, 1, 'Conversación de ensayo', NOW())",
  "INSERT INTO ai_messages (id, conversation_id, role, content) VALUES (1, 1, 'user', 'Mensaje de ensayo')",
  "INSERT INTO gastos (id, descripcion, monto, fecha) VALUES (1, 'Gasto de ensayo', 5.00, '2026-01-10')",
  "INSERT INTO sri_series (id, tipo_doc, serie, correlativo) VALUES (1, '01', '001001', 1)",
  `INSERT INTO comprobantes_electronicos
     (id, origen_tipo, origen_id, tipo_doc, serie, correlativo, fecha_emision, emisor_ruc, emisor_razon,
      cliente_tipo_doc, cliente_razon, total)
     VALUES (1, 'venta', 1, '01', '001001', 1, '2026-01-20', '0999999999001', 'Emisor Ensayo', '07', 'Consumidor Final', 2.00)`,
  `INSERT INTO comprobantes_detalle (id, comprobante_id, descripcion, valor_unitario, precio_unitario, subtotal, total_linea)
     VALUES (1, 1, 'Agua ensayo', 1.0000, 1.0000, 2.00, 2.00)`,
  "INSERT INTO sri_log (id, comprobante_id, accion) VALUES (1, 1, 'ensayo'), (2, NULL, 'ensayo-sin-comprobante')",
  `INSERT INTO auth_refresh_tokens (id, userId, tokenHash, jti, expiresAt)
     VALUES (1, 1, 'hash-no-valido', 'jti-ensayo-0001', '2030-01-01 00:00:00')`,
];

export async function seedLegacyFixtures(conn) {
  for (const statement of FIXTURE_STATEMENTS) {
    await conn.query(statement);
  }
}
