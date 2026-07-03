import '../core/models/membership_summary.dart';
import '../core/models/qr_card.dart';
import 'api_client.dart';

class MemberService {
  MemberService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<MembershipSummary> fetchMembership() {
    return _apiClient.getData(
      '/members/me/membership',
      parser: (raw) =>
          MembershipSummary.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<List<MembershipInfo>> fetchMembershipHistory() {
    return _apiClient.getData(
      '/members/me/memberships',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(MembershipInfo.fromJson)
            .toList();
      },
    );
  }

  Future<QrCardData> fetchQrCard() {
    return _apiClient.getData(
      '/qr-access/me/card',
      parser: (raw) => QrCardData.fromJson(raw as Map<String, dynamic>),
    );
  }
}
