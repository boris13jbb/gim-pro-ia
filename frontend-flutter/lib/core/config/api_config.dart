import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;

/// URL base de la API NestJS (REST) y origen de WebSocket.
///
/// Fase 18 — flujo principal: LAN (misma Wi‑Fi), sin ngrok.
/// REST y Socket.IO deben usar el mismo host (nunca mezclar LAN + ngrok).
///
/// Prioridad de configuración:
/// 1. `--dart-define=API_BASE_URL=http://IP-LAN:3000/api`
/// 2. `--dart-define=API_HOST=IP-LAN` (+ opcional `API_PORT`)
/// 3. Defaults por plataforma (localhost / 10.0.2.2)
///
/// Ejemplos:
/// ```bash
/// flutter run --dart-define=API_BASE_URL=http://192.168.1.50:3000/api
/// flutter run --dart-define=API_HOST=192.168.1.50
/// flutter run -d chrome --web-port=8888 --dart-define=API_BASE_URL=http://192.168.1.50:3000/api
/// ```
class ApiConfig {
  ApiConfig._();

  static const String _envBaseUrl = String.fromEnvironment('API_BASE_URL');
  static const String _envHost = String.fromEnvironment('API_HOST');
  static const int _apiPort = int.fromEnvironment('API_PORT', defaultValue: 3000);

  /// Base REST (incluye sufijo `/api`).
  static String get baseUrl {
    if (_envBaseUrl.isNotEmpty) {
      return _normalizeApiBase(_envBaseUrl);
    }
    if (_envHost.isNotEmpty) {
      return 'http://$_envHost:$_apiPort/api';
    }
    if (kIsWeb) {
      return 'http://localhost:$_apiPort/api';
    }
    if (!kIsWeb && Platform.isAndroid) {
      // Emulador: 10.0.2.2 → localhost del PC. Físico: usar API_HOST o API_BASE_URL.
      return 'http://10.0.2.2:$_apiPort/api';
    }
    return 'http://localhost:$_apiPort/api';
  }

  /// Base para Socket.IO (namespaces `/ai`, `/events`): mismo host que REST sin `/api`.
  static String get socketBaseUrl {
    final url = baseUrl;
    return url.endsWith('/api') ? url.substring(0, url.length - 4) : url;
  }

  static String _normalizeApiBase(String raw) {
    var value = raw.trim();
    while (value.endsWith('/')) {
      value = value.substring(0, value.length - 1);
    }
    if (value.endsWith('/api')) {
      return value;
    }
    return '$value/api';
  }
}
