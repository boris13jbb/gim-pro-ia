import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/body_progress.dart';
import '../../../core/models/workout_routine.dart';
import '../../../core/staff_permissions.dart';
import '../../../core/theme/app_theme.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/body_progress_service.dart';
import '../../../services/workout_service.dart';
import '../../../widgets/state_views.dart';

/// Progreso físico y rutina de un socio (staff: admin/entrenador/recepcionista).
class StaffMemberCoachingPage extends StatefulWidget {
  const StaffMemberCoachingPage({
    super.key,
    required this.memberId,
    this.memberName,
  });

  final int memberId;
  final String? memberName;

  @override
  State<StaffMemberCoachingPage> createState() =>
      _StaffMemberCoachingPageState();
}

class _StaffMemberCoachingPageState extends State<StaffMemberCoachingPage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs;

  @override
  void initState() {
    super.initState();
    _tabs = TabController(length: 2, vsync: this);
  }

  @override
  void dispose() {
    _tabs.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;

    if (!StaffPermissions.canViewCoaching(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Coaching')),
        body: const ErrorState(
          message: 'No tienes permiso para ver progreso y rutinas.',
        ),
      );
    }

    final title = widget.memberName ?? 'Socio #${widget.memberId}';
    final canManage = StaffPermissions.canManageCoaching(role);

    return Scaffold(
      appBar: AppBar(
        title: Text(title),
        bottom: TabBar(
          controller: _tabs,
          tabs: const [
            Tab(text: 'Progreso', icon: Icon(Icons.monitor_weight_outlined)),
            Tab(text: 'Rutina', icon: Icon(Icons.fitness_center)),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabs,
        children: [
          _BodyProgressPanel(
            memberId: widget.memberId,
            canManage: canManage,
          ),
          _WorkoutPanel(
            memberId: widget.memberId,
            canManage: canManage,
          ),
        ],
      ),
    );
  }
}

class _BodyProgressPanel extends StatefulWidget {
  const _BodyProgressPanel({
    required this.memberId,
    required this.canManage,
  });

  final int memberId;
  final bool canManage;

  @override
  State<_BodyProgressPanel> createState() => _BodyProgressPanelState();
}

class _BodyProgressPanelState extends State<_BodyProgressPanel> {
  bool _loading = true;
  String? _error;
  BodyProgressData? _data;

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
      final data = await context
          .read<BodyProgressService>()
          .fetchMemberProgress(widget.memberId);
      if (!mounted) return;
      setState(() => _data = data);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _deleteMeasurement(BodyMeasurement item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Eliminar medida'),
        content: const Text(
          '¿Eliminar esta medida corporal? Esta acción no se puede deshacer.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Eliminar'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    try {
      await context.read<BodyProgressService>().deleteMeasurement(item.id);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Medida eliminada')),
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
    final bottom = MediaQuery.of(context).padding.bottom;

    if (_loading) {
      return const LoadingView(message: 'Cargando progreso...');
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }

    final data = _data;
    final empty = data == null || data.items.isEmpty;

    return Stack(
      children: [
        empty
            ? const EmptyState(
                title: 'Sin medidas registradas',
                subtitle: 'Registra la primera medida corporal del socio.',
              )
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 96 + bottom),
                  children: [
                    _MetricChart(
                      title: 'Peso (kg)',
                      labels: data.chart.labels,
                      values: data.chart.weight,
                      color: AppTheme.accent,
                    ),
                    const SizedBox(height: 16),
                    _MetricChart(
                      title: '% Grasa',
                      labels: data.chart.labels,
                      values: data.chart.bodyFat,
                      color: const Color(0xFF4FC3F7),
                    ),
                    const SizedBox(height: 16),
                    ...data.items.reversed.map(
                      (item) => Card(
                        child: ListTile(
                          title: Text(_formatDate(item.measuredAt, item.id)),
                          subtitle: Text(_measurementSummary(item)),
                          trailing: widget.canManage
                              ? IconButton(
                                  icon: const Icon(Icons.delete_outline),
                                  onPressed: () => _deleteMeasurement(item),
                                )
                              : null,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
        if (widget.canManage)
          Positioned(
            right: 16,
            bottom: 16 + bottom,
            child: FloatingActionButton.extended(
              onPressed: () async {
                final created = await context.push<bool>(
                  '/staff/members/${widget.memberId}/coaching/measurement/new',
                );
                if (created == true && mounted) await _load();
              },
              icon: const Icon(Icons.add),
              label: const Text('Nueva medida'),
            ),
          ),
      ],
    );
  }

  String _formatDate(DateTime? date, int id) {
    if (date == null) return 'Medida #$id';
    return DateFormat('dd/MM/yyyy').format(date.toLocal());
  }

  String _measurementSummary(BodyMeasurement item) {
    return [
      if (item.weight != null) 'Peso: ${item.weight} kg',
      if (item.bodyFat != null) 'Grasa: ${item.bodyFat}%',
      if (item.waist != null) 'Cintura: ${item.waist} cm',
      if (item.arm != null) 'Brazo: ${item.arm} cm',
    ].join(' · ');
  }
}

class _WorkoutPanel extends StatefulWidget {
  const _WorkoutPanel({
    required this.memberId,
    required this.canManage,
  });

  final int memberId;
  final bool canManage;

  @override
  State<_WorkoutPanel> createState() => _WorkoutPanelState();
}

class _WorkoutPanelState extends State<_WorkoutPanel> {
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
      final response = await context
          .read<WorkoutService>()
          .fetchMemberCurrentRoutine(widget.memberId);
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
    final bottom = MediaQuery.of(context).padding.bottom;

    if (_loading) {
      return const LoadingView(message: 'Cargando rutina...');
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }

    final routine = _routine;
    final dateFormat = DateFormat('dd/MM/yyyy');

    return Stack(
      children: [
        routine == null
            ? const EmptyState(
                title: 'Sin rutina asignada',
                subtitle: 'Asigna una rutina de entrenamiento al socio.',
              )
            : RefreshIndicator(
                onRefresh: _load,
                child: ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: EdgeInsets.fromLTRB(16, 16, 16, 96 + bottom),
                  children: [
                    if (routine.assignedAt != null)
                      Text(
                        'Asignada: ${dateFormat.format(routine.assignedAt!.toLocal())}',
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
                                style: Theme.of(context)
                                    .textTheme
                                    .titleMedium
                                    ?.copyWith(fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(height: 8),
                              Text(day.content ?? ''),
                            ],
                          ),
                        ),
                      ),
                    ),
                    if (routine.notes != null &&
                        routine.notes!.trim().isNotEmpty) ...[
                      const SizedBox(height: 8),
                      Card(
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Notas',
                                style: Theme.of(context)
                                    .textTheme
                                    .titleSmall
                                    ?.copyWith(fontWeight: FontWeight.bold),
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
              ),
        if (widget.canManage)
          Positioned(
            right: 16,
            bottom: 16 + bottom,
            child: FloatingActionButton.extended(
              onPressed: () async {
                final created = await context.push<bool>(
                  '/staff/members/${widget.memberId}/coaching/routine/new',
                );
                if (created == true && mounted) await _load();
              },
              icon: const Icon(Icons.edit_note),
              label: Text(routine == null ? 'Asignar rutina' : 'Nueva versión'),
            ),
          ),
      ],
    );
  }
}

class _MetricChart extends StatelessWidget {
  const _MetricChart({
    required this.title,
    required this.labels,
    required this.values,
    required this.color,
  });

  final String title;
  final List<String> labels;
  final List<double?> values;
  final Color color;

  @override
  Widget build(BuildContext context) {
    final points = <FlSpot>[];
    for (var i = 0; i < values.length; i++) {
      final value = values[i];
      if (value != null) {
        points.add(FlSpot(i.toDouble(), value));
      }
    }

    if (points.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              height: 180,
              child: LineChart(
                LineChartData(
                  gridData: const FlGridData(show: true),
                  titlesData: FlTitlesData(
                    bottomTitles: AxisTitles(
                      sideTitles: SideTitles(
                        showTitles: true,
                        reservedSize: 28,
                        getTitlesWidget: (value, meta) {
                          final index = value.toInt();
                          if (index < 0 || index >= labels.length) {
                            return const SizedBox.shrink();
                          }
                          return Padding(
                            padding: const EdgeInsets.only(top: 8),
                            child: Text(
                              labels[index],
                              style: Theme.of(context).textTheme.bodySmall,
                            ),
                          );
                        },
                      ),
                    ),
                    leftTitles: const AxisTitles(
                      sideTitles: SideTitles(showTitles: true, reservedSize: 40),
                    ),
                    topTitles: const AxisTitles(
                      sideTitles: SideTitles(showTitles: false),
                    ),
                    rightTitles: const AxisTitles(
                      sideTitles: SideTitles(showTitles: false),
                    ),
                  ),
                  borderData: FlBorderData(show: false),
                  lineBarsData: [
                    LineChartBarData(
                      spots: points,
                      isCurved: true,
                      color: color,
                      barWidth: 3,
                      dotData: const FlDotData(show: true),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
