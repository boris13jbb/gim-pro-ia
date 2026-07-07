import '../core/models/downloaded_file.dart';
import '../core/models/financial_report.dart';
import 'api_client.dart';
class ReportsService {
  ReportsService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<FinancialSummary> fetchFinancialSummary({
    String? fromDate,
    String? toDate,
  }) {
    return _apiClient.getData(
      '/reports/financial/summary',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
      },
      parser: (raw) =>
          FinancialSummary.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<FinancialMovementsReport> fetchFinancialMovements({
    String? fromDate,
    String? toDate,
    int limit = 100,
  }) {
    return _apiClient.getData(
      '/reports/financial/movements',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
        'limit': limit,
      },
      parser: (raw) =>
          FinancialMovementsReport.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<DownloadedFile> exportFinancialExcel({
    String? fromDate,
    String? toDate,
  }) {
    return _apiClient.downloadFile(
      '/reports/financial/export/excel',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
      },
    );
  }

  Future<DownloadedFile> exportFinancialPdf({
    String? fromDate,
    String? toDate,
  }) {
    return _apiClient.downloadFile(
      '/reports/financial/export/pdf',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
      },
    );
  }
}
