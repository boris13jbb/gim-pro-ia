/// Usuario autenticado: socio (`userType: member`) o staff (`userType: staff`).
/// El backend distingue ambos en `/auth/me` y en el login.
enum UserType { member, staff }

class AuthUser {
  AuthUser({
    required this.userType,
    required this.id,
    required this.name,
    this.email,
    this.dni,
    this.phone,
    required this.status,
    this.photoUrl,
    this.role,
  });

  final UserType userType;
  final int id;
  final String name;
  final String? email;
  final String? dni;
  final String? phone;
  final String status;
  final String? photoUrl;
  /// Rol staff: admin, recepcionista, entrenador. Socio: socio.
  final String? role;

  bool get isStaff => userType == UserType.staff;
  bool get isMember => userType == UserType.member;

  /// Parsea perfil de `/auth/me` o `user` del login.
  factory AuthUser.fromJson(Map<String, dynamic> json) {
    if (json.containsKey('rol')) {
      return AuthUser(
        userType: UserType.staff,
        id: json['id'] as int,
        name: json['nombre']?.toString() ?? '',
        email: json['email']?.toString(),
        status: json['estado']?.toString() ?? 'activo',
        role: json['rol']?.toString(),
      );
    }

    return AuthUser(
      userType: UserType.member,
      id: json['id'] as int,
      name: json['nombre']?.toString() ?? '',
      dni: json['dni']?.toString(),
      email: json['email']?.toString(),
      phone: json['telefono']?.toString(),
      status: json['estado']?.toString() ?? 'activo',
      photoUrl: json['photoUrl']?.toString(),
      role: 'socio',
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
  final AuthUser user;

  factory AuthSession.fromJson(Map<String, dynamic> json) {
    return AuthSession(
      accessToken: json['accessToken'] as String,
      refreshToken: json['refreshToken'] as String,
      user: AuthUser.fromJson(json['user'] as Map<String, dynamic>),
    );
  }
}
