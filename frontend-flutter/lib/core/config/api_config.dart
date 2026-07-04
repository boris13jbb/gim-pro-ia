import 'dart:io' show Platform;

import 'package:flutter/foundation.dart' show kIsWeb;

/// URL base de la API NestJS.
/// Android emulador usa 10.0.2.2; web/desktop usa localhost.
/// Sobrescribir con: flutter run --dart-define=API_BASE_URL=http://192.168.x.x:3000/api
class ApiConfig {
  ApiConfig._();

  static const String _envBaseUrl = String.fromEnvironment('API_BASE_URL');

  static String get baseUrl {
    if (_envBaseUrl.isNotEmpty) {
      return _envBaseUrl;
    }
    if (kIsWeb) {
      return 'http://localhost:3000/api';
    }
    if (!kIsWeb && Platform.isAndroid) {
      return 'http://10.0.2.2:3000/api';
    }
    return 'http://localhost:3000/api';
  }

  /// Base para conexiones WebSocket (socket.io). Es la misma URL de la API sin
  /// el sufijo `/api`, ya que los gateways cuelgan del host raíz (namespaces).
  static String get socketBaseUrl {
    final url = baseUrl;
    return url.endsWith('/api') ? url.substring(0, url.length - 4) : url;
  }
}
