import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../providers/auth_provider.dart';

/// Contenedor del panel staff con navegación inferior.
/// Sin WebSockets de socio: el staff no usa notificaciones `/events` ni chat IA.
class StaffShell extends StatelessWidget {
  const StaffShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  static const _titles = ['Inicio', 'Socios', 'Asistencias'];

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final index = navigationShell.currentIndex;

    return Scaffold(
      appBar: AppBar(
        title: Text('${_titles[index]} — ${_roleShort(user?.role)}'),
        actions: [
          IconButton(
            tooltip: 'Cerrar sesión',
            onPressed: () async {
              await context.read<AuthProvider>().logout();
              if (context.mounted) context.go('/login');
            },
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: navigationShell,
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: navigationShell.goBranch,
        destinations: const [
          NavigationDestination(
            icon: Icon(Icons.dashboard_outlined),
            selectedIcon: Icon(Icons.dashboard),
            label: 'Inicio',
          ),
          NavigationDestination(
            icon: Icon(Icons.people_outline),
            selectedIcon: Icon(Icons.people),
            label: 'Socios',
          ),
          NavigationDestination(
            icon: Icon(Icons.qr_code_scanner_outlined),
            selectedIcon: Icon(Icons.qr_code_scanner),
            label: 'Asistencias',
          ),
        ],
      ),
    );
  }

  String _roleShort(String? role) {
    switch (role) {
      case 'admin':
        return 'Admin';
      case 'recepcionista':
        return 'Recepción';
      case 'entrenador':
        return 'Entrenador';
      default:
        return 'Staff';
    }
  }
}
