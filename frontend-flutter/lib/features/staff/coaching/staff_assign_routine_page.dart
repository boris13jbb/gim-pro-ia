import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/workout_service.dart';
import '../../../widgets/state_views.dart';

/// Asignar nueva versión de rutina a un socio (admin/entrenador).
class StaffAssignRoutinePage extends StatefulWidget {
  const StaffAssignRoutinePage({super.key, required this.memberId});

  final int memberId;

  @override
  State<StaffAssignRoutinePage> createState() => _StaffAssignRoutinePageState();
}

class _StaffAssignRoutinePageState extends State<StaffAssignRoutinePage> {
  final _formKey = GlobalKey<FormState>();
  final _day1Controller = TextEditingController();
  final _day2Controller = TextEditingController();
  final _day3Controller = TextEditingController();
  final _day4Controller = TextEditingController();
  final _day5Controller = TextEditingController();
  final _day6Controller = TextEditingController();
  final _notesController = TextEditingController();

  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _day1Controller.dispose();
    _day2Controller.dispose();
    _day3Controller.dispose();
    _day4Controller.dispose();
    _day5Controller.dispose();
    _day6Controller.dispose();
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final hasDay = [
      _day1Controller,
      _day2Controller,
      _day3Controller,
      _day4Controller,
      _day5Controller,
      _day6Controller,
    ].any((controller) => controller.text.trim().isNotEmpty);

    if (!hasDay) {
      setState(() => _error = 'Completa al menos un día de entrenamiento.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await context.read<WorkoutService>().assignMemberRoutine(
        memberId: widget.memberId,
        day1: _day1Controller.text.trim(),
        day2: _day2Controller.text.trim(),
        day3: _day3Controller.text.trim(),
        day4: _day4Controller.text.trim(),
        day5: _day5Controller.text.trim(),
        day6: _day6Controller.text.trim(),
        notes: _notesController.text.trim(),
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Rutina asignada')),
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

    if (!StaffPermissions.canManageCoaching(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Asignar rutina')),
        body: const ErrorState(
          message: 'Solo admin o entrenador pueden asignar rutinas.',
        ),
      );
    }

    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final safeBottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(title: const Text('Asignar rutina')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottomInset + safeBottom),
            children: [
              Text(
                'Conserva historial: cada asignación crea una nueva versión.',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ),
              const SizedBox(height: 16),
              for (var i = 1; i <= 6; i++)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: TextFormField(
                    controller: [
                      _day1Controller,
                      _day2Controller,
                      _day3Controller,
                      _day4Controller,
                      _day5Controller,
                      _day6Controller,
                    ][i - 1],
                    decoration: InputDecoration(
                      labelText: 'Día $i',
                      hintText: 'Ej: Press banca 4x12',
                      border: const OutlineInputBorder(),
                    ),
                    maxLines: 3,
                  ),
                ),
              TextFormField(
                controller: _notesController,
                decoration: const InputDecoration(
                  labelText: 'Notas',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              if (_error != null) ...[
                const SizedBox(height: 12),
                Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
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
                    : const Icon(Icons.save),
                label: Text(_saving ? 'Guardando...' : 'Asignar rutina'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
