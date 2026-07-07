class Plan {
  Plan({
    required this.id,
    required this.name,
    required this.price,
    required this.durationDays,
    required this.status,
    this.description,
  });

  final int id;
  final String name;
  final double price;
  final int durationDays;
  final String status;
  final String? description;

  bool get isActive => status == 'activo';

  factory Plan.fromJson(Map<String, dynamic> json) {
    return Plan(
      id: json['id'] as int,
      name: json['nombre']?.toString() ?? '',
      price: _parsePrice(json['precio']),
      durationDays: json['duracion_dias'] as int? ?? json['duracionDias'] as int? ?? 0,
      status: json['estado']?.toString() ?? 'activo',
      description: json['descripcion']?.toString(),
    );
  }
}

double _parsePrice(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  return double.tryParse(value.toString()) ?? 0;
}
