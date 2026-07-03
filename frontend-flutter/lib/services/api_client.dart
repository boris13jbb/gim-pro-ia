import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart' show kIsWeb;

import '../core/config/api_config.dart';
import '../core/models/api_response.dart';
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
    _dio = dio ?? Dio(BaseOptions(baseUrl: ApiConfig.baseUrl));
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
    if (error.type == DioExceptionType.connectionError) {
      if (kIsWeb) {
        return 'No se pudo conectar con la API (${ApiConfig.baseUrl}). '
            'Verifique que el backend esté activo en :3000 y que CORS incluya '
            'el puerto de Flutter web (ej. http://localhost:8080).';
      }
      return 'No se pudo conectar con el servidor. Verifique la API y la red.';
    }
    return error.message ?? 'Error de red';
  }
}
