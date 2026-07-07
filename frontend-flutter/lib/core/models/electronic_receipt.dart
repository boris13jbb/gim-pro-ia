class ElectronicReceiptSummary {
  ElectronicReceiptSummary({
    required this.id,
    this.originType,
    this.originId,
    required this.documentType,
    this.series,
    required this.sequence,
    this.accessKey,
    this.issueDate,
    this.customerTaxId,
    this.customerName,
    required this.total,
    required this.taxAmount,
    required this.status,
    this.errorMessage,
    this.createdAt,
  });

  final int id;
  final String? originType;
  final int? originId;
  final String documentType;
  final String? series;
  final int sequence;
  final String? accessKey;
  final DateTime? issueDate;
  final String? customerTaxId;
  final String? customerName;
  final double total;
  final double taxAmount;
  final String status;
  final String? errorMessage;
  final DateTime? createdAt;

  String get documentLabel {
    switch (documentType) {
      case '01':
        return 'Factura';
      case '04':
        return 'Nota de crédito';
      default:
        return documentType;
    }
  }

  String get fullNumber {
    final serie = series ?? '—';
    final seq = sequence.toString().padLeft(9, '0');
    return '$serie-$seq';
  }

  factory ElectronicReceiptSummary.fromJson(Map<String, dynamic> json) {
    return ElectronicReceiptSummary(
      id: json['id'] as int,
      originType: json['originType']?.toString(),
      originId: json['originId'] as int?,
      documentType: json['documentType']?.toString() ?? '01',
      series: json['series']?.toString(),
      sequence: json['sequence'] as int? ?? 0,
      accessKey: json['accessKey']?.toString(),
      issueDate: _parseDate(json['issueDate']),
      customerTaxId: json['customerTaxId']?.toString(),
      customerName: json['customerName']?.toString(),
      total: _toDouble(json['total']),
      taxAmount: _toDouble(json['taxAmount']),
      status: json['status']?.toString() ?? 'pendiente',
      errorMessage: json['errorMessage']?.toString(),
      createdAt: _parseDate(json['createdAt']),
    );
  }
}

class ElectronicReceiptLine {
  ElectronicReceiptLine({
    required this.line,
    this.code,
    required this.description,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
    required this.taxLine,
    required this.totalLine,
  });

  final int line;
  final String? code;
  final String description;
  final double quantity;
  final double unitPrice;
  final double subtotal;
  final double taxLine;
  final double totalLine;

  factory ElectronicReceiptLine.fromJson(Map<String, dynamic> json) {
    return ElectronicReceiptLine(
      line: json['line'] as int? ?? 0,
      code: json['code']?.toString(),
      description: json['description']?.toString() ?? '—',
      quantity: _toDouble(json['quantity']),
      unitPrice: _toDouble(json['unitPrice']),
      subtotal: _toDouble(json['subtotal']),
      taxLine: _toDouble(json['taxLine']),
      totalLine: _toDouble(json['totalLine']),
    );
  }
}

class ElectronicReceiptDetail extends ElectronicReceiptSummary {
  ElectronicReceiptDetail({
    required super.id,
    super.originType,
    super.originId,
    required super.documentType,
    super.series,
    required super.sequence,
    super.accessKey,
    super.issueDate,
    super.customerTaxId,
    super.customerName,
    required super.total,
    required super.taxAmount,
    required super.status,
    super.errorMessage,
    super.createdAt,
    this.customerEmail,
    this.authorizationCode,
    this.authorizationMessage,
    this.hasSignedXml = false,
    this.hasAuthorizedXml = false,
    this.items = const [],
  });

  final String? customerEmail;
  final String? authorizationCode;
  final String? authorizationMessage;
  final bool hasSignedXml;
  final bool hasAuthorizedXml;
  final List<ElectronicReceiptLine> items;

