import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;

/// URL base de la API NestJS.
/// Android emulador usa 10.0.2.2; web/desktop usa localhost.
/// Sobrescribir en dispositivo físico:
/// flutter run --dart-define=API_HOST=192.168.x.x
/// o flutter run --dart-define=API_BASE_URL=http://192.168.x.x:3000/api
class ApiConfig {
  ApiConfig._();

  static const String _envBaseUrl = String.fromEnvironment('API_BASE_URL');
  static const String _envHost = String.fromEnvironment('API_HOST');
  static const int _apiPort = int.fromEnvironment('API_PORT', defaultValue: 3000);

  static String get baseUrl {
    if (_envBaseUrl.isNotEmpty) {
      return _envBaseUrl;
    }
    if (kIsWeb) {
      return 'http://localhost:$_apiPort/api';
    }
    if (!kIsWeb && Platform.isAndroid) {
      // Emulador: 10.0.2.2 → localhost del PC. Físico: IP Wi‑Fi del PC (--dart-define).
      if (_envHost.isNotEmpty) {
        return 'http://$_envHost:$_apiPort/api';
      }
      return 'http://10.0.2.2:$_apiPort/api';
    }
    return 'http://localhost:$_apiPort/api';
  }

  /// Base para conexiones WebSocket (socket.io). Es la misma URL de la API sin
  /// el sufijo `/api`, ya que los gateways cuelgan del host raíz (namespaces).
  static String get socketBaseUrl {
    final url = baseUrl;
    return url.endsWith('/api') ? url.substring(0, url.length - 4) : url;
  }
}
