import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:socket_io_client/socket_io_client.dart' as io;

import '../core/config/api_config.dart';
import '../core/models/app_notification.dart';
import 'auth_storage.dart';

/// Cliente WebSocket de notificaciones en tiempo real del socio (namespace
/// `/events`).
///
/// Reutiliza el mismo access token JWT (AuthStorage) para autenticar el
/// handshake, igual que el socket del asistente IA. El servidor solo emite el
/// evento `notification` a la sala del socio, por lo que nunca llegan datos de
/// otros socios.
///
/// Mantiene en memoria las notificaciones recientes y un contador de no leídas
/// para la campana del AppBar, y expone [onNotification] para mostrar un aviso
/// puntual (SnackBar) cuando llega una nueva.
class RealtimeNotificationsService extends ChangeNotifier {
  RealtimeNotificationsService({AuthStorage? authStorage})
    : _authStorage = authStorage ?? AuthStorage();

  final AuthStorage _authStorage;
  io.Socket? _socket;

  static const int _maxItems = 30;
  final List<AppNotification> _items = [];
  int _unread = 0;
  final StreamController<AppNotification> _controller =
      StreamController<AppNotification>.broadcast();

  List<AppNotification> get items => List.unmodifiable(_items);
  int get unreadCount => _unread;
  bool get isConnected => _socket?.connected ?? false;

  /// Emite cada notificación nueva para avisos puntuales (SnackBar).
  Stream<AppNotification> get onNotification => _controller.stream;

  /// Conecta (si aún no lo está). Devuelve true si quedó conectado. No lanza: si
  /// falla, la app sigue funcionando sin notificaciones en tiempo real.
  Future<bool> connect() async {
    if (_socket?.connected == true) return true;

    final token = await _authStorage.readAccessToken();
    if (token == null || token.isEmpty) return false;

    if (_socket == null) {
      _socket = io.io(
        '${ApiConfig.socketBaseUrl}/events',
        io.OptionBuilder()
            .setTransports(['websocket'])
            .disableAutoConnect()
            .setAuth({'token': token})
            .build(),
      );
      _socket!.on('notification', _handleNotification);
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

  void _handleNotification(dynamic data) {
    final notification = AppNotification.fromSocket(data);
    if (notification == null) return;

    _items.insert(0, notification);
    if (_items.length > _maxItems) {
      _items.removeRange(_maxItems, _items.length);
    }
    _unread++;
    _controller.add(notification);
    notifyListeners();
  }

  /// Marca todas las notificaciones como leídas (resetea el badge).
  void markAllRead() {
    if (_unread == 0) return;
    _unread = 0;
    notifyListeners();
  }

  /// Cierra el socket sin destruir el servicio (p. ej. al cerrar sesión). El
  /// servicio sigue vivo para poder reconectar en el próximo inicio de sesión.
  void disconnect() {
    _socket?.dispose();
    _socket = null;
  }

  @override
  void dispose() {
    _socket?.dispose();
    _socket = null;
    _controller.close();
    super.dispose();
  }
}
