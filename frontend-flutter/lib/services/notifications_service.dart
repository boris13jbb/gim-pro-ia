import '../core/models/app_notification.dart';
import 'api_client.dart';

class NotificationsService {
  NotificationsService({required ApiClient apiClient}) : _apiClient = apiClient;

  final ApiClient _apiClient;

  Future<NotificationListResponse> fetchMyNotifications({int limit = 50}) {
    return _apiClient.getData(
      '/notifications',
      query: {'limit': limit},
      parser: (raw) =>
          NotificationListResponse.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<int> fetchUnreadCount() {
    return _apiClient.getData(
      '/notifications/unread-count',
      parser: (raw) => (raw as Map<String, dynamic>)['unreadCount'] as int? ?? 0,
    );
  }

  Future<void> markAllAsRead() async {
    await _apiClient.patchData<Object?>(
      '/notifications/read-all',
      parser: (raw) => raw,
    );
  }

  Future<void> markAsRead(int notificationId) async {
    await _apiClient.patchData<Object?>(
      '/notifications/$notificationId/read',
      parser: (raw) => raw,
    );
  }
}
