import '../core/models/sri_config.dart';
import 'api_client.dart';

class SriConfigService {
  SriConfigService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<SriConfig> fetchConfig() {
    return _apiClient.getData(
      '/sri-config',
      parser: (raw) => SriConfig.fromJson(raw as Map<String, dynamic>),
    );
  }
}
