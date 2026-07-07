class StaffUser {
  StaffUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.status,
  });

  final int id;
  final String name;
  final String email;
  final String role;
  final String status;

  bool get isActive => status == 'activo';

  factory StaffUser.fromJson(Map<String, dynamic> json) {
    return StaffUser(
      id: json['id'] as int,
      name: json['nombre']?.toString() ?? json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      role: json['rol']?.toString() ?? json['role']?.toString() ?? '',
      status: json['estado']?.toString() ?? json['status']?.toString() ?? 'activo',
    );
  }
}

String staffRoleLabel(String role) {
  switch (role) {
    case 'admin':
      return 'Administrador';
    case 'recepcionista':
      return 'Recepcionista';
    case 'entrenador':
      return 'Entrenador';
    default:
      return role;
  }
}
