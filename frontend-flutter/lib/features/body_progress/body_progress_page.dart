import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/models/body_progress.dart';
import '../../services/body_progress_service.dart';
import '../../widgets/state_views.dart';

class BodyProgressPage extends StatefulWidget {
  const BodyProgressPage({super.key});

  @override
  State<BodyProgressPage> createState() => _BodyProgressPageState();
}

class _BodyProgressPageState extends State<BodyProgressPage> {
  BodyProgressService get _service => context.read<BodyProgressService>();

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
      final data = await _service.fetchMyProgress();
      if (!mounted) return;
      setState(() => _data = data);
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
      return const LoadingView(message: 'Cargando progreso...');
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }
    final data = _data;
    if (data == null || data.items.isEmpty) {
      return const EmptyState(
        title: 'Sin medidas registradas',
        subtitle: 'Tu entrenador registrará tu progreso físico.',
      );
    }

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
          _MetricChart(
            title: 'Peso (kg)',
            labels: data.chart.labels,
            values: data.chart.weight,
            color: Colors.blue,
          ),
          const SizedBox(height: 16),
          _MetricChart(
            title: '% Grasa',
            labels: data.chart.labels,
            values: data.chart.bodyFat,
            color: Colors.orange,
          ),
          const SizedBox(height: 16),
          ...data.items.reversed.map(
            (item) => Card(
              child: ListTile(
                title: Text(
                  item.measuredAt != null
                      ? '${item.measuredAt!.day.toString().padLeft(2, '0')}/${item.measuredAt!.month.toString().padLeft(2, '0')}/${item.measuredAt!.year}'
                      : 'Medida #${item.id}',
                ),
                subtitle: Text(
                  [
                    if (item.weight != null) 'Peso: ${item.weight} kg',
                    if (item.bodyFat != null) 'Grasa: ${item.bodyFat}%',
                    if (item.waist != null) 'Cintura: ${item.waist} cm',
                    if (item.arm != null) 'Brazo: ${item.arm} cm',
                  ].join(' · '),
                ),
              ),
            ),
          ),
        ],
      ),
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
    final spots = <FlSpot>[];
    for (var i = 0; i < values.length; i++) {
      final value = values[i];
      if (value != null) {
        spots.add(FlSpot(i.toDouble(), value));
      }
    }

    if (spots.isEmpty) return const SizedBox.shrink();

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: Theme.of(context).textTheme.titleMedium),
            const SizedBox(height: 16),
            SizedBox(
              height: 200,
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
                              style: const TextStyle(fontSize: 10),
                            ),
                          );
                        },
                      ),
                    ),
                    leftTitles: const AxisTitles(
                      sideTitles: SideTitles(showTitles: true, reservedSize: 40),
                    ),
                    topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                    rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  ),
                  borderData: FlBorderData(show: true),
                  lineBarsData: [
                    LineChartBarData(
                      spots: spots,
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
