import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/staff_user.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/staff_users_service.dart';
import '../../../widgets/state_views.dart';

/// Listado de usuarios staff (solo admin).
class StaffUsersPage extends StatefulWidget {
  const StaffUsersPage({super.key});

  @override
  State<StaffUsersPage> createState() => _StaffUsersPageState();
}

class _StaffUsersPageState extends State<StaffUsersPage> {
  bool _loading = true;
  String? _error;
  List<StaffUser> _users = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final users = await context.read<StaffUsersService>().fetchStaffUsers();
      if (!mounted) return;
      setState(() => _users = users);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _toggleStatus(StaffUser user) async {
    final newStatus = user.isActive ? 'inactivo' : 'activo';
    try {
      await context.read<StaffUsersService>().updateStaffStatus(
        userId: user.id,
        status: newStatus,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Usuario ${user.name} → $newStatus')),
      );
      await _load();
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;
    final currentUserId = context.watch<AuthProvider>().user?.id;

    if (!StaffPermissions.canManageAdminModules(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Usuarios')),
        body: const ErrorState(
          message: 'Solo el administrador puede gestionar usuarios staff.',
        ),
      );
    }

    final bottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Usuarios staff'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/staff/home'),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          final created = await context.push<bool>('/staff/users/new');
          if (created == true && mounted) await _load();
        },
        icon: const Icon(Icons.person_add),
        label: const Text('Nuevo usuario'),
      ),
      body: _loading
          ? const LoadingView(message: 'Cargando usuarios...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : _users.isEmpty
          ? const EmptyState(
              title: 'Sin usuarios',
              subtitle: 'Crea el primer usuario staff del gimnasio.',
            )
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.separated(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(16, 16, 16, 88 + bottom),
                itemCount: _users.length,
                separatorBuilder: (_, _) => const SizedBox(height: 8),
                itemBuilder: (context, index) {
                  final user = _users[index];
                  final isSelf = user.id == currentUserId;

                  return Card(
                    child: ListTile(
                      leading: CircleAvatar(
                        child: Text(
                          user.name.isNotEmpty
                              ? user.name[0].toUpperCase()
                              : '?',
                        ),
                      ),
                      title: Text(user.name),
                      subtitle: Text(
                        '${user.email}\n${staffRoleLabel(user.role)}',
                      ),
                      isThreeLine: true,
                      trailing: isSelf
                          ? const Chip(label: Text('Tú'))
                          : Switch(
                              value: user.isActive,
                              onChanged: (_) => _toggleStatus(user),
                            ),
                    ),
                  );
                },
              ),
            ),
    );
  }
}
