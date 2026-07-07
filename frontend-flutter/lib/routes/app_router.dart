import 'package:flutter/material.dart';

import 'package:go_router/go_router.dart';

import 'package:provider/provider.dart';



import '../features/ai/ai_chat_page.dart';

import '../features/attendance/attendance_history_page.dart';

import '../features/auth/login_page.dart';

import '../features/body_progress/body_progress_page.dart';

import '../features/home/home_page.dart';

import '../features/profile/profile_page.dart';

import '../features/qr/qr_card_page.dart';

import '../features/shell/app_shell.dart';

import '../features/staff/attendance/staff_attendance_page.dart';
import '../features/staff/home/staff_home_page.dart';

import '../features/staff/members/staff_create_member_page.dart';

import '../features/staff/members/staff_create_membership_page.dart';

import '../features/staff/members/staff_member_detail_page.dart';

import '../features/staff/members/staff_members_page.dart';

import '../features/staff/coaching/staff_add_measurement_page.dart';
import '../features/staff/coaching/staff_assign_routine_page.dart';
import '../features/staff/coaching/staff_member_coaching_page.dart';
import '../features/staff/reports/staff_reports_page.dart';
import '../features/staff/users/staff_create_user_page.dart';
import '../features/staff/users/staff_users_page.dart';
import '../features/staff/pos/staff_pos_page.dart';
import '../features/staff/sri/staff_sri_detail_page.dart';
import '../features/staff/sri/staff_sri_page.dart';
import '../features/staff/shell/staff_shell.dart';

import '../features/workout/workout_page.dart';

import '../providers/auth_provider.dart';



GoRouter createAppRouter(AuthProvider authProvider) {

  return GoRouter(

    initialLocation: '/login',

    refreshListenable: authProvider,

    redirect: (context, state) {

      final status = authProvider.status;

      final location = state.matchedLocation;

      final isLogin = location == '/login';



      if (status == AuthStatus.unknown) {

        return null;

      }



      if (status == AuthStatus.unauthenticated && !isLogin) {

        return '/login';

      }



      if (status == AuthStatus.authenticated) {

        if (isLogin) {

          return authProvider.isStaff ? '/staff/home' : '/home';

        }



        final isStaffRoute = location.startsWith('/staff');

        if (authProvider.isStaff && !isStaffRoute) {

          return '/staff/home';

        }

        if (authProvider.isMember && isStaffRoute) {

          return '/home';

        }

      }



      return null;

    },

    routes: [

      GoRoute(

        path: '/login',

        builder: (context, state) => const LoginPage(),

      ),

      GoRoute(

        path: '/staff/pos',

        builder: (context, state) => const StaffPosPage(),

      ),

      GoRoute(

        path: '/staff/reports',

        builder: (context, state) => const StaffReportsPage(),

      ),

      GoRoute(

        path: '/staff/users',

        builder: (context, state) => const StaffUsersPage(),

      ),

      GoRoute(

        path: '/staff/users/new',

        builder: (context, state) => const StaffCreateUserPage(),

      ),

      GoRoute(

        path: '/staff/sri',

        builder: (context, state) => const StaffSriPage(),

      ),

      GoRoute(

        path: '/staff/sri/:id',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          return StaffSriDetailPage(receiptId: id);

        },

      ),

      StatefulShellRoute.indexedStack(

        builder: (context, state, navigationShell) {

          return StaffShell(navigationShell: navigationShell);

        },

        branches: [

          StatefulShellBranch(

            routes: [

              GoRoute(

                path: '/staff/home',

                builder: (context, state) => const StaffHomePage(),

              ),

            ],

          ),

          StatefulShellBranch(

            routes: [

              GoRoute(

                path: '/staff/members',

                builder: (context, state) => const StaffMembersPage(),

              ),

            ],

          ),

          StatefulShellBranch(

            routes: [

              GoRoute(

                path: '/staff/attendance',

                builder: (context, state) => const StaffAttendancePage(),

              ),

            ],

          ),

        ],

      ),

      GoRoute(

        path: '/staff/members/new',

        builder: (context, state) => const StaffCreateMemberPage(),

      ),

      GoRoute(

        path: '/staff/members/:id',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          return StaffMemberDetailPage(memberId: id);

        },

      ),

      GoRoute(

        path: '/staff/members/:id/membership/new',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          return StaffCreateMembershipPage(memberId: id);

        },

      ),

      GoRoute(

        path: '/staff/members/:id/coaching',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          final name = state.uri.queryParameters['name'];

          return StaffMemberCoachingPage(memberId: id, memberName: name);

        },

      ),

      GoRoute(

        path: '/staff/members/:id/coaching/measurement/new',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          return StaffAddMeasurementPage(memberId: id);

        },

      ),

      GoRoute(

        path: '/staff/members/:id/coaching/routine/new',

        builder: (context, state) {

          final id = int.parse(state.pathParameters['id']!);

          return StaffAssignRoutinePage(memberId: id);

        },

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

                path: '/assistant',

                builder: (context, state) => const AiChatPage(),

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


