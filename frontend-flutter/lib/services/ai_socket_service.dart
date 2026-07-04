import 'dart:async';

import 'package:socket_io_client/socket_io_client.dart' as io;

import '../core/config/api_config.dart';
import 'auth_storage.dart';

/// Error de la capa de socket del asistente IA (fallo de conexión, timeout o
/// error reportado por el servidor vía evento `ai.error`).
class AiSocketException implements Exception {
  const AiSocketException(this.message);
  final String message;

  @override
  String toString() => message;
}

/// Cliente WebSocket del asistente IA (namespace `/ai`).
///
/// Seguridad: reutiliza el access token JWT (AuthStorage) para autenticar el
/// handshake; la API key de Gemini nunca viaja al cliente. El servidor solo
/// envía texto por los eventos de streaming.
///
/// Eventos del servidor:
/// - `ai.response.chunk` { delta }  → fragmento de la respuesta
/// - `ai.response.done`  { conversationId, message } → fin del turno
/// - `ai.error`          { message } → error controlado
class AiSocketService {
  AiSocketService({AuthStorage? authStorage})
    : _authStorage = authStorage ?? AuthStorage();

  final AuthStorage _authStorage;
  io.Socket? _socket;

  // Callbacks del turno activo (el chat procesa un mensaje a la vez).
  void Function(String delta)? _onChunk;
  void Function(int conversationId)? _onDone;
  void Function(String message)? _onError;

  bool get isConnected => _socket?.connected ?? false;

  /// Conecta (si aún no lo está) y espera a que el handshake termine.
  /// Devuelve true si quedó conectado; false si falla, para que la UI use el
  /// respaldo REST sin bloquear al socio.
  Future<bool> connect() async {
    if (_socket?.connected == true) return true;

    final token = await _authStorage.readAccessToken();
    if (token == null || token.isEmpty) return false;

    if (_socket == null) {
      _socket = io.io(
        '${ApiConfig.socketBaseUrl}/ai',
        io.OptionBuilder()
            .setTransports(['websocket'])
            .disableAutoConnect()
            .setAuth({'token': token})
            .build(),
      );
      _registerListeners(_socket!);
    } else {
      // Refresca el token por si cambió desde la última conexión.
      _socket!.auth = {'token': token};
    }

    final socket = _socket!;
    final completer = Completer<bool>();

    void onConnect(dynamic _) {
      if (!completer.isCompleted) completer.complete(true);
    }

    void onError(dynamic _) {
      if (!completer.isCompleted) completer.complete(false);
    }

    socket.once('connect', onConnect);
    socket.once('connect_error', onError);
    socket.connect();

    return completer.future.timeout(
      const Duration(seconds: 6),
      onTimeout: () => socket.connected,
    );
  }

  void _registerListeners(io.Socket socket) {
    socket.on('ai.response.chunk', (data) {
      final delta = _readString(data, 'delta');
      if (delta.isNotEmpty) _onChunk?.call(delta);
    });
    socket.on('ai.response.done', (data) {
      final conversationId = _readInt(data, 'conversationId');
      if (conversationId != null) _onDone?.call(conversationId);
    });
    socket.on('ai.error', (data) {
      _onError?.call(_readString(data, 'message'));
    });
  }

  /// Envía un mensaje al asistente. Los fragmentos llegan por [onChunk]; el
  /// Future se completa con el conversationId al terminar, o con
  /// [AiSocketException] si el servidor reporta un error o se agota el tiempo.
  Future<int> sendMessage({
    required String message,
    int? conversationId,
    required void Function(String delta) onChunk,
    Duration timeout = const Duration(seconds: 45),
  }) {
    final socket = _socket;
    if (socket == null || !socket.connected) {
      return Future.error(
        const AiSocketException('Sin conexión en tiempo real'),
      );
    }

    final completer = Completer<int>();
    _onChunk = onChunk;
    _onDone = (cid) {
      if (!completer.isCompleted) completer.complete(cid);
    };
    _onError = (msg) {
      if (!completer.isCompleted) {
        completer.completeError(AiSocketException(msg));
      }
    };

    socket.emit('ai.message', {
      'message': message,
      'conversationId': ?conversationId,
    });

    return completer.future.timeout(
      timeout,
      onTimeout: () => throw const AiSocketException(
        'El asistente tardó demasiado en responder',
      ),
    );
  }

  void dispose() {
    _socket?.dispose();
    _socket = null;
  }

  String _readString(dynamic data, String key) {
    if (data is Map && data[key] != null) return data[key].toString();
    return '';
  }

  int? _readInt(dynamic data, String key) {
    if (data is Map) {
      final value = data[key];
      if (value is int) return value;
      if (value is num) return value.toInt();
      if (value is String) return int.tryParse(value);
    }
    return null;
  }
}
