import '../core/models/attendance_staff.dart';
import '../core/models/attendance_report.dart';
import 'api_client.dart';

class AttendanceService {
  AttendanceService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  // —— Socio ——

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

  // —— Staff ——

  /// Staff: asistencias registradas hoy (conteo).
  Future<int> fetchTodayCount() async {
    final items = await fetchTodayList();
    return items.length;
  }

  /// Staff: listado completo de asistencias de hoy.
  Future<List<StaffAttendanceRecord>> fetchTodayList() {
    return _apiClient.getData(
      '/attendance/today',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(StaffAttendanceRecord.fromJson)
            .toList();
      },
    );
  }

  /// Paso 1 PHP: validar DNI sin registrar.
  Future<AttendanceAccessPreview> validateAccess(String dni) {
    return _apiClient.postData(
      '/attendance/validate',
      body: {'dni': dni.trim()},
      parser: (raw) =>
          AttendanceAccessPreview.fromJson(raw as Map<String, dynamic>),
    );
  }

  /// Paso 2 PHP: registrar tras verificación visual.
  Future<StaffAttendanceRecord> registerAttendance({
    required int memberId,
    required String method,
  }) {
    return _apiClient.postData(
      '/attendance/register',
      body: {'memberId': memberId, 'method': method},
      parser: (raw) =>
          StaffAttendanceRecord.fromJson(raw as Map<String, dynamic>),
    );
  }

  /// Flujo rápido: validar DNI/QR y registrar en un paso.
  Future<StaffAttendanceRecord> scanAndRegister({
    required String dni,
    required String method,
  }) {
    return _apiClient.postData(
      '/attendance/scan',
      body: {'dni': dni.trim(), 'method': method},
      parser: (raw) =>
          StaffAttendanceRecord.fromJson(raw as Map<String, dynamic>),
    );
  }
}