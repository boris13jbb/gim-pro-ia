import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'core/theme/app_theme.dart';
import 'providers/auth_provider.dart';
import 'routes/app_router.dart';
import 'services/api_client.dart';
import 'services/attendance_service.dart';
import 'services/auth_service.dart';
import 'services/auth_storage.dart';
import 'services/body_progress_service.dart';
import 'services/member_service.dart';
import 'services/workout_service.dart';

class GymProApp extends StatefulWidget {
  const GymProApp({super.key});

  @override
  State<GymProApp> createState() => _GymProAppState();
}

class _GymProAppState extends State<GymProApp> {
  late final AuthStorage _authStorage;
  late final AuthService _authService;
  late final ApiClient _apiClient;
  late final AuthProvider _authProvider;
  late final GoRouter _router;

  @override
  void initState() {
    super.initState();
    _authStorage = AuthStorage();
    late final AuthService authServiceRef;
    _apiClient = ApiClient(
      authStorage: _authStorage,
      onUnauthorized: () => authServiceRef.refreshSession(),
    );
    authServiceRef = AuthService(
      apiClient: _apiClient,
      authStorage: _authStorage,
    );
    _authService = authServiceRef;
    _authProvider = AuthProvider(authService: _authService);
    _router = createAppRouter(_authProvider);
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthProvider>.value(value: _authProvider),
        Provider<AuthService>.value(value: _authService),
        Provider<ApiClient>.value(value: _apiClient),
        Provider<MemberService>(
          create: (_) => MemberService(apiClient: _apiClient),
        ),
        Provider<BodyProgressService>(
          create: (_) => BodyProgressService(apiClient: _apiClient),
        ),
        Provider<WorkoutService>(
          create: (_) => WorkoutService(apiClient: _apiClient),
        ),
        Provider<AttendanceService>(
          create: (_) => AttendanceService(apiClient: _apiClient),
        ),
      ],
      child: AppBootstrap(
        child: MaterialApp.router(
          title: 'Iron Gym',
          debugShowCheckedModeBanner: false,
          theme: AppTheme.light(),
          routerConfig: _router,
        ),
      ),
    );
  }
}
