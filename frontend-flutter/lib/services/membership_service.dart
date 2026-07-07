import '../core/models/membership_summary.dart';
import 'api_client.dart';

class MembershipService {
  MembershipService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  /// Registra membresía; el backend calcula fecha fin según el plan.
  Future<MembershipInfo> createMembership({
    required int memberId,
    required int planId,
    required String startDate,
  }) {
    return _apiClient.postData(
      '/memberships',
      body: {
        'memberId': memberId,
        'planId': planId,
        'startDate': startDate,
      },
      parser: (raw) =>
          MembershipInfo.fromJson(raw as Map<String, dynamic>),
    );
  }
}
