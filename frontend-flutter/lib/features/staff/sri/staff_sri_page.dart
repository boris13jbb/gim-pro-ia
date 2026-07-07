import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/electronic_receipt.dart';
import '../../../core/models/sri_config.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/billing_sri_service.dart';
import '../../../services/sri_config_service.dart';
import '../../../widgets/state_views.dart';
import '../../../widgets/sri_status_chip.dart';

/// Bandeja de comprobantes electrónicos SRI (admin/recepcionista).
class StaffSriPage extends StatefulWidget {
  const StaffSriPage({super.key});

  @override
  State<StaffSriPage> createState() => _StaffSriPageState();
}

class _StaffSriPageState extends State<StaffSriPage> {
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
  final _dateFormat = DateFormat('yyyy-MM-dd');
  final _displayFormat = DateFormat('dd/MM/yyyy');

  late DateTime _fromDate;
  late DateTime _toDate;
  String? _statusFilter;
  String? _documentTypeFilter;

  bool _loading = true;
  String? _error;
  List<ElectronicReceiptSummary> _items = [];
  SriConfig? _config;

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

    try {
      final billing = context.read<BillingSriService>();
      final role = context.read<AuthProvider>().user?.role;
      final list = await billing.fetchReceipts(
        fromDate: _dateFormat.format(_fromDate),
        toDate: _dateFormat.format(_toDate),
        status: _statusFilter,
        documentType: _documentTypeFilter,
      );

      SriConfig? config;
      if (StaffPermissions.canManageAdminModules(role) && mounted) {
        try {
          config = await context.read<SriConfigService>().fetchConfig();
        } catch (_) {
          config = null;
        }
      }

      if (!mounted) return;
      setState(() {
        _items = list.items;
        _config = config;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
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

    if (!StaffPermissions.canUseBillingSri(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Facturación SRI')),
        body: const ErrorState(
          message: 'Solo admin o recepcionista pueden acceder a facturación SRI.',
        ),
      );
    }

    final bottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Facturación SRI'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/staff/home'),
        ),
      ),
      body: _loading
          ? const LoadingView(message: 'Cargando comprobantes...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
                children: [
                  if (_config != null) _ConfigCard(config: _config!),
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
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      FilterChip(
                        label: const Text('Todos'),
                        selected: _statusFilter == null,
                        onSelected: (_) {
                          setState(() => _statusFilter = null);
                          _load();
                        },
                      ),
                      for (final status in const [
                        'autorizado',
                        'pendiente',
                        'error',
                        'no_autorizado',
                      ])
                        FilterChip(
                          label: Text(sriStatusLabel(status)),
                          selected: _statusFilter == status,
                          onSelected: (_) {
                            setState(
                              () => _statusFilter =
                                  _statusFilter == status ? null : status,
                            );
                            _load();
                          },
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  if (_items.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 32),
                      child: EmptyState(
                        title: 'Sin comprobantes',
                        subtitle: 'No hay facturas en el período seleccionado.',
                      ),
                    )
                  else
                    ..._items.map(
                      (item) => Card(
                        child: ListTile(
                          leading: Icon(
                            item.documentType == '04'
                                ? Icons.undo
                                : Icons.receipt_long,
                          ),
                          title: Text(
                            '${item.documentLabel} ${item.fullNumber}',
                          ),
                          subtitle: Text(
                            '${item.customerName ?? 'Cliente'} · ${_displayFormat.format(item.issueDate ?? DateTime.now())}',
                          ),
                          trailing: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text(
                                _currency.format(item.total),
                                style: const TextStyle(fontWeight: FontWeight.bold),
                              ),
                              SriStatusChip(status: item.status),
                            ],
                          ),
                          onTap: () => context.push('/staff/sri/${item.id}'),
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

class _ConfigCard extends StatelessWidget {
  const _ConfigCard({required this.config});

  final SriConfig config;

  @override
  Widget build(BuildContext context) {
    final failed = config.readinessChecks.where((c) => !c.ok).toList();

    return Card(
      color: config.productionReady
          ? Theme.of(context).colorScheme.primaryContainer.withValues(alpha: 0.3)
          : Theme.of(context).colorScheme.errorContainer.withValues(alpha: 0.2),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Configuración fiscal',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 8),
            Text('RUC: ${config.taxId ?? '—'}'),
            Text('Ambiente: ${config.environmentLabel}'),
            Text('Certificado: ${config.hasCertificate ? 'OK' : 'Falta'}'),
            Text('SMTP: ${config.smtpConfigured ? 'Configurado' : 'No configurado'}'),
            if (failed.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Pendientes: ${failed.map((c) => c.message).join(' · ')}',
                style: TextStyle(
                  color: Theme.of(context).colorScheme.error,
                  fontSize: 12,
                ),
              ),
            ],
            const SizedBox(height: 12),
          ],
        ),
      ),
    );
  }
}
