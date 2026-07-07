import '../core/models/downloaded_file.dart';
import '../core/models/electronic_receipt.dart';
import 'api_client.dart';

class BillingSriService {
  BillingSriService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<ElectronicReceiptList> fetchReceipts({
    String? fromDate,
    String? toDate,
    String? documentType,
    String? status,
    int limit = 100,
  }) {
    return _apiClient.getData(
      '/electronic-receipts',
      query: {
        if (fromDate != null) 'fromDate': fromDate,
        if (toDate != null) 'toDate': toDate,
        if (documentType != null) 'documentType': documentType,
        if (status != null) 'status': status,
        'limit': limit,
      },
      parser: (raw) =>
          ElectronicReceiptList.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<ElectronicReceiptDetail> fetchReceiptDetail(int receiptId) {
    return _apiClient.getData(
      '/electronic-receipts/$receiptId',
      parser: (raw) =>
          ElectronicReceiptDetail.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<List<SriLogEntry>> fetchReceiptLogs(int receiptId) {
    return _apiClient.getData(
      '/electronic-receipts/$receiptId/logs',
      parser: (raw) {
        final list = raw as List? ?? [];
        return list
            .whereType<Map<String, dynamic>>()
            .map(SriLogEntry.fromJson)
            .toList();
      },
    );
  }

  Future<SriIssueResult> issueFromSale(int saleId) {
    return _apiClient.postData(
      '/electronic-receipts/issue/sale/$saleId',
      parser: (raw) => SriIssueResult.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<SriIssueResult> issueFromMembership(int membershipId) {
    return _apiClient.postData(
      '/electronic-receipts/issue/membership/$membershipId',
      parser: (raw) => SriIssueResult.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<SriIssueResult> retryAuthorization(int receiptId) {
    return _apiClient.postData(
      '/electronic-receipts/$receiptId/retry',
      parser: (raw) => SriIssueResult.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<SriIssueResult> issueCreditNote({
    required int receiptId,
    String? reasonCode,
    String? reasonDescription,
  }) {
    return _apiClient.postData(
      '/electronic-receipts/$receiptId/credit-note',
      body: {
        if (reasonCode != null) 'reasonCode': reasonCode,
        if (reasonDescription != null) 'reasonDescription': reasonDescription,
      },
      parser: (raw) => SriIssueResult.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<SriEmailResult> sendReceiptEmail({
    required int receiptId,
    String? email,
  }) {
    return _apiClient.postData(
      '/electronic-receipts/$receiptId/send-email',
      body: email != null ? {'email': email} : null,
      parser: (raw) => SriEmailResult.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<DownloadedFile> downloadPdf(int receiptId) {
    return _apiClient.downloadFile('/electronic-receipts/$receiptId/pdf');
  }

  Future<DownloadedFile> downloadXml(int receiptId) {
    return _apiClient.downloadFile('/electronic-receipts/$receiptId/xml');
  }
}
