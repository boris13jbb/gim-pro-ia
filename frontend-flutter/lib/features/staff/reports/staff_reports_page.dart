import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/financial_report.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/reports_service.dart';
import '../../../utils/file_export_helper.dart';
import '../../../widgets/state_views.dart';

/// Dashboard financiero admin (KPIs + movimientos del período).
class StaffReportsPage extends StatefulWidget {
  const StaffReportsPage({super.key});

  @override
  State<StaffReportsPage> createState() => _StaffReportsPageState();
}

class _StaffReportsPageState extends State<StaffReportsPage> {
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
  final _dateFormat = DateFormat('yyyy-MM-dd');
  final _displayFormat = DateFormat('dd/MM/yyyy');

  late DateTime _fromDate;
  late DateTime _toDate;

  bool _loading = true;
  bool _exporting = false;
  String? _error;
  FinancialSummary? _summary;
  FinancialMovementsReport? _movements;

  @override
  void initState() {
    super.initState();
    final now = DateTime.now();
    _fromDate = DateTime(now.year, now.month, 1);
    _toDate = now;
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final from = _dateFormat.format(_fromDate);
    final to = _dateFormat.format(_toDate);

    try {
      final service = context.read<ReportsService>();
      final results = await Future.wait([
        service.fetchFinancialSummary(fromDate: from, toDate: to),
        service.fetchFinancialMovements(fromDate: from, toDate: to),
      ]);
      if (!mounted) return;
      setState(() {
        _summary = results[0] as FinancialSummary;
        _movements = results[1] as FinancialMovementsReport;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _export({required bool asPdf}) async {
    setState(() => _exporting = true);
    final from = _dateFormat.format(_fromDate);
    final to = _dateFormat.format(_toDate);

    try {
      final service = context.read<ReportsService>();
      final file = asPdf
          ? await service.exportFinancialPdf(fromDate: from, toDate: to)
          : await service.exportFinancialExcel(fromDate: from, toDate: to);
      if (!mounted) return;
      await FileExportHelper.shareDownloadedFile(file);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            asPdf ? 'PDF listo para compartir' : 'Excel listo para compartir',
          ),
        ),
      );
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString())),
      );
    } finally {
      if (mounted) setState(() => _exporting = false);
    }
  }

  Future<void> _pickFromDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _fromDate,
      firstDate: DateTime(2020),
      lastDate: _toDate,
    );
    if (picked != null) {
      setState(() => _fromDate = picked);
      await _load();
    }
  }

  Future<void> _pickToDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _toDate,
      firstDate: _fromDate,
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      setState(() => _toDate = picked);
      await _load();
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;

    if (!StaffPermissions.canManageAdminModules(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Reportes')),
        body: const ErrorState(
          message: 'Solo el administrador puede ver reportes financieros.',
        ),
      );
    }

    final bottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Reportes financieros'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/staff/home'),
        ),
      ),
      body: _loading
          ? const LoadingView(message: 'Cargando reportes...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _pickFromDate,
                          icon: const Icon(Icons.date_range),
                          label: Text('Desde ${_displayFormat.format(_fromDate)}'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _pickToDate,
                          icon: const Icon(Icons.date_range),
                          label: Text('Hasta ${_displayFormat.format(_toDate)}'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _exporting ? null : () => _export(asPdf: false),
                          icon: _exporting
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2),
                                )
                              : const Icon(Icons.table_chart_outlined),
                          label: const Text('Exportar Excel'),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: _exporting ? null : () => _export(asPdf: true),
                          icon: const Icon(Icons.picture_as_pdf_outlined),
                          label: const Text('Exportar PDF'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (_summary != null) ...[
                    _KpiGrid(summary: _summary!, currency: _currency),
                    const SizedBox(height: 16),
                  ],
                  Text(
                    'Movimientos',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if (_movements == null || _movements!.items.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 24),
                      child: EmptyState(
                        title: 'Sin movimientos',
                        subtitle: 'No hay ingresos ni gastos en el período.',
                      ),
                    )
                  else
                    ..._movements!.items.map(
                      (item) => Card(
                        child: ListTile(
                          leading: Icon(
                            item.isIncome
                                ? Icons.arrow_downward
                                : Icons.arrow_upward,
                            color: item.isIncome ? Colors.green : Colors.red,
                          ),
                          title: Text(item.description),
                          subtitle: item.date != null
                              ? Text(_displayFormat.format(item.date!.toLocal()))
                              : null,
                          trailing: Text(
                            '${item.isIncome ? '+' : '-'}${_currency.format(item.amount)}',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: item.isIncome ? Colors.green : Colors.red,
                            ),
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

class _KpiGrid extends StatelessWidget {
  const _KpiGrid({required this.summary, required this.currency});

  final FinancialSummary summary;
  final NumberFormat currency;

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      mainAxisSpacing: 8,
      crossAxisSpacing: 8,
      childAspectRatio: 1.6,
      children: [
        _KpiCard('Ingresos total', currency.format(summary.totalIncome)),
        _KpiCard('Utilidad neta', currency.format(summary.netProfit)),
        _KpiCard('Membresías', currency.format(summary.membershipIncome)),
        _KpiCard('Ventas POS', currency.format(summary.posIncome)),
        _KpiCard('Gastos', currency.format(summary.totalExpenses)),
        _KpiCard('Socios activos', '${summary.activeMembers}'),
      ],
    );
  }
}

class _KpiCard extends StatelessWidget {
  const _KpiCard(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text(
              label,
              style: Theme.of(context).textTheme.bodySmall?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
