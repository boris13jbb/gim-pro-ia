import 'member_user.dart';

/// Resultado de `POST /attendance/validate` (paso 1 del flujo PHP).
class AttendanceAccessPreview {
  AttendanceAccessPreview({
    required this.found,
    required this.canAccess,
    required this.reason,
    required this.message,
    this.member,
    this.memberStatus,
    this.isMembershipValid,
    this.effectiveStatus,
    this.daysRemaining,
    this.endDate,
  });

  final bool found;
  final bool canAccess;
  final String reason;
  final String message;
  final MemberUser? member;
  final String? memberStatus;
  final bool? isMembershipValid;
  final String? effectiveStatus;
  final int? daysRemaining;
  final DateTime? endDate;

  factory AttendanceAccessPreview.fromJson(Map<String, dynamic> json) {
    final memberJson = json['member'];
    return AttendanceAccessPreview(
      found: json['found'] == true,
      canAccess: json['canAccess'] == true,
      reason: json['reason']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      member: memberJson is Map<String, dynamic>
          ? MemberUser.fromJson(memberJson)
          : null,
      memberStatus: json['memberStatus']?.toString(),
      isMembershipValid: json['isMembershipValid'] as bool?,
      effectiveStatus: json['effectiveStatus']?.toString(),
      daysRemaining: json['daysRemaining'] as int?,
      endDate: _parseDate(json['endDate']),
    );
  }
}

/// Registro de asistencia con datos del socio (staff).
class StaffAttendanceRecord {
  StaffAttendanceRecord({
    required this.id,
    required this.memberId,
    this.memberName,
    this.memberDni,
    this.checkedInAt,
    this.method,
    this.photoUrl,
  });

  final int id;
  final int memberId;
  final String? memberName;
  final String? memberDni;
  final DateTime? checkedInAt;
  final String? method;
  final String? photoUrl;

  factory StaffAttendanceRecord.fromJson(Map<String, dynamic> json) {
    return StaffAttendanceRecord(
      id: json['id'] as int,
      memberId: json['memberId'] as int? ?? 0,
      memberName: json['memberName']?.toString(),
      memberDni: json['memberDni']?.toString(),
      checkedInAt: _parseDate(json['checkedInAt']),
      method: json['method']?.toString(),
      photoUrl: json['photoUrl']?.toString(),
    );
  }
}

DateTime? _parseDate(dynamic value) {
  if (value == null) return null;
  return DateTime.tryParse(value.toString());
}

String methodLabel(String? method) {
  switch (method) {
    case 'manual':
      return 'Manual';
    case 'dni':
      return 'DNI';
    case 'qr':
      return 'QR';
    case 'app':
      return 'App';
    default:
      return method ?? '—';
  }
}
