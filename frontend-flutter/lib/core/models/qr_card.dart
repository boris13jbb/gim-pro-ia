import 'member_user.dart';

class QrCardData {
  QrCardData({
    required this.member,
    required this.qrPayload,
    required this.canAccess,
    required this.effectiveStatus,
    required this.isMembershipValid,
    this.daysRemaining,
    this.endDate,
  });

  final MemberUser member;
  final String qrPayload;
  final bool canAccess;
  final String effectiveStatus;
  final bool isMembershipValid;
  final int? daysRemaining;
  final DateTime? endDate;

  factory QrCardData.fromJson(Map<String, dynamic> json) {
    return QrCardData(
      member: MemberUser.fromJson(json['member'] as Map<String, dynamic>),
      qrPayload: json['qrPayload']?.toString() ?? '',
      canAccess: json['canAccess'] == true,
      effectiveStatus: json['effectiveStatus']?.toString() ?? 'sin_membresia',
      isMembershipValid: json['isMembershipValid'] == true,
      daysRemaining: json['daysRemaining'] as int?,
      endDate: json['endDate'] != null
          ? DateTime.tryParse(json['endDate'].toString())
          : null,
    );
  }
}
