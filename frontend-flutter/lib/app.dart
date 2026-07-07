import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import 'core/theme/app_theme.dart';
import 'providers/auth_provider.dart';
import 'providers/theme_provider.dart';
import 'routes/app_router.dart';
import 'services/ai_service.dart';
import 'services/ai_socket_service.dart';
import 'services/api_client.dart';
import 'services/attendance_service.dart';
import 'services/auth_service.dart';
import 'services/auth_storage.dart';
import 'services/billing_sri_service.dart';
import 'services/body_progress_service.dart';
import 'services/sri_config_service.dart';
import 'services/member_service.dart';
import 'services/membership_service.dart';
import 'services/plan_service.dart';
import 'services/cash_register_service.dart';
import 'services/product_service.dart';
import 'services/reports_service.dart';
import 'services/sales_service.dart';
import 'services/staff_users_service.dart';
import 'services/notifications_service.dart';
import 'services/realtime_notifications_service.dart';
import 'services/theme_storage.dart';
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
  late final ThemeProvider _themeProvider;
  late final GoRouter _router;

  // Temas construidos una sola vez para evitar reconstruirlos en cada rebuild.
  final ThemeData _lightTheme = AppTheme.light();
  final ThemeData _darkTheme = AppTheme.dark();

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
    _themeProvider = ThemeProvider(themeStorage: ThemeStorage());
    // Carga la preferencia de tema guardada (asíncrona; inicia en "sistema").
    _themeProvider.load();
    _router = createAppRouter(_authProvider);
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider<AuthProvider>.value(value: _authProvider),
        ChangeNotifierProvider<ThemeProvider>.value(value: _themeProvider),
        Provider<AuthService>.value(value: _authService),
        Provider<ApiClient>.value(value: _apiClient),
        Provider<MemberService>(
          create: (_) => MemberService(apiClient: _apiClient),
        ),
        Provider<PlanService>(
          create: (_) => PlanService(apiClient: _apiClient),
        ),
        Provider<MembershipService>(
          create: (_) => MembershipService(apiClient: _apiClient),
        ),
        Provider<ProductService>(
          create: (_) => ProductService(apiClient: _apiClient),
        ),
        Provider<CashRegisterService>(
          create: (_) => CashRegisterService(apiClient: _apiClient),
        ),
        Provider<SalesService>(
          create: (_) => SalesService(apiClient: _apiClient),
        ),
        Provider<ReportsService>(
          create: (_) => ReportsService(apiClient: _apiClient),
        ),
        Provider<StaffUsersService>(
          create: (_) => StaffUsersService(apiClient: _apiClient),
        ),
        Provider<BillingSriService>(
          create: (_) => BillingSriService(apiClient: _apiClient),
        ),
        Provider<SriConfigService>(
          create: (_) => SriConfigService(apiClient: _apiClient),
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
        Provider<AiService>(
          create: (_) => AiService(apiClient: _apiClient),
        ),
        Provider<NotificationsService>(
          create: (_) => NotificationsService(apiClient: _apiClient),
        ),
        // Socket del asistente IA (streaming). Se cierra al destruir el árbol.
        Provider<AiSocketService>(
          create: (_) => AiSocketService(authStorage: _authStorage),
          dispose: (_, service) => service.dispose(),
        ),
        // Notificaciones en tiempo real del socio (asistencia/membresía).
        // ChangeNotifierProvider libera el servicio (dispose) automáticamente.
        ChangeNotifierProvider<RealtimeNotificationsService>(
          create: (_) =>
              RealtimeNotificationsService(authStorage: _authStorage),
        ),
      ],
      child: AppBootstrap(
        // Consumer para que la app se reconstruya cuando el usuario cambia el
        // tema desde Perfil (o al cargar la preferencia guardada).
        child: Consumer<ThemeProvider>(
          builder: (context, themeProvider, _) => MaterialApp.router(
            title: 'Iron Gym',
            debugShowCheckedModeBanner: false,
            // Tema claro/oscuro según la preferencia del usuario o del sistema.
            theme: _lightTheme,
            darkTheme: _darkTheme,
            themeMode: themeProvider.themeMode,
            routerConfig: _router,
          ),
        ),
      ),
    );
  }
}
