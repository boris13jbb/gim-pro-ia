import '../core/models/membership_summary.dart';
import '../core/models/paged_members.dart';
import '../core/models/qr_card.dart';
import '../core/models/member_user.dart';
import 'api_client.dart';

class MemberService {
  MemberService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  // —— Socio (app móvil) ——

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

  // —— Staff ——

  Future<PagedMembers> fetchMembers({
    int page = 1,
    int limit = 20,
    String? search,
    String? status,
  }) {
    return _apiClient.getData(
      '/members',
      query: {
        'page': page,
        'limit': limit,
        if (search != null && search.trim().isNotEmpty) 'search': search.trim(),
        if (status != null && status.isNotEmpty) 'estado': status,
      },
      parser: (raw) => PagedMembers.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<MemberUser> fetchMemberById(int id) {
    return _apiClient.getData(
      '/members/$id',
      parser: (raw) => MemberUser.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<MembershipSummary> fetchMemberMembership(int memberId) {
    return _apiClient.getData(
      '/members/$memberId/membership',
      parser: (raw) =>
          MembershipSummary.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<List<MembershipInfo>> fetchMemberMembershipHistory(int memberId) {
    return _apiClient.getData(
      '/members/$memberId/memberships',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(MembershipInfo.fromJson)
            .toList();
      },
    );
  }

  Future<MemberUser> createMember({
    required String name,
    required String dni,
    String? email,
    String? phone,
    String? password,
  }) {
    return _apiClient.postData(
      '/members',
      body: {
        'nombre': name,
        'dni': dni,
        if (email != null && email.isNotEmpty) 'email': email,
        if (phone != null && phone.isNotEmpty) 'telefono': phone,
        if (password != null && password.isNotEmpty) 'password': password,
      },
      parser: (raw) => MemberUser.fromJson(raw as Map<String, dynamic>),
    );
  }
}
