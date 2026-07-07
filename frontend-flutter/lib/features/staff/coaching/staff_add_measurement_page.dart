import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/body_progress_service.dart';
import '../../../widgets/state_views.dart';

/// Registro de medida corporal para un socio (admin/entrenador).
class StaffAddMeasurementPage extends StatefulWidget {
  const StaffAddMeasurementPage({super.key, required this.memberId});

  final int memberId;

  @override
  State<StaffAddMeasurementPage> createState() =>
      _StaffAddMeasurementPageState();
}

class _StaffAddMeasurementPageState extends State<StaffAddMeasurementPage> {
  final _formKey = GlobalKey<FormState>();
  final _weightController = TextEditingController();
  final _bodyFatController = TextEditingController();
  final _waistController = TextEditingController();
  final _armController = TextEditingController();

  DateTime _measuredAt = DateTime.now();
  bool _saving = false;
  String? _error;

  @override
  void dispose() {
    _weightController.dispose();
    _bodyFatController.dispose();
    _waistController.dispose();
    _armController.dispose();
    super.dispose();
  }

  double? _parseOptional(String value) {
    if (value.trim().isEmpty) return null;
    return double.tryParse(value.replaceAll(',', '.'));
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _measuredAt,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );
    if (picked != null) setState(() => _measuredAt = picked);
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    final weight = _parseOptional(_weightController.text);
    final bodyFat = _parseOptional(_bodyFatController.text);
    final waist = _parseOptional(_waistController.text);
    final arm = _parseOptional(_armController.text);

    if (weight == null && bodyFat == null && waist == null && arm == null) {
      setState(() => _error = 'Ingresa al menos una medida.');
      return;
    }

    setState(() {
      _saving = true;
      _error = null;
    });

    try {
      await context.read<BodyProgressService>().createMeasurement(
        memberId: widget.memberId,
        measuredAt: DateFormat('yyyy-MM-dd').format(_measuredAt),
        weight: weight,
        bodyFat: bodyFat,
        waist: waist,
        arm: arm,
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Medida registrada')),
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
        appBar: AppBar(title: const Text('Nueva medida')),
        body: const ErrorState(
          message: 'Solo admin o entrenador pueden registrar medidas.',
        ),
      );
    }

    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final safeBottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(title: const Text('Nueva medida corporal')),
      body: SafeArea(
        child: Form(
          key: _formKey,
          child: ListView(
            padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottomInset + safeBottom),
            children: [
              OutlinedButton.icon(
                onPressed: _pickDate,
                icon: const Icon(Icons.calendar_today),
                label: Text(
                  'Fecha: ${DateFormat('dd/MM/yyyy').format(_measuredAt)}',
                ),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _weightController,
                decoration: const InputDecoration(
                  labelText: 'Peso (kg)',
                  border: OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _bodyFatController,
                decoration: const InputDecoration(
                  labelText: '% Grasa corporal',
                  border: OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _waistController,
                decoration: const InputDecoration(
                  labelText: 'Cintura (cm)',
                  border: OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 12),
              TextFormField(
                controller: _armController,
                decoration: const InputDecoration(
                  labelText: 'Brazo (cm)',
                  border: OutlineInputBorder(),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
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
                label: Text(_saving ? 'Guardando...' : 'Registrar medida'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
