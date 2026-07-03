class MemberUser {
  MemberUser({
    required this.id,
    required this.name,
    required this.dni,
    this.email,
    this.phone,
    required this.status,
    this.photoUrl,
  });

  final int id;
  final String name;
  final String dni;
  final String? email;
  final String? phone;
  final String status;
  final String? photoUrl;

  factory MemberUser.fromJson(Map<String, dynamic> json) {
    return MemberUser(
      id: json['id'] as int,
      name: json['nombre']?.toString() ?? '',
      dni: json['dni']?.toString() ?? '',
      email: json['email']?.toString(),
      phone: json['telefono']?.toString(),
      status: json['estado']?.toString() ?? 'activo',
      photoUrl: json['photoUrl']?.toString(),
    );
  }
}

class AuthSession {
  AuthSession({
    required this.accessToken,
    required this.refreshToken,
    required this.user,
  });

  final String accessToken;
  final String refreshToken;
  final MemberUser user;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    return AuthSession(
      accessToken: json['accessToken'] as String,
      refreshToken: json['refreshToken'] as String,
      user: MemberUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }
}
