import 'api_client.dart';

class AttendanceService {
  AttendanceService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<Map<String, dynamic>> registerSelf() {
    return _apiClient.postData(
      '/attendance/self',
      body: const <String, dynamic>{},
      parser: (raw) => raw as Map<String, dynamic>,
    );
  }
}
