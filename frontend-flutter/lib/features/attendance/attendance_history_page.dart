import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../core/models/attendance_report.dart';
import '../../services/attendance_service.dart';
import '../../widgets/state_views.dart';

class AttendanceHistoryPage extends StatefulWidget {
  const AttendanceHistoryPage({super.key});

  @override
  State<AttendanceHistoryPage> createState() => _AttendanceHistoryPageState();
}

class _AttendanceHistoryPageState extends State<AttendanceHistoryPage> {
  AttendanceService get _service => context.read<AttendanceService>();

  bool _loading = true;
  String? _error;
  AttendanceReport? _report;

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
      final report = await _service.fetchMyAttendance();
      if (!mounted) return;
      setState(() => _report = report);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');

    return Scaffold(
      appBar: AppBar(title: const Text('Historial de asistencias')),
      body: _loading
          ? const LoadingView(message: 'Cargando historial...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : RefreshIndicator(
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
                  if (_report != null) ...[
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            Expanded(
                              child: Column(
                                children: [
                                  Text(
                                    '${_report!.totalVisits}',
                                    style: Theme.of(context)
                                        .textTheme
                                        .headlineMedium
                                        ?.copyWith(fontWeight: FontWeight.bold),
                                  ),
                                  const Text('Visitas en el período'),
                                ],
                              ),
                            ),
                            Expanded(
                              child: Column(
                                children: [
                                  Text(
                                    _report!.averageDaily.toStringAsFixed(1),
                                    style: Theme.of(context)
                                        .textTheme
                                        .headlineMedium
                                        ?.copyWith(fontWeight: FontWeight.bold),
                                  ),
                                  const Text('Promedio diario'),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Período: ${_report!.from} — ${_report!.to}',
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                    const SizedBox(height: 12),
                    if (_report!.items.isEmpty)
                      const Card(
                        child: ListTile(
                          leading: Icon(Icons.info_outline),
                          title: Text('No hay asistencias en este período'),
                        ),
                      )
                    else
                      ..._report!.items.map(
                        (item) => Card(
                          child: ListTile(
                            leading: const Icon(Icons.check_circle_outline),
                            title: Text(
                              item.checkedInAt != null
                                  ? dateFormat.format(item.checkedInAt!.toLocal())
                                  : 'Asistencia #${item.id}',
                            ),
                            subtitle: Text('Método: ${item.method ?? 'app'}'),
                          ),
                        ),
                      ),
                  ],
                ],
              ),
            ),
    );
  }
}
