import 'package:dio/dio.dart';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;

import '../core/config/api_config.dart';
import '../core/models/api_response.dart';
import '../core/models/downloaded_file.dart';
import 'auth_storage.dart';

typedef TokenRefreshCallback = Future<bool> Function();

/// Cliente HTTP centralizado con JWT y refresh automático en 401.
class ApiClient {
  ApiClient({
    required AuthStorage authStorage,
    Dio? dio,
    TokenRefreshCallback? onUnauthorized,
  }) : _authStorage = authStorage,
       _onUnauthorized = onUnauthorized {
    _dio = dio ??
        Dio(
          BaseOptions(
            baseUrl: ApiConfig.baseUrl,
            // Dio 5 usa Duration.zero por defecto; en Android eso aborta al instante.
            connectTimeout: const Duration(seconds: 15),
            receiveTimeout: const Duration(seconds: 30),
            sendTimeout: const Duration(seconds: 15),
            headers: {
              'Content-Type': 'application/json',
              if (ApiConfig.baseUrl.contains('ngrok'))
                'ngrok-skip-browser-warning': 'true',
            },
          ),
        );
    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _authStorage.readAccessToken();
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) async {
          final status = error.response?.statusCode;
          if (status == 401 && _onUnauthorized != null) {
            final refreshed = await _onUnauthorized!();
            if (refreshed) {
              final request = error.requestOptions;
              final token = await _authStorage.readAccessToken();
              request.headers['Authorization'] = 'Bearer $token';
              try {
                final response = await _dio.fetch(request);
                handler.resolve(response);
                return;
              } catch (retryError) {
                if (retryError is DioException) {
                  handler.next(retryError);
                  return;
                }
              }
            }
          }
          handler.next(error);
        },
      ),
    );
  }

  final AuthStorage _authStorage;
  final TokenRefreshCallback? _onUnauthorized;
  late final Dio _dio;

  Future<T> getData<T>(
    String path, {
    Map<String, dynamic>? query,
    required T Function(dynamic raw) parser,
  }) async {
    return _request(
      () => _dio.get(path, queryParameters: query),
      parser: parser,
    );
  }

  Future<T> postData<T>(
    String path, {
    Object? body,
    required T Function(dynamic raw) parser,
  }) async {
    return _request(() => _dio.post(path, data: body), parser: parser);
  }

  Future<T> patchData<T>(
    String path, {
    Object? body,
    required T Function(dynamic raw) parser,
  }) async {
    return _request(() => _dio.patch(path, data: body), parser: parser);
  }

  Future<T> deleteData<T>(
    String path, {
    required T Function(dynamic raw) parser,
  }) async {
    return _request(() => _dio.delete(path), parser: parser);
  }

  /// Descarga archivos binarios (export Excel/PDF). La API devuelve bytes directos.
  Future<DownloadedFile> downloadFile(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    try {
      final response = await _dio.get<List<int>>(
        path,
        queryParameters: query,
        options: Options(responseType: ResponseType.bytes),
      );
      final bytes = response.data;
      if (bytes == null || bytes.isEmpty) {
        throw ApiException('El archivo exportado está vacío');
      }

      final headers = response.headers;
      final disposition = headers.value('content-disposition') ?? '';
      final filename = _parseFilename(disposition) ?? _defaultFilename(path);
      final mimeType =
          headers.value('content-type') ?? _guessMimeType(filename);

      return DownloadedFile(
        bytes: bytes,
        filename: filename,
        mimeType: mimeType,
      );
    } on DioException catch (error) {
      final message = _extractMessage(error);
      throw ApiException(message, statusCode: error.response?.statusCode);
    }
  }

  String? _parseFilename(String disposition) {
    final utf8Match = RegExp(
      r"filename\*=UTF-8''(.+)",
      caseSensitive: false,
    ).firstMatch(disposition);
    if (utf8Match != null) {
      return Uri.decodeComponent(utf8Match.group(1)!.trim());
    }

    final simple = RegExp(
      r'filename="?([^";\n]+)"?',
      caseSensitive: false,
    ).firstMatch(disposition);
    return simple?.group(1)?.trim();
  }

  String _defaultFilename(String path) {
    if (path.contains('pdf')) return 'reporte-financiero.pdf';
    if (path.contains('excel')) return 'reporte-financiero.xlsx';
    return 'export.dat';
  }

  String _guessMimeType(String filename) {
    if (filename.endsWith('.pdf')) return 'application/pdf';
    if (filename.endsWith('.xlsx')) {
      return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }
    return 'application/octet-stream';
  }

  Future<T> _request<T>(
    Future<Response<dynamic>> Function() call, {
    required T Function(dynamic raw) parser,
  }) async {
    try {
      final response = await call();
      final data = response.data;
      if (data is! Map<String, dynamic>) {
        throw ApiException('Respuesta inválida del servidor');
      }

      final parsed = ApiResponse.fromJson(data, parser);
      if (!parsed.ok) {
        throw ApiException(parsed.errorMessage ?? 'Error de API');
      }
      return parsed.data as T;
    } on DioException catch (error) {
      final message = _extractMessage(error);
      throw ApiException(message, statusCode: error.response?.statusCode);
    }
  }

  String _extractMessage(DioException error) {
    final data = error.response?.data;
    if (data is Map<String, dynamic>) {
      final apiError = data['error'];
      if (apiError is Map && apiError['message'] != null) {
        final msg = apiError['message'];
        if (msg is List) return msg.join(', ');
        return msg.toString();
      }
    }
    if (error.type == DioExceptionType.connectionError ||
        error.type == DioExceptionType.connectionTimeout) {
      final base = ApiConfig.baseUrl;
      if (!kIsWeb && Platform.isAndroid && base.contains('127.0.0.1')) {
        return 'No se pudo conectar con $base.\n\n'
            'En teléfono físico, 127.0.0.1 es el propio móvil, no tu PC.\n'
            'Usa la IP de tu PC en la misma red WiFi, por ejemplo:\n'
            'flutter run --dart-define=API_BASE_URL=http://192.168.x.x:3001/api\n\n'
            'Luego reinstala la app o genera un APK con esa URL.';
      }
      if (!kIsWeb &&
          Platform.isAndroid &&
          base.contains('10.0.2.2')) {
        return 'No se pudo conectar. La URL 10.0.2.2 solo funciona en '
            'emulador Android, no en teléfono físico.\n\n'
            'Ejecuta la app con la IP de tu PC (misma red WiFi):\n'
            'flutter run --dart-define=API_BASE_URL=http://192.168.x.x:3001/api';
      }
      if (kIsWeb) {
        return 'No se pudo conectar con la API ($base). '
            'Verifique que el backend esté activo y que CORS incluya '
            'el puerto de Flutter web (ej. http://localhost:8080).';
      }
      return 'No se pudo conectar con $base. '
          'Verifique que el backend esté activo (npm run start:dev). '
          'En dispositivo físico use: flutter run --dart-define=API_BASE_URL=http://IP_PC:3001/api';
    }
    return error.message ?? 'Error de red';
  }
}
