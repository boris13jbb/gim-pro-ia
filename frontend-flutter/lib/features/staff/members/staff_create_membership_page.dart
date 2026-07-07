import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/plan.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/membership_service.dart';
import '../../../services/plan_service.dart';
import '../../../widgets/state_views.dart';

/// Asigna un plan al socio; el backend calcula la fecha de fin.
class StaffCreateMembershipPage extends StatefulWidget {
  const StaffCreateMembershipPage({super.key, required this.memberId});

  final int memberId;

  @override
  State<StaffCreateMembershipPage> createState() =>
      _StaffCreateMembershipPageState();
}

class _StaffCreateMembershipPageState extends State<StaffCreateMembershipPage> {
  final _dateFormat = DateFormat('yyyy-MM-dd');

  bool _loadingPlans = true;
  bool _saving = false;
  String? _error;
  List<Plan> _plans = [];
  Plan? _selectedPlan;
  DateTime _startDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadPlans());
  }

  Future<void> _loadPlans() async {
    setState(() {
      _loadingPlans = true;
      _error = null;
    });

    try {
      final plans = await context.read<PlanService>().fetchActivePlans();
      if (!mounted) return;
      setState(() {
        _plans = plans;
        _selectedPlan = plans.isNotEmpty ? plans.first : null;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loadingPlans = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _startDate,
      firstDate: DateTime(2020),
      lastDate: DateTime(2100),
    );
    if (picked != null) setState(() => _startDate = picked);
  }

  Future<void> _submit() async {
    final plan = _selectedPlan;
    if (plan == null) {
      setState(() => _error = 'Selecciona un plan activo');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await context.read<MembershipService>().createMembership(
        memberId: widget.memberId,
        planId: plan.id,
        startDate: _dateFormat.format(_startDate),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Membresía "${plan.name}" asignada')),
      );
      context.pop(true);
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;
    if (!StaffPermissions.canAssignMembership(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Asignar membresía')),
        body: const ErrorState(
          message: 'Tu rol no tiene permiso para asignar membresías.',
        ),
      );
    }

    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final safeBottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(title: const Text('Asignar membresía')),
      body: SafeArea(
        child: _loadingPlans
            ? const LoadingView(message: 'Cargando planes...')
            : _plans.isEmpty
            ? ErrorState(
                message: _error ?? 'No hay planes activos disponibles.',
                onRetry: _loadPlans,
              )
            : ListView(
                padding: EdgeInsets.fromLTRB(
                  16,
                  16,
                  16,
                  16 + bottomInset + safeBottom,
                ),
                children: [
                  Text(
                    'Selecciona el plan y la fecha de inicio. '
                    'La fecha de fin la calcula el servidor según la duración del plan.',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                      color: Theme.of(context).colorScheme.onSurfaceVariant,
                    ),
                  ),
                  const SizedBox(height: 20),
                  DropdownMenu<Plan>(
                    expandedInsets: EdgeInsets.zero,
                    label: const Text('Plan *'),
                    initialSelection: _selectedPlan,
                    dropdownMenuEntries: _plans
                        .map(
                          (plan) => DropdownMenuEntry(
                            value: plan,
                            label:
                                '${plan.name} — \$${plan.price.toStringAsFixed(2)} (${plan.durationDays} días)',
                          ),
                        )
                        .toList(),
                    onSelected: (plan) => setState(() => _selectedPlan = plan),
                  ),
                  const SizedBox(height: 16),
                  ListTile(
                    contentPadding: EdgeInsets.zero,
                    title: const Text('Fecha de inicio'),
                    subtitle: Text(DateFormat('dd/MM/yyyy').format(_startDate)),
                    trailing: const Icon(Icons.calendar_today),
                    onTap: _pickDate,
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 12),
                    Text(
                      _error!,
                      style: TextStyle(
                        color: Theme.of(context).colorScheme.error,
                      ),
                    ),
                  ],
                  const SizedBox(height: 24),
                  FilledButton.icon(
                    onPressed: _saving ? null : _submit,
                    icon: _saving
                        ? const SizedBox(
                            width: 18,
                            height: 18,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.check),
                    label: Text(_saving ? 'Guardando...' : 'Asignar membresía'),
                  ),
                ],
              ),
      ),
    );
  }
}
