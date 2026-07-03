import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/membership_summary.dart';
import '../../providers/auth_provider.dart';
import '../../services/attendance_service.dart';
import '../../services/member_service.dart';
import '../../widgets/state_views.dart';
import 'membership_status_card.dart';

class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  MemberService get _memberService => context.read<MemberService>();
  AttendanceService get _attendanceService => context.read<AttendanceService>();

  bool _loading = true;
  String? _error;
  MembershipSummary? _membership;
  bool _checkingIn = false;

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
      final summary = await _memberService.fetchMembership();
      if (!mounted) return;
      setState(() => _membership = summary);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _registerAttendance() async {
    setState(() => _checkingIn = true);
    try {
      await _attendanceService.registerSelf();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Asistencia registrada correctamente')),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString())),
      );
    } finally {
      if (mounted) setState(() => _checkingIn = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(
          16,
          16,
          16,
          16 + MediaQuery.of(context).padding.bottom,
        ),
        children: [
          Text(
            'Hola, ${user?.name ?? 'Socio'}',
            style: Theme.of(context).textTheme.headlineSmall?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: 4),
          Text('Bienvenido a tu espacio Iron Gym'),
          const SizedBox(height: 20),
          if (_loading)
            const LoadingView(message: 'Cargando membresía...')
          else if (_error != null)
            ErrorState(message: _error!, onRetry: _load)
          else if (_membership != null)
            MembershipStatusCard(summary: _membership!),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _checkingIn ? null : _registerAttendance,
            icon: _checkingIn
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.fitness_center),
            label: const Text('Registrar asistencia'),
          ),
        ],
      ),
    );
  }
}
