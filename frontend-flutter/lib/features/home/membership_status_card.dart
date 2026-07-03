import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../../core/models/membership_summary.dart';

class MembershipStatusCard extends StatelessWidget {
  const MembershipStatusCard({super.key, required this.summary});

  final MembershipSummary summary;

  @override
  Widget build(BuildContext context) {
    final membership = summary.currentMembership;
    final dateFormat = DateFormat('dd/MM/yyyy');
    final color = summary.isMembershipValid ? Colors.green : Colors.orange;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(
                  summary.isMembershipValid ? Icons.verified : Icons.warning_amber,
                  color: color,
                ),
                const SizedBox(width: 8),
                Text(
                  _statusLabel(summary.effectiveStatus),
                  style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (membership?.planName != null)
              Text('Plan: ${membership!.planName}'),
            if (membership?.endDate != null)
              Text('Vence: ${dateFormat.format(membership!.endDate!)}'),
            const SizedBox(height: 8),
            Text(
              summary.isMembershipValid
                  ? 'Tu membresía está vigente.'
                  : 'Tu membresía no está activa. Acércate a recepción.',
              style: TextStyle(color: Colors.grey.shade700),
            ),
          ],
        ),
      ),
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'activa':
        return 'Membresía activa';
      case 'vencida':
        return 'Membresía vencida';
      case 'cancelada':
        return 'Membresía cancelada';
      case 'suspendida':
        return 'Membresía suspendida';
      default:
        return 'Sin membresía';
    }
  }
}
