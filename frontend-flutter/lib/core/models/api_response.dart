class ApiException implements Exception {
  ApiException(this.message, {this.statusCode});

  final String message;
  final int? statusCode;

  @override
  String toString() => message;
}

class ApiResponse<T> {
  ApiResponse({required this.ok, this.data, this.errorMessage});

  final bool ok;
  final T? data;
  final String? errorMessage;

  factory ApiResponse.fromJson(
    Map<String, dynamic> json,
    T Function(dynamic raw) parser,
  ) {
    final ok = json['ok'] == true;
    if (!ok) {
      final error = json['error'];
      final message = error is Map
          ? (error['message']?.toString() ?? 'Error de API')
          : 'Error de API';
      return ApiResponse(ok: false, errorMessage: message);
    }

    return ApiResponse(ok: true, data: parser(json['data']));
  }
}
