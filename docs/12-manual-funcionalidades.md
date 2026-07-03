# Manual de Funcionalidades — Sistema Actual PHP

**Estado:** Borrador basado en diagnóstico Fase 00

## Módulos del sistema

1. **Autenticación** — Login por email/password, roles admin/recepcionista/entrenador
2. **Dashboard** — KPIs financieros, vencimientos, gráficos ventas y SRI
3. **Socios** — CRUD con foto, carnet QR PDF, progreso físico
4. **Membresías** — Suscripciones a planes con fechas y estados
5. **Asistencias** — Validación DNI + registro manual de ingreso
6. **Inventario** — Categorías, productos, stock, fotos
7. **POS** — Venta de productos con carrito en sesión
8. **Caja** — Apertura/cierre con cuadre (⚠️ bug en totales)
9. **Gastos** — Registro y anulación
10. **Reportes** — Financieros con export PDF/Excel
11. **Facturación SRI** — Comprobantes electrónicos Ecuador
12. **Notificaciones** — Alertas WhatsApp vencimientos
13. **Mantenimiento** — Backup/restaurar/limpiar BD
14. **Usuarios** — CRUD staff del gimnasio
15. **Configuración** — Datos empresa y certificado SRI

Detalle técnico: ver `docs/01-mapa-funcionalidades-actuales.md`
