import '../core/models/attendance_report.dart';
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

  Future<AttendanceReport> fetchMyAttendance({String? from, String? to}) {
    return _apiClient.getData(
      '/attendance/me',
      query: {
        if (from != null) 'from': from,
        if (to != null) 'to': to,
      },
      parser: (raw) => AttendanceReport.fromJson(raw as Map<String, dynamic>),
    );
  }
}
