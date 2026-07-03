class AttendanceRecord {
  AttendanceRecord({
    required this.id,
    required this.memberId,
    this.checkedInAt,
    this.method,
  });

  final int id;
  final int memberId;
  final DateTime? checkedInAt;
  final String? method;

  factory AttendanceRecord.fromJson(Map<String, dynamic> json) {
    return AttendanceRecord(
      id: json['id'] as int,
      memberId: json['memberId'] as int? ?? json['socio_id'] as int? ?? 0,
      checkedInAt: DateTime.tryParse(json['checkedInAt']?.toString() ?? ''),
      method: json['method']?.toString() ?? json['metodo_ingreso']?.toString(),
    );
  }
}

class AttendanceReport {
  AttendanceReport({
    required this.from,
    required this.to,
    required this.totalVisits,
    required this.averageDaily,
    required this.items,
  });

  final String from;
  final String to;
  final int totalVisits;
  final double averageDaily;
  final List<AttendanceRecord> items;

  factory AttendanceReport.fromJson(Map<String, dynamic> json) {
    final list = json['items'] as List? ?? [];
    return AttendanceReport(
      from: json['from']?.toString() ?? '',
      to: json['to']?.toString() ?? '',
      totalVisits: json['totalVisits'] as int? ?? 0,
      averageDaily: (json['averageDaily'] as num?)?.toDouble() ?? 0,
      items: list
          .whereType<Map<String, dynamic>>()
          .map(AttendanceRecord.fromJson)
          .toList(),
    );
  }
}
