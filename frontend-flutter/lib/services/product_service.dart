import '../core/models/product.dart';
import 'api_client.dart';

class ProductService {
  ProductService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<List<Product>> fetchActiveProducts() {
    return _apiClient.getData(
      '/products/active',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(Product.fromJson)
            .where((p) => p.isActive)
            .toList();
      },
    );
  }
}
