import 'package:flutter/material.dart';

import 'staff_attendance_panels.dart';

/// Módulo de asistencias staff: escaneo QR, ingreso por DNI y listado del día.
class StaffAttendancePage extends StatefulWidget {
  const StaffAttendancePage({super.key});

  @override
  State<StaffAttendancePage> createState() => _StaffAttendancePageState();
}

class _StaffAttendancePageState extends State<StaffAttendancePage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  int _refreshKey = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  void _onRegistered() {
    setState(() => _refreshKey++);
    _tabController.animateTo(2);
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.qr_code_scanner), text: 'QR'),
            Tab(icon: Icon(Icons.badge_outlined), text: 'DNI'),
            Tab(icon: Icon(Icons.today_outlined), text: 'Hoy'),
          ],
        ),
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              Padding(
                padding: const EdgeInsets.all(16),
                child: StaffQrScannerPanel(onRegistered: _onRegistered),
              ),
              StaffDniAttendancePanel(onRegistered: _onRegistered),
              StaffTodayAttendancePanel(refreshKey: _refreshKey),
            ],
          ),
        ),
      ],
    );
  }
}
