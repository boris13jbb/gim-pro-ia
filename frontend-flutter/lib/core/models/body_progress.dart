class BodyMeasurement {
  BodyMeasurement({
    required this.id,
    required this.memberId,
    this.weight,
    this.bodyFat,
    this.waist,
    this.arm,
    this.measuredAt,
  });

  final int id;
  final int memberId;
  final double? weight;
  final double? bodyFat;
  final double? waist;
  final double? arm;
  final DateTime? measuredAt;

  factory BodyMeasurement.fromJson(Map<String, dynamic> json) {
    return BodyMeasurement(
      id: json['id'] as int,
      memberId: json['memberId'] as int,
      weight: _toDouble(json['weight']),
      bodyFat: _toDouble(json['bodyFat']),
      waist: _toDouble(json['waist']),
      arm: _toDouble(json['arm']),
      measuredAt: json['measuredAt'] != null
          ? DateTime.tryParse(json['measuredAt'].toString())
          : null,
    );
  }
}

class BodyProgressChart {
  BodyProgressChart({
    required this.labels,
    required this.weight,
    required this.bodyFat,
    required this.waist,
    required this.arm,
  });

  final List<String> labels;
  final List<double?> weight;
  final List<double?> bodyFat;
  final List<double?> waist;
  final List<double?> arm;

  factory BodyProgressChart.fromJson(Map<String, dynamic> json) {
    return BodyProgressChart(
      labels: (json['labels'] as List? ?? [])
          .map((e) => e.toString())
          .toList(),
      weight: _toDoubleList(json['weight']),
      bodyFat: _toDoubleList(json['bodyFat']),
      waist: _toDoubleList(json['waist']),
      arm: _toDoubleList(json['arm']),
    );
  }
}

class BodyProgressData {
  BodyProgressData({
    required this.memberId,
    required this.items,
    required this.chart,
  });

  final int memberId;
  final List<BodyMeasurement> items;
  final BodyProgressChart chart;

  factory BodyProgressData.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return BodyProgressData(
      memberId: json['memberId'] as int,
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(BodyMeasurement.fromJson)
          .toList(),
      chart: BodyProgressChart.fromJson(
        json['chart'] as Map<String, dynamic>? ?? {},
      ),
    );
  }
}

double? _toDouble(dynamic value) {
  if (value == null) return null;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString());
}

List<double?> _toDoubleList(dynamic value) {
  if (value is! List) return [];
  return value.map(_toDouble).toList();
}
