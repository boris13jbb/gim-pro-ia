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
                  // El QR se muestra siempre sobre fondo blanco para garantizar
                  // la legibilidad del escáner, independientemente del tema.
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
                  _AccessChip(canAccess: card.canAccess),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Chip de estado de acceso legible en tema claro y oscuro.
///
/// Usa un color semántico (verde/naranja) con fondo translúcido sobre la
/// superficie del tema, de modo que el texto ([onSurface]) mantiene contraste
/// tanto en modo claro como oscuro.
class _AccessChip extends StatelessWidget {
  const _AccessChip({required this.canAccess});

  final bool canAccess;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;
    final statusColor = canAccess ? Colors.green : Colors.orange;

    return Chip(
      avatar: Icon(
        canAccess ? Icons.check_circle : Icons.error_outline,
        color: statusColor,
        size: 18,
      ),
      label: Text(canAccess ? 'Acceso permitido' : 'Acceso no permitido'),
      labelStyle: TextStyle(color: colorScheme.onSurface),
      backgroundColor: statusColor.withValues(alpha: 0.15),
      side: BorderSide(color: statusColor.withValues(alpha: 0.4)),
    );
  }
}
