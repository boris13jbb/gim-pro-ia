# Fase 15 — Facturación SRI en app staff Flutter

**Estado:** Implementada — aprobada por usuario (continuar a Fase 16)

**Fecha inicio:** 2026-07-07

## Objetivo

Consumir el módulo **billing-sri** de NestJS (Fase 09) desde la app staff: bandeja de comprobantes, emisión desde ventas/membresías, descargas y acciones fiscales. Sin cambios en backend ni flujo fiscal.

## Alcance

| Funcionalidad | Rol | Estado |
|---------------|-----|:------:|
| Bandeja `/staff/sri` con filtros | admin, recepcionista | ✅ |
| Detalle + logs + PDF/XML | admin, recepcionista | ✅ |
| Emitir desde venta POS | admin, recepcionista | ✅ |
| Emitir desde membresía | admin, recepcionista | ✅ |
| Reintentar autorización SRI | admin, recepcionista | ✅ |
| Nota de crédito | admin, recepcionista | ✅ |
| Enviar email RIDE+XML | admin, recepcionista | ✅ |
| Config fiscal readiness | solo admin | ✅ |

## API reutilizada

- `GET /electronic-receipts`, `GET /electronic-receipts/:id`, `GET /electronic-receipts/:id/logs`
- `POST /electronic-receipts/issue/sale/:saleId`
- `POST /electronic-receipts/issue/membership/:membershipId`
- `POST /electronic-receipts/:id/retry`, `POST /electronic-receipts/:id/credit-note`
- `GET /electronic-receipts/:id/pdf`, `GET /electronic-receipts/:id/xml`
- `POST /electronic-receipts/:id/send-email`
- `GET /sri-config` (admin)

## Archivos creados

- `core/models/electronic_receipt.dart`, `core/models/sri_config.dart`
- `services/billing_sri_service.dart`, `services/sri_config_service.dart`
- `features/staff/sri/staff_sri_page.dart`, `staff_sri_detail_page.dart`
- `widgets/sri_status_chip.dart`

## Archivos modificados

- `staff_permissions.dart` (`canUseBillingSri`)
- `membership_summary.dart` (`receiptId`)
- `staff_home_page.dart`, `staff_member_detail_page.dart`, `staff_pos_panels.dart`
- `app_router.dart`, `app.dart`

## Pruebas

- `flutter analyze` → 0 errores
- `flutter test` → 1/1 OK
- Pendiente manual con ambiente SRI de pruebas

## Estado final

Lista para prueba manual. **No avanzar sin aprobación.**
