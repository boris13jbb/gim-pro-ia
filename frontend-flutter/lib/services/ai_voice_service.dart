import 'dart:async';
import 'dart:convert';

import 'package:audioplayers/audioplayers.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_tts/flutter_tts.dart';
import 'package:path_provider/path_provider.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:record/record.dart';
import 'package:speech_to_text/speech_recognition_error.dart';
import 'package:speech_to_text/speech_recognition_result.dart';
import 'package:speech_to_text/speech_to_text.dart';

import 'ai_service.dart';

/// Pipeline de voz:
/// - Móvil: graba audio → NestJS (Whisper + Ollama + Piper) → reproduce WAV.
/// - Web: fallback STT/TTS local del dispositivo.
class AiVoiceService {
  AiVoiceService();

  final AudioRecorder _recorder = AudioRecorder();
  final AudioPlayer _player = AudioPlayer();
  final SpeechToText _speech = SpeechToText();
  final FlutterTts _tts = FlutterTts();

  AiService? _aiService;
  bool _serverPipeline = false;
  bool _localInitialized = false;
  bool _isRecording = false;
  String? _recordingPath;
  String _lastPartial = '';

  void Function(String status)? _sessionStatusHandler;
  void Function(String message)? _sessionErrorHandler;

  bool get usesServerPipeline => _serverPipeline;
  bool get isRecording => _isRecording;
  bool get isListening => _speech.isListening;

  Future<bool> initialize({AiService? aiService}) async {
    _aiService = aiService;

    if (!kIsWeb && aiService != null) {
      try {
        final status = await aiService.getVoiceStatus();
        _serverPipeline = status.enabled;
        if (_serverPipeline) {
          final mic = await Permission.microphone.request();
          return mic.isGranted;
        }
      } catch (_) {
        _serverPipeline = false;
      }
    }

    return _initializeLocal();
  }

  Future<bool> _initializeLocal() async {
    if (_localInitialized) return true;

    if (!kIsWeb) {
      final mic = await Permission.microphone.request();
      if (!mic.isGranted) return false;
    }

    _localInitialized = await _speech.initialize(
      debugLogging: kDebugMode,
      onStatus: (status) => _sessionStatusHandler?.call(status),
      onError: (SpeechRecognitionError error) {
        _sessionErrorHandler?.call(
          error.errorMsg.isNotEmpty
              ? error.errorMsg
              : 'Error de reconocimiento de voz',
        );
      },
    );
    if (!_localInitialized) return false;

    await _tts.setLanguage('es-ES');
    await _tts.setSpeechRate(kIsWeb ? 0.9 : 0.48);
    return true;
  }

  Future<void> startRecording() async {
    if (!_serverPipeline || _isRecording) return;

    final dir = await getTemporaryDirectory();
    _recordingPath =
        '${dir.path}/gym_voice_${DateTime.now().millisecondsSinceEpoch}.wav';

    await _recorder.start(
      const RecordConfig(
        encoder: AudioEncoder.wav,
        sampleRate: 16000,
        numChannels: 1,
      ),
      path: _recordingPath!,
    );
    _isRecording = true;
  }

  Future<void> stopRecording() async {
    if (!_isRecording) return;
    await _recorder.stop();
    _isRecording = false;
  }

  Future<AiVoiceTurnResult?> sendRecording({int? conversationId}) async {
    if (!_serverPipeline || _aiService == null) return null;

    await stopRecording();
    final path = _recordingPath;
    if (path == null || path.isEmpty) return null;

    return _aiService!.sendVoiceTurn(
      filePath: path,
      conversationId: conversationId,
    );
  }

  Future<void> playServerAudio(String base64Wav) async {
    final bytes = base64Decode(base64Wav);
    await _player.stop();
    await _player.play(BytesSource(bytes));
    await _player.onPlayerComplete.first;
  }

  Future<void> speakLocal(String text) async {
    final trimmed = text.trim();
    if (trimmed.isEmpty) return;
    await _tts.stop();
    await _tts.speak(trimmed);
    await Future<void>.delayed(
      Duration(milliseconds: (trimmed.length * 55).clamp(1500, 45000)),
    );
  }

  Future<bool> startListening({
    required void Function(String partial) onPartial,
    required void Function(String finalText) onFinal,
    void Function(String message)? onError,
    void Function(String status)? onStatus,
    void Function(double level)? onSoundLevel,
  }) async {
    if (_serverPipeline) return false;
    if (!_localInitialized) {
      onError?.call('Reconocimiento de voz no disponible.');
      return false;
    }

    _lastPartial = '';
    _sessionErrorHandler = onError;
    _sessionStatusHandler = (status) {
      onStatus?.call(status);
      if (status == 'done' || status == 'notListening') {
        _sessionStatusHandler = null;
      }
    };

    final locales = await _speech.locales();
    final localeId = locales
            .where((l) => l.localeId.startsWith('es'))
            .map((l) => l.localeId)
            .firstOrNull ??
        locales.map((l) => l.localeId).firstOrNull;

    await _speech.listen(
      onResult: (SpeechRecognitionResult result) {
        final text = result.recognizedWords.trim();
        if (text.isEmpty) return;
        _lastPartial = text;
        if (result.finalResult) {
          onFinal(text);
        } else {
          onPartial(text);
        }
      },
      onSoundLevelChange: onSoundLevel,
      listenOptions: SpeechListenOptions(
        localeId: localeId,
        listenMode: ListenMode.dictation,
        pauseFor: const Duration(seconds: 1),
        listenFor: const Duration(seconds: 45),
        cancelOnError: false,
        partialResults: true,
      ),
    );
    return _speech.isListening;
  }

  Future<String> stopListeningCapture() async {
    if (_speech.isListening) await _speech.stop();
    _sessionStatusHandler = null;
    _sessionErrorHandler = null;
    return _lastPartial.trim();
  }

  Future<void> stopListening() => stopListeningCapture();

  Future<void> stopSpeaking() async {
    await _player.stop();
    await _tts.stop();
  }

  Future<void> dispose() async {
    await stopRecording();
    await stopSpeaking();
    await _recorder.dispose();
    await _player.dispose();
  }
}
