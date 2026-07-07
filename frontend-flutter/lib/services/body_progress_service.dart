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

  Future<BodyProgressData> fetchMemberProgress(int memberId) {
    return _apiClient.getData(
      '/body-progress/members/$memberId/measurements',
      parser: (raw) => BodyProgressData.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<BodyMeasurement> createMeasurement({
    required int memberId,
    required String measuredAt,
    double? weight,
    double? bodyFat,
    double? waist,
    double? arm,
  }) {
    return _apiClient.postData(
      '/body-progress/members/$memberId/measurements',
      body: {
        'measuredAt': measuredAt,
        if (weight != null) 'weight': weight,
        if (bodyFat != null) 'bodyFat': bodyFat,
        if (waist != null) 'waist': waist,
        if (arm != null) 'arm': arm,
      },
      parser: (raw) => BodyMeasurement.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<void> deleteMeasurement(int measurementId) async {
    await _apiClient.deleteData<Object?>(
      '/body-progress/measurements/$measurementId',
      parser: (raw) => raw,
    );
  }
}
