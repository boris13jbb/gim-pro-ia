import 'package:flutter/foundation.dart';

import '../core/models/api_response.dart';
import '../core/models/auth_user.dart';
import '../services/auth_service.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthProvider extends ChangeNotifier {
  AuthProvider({required AuthService authService}) : _authService = authService;

  final AuthService _authService;

  AuthStatus status = AuthStatus.unknown;
  AuthUser? user;
  String? errorMessage;
  bool isLoading = false;

  bool get isStaff => user?.isStaff ?? false;
  bool get isMember => user?.isMember ?? false;

  Future<void> bootstrap() async {
    isLoading = true;
    notifyListeners();

    try {
      final hasSession = await _authService.hasStoredSession();
      if (!hasSession) {
        status = AuthStatus.unauthenticated;
        user = null;
        return;
      }

      final refreshed = await _authService.refreshSession();
      if (!refreshed) {
        status = AuthStatus.unauthenticated;
        user = null;
        return;
      }

      user = await _authService.fetchProfile();
      status = AuthStatus.authenticated;
    } catch (_) {
      await _authService.logout();
      status = AuthStatus.unauthenticated;
      user = null;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<bool> loginMember(String login, String password) async {
    return _login(
      () => _authService.loginMember(login: login, password: password),
    );
  }

  Future<bool> loginStaff(String email, String password) async {
    return _login(
      () => _authService.loginStaff(email: email, password: password),
    );
  }

  Future<bool> _login(Future<AuthSession> Function() call) async {
    errorMessage = null;
    isLoading = true;
    notifyListeners();

    try {
      final session = await call();
      user = session.user;
      status = AuthStatus.authenticated;
      return true;
    } on ApiException catch (error) {
      errorMessage = error.message;
      status = AuthStatus.unauthenticated;
      return false;
    } catch (_) {
      errorMessage = 'No se pudo iniciar sesión. Intente nuevamente.';
      status = AuthStatus.unauthenticated;
      return false;
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> logout() async {
    await _authService.logout();
    user = null;
    status = AuthStatus.unauthenticated;
    notifyListeners();
  }

  Future<void> reloadProfile() async {
    user = await _authService.fetchProfile();
    notifyListeners();
  }
}
