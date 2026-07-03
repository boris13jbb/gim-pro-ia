import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../features/attendance/attendance_history_page.dart';
import '../features/auth/login_page.dart';
import '../features/body_progress/body_progress_page.dart';
import '../features/home/home_page.dart';
import '../features/profile/profile_page.dart';
import '../features/qr/qr_card_page.dart';
import '../features/shell/app_shell.dart';
import '../features/workout/workout_page.dart';
import '../providers/auth_provider.dart';

GoRouter createAppRouter(AuthProvider authProvider) {
  return GoRouter(
    initialLocation: '/login',
    refreshListenable: authProvider,
    redirect: (context, state) {
      final status = authProvider.status;
      final isLogin = state.matchedLocation == '/login';

      if (status == AuthStatus.unknown) {
        return null;
      }
      if (status == AuthStatus.unauthenticated && !isLogin) {
        return '/login';
      }
      if (status == AuthStatus.authenticated && isLogin) {
        return '/home';
      }
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginPage(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return AppShell(navigationShell: navigationShell);
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                builder: (context, state) => const HomePage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/qr',
                builder: (context, state) => const QrCardPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/progress',
                builder: (context, state) => const BodyProgressPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/workout',
                builder: (context, state) => const WorkoutPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/profile',
                builder: (context, state) => const ProfilePage(),
              ),
            ],
          ),
        ],
      ),
      GoRoute(
        path: '/attendance-history',
        builder: (context, state) => const AttendanceHistoryPage(),
      ),
    ],
  );
}

class AppBootstrap extends StatefulWidget {
  const AppBootstrap({super.key, required this.child});

  final Widget child;

  @override
  State<AppBootstrap> createState() => _AppBootstrapState();
}

class _AppBootstrapState extends State<AppBootstrap> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AuthProvider>().bootstrap();
    });
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    if (auth.status == AuthStatus.unknown && auth.isLoading) {
      return const MaterialApp(
        home: Scaffold(
          body: Center(child: CircularProgressIndicator()),
        ),
      );
    }
    return widget.child;
  }
}
