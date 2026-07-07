import 'package:flutter/material.dart';

import '../core/models/electronic_receipt.dart';

class SriStatusChip extends StatelessWidget {
  const SriStatusChip({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = _colorForStatus(context, status);
    return Chip(
      label: Text(
        sriStatusLabel(status),
        style: TextStyle(color: color, fontSize: 11),
      ),
      visualDensity: VisualDensity.compact,
      side: BorderSide(color: color.withValues(alpha: 0.5)),
      backgroundColor: color.withValues(alpha: 0.12),
    );
  }

  Color _colorForStatus(BuildContext context, String status) {
    switch (status) {
      case 'autorizado':
        return Colors.green.shade700;
      case 'pendiente':
      case 'recibida':
        return Colors.orange.shade800;
      case 'error':
      case 'no_autorizado':
      case 'devuelta':
        return Theme.of(context).colorScheme.error;
      default:
        return Theme.of(context).colorScheme.onSurfaceVariant;
    }
  }
}
