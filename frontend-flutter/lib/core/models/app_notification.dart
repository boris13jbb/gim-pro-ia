/// Notificación en tiempo real recibida del backend por WebSocket (namespace
/// `/events`). Es un objeto de solo lectura que describe un aviso para el socio
/// (por ejemplo, asistencia registrada o cambio de membresía).
class AppNotification {
  const AppNotification({
    required this.type,
    required this.title,
    required this.body,
    required this.createdAt,
    this.data,
  });

  final String type;
  final String title;
  final String body;
  final DateTime createdAt;
  final Map<String, dynamic>? data;

  /// Construye la notificación desde el payload del evento `notification`.
  /// Devuelve null si el dato no tiene la forma mínima esperada, para que la
  /// capa de servicio lo descarte sin romper la UI.
  static AppNotification? fromSocket(dynamic raw) {
    if (raw is! Map) return null;

    final title = (raw['title'] ?? 'Notificación').toString();
    final body = (raw['body'] ?? '').toString();
    final type = (raw['type'] ?? 'generic').toString();
    final createdAt =
        DateTime.tryParse((raw['createdAt'] ?? '').toString())?.toLocal() ??
        DateTime.now();
    final data = raw['data'] is Map
        ? Map<String, dynamic>.from(raw['data'] as Map)
        : null;

    return AppNotification(
      type: type,
      title: title,
      body: body,
      createdAt: createdAt,
      data: data,
    );
  }
}
