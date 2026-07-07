import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/cash_register_service.dart';
import '../../../widgets/state_views.dart';
import 'staff_pos_panels.dart';

/// POS y caja para admin/recepcionista.
class StaffPosPage extends StatefulWidget {
  const StaffPosPage({super.key});

  @override
  State<StaffPosPage> createState() => _StaffPosPageState();
}

class _StaffPosPageState extends State<StaffPosPage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabController;
  bool _cashOpen = false;
  bool _checkingCash = true;
  int _refreshKey = 0;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) => _refreshCashState());
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _refreshCashState() async {
    setState(() => _checkingCash = true);
    try {
      final current =
          await context.read<CashRegisterService>().fetchCurrent();
      if (!mounted) return;
      setState(() => _cashOpen = current?.isOpen ?? false);
    } catch (_) {
      if (!mounted) return;
      setState(() => _cashOpen = false);
    } finally {
      if (mounted) setState(() => _checkingCash = false);
    }
  }

  void _onCashOrSaleChanged() {
    setState(() => _refreshKey++);
    _refreshCashState();
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;

    if (!StaffPermissions.canUsePos(role)) {
      return Scaffold(
        appBar: AppBar(title: const Text('POS y caja')),
        body: const ErrorState(
          message: 'Tu rol no tiene permiso para usar el punto de venta.',
        ),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('POS y caja'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go('/staff/home'),
        ),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(icon: Icon(Icons.account_balance_wallet_outlined), text: 'Caja'),
            Tab(icon: Icon(Icons.shopping_cart_outlined), text: 'Venta'),
            Tab(icon: Icon(Icons.receipt_long_outlined), text: 'Ventas'),
          ],
        ),
      ),
      body: _checkingCash
          ? const LoadingView(message: 'Verificando caja...')
          : TabBarView(
              controller: _tabController,
              children: [
                StaffPosCashPanel(onCashChanged: _onCashOrSaleChanged),
                StaffPosSalePanel(
                  cashOpen: _cashOpen,
                  onSaleCreated: _onCashOrSaleChanged,
                ),
                StaffPosSalesPanel(refreshKey: _refreshKey),
              ],
            ),
    );
  }
}
