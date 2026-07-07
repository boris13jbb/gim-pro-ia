import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../providers/auth_provider.dart';
import '../../../services/attendance_service.dart';
import '../../../widgets/state_views.dart';
/// Panel inicial staff (slice 1): perfil real + KPI de asistencias de hoy.
class StaffHomePage extends StatefulWidget {
  const StaffHomePage({super.key});

  @override
  State<StaffHomePage> createState() => _StaffHomePageState();
}

class _StaffHomePageState extends State<StaffHomePage> {
  bool _loading = true;
  String? _error;
  int? _todayCount;

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
      final count = await context.read<AttendanceService>().fetchTodayCount();
      if (!mounted) return;
      setState(() => _todayCount = count);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _roleLabel(String? role) {
    switch (role) {
      case 'admin':
        return 'Administrador';
      case 'recepcionista':
        return 'Recepcionista';
      case 'entrenador':
        return 'Entrenador';
      default:
        return role ?? 'Staff';
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;
    final bottom = MediaQuery.of(context).padding.bottom;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
        children: [
          Text(
            'Hola, ${user?.name ?? 'Usuario'}',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            children: [
              Chip(
                label: Text(_roleLabel(user?.role)),
                avatar: const Icon(Icons.badge_outlined, size: 18),
              ),
              Chip(
                label: Text(user?.status ?? 'activo'),
                avatar: const Icon(Icons.circle, size: 10),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Tu cuenta',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _InfoRow(label: 'Email', value: user?.email ?? '—'),
                  _InfoRow(label: 'ID', value: '${user?.id ?? '—'}'),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          if (_loading)
            const Padding(
              padding: EdgeInsets.all(24),
              child: Center(child: CircularProgressIndicator()),
            )
          else if (_error != null)
            ErrorState(message: _error!, onRetry: _load)
          else
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Row(
                  children: [
                    Icon(
                      Icons.how_to_reg,
                      size: 40,
                      color: Theme.of(context).colorScheme.primary,
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Asistencias hoy',
                            style: Theme.of(context).textTheme.titleMedium,
                          ),
                          Text(
                            '${_todayCount ?? 0}',
                            style: Theme.of(context)
                                .textTheme
                                .headlineMedium
                                ?.copyWith(fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          const SizedBox(height: 24),
          Text(
            'Módulos según tu rol',
            style: Theme.of(context).textTheme.titleSmall?.copyWith(
              color: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          ),
          const SizedBox(height: 8),
          ..._modulesForRole(user?.role).map(
            (module) => ListTile(
              leading: Icon(module.icon),
              title: Text(module.title),
              subtitle: Text(module.subtitle),
              onTap: module.route == null
                  ? null
                  : () => context.go(module.route!),
            ),
          ),
        ],
      ),
    );
  }

  List<_StaffModuleHint> _modulesForRole(String? role) {
    const common = [
      _StaffModuleHint(
        Icons.people_outline,
        'Socios y membresías',
        'Alta, consulta y planes',
        '/staff/members',
      ),
      _StaffModuleHint(
        Icons.qr_code_scanner,
        'Asistencias',
        'Validación DNI/QR y registro',
        '/staff/attendance',
      ),
    ];

    switch (role) {
      case 'admin':
        return [
          ...common,
          const _StaffModuleHint(
            Icons.point_of_sale,
            'POS y caja',
            'Ventas y cierre de caja',
            '/staff/pos',
          ),
          const _StaffModuleHint(
            Icons.receipt_long,
            'Facturación SRI',
            'Comprobantes electrónicos',
            '/staff/sri',
          ),
          const _StaffModuleHint(
            Icons.assessment_outlined,
            'Reportes',
            'Financieros y exportaciones',
            '/staff/reports',
          ),
          const _StaffModuleHint(
            Icons.settings_outlined,
            'Usuarios',
            'Gestión de staff del gimnasio',
            '/staff/users',
          ),
        ];
      case 'recepcionista':
        return [
          ...common,
          const _StaffModuleHint(
            Icons.point_of_sale,
            'POS y caja',
            'Ventas y cierre de caja',
            '/staff/pos',
          ),
          const _StaffModuleHint(
            Icons.receipt_long,
            'Facturación SRI',
            'Comprobantes electrónicos',
            '/staff/sri',
          ),
        ];
      case 'entrenador':
        return [
          ...common,
          const _StaffModuleHint(
            Icons.fitness_center,
            'Rutinas y progreso',
            'Selecciona un socio para coaching',
            '/staff/members',
          ),
        ];
      default:
        return common;
    }
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 72,
            child: Text(
              label,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}

class _StaffModuleHint {
  const _StaffModuleHint(
    this.icon,
    this.title,
    this.subtitle, [
    this.route,
  ]);

  final IconData icon;
  final String title;
  final String subtitle;
  final String? route;
}
