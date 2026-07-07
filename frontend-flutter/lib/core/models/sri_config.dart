class SriReadinessCheck {
  SriReadinessCheck({
    required this.id,
    required this.ok,
    required this.message,
  });

  final String id;
  final bool ok;
  final String message;

  factory SriReadinessCheck.fromJson(Map<String, dynamic> json) {
    return SriReadinessCheck(
      id: json['id']?.toString() ?? '',
      ok: json['ok'] == true,
      message: json['message']?.toString() ?? '',
    );
  }
}

class SriActiveSeries {
  SriActiveSeries({
    required this.id,
    required this.documentType,
    required this.series,
    required this.currentSequence,
    this.description,
  });

  final int id;
  final String documentType;
  final String series;
  final int currentSequence;
  final String? description;

  factory SriActiveSeries.fromJson(Map<String, dynamic> json) {
    return SriActiveSeries(
      id: json['id'] as int,
      documentType: json['documentType']?.toString() ?? '01',
      series: json['series']?.toString() ?? '',
      currentSequence: json['currentSequence'] as int? ?? 0,
      description: json['description']?.toString(),
    );
  }
}

class SriConfig {
  SriConfig({
    this.taxId,
    this.legalName,
    this.environment,
    this.hasCertificate = false,
    this.smtpConfigured = false,
    this.productionReady = false,
    this.readinessChecks = const [],
    this.activeSeries = const [],
  });

  final String? taxId;
  final String? legalName;
  final String? environment;
  final bool hasCertificate;
  final bool smtpConfigured;
  final bool productionReady;
  final List<SriReadinessCheck> readinessChecks;
  final List<SriActiveSeries> activeSeries;

  bool get isTestEnvironment => environment == '1';

  String get environmentLabel {
    switch (environment) {
      case '2':
        return 'Producción';
      case '1':
        return 'Pruebas';
      default:
        return 'No configurado';
    }
  }

  factory SriConfig.fromJson(Map<String, dynamic> json) {
    final checks = json['readinessChecks'] as List? ?? [];
    final series = json['activeSeries'] as List? ?? [];
    return SriConfig(
      taxId: json['taxId']?.toString(),
      legalName: json['legalName']?.toString(),
      environment: json['environment']?.toString(),
      hasCertificate: json['hasCertificate'] == true,
      smtpConfigured: json['smtpConfigured'] == true,
      productionReady: json['productionReady'] == true,
      readinessChecks: checks
          .whereType<Map<String, dynamic>>()
          .map(SriReadinessCheck.fromJson)
          .toList(),
      activeSeries: series
          .whereType<Map<String, dynamic>>()
          .map(SriActiveSeries.fromJson)
          .toList(),
    );
  }
}
