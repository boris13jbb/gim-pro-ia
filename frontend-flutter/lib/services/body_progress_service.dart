import '../core/models/body_progress.dart';
import 'api_client.dart';

class BodyProgressService {
  BodyProgressService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<BodyProgressData> fetchMyProgress() {
    return _apiClient.getData(
      '/body-progress/me',
      parser: (raw) => BodyProgressData.fromJson(raw as Map<String, dynamic>),
    );
  }
}
