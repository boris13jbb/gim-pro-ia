# Plan de Migración por Fases

**Fecha:** 2026-07-02  
**Estado:** En ejecución — Fase 10 slice 1 implementado; pendiente aprobación

| Fase | Nombre | Dependencias | Riesgo |
|:---:|---|---|---|
| 00 | Diagnóstico PHP | — | Bajo ✅ |
| 01 | Backend base NestJS | Fase 00 | Bajo ✅ |
| 02 | Auth, usuarios, roles | Fase 01 | Medio ✅ |
| 03 | Socios, planes, membresías | Fase 02 | Medio ✅ |
| 04 | Asistencias y QR | Fase 03 | Medio ✅ |
| 05 | Progreso y rutinas | Fase 03 | Bajo ✅ |
| 06 | Inventario y productos | Fase 02 | Medio |
| 07 | POS, ventas y caja | Fases 03, 06 | **Alto** |
| 08 | Reportes | Fases 03, 06, 07 | Medio ✅ |
| 09 | Facturación SRI | Fases 03, 07 | **Crítico** ✅ |
| 10 | Flutter app socio | Fases 02–05 mínimo | Medio |
| 11 | IA Gemini + WebSockets | Fases 10, 05 | Medio |
| 12 | Cierre migración | Todas | Alto |

## Criterios para avanzar de fase

- [ ] Documentación de fase completada en `docs/fases/`
- [ ] Bitácora actualizada
- [ ] Pruebas documentadas en `docs/06-checklist-pruebas.md`
- [ ] Endpoints documentados en `docs/07-endpoints-api.md` (si aplica)
- [ ] Resumen de fase entregado
- [ ] **Aprobación explícita del usuario**

## Correcciones obligatorias en NestJS (no replicar bugs PHP)

1. **E01:** Caja debe sumar `ventas.total`, no suscripciones
2. **E02:** Registrar asistencia solo con membresía vigente validada en backend
3. **E03:** Stock nunca negativo (CHECK o validación transaccional)
4. **E07:** Estados membresía: activa, vencida, cancelada, suspendida
5. **E12:** Rol `socio` para app Flutter
