import 'package:flutter/material.dart';

import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import 'package:provider/provider.dart';



import '../../core/models/attendance_report.dart';

import '../../core/models/membership_summary.dart';

import '../../providers/auth_provider.dart';

import '../../services/attendance_service.dart';

import '../../services/member_service.dart';

import '../../widgets/state_views.dart';

import 'membership_status_card.dart';



class HomePage extends StatefulWidget {

  const HomePage({super.key});



  @override

  State<HomePage> createState() => _HomePageState();

}



class _HomePageState extends State<HomePage> {

  MemberService get _memberService => context.read<MemberService>();

  AttendanceService get _attendanceService => context.read<AttendanceService>();



  bool _loading = true;

  String? _error;

  MembershipSummary? _membership;

  AttendanceReport? _attendance;

  bool _checkingIn = false;



  @override

  void initState() {

    super.initState();

    WidgetsBinding.instance.addPostFrameCallback((_) => _load());

  }



  Future<void> _load() async {

    setState(() {

      _loading = true;

      _error = null;

    });

    try {

      final results = await Future.wait([

        _memberService.fetchMembership(),

        _attendanceService.fetchMyAttendance(),

      ]);

      if (!mounted) return;

      setState(() {

        _membership = results[0] as MembershipSummary;

        _attendance = results[1] as AttendanceReport;

      });

    } catch (error) {

      if (!mounted) return;

      setState(() => _error = error.toString());

    } finally {

      if (mounted) setState(() => _loading = false);

    }

  }



  Future<void> _registerAttendance() async {

    setState(() => _checkingIn = true);

    try {

      await _attendanceService.registerSelf();

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(

        const SnackBar(content: Text('Asistencia registrada correctamente')),

      );

      await _load();

    } catch (error) {

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(

        SnackBar(content: Text(error.toString())),

      );

    } finally {

      if (mounted) setState(() => _checkingIn = false);

    }

  }



  @override

  Widget build(BuildContext context) {

    final user = context.watch<AuthProvider>().user;

    final dateFormat = DateFormat('dd/MM/yyyy HH:mm');



    return RefreshIndicator(

      onRefresh: _load,

      child: ListView(

        physics: const AlwaysScrollableScrollPhysics(),

        padding: EdgeInsets.fromLTRB(

          16,

          16,

          16,

          16 + MediaQuery.of(context).padding.bottom,

        ),

        children: [

          Text(

            'Hola, ${user?.name ?? 'Socio'}',

            style: Theme.of(context).textTheme.headlineSmall?.copyWith(

              fontWeight: FontWeight.bold,

            ),

          ),

          const SizedBox(height: 4),

          Text('Bienvenido a tu espacio Iron Gym'),

          const SizedBox(height: 20),

          if (_loading)

            const LoadingView(message: 'Cargando membresía...')

          else if (_error != null)

            ErrorState(message: _error!, onRetry: _load)

          else ...[

            if (_membership != null)

              MembershipStatusCard(summary: _membership!),

            const SizedBox(height: 16),

            FilledButton.icon(

              onPressed: _checkingIn ? null : _registerAttendance,

              icon: _checkingIn

                  ? const SizedBox(

                      width: 18,

                      height: 18,

                      child: CircularProgressIndicator(

                        strokeWidth: 2,

                        color: Colors.black87,

                      ),

                    )

                  : const Icon(Icons.fitness_center),

              label: const Text('Registrar asistencia'),

            ),

            if (_attendance != null) ...[

              const SizedBox(height: 24),

              Text(

                'Mis asistencias del mes',

                style: Theme.of(context).textTheme.titleMedium?.copyWith(

                  fontWeight: FontWeight.bold,

                ),

              ),

              const SizedBox(height: 8),

              Card(

                child: Padding(

                  padding: const EdgeInsets.all(16),

                  child: Row(

                    children: [

                      Expanded(

                        child: _StatTile(

                          label: 'Visitas',

                          value: '${_attendance!.totalVisits}',

                          icon: Icons.event_available,

                        ),

                      ),

                      Expanded(

                        child: _StatTile(

                          label: 'Promedio/día',

                          value: _attendance!.averageDaily.toStringAsFixed(1),

                          icon: Icons.trending_up,

                        ),

                      ),

                    ],

                  ),

                ),

              ),

              const SizedBox(height: 8),

              if (_attendance!.items.isEmpty)

                const Card(

                  child: ListTile(

                    leading: Icon(Icons.info_outline),

                    title: Text('Aún no hay asistencias este mes'),

                  ),

                )

              else

                ..._attendance!.items.take(5).map(

                  (item) => Card(

                    child: ListTile(

                      leading: const Icon(Icons.check_circle_outline),

                      title: Text(

                        item.checkedInAt != null

                            ? dateFormat.format(item.checkedInAt!.toLocal())

                            : 'Asistencia #${item.id}',

                      ),

                      subtitle: Text(

                        'Método: ${item.method ?? 'app'}',

                      ),

                    ),

                  ),

                ),

              Align(
                alignment: Alignment.centerRight,
                child: TextButton(
                  onPressed: () => context.push('/attendance-history'),
                  child: Text(
                    _attendance!.items.length > 5
                        ? 'Ver historial completo'
                        : 'Ver historial',
                  ),
                ),
              ),

            ],

          ],

        ],

      ),

    );

  }

}



class _StatTile extends StatelessWidget {

  const _StatTile({

    required this.label,

    required this.value,

    required this.icon,

  });



  final String label;

  final String value;

  final IconData icon;



  @override

  Widget build(BuildContext context) {

    return Column(

      children: [

        Icon(icon, color: Theme.of(context).colorScheme.primary),

        const SizedBox(height: 4),

        Text(

          value,

          style: Theme.of(context).textTheme.titleLarge?.copyWith(

            fontWeight: FontWeight.bold,

          ),

        ),

        Text(label, style: Theme.of(context).textTheme.bodySmall),

      ],

    );

  }

}


