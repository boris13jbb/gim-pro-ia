import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/electronic_receipt.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/billing_sri_service.dart';
import '../../../utils/file_export_helper.dart';
import '../../../widgets/sri_status_chip.dart';
import '../../../widgets/state_views.dart';

/// Detalle de comprobante SRI con acciones fiscales.
class StaffSriDetailPage extends StatefulWidget {
  const StaffSriDetailPage({super.key, required this.receiptId});

  final int receiptId;

  @override
  State<StaffSriDetailPage> createState() => _StaffSriDetailPageState();
}

class _StaffSriDetailPageState extends State<StaffSriDetailPage> {
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
  final _displayFormat = DateFormat('dd/MM/yyyy HH:mm');

  bool _loading = true;
  bool _acting = false;
  String? _error;
  ElectronicReceiptDetail? _detail;
  List<SriLogEntry> _logs = [];

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
      final service = context.read<BillingSriService>();
      final results = await Future.wait([
        service.fetchReceiptDetail(widget.receiptId),
        service.fetchReceiptLogs(widget.receiptId),
      ]);
      if (!mounted) return;
      setState(() {
        _detail = results[0] as ElectronicReceiptDetail;
        _logs = results[1] as List<SriLogEntry>;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _runAction(
    Future<void> Function() action, {
    String successMessage = 'Operación completada',
  }) async {
    setState(() => _acting = true);
    try {
      await action();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(successMessage)),
      );
      await _load();
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _downloadPdf() async {
    setState(() => _acting = true);
    try {
      final file = await context
          .read<BillingSriService>()
          .downloadPdf(widget.receiptId);
      if (!mounted) return;
      await FileExportHelper.shareDownloadedFile(file);
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _downloadXml() async {
    setState(() => _acting = true);
    try {
      final file = await context
          .read<BillingSriService>()
          .downloadXml(widget.receiptId);
      if (!mounted) return;
      await FileExportHelper.shareDownloadedFile(file);
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  Future<void> _sendEmail() async {
    final emailController = TextEditingController(
      text: _detail?.customerEmail ?? '',
    );

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Enviar por correo'),
        content: TextField(
          controller: emailController,
          decoration: const InputDecoration(
            labelText: 'Email del cliente',
            border: OutlineInputBorder(),
          ),
          keyboardType: TextInputType.emailAddress,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Enviar'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    await _runAction(() async {
      await context.read<BillingSriService>().sendReceiptEmail(
        receiptId: widget.receiptId,
        email: emailController.text.trim().isEmpty
            ? null
            : emailController.text.trim(),
      );
    }, successMessage: 'Correo enviado');
  }

  Future<void> _issueCreditNote() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Nota de crédito'),
        content: const Text(
          '¿Emitir nota de crédito sobre esta factura autorizada?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Emitir'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    await _runAction(() async {
      final result = await context.read<BillingSriService>().issueCreditNote(
        receiptId: widget.receiptId,
        reasonDescription: 'Anulación de la operación',
      );
      if (!result.ok) {
        throw ApiException(result.description ?? 'No se pudo emitir la nota');
      }
      if (result.receiptId != null && mounted) {
        context.pushReplacement('/staff/sri/${result.receiptId}');
      }
    }, successMessage: 'Nota de crédito emitida');
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;

    if (!StaffPermissions.canUseBillingSri(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('Comprobante SRI')),
        body: const ErrorState(message: 'Acceso denegado.'),
      );
    }

    final bottom = MediaQuery.of(context).padding.bottom;
    final detail = _detail;

    return Scaffold(
      appBar: AppBar(
        title: Text(detail?.documentLabel ?? 'Comprobante SRI'),
      ),
      body: _loading
          ? const LoadingView(message: 'Cargando comprobante...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : detail == null
          ? const EmptyState(title: 'Comprobante no encontrado')
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${detail.documentLabel} ${detail.fullNumber}',
                          style: Theme.of(context).textTheme.titleLarge?.copyWith(
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                      SriStatusChip(status: detail.status),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text('Cliente: ${detail.customerName ?? '—'}'),
                  Text('RUC/CI: ${detail.customerTaxId ?? '—'}'),
                  if (detail.accessKey != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        'Clave: ${detail.accessKey}',
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ),
                  const SizedBox(height: 16),
                  _InfoTile('Total', _currency.format(detail.total)),
                  _InfoTile('IVA', _currency.format(detail.taxAmount)),
                  if (detail.authorizationMessage != null)
                    _InfoTile('Autorización', detail.authorizationMessage!),
                  if (detail.errorMessage != null)
                    _InfoTile('Error', detail.errorMessage!),
                  const SizedBox(height: 16),
                  Text(
                    'Líneas',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ...detail.items.map(
                    (line) => Card(
                      child: ListTile(
                        title: Text(line.description),
                        subtitle: Text('Cant: ${line.quantity}'),
                        trailing: Text(_currency.format(line.totalLine)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Acciones',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: [
                      OutlinedButton.icon(
                        onPressed: _acting ? null : _downloadPdf,
                        icon: const Icon(Icons.picture_as_pdf_outlined),
                        label: const Text('RIDE PDF'),
                      ),
                      OutlinedButton.icon(
                        onPressed: _acting ? null : _downloadXml,
                        icon: const Icon(Icons.code),
                        label: const Text('XML'),
                      ),
                      OutlinedButton.icon(
                        onPressed: _acting ? null : _sendEmail,
                        icon: const Icon(Icons.email_outlined),
                        label: const Text('Email'),
                      ),
                      if (detail.status != 'autorizado')
                        FilledButton.icon(
                          onPressed: _acting
                              ? null
                              : () => _runAction(
                                  () async {
                                    final result = await context
                                        .read<BillingSriService>()
                                        .retryAuthorization(widget.receiptId);
                                    if (!result.ok) {
                                      throw ApiException(
                                        result.description ??
                                            'Reintento sin autorización',
                                      );
                                    }
                                  },
                                  successMessage: 'Autorización actualizada',
                                ),
                          icon: const Icon(Icons.refresh),
                          label: const Text('Reintentar SRI'),
                        ),
                      if (detail.documentType == '01' &&
                          detail.status == 'autorizado')
                        FilledButton.tonalIcon(
                          onPressed: _acting ? null : _issueCreditNote,
                          icon: const Icon(Icons.undo),
                          label: const Text('Nota de crédito'),
                        ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Logs SRI',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if (_logs.isEmpty)
                    const Text('Sin logs registrados.')
                  else
                    ..._logs.map(
                      (log) => Card(
                        child: ListTile(
                          title: Text(log.action),
                          subtitle: Text(log.message ?? '—'),
                          trailing: log.createdAt != null
                              ? Text(
                                  _displayFormat.format(log.createdAt!.toLocal()),
                                  style: Theme.of(context).textTheme.bodySmall,
                                )
                              : null,
                        ),
                      ),
                    ),
                ],
              ),
            ),
    );
  }
}

class _InfoTile extends StatelessWidget {
  const _InfoTile(this.label, this.value);

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
              ),
            ),
          ),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}
