class FinancialSummary {
  FinancialSummary({
    required this.fromDate,
    required this.toDate,
    required this.membershipIncome,
    required this.posIncome,
    required this.totalIncome,
    required this.totalExpenses,
    required this.netProfit,
    required this.activeMembers,
  });

  final String fromDate;
  final String toDate;
  final double membershipIncome;
  final double posIncome;
  final double totalIncome;
  final double totalExpenses;
  final double netProfit;
  final int activeMembers;

  factory FinancialSummary.fromJson(Map<String, dynamic> json) {
    return FinancialSummary(
      fromDate: json['fromDate']?.toString() ?? '',
      toDate: json['toDate']?.toString() ?? '',
      membershipIncome: _num(json['membershipIncome']),
      posIncome: _num(json['posIncome']),
      totalIncome: _num(json['totalIncome']),
      totalExpenses: _num(json['totalExpenses']),
      netProfit: _num(json['netProfit']),
      activeMembers: json['activeMembers'] as int? ?? 0,
    );
  }
}

class FinancialMovement {
  FinancialMovement({
    required this.date,
    required this.description,
    required this.amount,
    required this.type,
  });

  final DateTime? date;
  final String description;
  final double amount;
  final String type;

  bool get isIncome => type == 'ingreso';

  factory FinancialMovement.fromJson(Map<String, dynamic> json) {
    return FinancialMovement(
      date: DateTime.tryParse(json['date']?.toString() ?? ''),
      description: json['description']?.toString() ?? '',
      amount: _num(json['amount']),
      type: json['type']?.toString() ?? 'ingreso',
    );
  }
}

class FinancialMovementsReport {
  FinancialMovementsReport({
    required this.fromDate,
    required this.toDate,
    required this.items,
  });

  final String fromDate;
  final String toDate;
  final List<FinancialMovement> items;

  factory FinancialMovementsReport.fromJson(Map<String, dynamic> json) {
    final list = json['items'] as List? ?? [];
    return FinancialMovementsReport(
      fromDate: json['fromDate']?.toString() ?? '',
      toDate: json['toDate']?.toString() ?? '',
      items: list
          .whereType<Map<String, dynamic>>()
          .map(FinancialMovement.fromJson)
          .toList(),
    );
  }
}

double _num(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}
