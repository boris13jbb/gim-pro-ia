class CashRegister {
  CashRegister({
    required this.id,
    required this.userId,
    required this.openingAmount,
    required this.totalSales,
    required this.totalExpenses,
    required this.difference,
    required this.status,
    this.userName,
    this.closingAmount,
    this.openedAt,
    this.closedAt,
  });

  final int id;
  final int userId;
  final String? userName;
  final double openingAmount;
  final double? closingAmount;
  final double totalSales;
  final double totalExpenses;
  final double difference;
  final DateTime? openedAt;
  final DateTime? closedAt;
  final String status;

  bool get isOpen => status == 'abierta';

  factory CashRegister.fromJson(Map<String, dynamic> json) {
    return CashRegister(
      id: json['id'] as int,
      userId: json['userId'] as int? ?? 0,
      userName: json['userName']?.toString(),
      openingAmount: _parseNum(json['openingAmount']),
      closingAmount: json['closingAmount'] != null
          ? _parseNum(json['closingAmount'])
          : null,
      totalSales: _parseNum(json['totalSales']),
      totalExpenses: _parseNum(json['totalExpenses']),
      difference: _parseNum(json['difference']),
      openedAt: _parseDate(json['openedAt']),
      closedAt: _parseDate(json['closedAt']),
      status: json['status']?.toString() ?? 'abierta',
    );
  }
}

class CashRegisterSummary {
  CashRegisterSummary({
    required this.register,
    required this.totalSales,
    required this.totalExpenses,
    required this.salesCount,
    required this.expectedAmount,
  });

  final CashRegister register;
  final double totalSales;
  final double totalExpenses;
  final int salesCount;
  final double expectedAmount;

  factory CashRegisterSummary.fromJson(Map<String, dynamic> json) {
    return CashRegisterSummary(
      register: CashRegister.fromJson(
        json['register'] as Map<String, dynamic>,
      ),
      totalSales: _parseNum(json['totalSales']),
      totalExpenses: _parseNum(json['totalExpenses']),
      salesCount: json['salesCount'] as int? ?? 0,
      expectedAmount: _parseNum(json['expectedAmount']),
    );
  }
}

class CashRegisterCloseResult extends CashRegister {
  CashRegisterCloseResult({
    required super.id,
    required super.userId,
    required super.openingAmount,
    required super.totalSales,
    required super.totalExpenses,
    required super.difference,
    required super.status,
    required this.expectedAmount,
    required this.salesCount,
    super.userName,
    super.closingAmount,
    super.openedAt,
    super.closedAt,
  });

  final double expectedAmount;
  final int salesCount;

  factory CashRegisterCloseResult.fromJson(Map<String, dynamic> json) {
    return CashRegisterCloseResult(
      id: json['id'] as int,
      userId: json['userId'] as int? ?? 0,
      userName: json['userName']?.toString(),
      openingAmount: _parseNum(json['openingAmount']),
      closingAmount: json['closingAmount'] != null
          ? _parseNum(json['closingAmount'])
          : null,
      totalSales: _parseNum(json['totalSales']),
      totalExpenses: _parseNum(json['totalExpenses']),
      difference: _parseNum(json['difference']),
      openedAt: _parseDate(json['openedAt']),
      closedAt: _parseDate(json['closedAt']),
      status: json['status']?.toString() ?? 'cerrada',
      expectedAmount: _parseNum(json['expectedAmount']),
      salesCount: json['salesCount'] as int? ?? 0,
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
