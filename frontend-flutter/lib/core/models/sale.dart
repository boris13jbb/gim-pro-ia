class SaleItem {
  SaleItem({
    required this.id,
    required this.productId,
    required this.quantity,
    required this.unitPrice,
    required this.subtotal,
    this.productName,
  });

  final int id;
  final int productId;
  final int quantity;
  final double unitPrice;
  final double subtotal;
  final String? productName;

  factory SaleItem.fromJson(Map<String, dynamic> json) {
    return SaleItem(
      id: json['id'] as int? ?? 0,
      productId: json['productId'] as int,
      quantity: json['quantity'] as int,
      unitPrice: _parseNum(json['unitPrice']),
      subtotal: _parseNum(json['subtotal']),
      productName: json['productName']?.toString(),
    );
  }
}

class Sale {
  Sale({
    required this.id,
    required this.total,
    required this.discount,
    required this.paymentMethod,
    required this.items,
    this.memberName,
    this.cashierName,
    this.createdAt,
  });

  final int id;
  final double total;
  final double discount;
  final String? paymentMethod;
  final String? memberName;
  final String? cashierName;
  final DateTime? createdAt;
  final List<SaleItem> items;

  factory Sale.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return Sale(
      id: json['id'] as int,
      total: _parseNum(json['total']),
      discount: _parseNum(json['discount']),
      paymentMethod: json['paymentMethod']?.toString(),
      memberName: json['memberName']?.toString(),
      cashierName: json['cashierName']?.toString(),
      createdAt: _parseDate(json['createdAt']),
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(SaleItem.fromJson)
          .toList(),
    );
  }
}

class SaleSummary {
  SaleSummary({
    required this.id,
    required this.total,
    required this.discount,
    this.paymentMethod,
    this.memberName,
    this.cashierName,
    this.createdAt,
  });

  final int id;
  final double total;
  final double discount;
  final String? paymentMethod;
  final String? memberName;
  final String? cashierName;
  final DateTime? createdAt;

  factory SaleSummary.fromJson(Map<String, dynamic> json) {
    return SaleSummary(
      id: json['id'] as int,
      total: _parseNum(json['total']),
      discount: _parseNum(json['discount']),
      paymentMethod: json['paymentMethod']?.toString(),
      memberName: json['memberName']?.toString(),
      cashierName: json['cashierName']?.toString(),
      createdAt: _parseDate(json['createdAt']),
    );
  }
}

double _parseNum(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}

DateTime? _parseDate(dynamic value) {
  if (value == null) return null;
  return DateTime.tryParse(value.toString());
}

String paymentMethodLabel(String? method) {
  switch (method) {
    case 'efectivo':
      return 'Efectivo';
    case 'tarjeta':
      return 'Tarjeta';
    case 'transferencia':
      return 'Transferencia';
    default:
      return method ?? '—';
  }
}
