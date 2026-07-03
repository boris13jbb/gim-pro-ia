import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/models/workout_routine.dart';
import '../../services/workout_service.dart';
import '../../widgets/state_views.dart';

class WorkoutPage extends StatefulWidget {
  const WorkoutPage({super.key});

  @override
  State<WorkoutPage> createState() => _WorkoutPageState();
}

class _WorkoutPageState extends State<WorkoutPage> {
  WorkoutService get _service => context.read<WorkoutService>();

  bool _loading = true;
  String? _error;
  WorkoutRoutine? _routine;

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
      final response = await _service.fetchCurrentRoutine();
      if (!mounted) return;
      setState(() => _routine = response.current);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const LoadingView(message: 'Cargando rutina...');
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }
    final routine = _routine;
    if (routine == null) {
      return const EmptyState(
        title: 'Sin rutina asignada',
        subtitle: 'Tu entrenador te asignará una rutina pronto.',
      );
    }

    final dateFormat = DateFormat('dd/MM/yyyy');

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
          if (routine.assignedAt != null)
            Text(
              'Asignada: ${dateFormat.format(routine.assignedAt!)}',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          const SizedBox(height: 12),
          ...routine.days.map(
            (day) => Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      day.label,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(day.content ?? ''),
                  ],
                ),
              ),
            ),
          ),
          if (routine.notes != null && routine.notes!.trim().isNotEmpty) ...[
            const SizedBox(height: 8),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Observaciones',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(routine.notes!),
                  ],
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}
