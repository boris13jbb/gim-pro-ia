/// Notificación del socio (persistida en API y/o recibida por WebSocket `/events`).
class AppNotification {
  const AppNotification({
    this.id,
    required this.type,
    required this.title,
    required this.body,
    required this.createdAt,
    this.data,
    this.isRead = false,
  });

  final int? id;
  final String type;
  final String title;
  final String body;
  final DateTime createdAt;
  final Map<String, dynamic>? data;
  final bool isRead;

  AppNotification copyWith({bool? isRead}) {
    return AppNotification(
      id: id,
      type: type,
      title: title,
      body: body,
      createdAt: createdAt,
      data: data,
      isRead: isRead ?? this.isRead,
    );
  }

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
    final id = raw['id'] is int ? raw['id'] as int : int.tryParse('${raw['id']}');

    return AppNotification(
      id: id,
      type: type,
      title: title,
      body: body,
      createdAt: createdAt,
      data: data,
      isRead: raw['isRead'] == true,
    );
  }

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as int?,
      type: json['type']?.toString() ?? 'generic',
      title: json['title']?.toString() ?? 'Notificación',
      body: json['body']?.toString() ?? '',
      createdAt:
          DateTime.tryParse(json['createdAt']?.toString() ?? '')?.toLocal() ??
          DateTime.now(),
      data: json['data'] is Map
          ? Map<String, dynamic>.from(json['data'] as Map)
          : null,
      isRead: json['isRead'] == true || json['readAt'] != null,
    );
  }
}

class NotificationListResponse {
  NotificationListResponse({
    required this.items,
    required this.unreadCount,
  });

  final List<AppNotification> items;
  final int unreadCount;

  factory NotificationListResponse.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return NotificationListResponse(
      items: rawItems
          .whereType<Map<String, dynamic>>()
          .map(AppNotification.fromJson)
          .toList(),
      unreadCount: json['unreadCount'] as int? ?? 0,
    );
  }
}
