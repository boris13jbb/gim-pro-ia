import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/models/qr_card.dart';
import '../../services/member_service.dart';
import '../../widgets/state_views.dart';

class QrCardPage extends StatefulWidget {
  const QrCardPage({super.key});

  @override
  State<QrCardPage> createState() => _QrCardPageState();
}

class _QrCardPageState extends State<QrCardPage> {
  MemberService get _memberService => context.read<MemberService>();

  bool _loading = true;
  String? _error;
  QrCardData? _card;

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
      final card = await _memberService.fetchQrCard();
      if (!mounted) return;
      setState(() => _card = card);
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
      return const LoadingView(message: 'Cargando carnet...');
    }
    if (_error != null) {
      return ErrorState(message: _error!, onRetry: _load);
    }
    final card = _card;
    if (card == null) {
      return const EmptyState(title: 'No se pudo cargar el carnet');
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
          Card(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  Text(
                    card.member.name,
                    style: Theme.of(context).textTheme.titleLarge?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text('DNI: ${card.member.dni}'),
                  const SizedBox(height: 20),
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: QrImageView(
                      data: card.qrPayload,
                      version: QrVersions.auto,
                      size: 220,
                    ),
                  ),
                  const SizedBox(height: 16),
                  Chip(
                    label: Text(
                      card.canAccess ? 'Acceso permitido' : 'Acceso no permitido',
                    ),
                    backgroundColor: card.canAccess
                        ? Colors.green.shade50
                        : Colors.orange.shade50,
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
