import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../core/models/membership_summary.dart';
import '../../providers/auth_provider.dart';
import '../../services/member_service.dart';
import '../../widgets/state_views.dart';

class ProfilePage extends StatefulWidget {
  const ProfilePage({super.key});

  @override
  State<ProfilePage> createState() => _ProfilePageState();
}

class _ProfilePageState extends State<ProfilePage> {
  bool _loadingHistory = true;
  String? _historyError;
  List<MembershipInfo> _history = const [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadHistory());
  }

  Future<void> _loadHistory() async {
    setState(() {
      _loadingHistory = true;
      _historyError = null;
    });
    try {
      final history = await context.read<MemberService>().fetchMembershipHistory();
      if (!mounted) return;
      setState(() => _history = history);
    } catch (error) {
      if (!mounted) return;
      setState(() => _historyError = error.toString());
    } finally {
      if (mounted) setState(() => _loadingHistory = false);
    }
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '—';
    return '${date.day.toString().padLeft(2, '0')}/${date.month.toString().padLeft(2, '0')}/${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final bottomPadding = MediaQuery.of(context).padding.bottom;

    return RefreshIndicator(
      onRefresh: _loadHistory,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottomPadding),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CircleAvatar(
                    radius: 36,
                    backgroundImage: user?.photoUrl != null
                        ? NetworkImage(user!.photoUrl!)
                        : null,
                    child: user?.photoUrl == null
                        ? Text(
                            (user?.name.isNotEmpty == true)
                                ? user!.name[0].toUpperCase()
                                : '?',
                            style: const TextStyle(fontSize: 28),
                          )
                        : null,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    user?.name ?? 'Socio',
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if (user?.dni != null) Text('DNI: ${user!.dni}'),
                  if (user?.email != null) Text('Email: ${user!.email}'),
                  if (user?.phone != null) Text('Teléfono: ${user!.phone}'),
                  const SizedBox(height: 8),
                  Chip(label: Text('Estado: ${user?.status ?? 'activo'}')),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Text(
            'Historial de membresías',
            style: Theme.of(context).textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          if (_loadingHistory)
            const LoadingView(message: 'Cargando historial...')
          else if (_historyError != null)
            ErrorState(message: _historyError!, onRetry: _loadHistory)
          else if (_history.isEmpty)
            const Card(
              child: ListTile(
                leading: Icon(Icons.card_membership_outlined),
                title: Text('Sin membresías registradas'),
              ),
            )
          else
            ..._history.map(
              (item) => Card(
                child: ListTile(
                  title: Text(item.planName ?? 'Plan #${item.id}'),
                  subtitle: Text(
                    '${_formatDate(item.startDate)} → ${_formatDate(item.endDate)}',
                  ),
                  trailing: Chip(
                    label: Text(
                      item.effectiveStatus,
                      style: const TextStyle(fontSize: 11),
                    ),
                  ),
                ),
              ),
            ),
          const SizedBox(height: 16),
          OutlinedButton.icon(
            onPressed: () async {
              await context.read<AuthProvider>().logout();
              if (context.mounted) context.go('/login');
            },
            icon: const Icon(Icons.logout),
            label: const Text('Cerrar sesión'),
          ),
        ],
      ),
    );
  }
}
