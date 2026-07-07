import '../core/models/staff_user.dart';
import 'api_client.dart';

class StaffUsersService {
  StaffUsersService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<List<StaffUser>> fetchStaffUsers() {
    return _apiClient.getData(
      '/users',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(StaffUser.fromJson)
            .toList();
      },
    );
  }

  Future<StaffUser> createStaffUser({
    required String name,
    required String email,
    required String password,
    required String role,
  }) {
    return _apiClient.postData(
      '/users',
      body: {
        'nombre': name,
        'email': email,
        'password': password,
        'rol': role,
      },
      parser: (raw) => StaffUser.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<StaffUser> updateStaffStatus({
    required int userId,
    required String status,
  }) {
    return _apiClient.patchData(
      '/users/$userId/status',
      body: {'estado': status},
      parser: (raw) => StaffUser.fromJson(raw as Map<String, dynamic>),
    );
  }
}
