import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/membership_summary.dart';
import '../../../core/models/member_user.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/billing_sri_service.dart';
import '../../../services/member_service.dart';
import '../../../widgets/membership_status_chip.dart';
import '../../../widgets/state_views.dart';

/// Detalle de socio: datos, membresía vigente e historial.
class StaffMemberDetailPage extends StatefulWidget {
  const StaffMemberDetailPage({super.key, required this.memberId});

  final int memberId;

  @override
  State<StaffMemberDetailPage> createState() => _StaffMemberDetailPageState();
}

class _StaffMemberDetailPageState extends State<StaffMemberDetailPage> {
  bool _loading = true;
  String? _error;
  MemberUser? _member;
  MembershipSummary? _membership;
  List<MembershipInfo> _history = [];

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
      final service = context.read<MemberService>();
      final results = await Future.wait([
        service.fetchMemberById(widget.memberId),
        service.fetchMemberMembership(widget.memberId),
        service.fetchMemberMembershipHistory(widget.memberId),
      ]);
      if (!mounted) return;
      setState(() {
        _member = results[0] as MemberUser;
        _membership = results[1] as MembershipSummary;
        _history = results[2] as List<MembershipInfo>;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '—';
    return DateFormat('dd/MM/yyyy').format(date.toLocal());
  }

  Future<void> _issueMembershipInvoice(MembershipInfo item) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Emitir factura SRI'),
        content: Text(
          '¿Emitir factura para la membresía ${item.planName ?? '#${item.id}'}?',
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

    try {
      final result = await context
          .read<BillingSriService>()
          .issueFromMembership(item.id);
      if (!mounted) return;
      if (result.ok && result.receiptId != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result.description ?? 'Factura emitida')),
        );
        context.push('/staff/sri/${result.receiptId}');
        await _load();
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result.description ?? 'Emisión con observaciones'),
          ),
        );
      }
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;
    final canAssign = StaffPermissions.canAssignMembership(role);
    final canBill = StaffPermissions.canUseBillingSri(role);
    final bottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      appBar: AppBar(title: Text(_member?.name ?? 'Socio')),
      floatingActionButton: canAssign && _member != null
          ? FloatingActionButton.extended(
              onPressed: () async {
                final created = await context.push<bool>(
                  '/staff/members/${widget.memberId}/membership/new',
                );
                if (created == true && mounted) await _load();
              },
              icon: const Icon(Icons.card_membership),
              label: const Text('Asignar plan'),
            )
          : null,
      body: _loading
          ? const LoadingView(message: 'Cargando socio...')
          : _error != null
          ? ErrorState(message: _error!, onRetry: _load)
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: EdgeInsets.fromLTRB(16, 16, 16, 96 + bottom),
                children: [
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Información',
                            style: Theme.of(context).textTheme.titleMedium
                                ?.copyWith(fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(height: 12),
                          _InfoRow(label: 'DNI', value: _member!.dni),
                          _InfoRow(label: 'Email', value: _member!.email ?? '—'),
                          _InfoRow(label: 'Teléfono', value: _member!.phone ?? '—'),
                          _InfoRow(
                            label: 'Estado',
                            value: _member!.status == 'activo'
                                ? 'Activo'
                                : 'Inactivo',
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(16),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Expanded(
                                child: Text(
                                  'Membresía actual',
                                  style: Theme.of(context).textTheme.titleMedium
                                      ?.copyWith(fontWeight: FontWeight.bold),
                                ),
                              ),
                              MembershipStatusChip(
                                status: _membership?.effectiveStatus ?? 'sin_membresia',
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          if (_membership?.currentMembership != null) ...[
                            _InfoRow(
                              label: 'Plan',
                              value:
                                  _membership!.currentMembership!.planName ??
                                  '—',
                            ),
                            _InfoRow(
                              label: 'Inicio',
                              value: _formatDate(
                                _membership!.currentMembership!.startDate,
                              ),
                            ),
                            _InfoRow(
                              label: 'Fin',
                              value: _formatDate(
                                _membership!.currentMembership!.endDate,
                              ),
                            ),
                          ] else
                            Text(
                              'Este socio no tiene membresía registrada.',
                              style: Theme.of(context).textTheme.bodyMedium
                                  ?.copyWith(
                                    color: Theme.of(
                                      context,
                                    ).colorScheme.onSurfaceVariant,
                                  ),
                            ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  if (StaffPermissions.canViewCoaching(role))
                    Card(
                      child: ListTile(
                        leading: const Icon(Icons.fitness_center),
                        title: const Text('Progreso y rutina'),
                        subtitle: const Text(
                          'Medidas corporales y rutina de entrenamiento',
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          final name = Uri.encodeComponent(_member!.name);
                          context.push(
                            '/staff/members/${widget.memberId}/coaching?name=$name',
                          );
                        },
                      ),
                    ),
                  const SizedBox(height: 16),
                  Text(
                    'Historial',
                    style: Theme.of(context).textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if (_history.isEmpty)
                    const Padding(
                      padding: EdgeInsets.symmetric(vertical: 16),
                      child: EmptyState(title: 'Sin historial de membresías'),
                    )
                  else
                    ..._history.map(
                      (item) => Card(
                        child: ListTile(
                          title: Text(item.planName ?? 'Plan'),
                          subtitle: Text(
                            '${_formatDate(item.startDate)} → ${_formatDate(item.endDate)}',
                          ),
                          trailing: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              if (canBill && item.receiptId == null)
                                IconButton(
                                  tooltip: 'Emitir factura SRI',
                                  icon: const Icon(Icons.verified_outlined),
                                  onPressed: () => _issueMembershipInvoice(item),
                                ),
                              if (canBill && item.receiptId != null)
                                IconButton(
                                  tooltip: 'Ver comprobante',
                                  icon: const Icon(Icons.receipt),
                                  onPressed: () => context.push(
                                    '/staff/sri/${item.receiptId}',
                                  ),
                                ),
                              MembershipStatusChip(
                                status: item.effectiveStatus,
                              ),
                            ],
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

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 88,
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
