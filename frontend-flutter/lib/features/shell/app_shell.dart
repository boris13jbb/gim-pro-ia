import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/app_notification.dart';
import '../../services/notifications_service.dart';
import '../../services/realtime_notifications_service.dart';

/// Contenedor con la barra de navegación inferior del área de socio.
///
/// Además gestiona las notificaciones en tiempo real: conecta el socket al
/// entrar, muestra un aviso puntual (SnackBar) por cada notificación nueva y
/// expone una campana con contador de no leídas en el AppBar.
class AppShell extends StatefulWidget {
  const AppShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  @override
  State<AppShell> createState() => _AppShellState();
}

class _AppShellState extends State<AppShell> {
  late final RealtimeNotificationsService _notifications;
  StreamSubscription<AppNotification>? _subscription;

  static const _titles = [
    'Inicio',
    'Carnet QR',
    'Progreso',
    'Rutina',
    'Asistente',
    'Perfil',
  ];

  @override
  void initState() {
    super.initState();
    _notifications = context.read<RealtimeNotificationsService>();
    _subscription = _notifications.onNotification.listen(_showSnackBar);
    // Conecta tras el primer frame (el shell solo se muestra ya autenticado).
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      final api = context.read<NotificationsService>();
      await _notifications.loadPersisted(api);
      await _notifications.connect();
    });
  }

  @override
  void dispose() {
    _subscription?.cancel();
    // Cierra el socket al salir del área autenticada (p. ej. al cerrar sesión).
    _notifications.disconnect();
    super.dispose();
  }

  void _showSnackBar(AppNotification notification) {
    if (!mounted) return;
    final messenger = ScaffoldMessenger.of(context);
    messenger.hideCurrentSnackBar();
    messenger.showSnackBar(
      SnackBar(
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 4),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              notification.title,
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            if (notification.body.isNotEmpty) Text(notification.body),
          ],
        ),
      ),
    );
  }

  void _openNotifications() {
    final api = context.read<NotificationsService>();
    _notifications.markAllReadPersisted(api);
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      builder: (_) => _NotificationsSheet(notifications: _notifications),
    );
  }

  void _onTap(int index) {
    widget.navigationShell.goBranch(
      index,
      initialLocation: index == widget.navigationShell.currentIndex,
    );
  }

  @override
  Widget build(BuildContext context) {
    final index = widget.navigationShell.currentIndex;

    return Scaffold(
      appBar: AppBar(
        title: Text(_titles[index]),
        centerTitle: false,
        actions: [_NotificationBell(onPressed: _openNotifications)],
      ),
      body: widget.navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: _onTap,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.home_outlined),
            selectedIcon: Icon(Icons.home),
            label: 'Inicio',
          ),
          NavigationDestination(icon: Icon(Icons.qr_code), label: 'Carnet'),
          NavigationDestination(icon: Icon(Icons.show_chart), label: 'Progreso'),
          NavigationDestination(
            icon: Icon(Icons.fitness_center_outlined),
            selectedIcon: Icon(Icons.fitness_center),
            label: 'Rutina',
          ),
          NavigationDestination(
            icon: Icon(Icons.smart_toy_outlined),
            selectedIcon: Icon(Icons.smart_toy),
            label: 'Asistente',
          ),
          NavigationDestination(
            icon: Icon(Icons.person_outline),
            selectedIcon: Icon(Icons.person),
            label: 'Perfil',
          ),
        ],
      ),
    );
  }
}

/// Icono de notificaciones con badge de no leídas. Observa el servicio para
/// actualizar el contador en tiempo real.
class _NotificationBell extends StatelessWidget {
  const _NotificationBell({required this.onPressed});

  final VoidCallback onPressed;

  @override
  Widget build(BuildContext context) {
    final unread = context.select<RealtimeNotificationsService, int>(
      (service) => service.unreadCount,
    );

    return IconButton(
      onPressed: onPressed,
      tooltip: 'Notificaciones',
      icon: Badge(
        isLabelVisible: unread > 0,
        label: Text('$unread'),
        child: const Icon(Icons.notifications_outlined),
      ),
    );
  }
}

/// Panel inferior con el historial reciente de notificaciones del socio.
class _NotificationsSheet extends StatelessWidget {
  const _NotificationsSheet({required this.notifications});

  final RealtimeNotificationsService notifications;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SafeArea(
      child: AnimatedBuilder(
        animation: notifications,
        builder: (context, _) {
          final items = notifications.items;

          if (items.isEmpty) {
            return Padding(
              padding: const EdgeInsets.all(32),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    Icons.notifications_off_outlined,
                    size: 40,
                    color: theme.colorScheme.onSurfaceVariant,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'No tienes notificaciones todavía.',
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: theme.colorScheme.onSurfaceVariant,
                    ),
                  ),
                ],
              ),
            );
          }

          return ListView.separated(
            shrinkWrap: true,
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: items.length,
            separatorBuilder: (_, _) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final item = items[i];
              return ListTile(
                leading: Icon(_iconFor(item.type)),
                title: Text(item.title),
                subtitle: item.body.isEmpty ? null : Text(item.body),
                trailing: Text(
                  _timeLabel(item.createdAt),
                  style: theme.textTheme.bodySmall,
                ),
              );
            },
          );
        },
      ),
    );
  }

  IconData _iconFor(String type) {
    switch (type) {
      case 'attendance.registered':
        return Icons.how_to_reg;
      case 'membership.updated':
        return Icons.card_membership;
      case 'membership.expiring':
        return Icons.event_busy;
      default:
        return Icons.notifications;
    }
  }

  String _timeLabel(DateTime dateTime) {
    final hour = dateTime.hour.toString().padLeft(2, '0');
    final minute = dateTime.minute.toString().padLeft(2, '0');
    return '$hour:$minute';
  }
}
