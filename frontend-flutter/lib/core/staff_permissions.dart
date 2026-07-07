/// Permisos de UI staff según rol. La API sigue siendo la autoridad final (403).
class StaffPermissions {
  const StaffPermissions._();

  static bool canManageMembers(String? role) {
    return role == 'admin' || role == 'recepcionista';
  }

  static bool canAssignMembership(String? role) {
    return role == 'admin' || role == 'recepcionista';
  }

  static bool canUsePos(String? role) {
    return role == 'admin' || role == 'recepcionista';
  }

  static bool canManageAdminModules(String? role) {
    return role == 'admin';
  }

  static bool canViewCoaching(String? role) {
    return role == 'admin' || role == 'recepcionista' || role == 'entrenador';
  }

  static bool canManageCoaching(String? role) {
    return role == 'admin' || role == 'entrenador';
  }

  static bool canUseBillingSri(String? role) {
    return role == 'admin' || role == 'recepcionista';
  }
}
