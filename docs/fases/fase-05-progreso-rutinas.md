# Progreso físico y rutinas

**Estado:** Completada — pendiente aprobación para Fase 06

## Objetivo de la fase

Migrar medidas corporales y rutinas de entrenamiento del PHP (`ProgresoController`) a NestJS, conservando historial y preparando datos para gráficos e IA.

## Archivos PHP analizados

- `gym-system/app/controllers/ProgresoController.php`
- `gym-system/app/models/Progreso.php`
- `gym-system/app/views/progreso/ver.php`

## Tablas involucradas

- `medidas` — peso, grasa, cintura, brazo, fecha
- `rutinas` — dia1–dia6, observaciones, fecha_asignacion

Sin cambios de esquema (tablas legacy).

## Reglas de negocio detectadas

- Medidas ordenadas ASC por fecha para gráficos  s 
- Al menos una métrica por registro de medida
- Rutina: INSERT nueva versión (historial), no UPDATE
- Rutina actual = último registro por `id DESC`
- Eliminar medida permitido (PHP `eliminar_medida`)
- Socio solo ve su propio progreso y rutina
- Crear medidas/rutinas: admin y entrenador
- Recepcionista: lectura de progreso, sin crear medidas

## Nuevos módulos NestJS creados

- `BodyProgressModule` — medidas corporales + series para gráficos
- `WorkoutRoutinesModule` — rutina actual e historial
- `common/utils/member-access.util.ts` — socio no accede a otros socios

## Endpoints creados

| Método | Ruta | Roles |
|--------|------|-------|
| GET | `/api/body-progress/me` | socio |
| GET | `/api/body-progress/members/:memberId/measurements` | admin, recepcionista, entrenador, socio* |
| GET | `/api/body-progress/measurements/:id` | admin, recepcionista, entrenador, socio* |
| POST | `/api/body-progress/members/:memberId/measurements` | admin, entrenador |
| DELETE | `/api/body-progress/measurements/:id` | admin, entrenador |
| GET | `/api/workout-routines/me/current` | socio |
| GET | `/api/workout-routines/members/:memberId/current` | admin, recepcionista, entrenador, socio* |
| GET | `/api/workout-routines/members/:memberId` | admin, recepcionista, entrenador, socio* |
| POST | `/api/workout-routines/members/:memberId` | admin, entrenador |

\*Socio solo si `memberId` coincide con su JWT.

## Cambios de base de datos

Ninguno.

## Pruebas realizadas

| Prueba | Resultado |
|--------|-----------|
| `npm run build` | OK |
| `npm run audit:phase-05` | 12/12 OK |
| Crear/listar medidas + chart | OK |
| Crear rutina + current + history | OK |
| Eliminar medida | OK |
| Recepcionista crear medida | 403 |
| Socio `/me` y bloqueo otro socio | OK |

## Pendientes

- Gráficos en Flutter (consume `chart` del API)
- Contexto IA Gemini (Fase 11) usará estos endpoints

## Cómo hacer rollback

Eliminar `body-progress/` y `workout-routines/` del backend. Tablas legacy intactas.

### Guía de prueba

```bash
# Terminal 1
cd backend-nest && npm run start:dev

# Terminal 2 (raíz del proyecto)
npm run audit:phase-05
```

Manual — crear medida:
```http
POST /api/body-progress/members/3/measurements
{ "measuredAt": "2026-07-02", "weight": 78.5, "bodyFat": 18.2 }
```

Manual — asignar rutina:
```http
POST /api/workout-routines/members/3
{ "day1": "Press banca 4x12", "notes": "Técnica estricta" }
```
