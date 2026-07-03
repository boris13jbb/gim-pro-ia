class MembershipInfo {
  MembershipInfo({
    required this.id,
    required this.planName,
    this.startDate,
    this.endDate,
    required this.status,
    required this.effectiveStatus,
  });

  final int id;
  final String? planName;
  final DateTime? startDate;
  final DateTime? endDate;
  final String? status;
  final String effectiveStatus;

  factory MembershipInfo.fromJson(Map<String, dynamic> json) {
    return MembershipInfo(
      id: json['id'] as int,
      planName: json['planName']?.toString(),
      startDate: _parseDate(json['startDate']),
      endDate: _parseDate(json['endDate']),
      status: json['status']?.toString(),
      effectiveStatus: json['effectiveStatus']?.toString() ?? 'sin_membresia',
    );
  }
}

class MembershipSummary {
  MembershipSummary({
    required this.memberId,
    required this.memberStatus,
    required this.isMembershipValid,
    required this.effectiveStatus,
    this.currentMembership,
  });

  final int memberId;
  final String memberStatus;
  final bool isMembershipValid;
  final String effectiveStatus;
  final MembershipInfo? currentMembership;

  factory MembershipSummary.fromJson(Map<String, dynamic> json) {
    final current = json['currentMembership'];
    return MembershipSummary(
      memberId: json['memberId'] as int,
      memberStatus: json['memberStatus']?.toString() ?? 'activo',
      isMembershipValid: json['isMembershipValid'] == true,
      effectiveStatus: json['effectiveStatus']?.toString() ?? 'sin_membresia',
      currentMembership: current is Map<String, dynamic>
          ? MembershipInfo.fromJson(current)
          : null,
    );
  }
}

DateTime? _parseDate(dynamic value) {
  if (value == null) return null;
  return DateTime.tryParse(value.toString());
}
