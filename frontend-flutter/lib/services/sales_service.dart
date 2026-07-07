import '../core/models/sale.dart';
import 'api_client.dart';

class SalesService {
  SalesService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<Sale> createSale({
    required List<Map<String, dynamic>> items,
    double discount = 0,
    String paymentMethod = 'efectivo',
    int? memberId,
  }) {
    return _apiClient.postData(
      '/sales',
      body: {
        'items': items,
        'discount': discount,
        'paymentMethod': paymentMethod,
        if (memberId != null) 'memberId': memberId,
      },
      parser: (raw) => Sale.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<List<SaleSummary>> fetchSales({
    String? fromDate,
    String? toDate,
    int limit = 30,
  }) {
    return _apiClient.getData(
      '/sales',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
        'limit': limit,
      },
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(SaleSummary.fromJson)
            .toList();
      },
    );
  }
}
