import '../core/models/cash_register.dart';
import 'api_client.dart';

class CashRegisterService {
  CashRegisterService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<CashRegister?> fetchCurrent() {
    return _apiClient.getData(
      '/cash-registers/current',
      parser: (raw) {
        if (raw == null) return null;
        return CashRegister.fromJson(raw as Map<String, dynamic>);
      },
    );
  }

  Future<CashRegisterSummary?> fetchCurrentSummary() {
    return _apiClient.getData(
      '/cash-registers/current/summary',
      parser: (raw) {
        if (raw == null) return null;
        return CashRegisterSummary.fromJson(raw as Map<String, dynamic>);
      },
    );
  }

  Future<CashRegister> openRegister(double openingAmount) {
    return _apiClient.postData(
      '/cash-registers/open',
      body: {'openingAmount': openingAmount},
      parser: (raw) => CashRegister.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<CashRegisterCloseResult> closeRegister(double closingAmount) {
    return _apiClient.postData(
      '/cash-registers/close',
      body: {'closingAmount': closingAmount},
      parser: (raw) =>
          CashRegisterCloseResult.fromJson(raw as Map<String, dynamic>),
    );
  }
}
