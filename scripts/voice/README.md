# Voz local — Iron Gym

Pipeline: **Micrófono → faster-whisper → Ollama → Piper → altavoz**

## 1. Ollama (LLM)

```powershell
ollama serve
ollama pull qwen3:8b
# alternativa: ollama pull llama3.1:8b
```

En `backend-nest/.env`:

```env
AI_PROVIDER=ollama
OLLAMA_MODEL=qwen3:8b
VOICE_PIPELINE_ENABLED=true
```

## 2. STT — faster-whisper

```powershell
cd scripts/voice
pip install -r requirements-voice.txt
```

Variables opcionales en `.env`:

```env
FASTER_WHISPER_MODEL=base
WHISPER_LANGUAGE=es
PYTHON_EXECUTABLE=python
```

## 3. TTS — Piper

Descarga Piper para Windows desde: https://github.com/rhasspy/piper/releases

Coloca el ejecutable en PATH o define en `.env`:

```env
PIPER_EXECUTABLE=C:\ruta\piper\piper.exe
PIPER_MODEL_PATH=C:\ruta\models\es_ES-sharvard-medium.onnx
PIPER_CONFIG_PATH=C:\ruta\models\es_ES-sharvard-medium.onnx.json
```

Modelos de voz español: https://github.com/rhasspy/piper/blob/master/VOICES.md

## 4. Probar

```powershell
cd backend-nest
npm run start:dev
```

`GET /api/ai/voice/status` — estado de whisper, piper y ollama.

`POST /api/ai/voice/turn` — audio multipart + JWT socio.

## 5. Flutter (teléfono físico)

```powershell
cd frontend-flutter
flutter run --dart-define-from-file=dart_defines.physical_device.json
```

La app graba audio, lo envía al backend y reproduce la respuesta en WAV.