  factory ElectronicReceiptDetail.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return ElectronicReceiptDetail(
      id: json['id'] as int,
      originType: json['originType']?.toString(),
      originId: json['originId'] as int?,
      documentType: json['documentType']?.toString() ?? '01',
      series: json['series']?.toString(),
      sequence: json['sequence'] as int? ?? 0,
      accessKey: json['accessKey']?.toString(),
      issueDate: _parseDate(json['issueDate']),
      customerTaxId: json['customerTaxId']?.toString(),
      customerName: json['customerName']?.toString(),
      total: _toDouble(json['total']),
      taxAmount: _toDouble(json['taxAmount']),
      status: json['status']?.toString() ?? 'pendiente',
      errorMessage: json['errorMessage']?.toString(),
      createdAt: _parseDate(json['createdAt']),
      customerEmail: json['customerEmail']?.toString(),
      authorizationCode: json['authorizationCode']?.toString(),
      authorizationMessage: json['authorizationMessage']?.toString(),
      hasSignedXml: json['hasSignedXml'] == true,
      hasAuthorizedXml: json['hasAuthorizedXml'] == true,
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(ElectronicReceiptLine.fromJson)
          .toList(),
    );
  }
}

class ElectronicReceiptList {
  ElectronicReceiptList({
    required this.fromDate,
    required this.toDate,
    required this.items,
  });

  final String fromDate;
  final String toDate;
  final List<ElectronicReceiptSummary> items;

  factory ElectronicReceiptList.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return ElectronicReceiptList(
      fromDate: json['fromDate']?.toString() ?? '',
      toDate: json['toDate']?.toString() ?? '',
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(ElectronicReceiptSummary.fromJson)
          .toList(),
    );
  }
}

class SriLogEntry {
  SriLogEntry({
    required this.id,
    required this.receiptId,
    required this.action,
    this.code,
    this.message,
    this.createdAt,
  });

  final int id;
  final int receiptId;
  final String action;
  final String? code;
  final String? message;
  final DateTime? createdAt;

  factory SriLogEntry.fromJson(Map<String, dynamic> json) {
    return SriLogEntry(
      id: json['id'] as int,
      receiptId: json['receiptId'] as int,
      action: json['action']?.toString() ?? '—',
      code: json['code']?.toString(),
      message: json['message']?.toString(),
      createdAt: _parseDate(json['createdAt']),
    );
  }
}

class SriIssueResult {
  SriIssueResult({
    required this.ok,
    this.receiptId,
    this.code,
    this.description,
    this.simulated = false,
  });

  final bool ok;
  final int? receiptId;
  final String? code;
  final String? description;
  final bool simulated;

  factory SriIssueResult.fromJson(Map<String, dynamic> json) {
    return SriIssueResult(
      ok: json['ok'] == true,
      receiptId: json['receiptId'] as int?,
      code: json['code']?.toString(),
      description: json['description']?.toString(),
      simulated: json['simulated'] == true,
    );
  }
}

class SriEmailResult {
  SriEmailResult({
    required this.sent,
    this.recipient,
    this.messageId,
  });

  final bool sent;
  final String? recipient;
  final String? messageId;

  factory SriEmailResult.fromJson(Map<String, dynamic> json) {
    return SriEmailResult(
      sent: json['sent'] == true,
      recipient: json['recipient']?.toString(),
      messageId: json['messageId']?.toString(),
    );
  }
}

double _toDouble(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}

DateTime? _parseDate(dynamic value) {
  if (value == null) return null;
  return DateTime.tryParse(value.toString());
}

String sriStatusLabel(String status) {
  switch (status) {
    case 'autorizado':
      return 'Autorizado';
    case 'pendiente':
      return 'Pendiente';
    case 'recibida':
      return 'Recibida';
    case 'devuelta':
      return 'Devuelta';
    case 'no_autorizado':
      return 'No autorizado';
    case 'anulado':
      return 'Anulado';
    case 'error':
      return 'Error';
    default:
      return status;
  }
}
