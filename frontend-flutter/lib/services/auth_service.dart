import '../core/models/auth_user.dart';
import 'api_client.dart';
import 'auth_storage.dart';

class AuthService {
  AuthService({required ApiClient apiClient, required AuthStorage authStorage})
    : _apiClient = apiClient,
      _authStorage = authStorage;

  final ApiClient _apiClient;
  final AuthStorage _authStorage;

  Future<AuthSession> loginMember({
    required String login,
    required String password,
  }) async {
    final session = await _apiClient.postData(
      '/auth/member/login',
      body: {'login': login, 'password': password},
      parser: (raw) => AuthSession.fromJson(raw as Map<String, dynamic>),
    );

    await _authStorage.saveTokens(
      accessToken: session.accessToken,
      refreshToken: session.refreshToken,
    );
    return session;
  }

  /// Login staff (admin, recepcionista, entrenador) vía `/auth/login`.
  Future<AuthSession> loginStaff({
    required String email,
    required String password,
  }) async {
    final session = await _apiClient.postData(
      '/auth/login',
      body: {'email': email, 'password': password},
      parser: (raw) => AuthSession.fromJson(raw as Map<String, dynamic>),
    );

    await _authStorage.saveTokens(
      accessToken: session.accessToken,
      refreshToken: session.refreshToken,
    );
    return session;
  }

  Future<bool> refreshSession() async {
    final refreshToken = await _authStorage.readRefreshToken();
    if (refreshToken == null || refreshToken.isEmpty) {
      return false;
    }

    try {
      final session = await _apiClient.postData(
        '/auth/refresh',
        body: {'refreshToken': refreshToken},
        parser: (raw) => AuthSession.fromJson(raw as Map<String, dynamic>),
      );
      await _authStorage.saveTokens(
        accessToken: session.accessToken,
        refreshToken: session.refreshToken,
      );
      return true;
    } catch (_) {
      await _authStorage.clear();
      return false;
    }
  }

  Future<AuthUser> fetchProfile() async {
    return _apiClient.getData(
      '/auth/me',
      parser: (raw) => AuthUser.fromJson(raw as Map<String, dynamic>),
    );
  }

  Future<void> logout() async {
    final refreshToken = await _authStorage.readRefreshToken();
    if (refreshToken != null && refreshToken.isNotEmpty) {
      try {
        await _apiClient.postData(
          '/auth/logout',
          body: {'refreshToken': refreshToken},
          parser: (_) => null,
        );
      } catch (_) {
        // Revocación best-effort; siempre limpiar almacenamiento local.
      }
    }
    await _authStorage.clear();
  }

  Future<bool> hasStoredSession() async {
    final refresh = await _authStorage.readRefreshToken();
    return refresh != null && refresh.isNotEmpty;
  }
}
