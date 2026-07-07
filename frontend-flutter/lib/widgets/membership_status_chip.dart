import 'package:flutter/material.dart';

/// Chip visual para el estado efectivo de membresía (calculado en backend).
class MembershipStatusChip extends StatelessWidget {
  const MembershipStatusChip({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final (label, color) = _styleFor(status);
    return Chip(
      label: Text(label),
      backgroundColor: color.withValues(alpha: 0.15),
      side: BorderSide(color: color.withValues(alpha: 0.4)),
      labelStyle: TextStyle(color: color, fontWeight: FontWeight.w600),
      visualDensity: VisualDensity.compact,
      materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
    );
  }

  (String, Color) _styleFor(String raw) {
    switch (raw) {
      case 'activa':
        return ('Activa', Colors.green.shade700);
      case 'vencida':
        return ('Vencida', Colors.red.shade700);
      case 'suspendida':
        return ('Suspendida', Colors.orange.shade800);
      case 'cancelada':
        return ('Cancelada', Colors.grey.shade700);
      case 'sin_membresia':
        return ('Sin membresía', Colors.blueGrey);
      default:
        return (raw, Colors.blueGrey);
    }
  }
}
