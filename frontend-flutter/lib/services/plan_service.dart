import '../core/models/plan.dart';
import 'api_client.dart';

class PlanService {
  PlanService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  /// Planes activos para asignar membresía (admin/recepcionista).
  Future<List<Plan>> fetchActivePlans() {
    return _apiClient.getData(
      '/plans',
      query: {'estado': 'activo'},
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(Plan.fromJson)
            .where((plan) => plan.isActive)
            .toList();
      },
    );
  }
}
