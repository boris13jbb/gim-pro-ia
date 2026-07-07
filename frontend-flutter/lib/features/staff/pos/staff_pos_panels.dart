import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/cash_register.dart';
import '../../../core/models/product.dart';
import '../../../core/models/sale.dart';
import '../../../services/billing_sri_service.dart';
import '../../../services/cash_register_service.dart';
import '../../../services/product_service.dart';
import '../../../services/sales_service.dart';
import '../../../widgets/state_views.dart';

/// Estado de caja: abrir, resumen y cierre con cuadre.
class StaffPosCashPanel extends StatefulWidget {
  const StaffPosCashPanel({super.key, required this.onCashChanged});

  final VoidCallback onCashChanged;

  @override
  State<StaffPosCashPanel> createState() => _StaffPosCashPanelState();
}

class _StaffPosCashPanelState extends State<StaffPosCashPanel> {
  final _openAmountController = TextEditingController(text: '0');
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

  bool _loading = true;
  String? _error;
  CashRegisterSummary? _summary;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void dispose() {
    _openAmountController.dispose();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final summary =
          await context.read<CashRegisterService>().fetchCurrentSummary();
      if (!mounted) return;
      setState(() => _summary = summary);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _openRegister() async {
    final amount = double.tryParse(_openAmountController.text.trim()) ?? 0;
    setState(() => _loading = true);
    try {
      await context.read<CashRegisterService>().openRegister(amount);
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Caja abierta correctamente')),
      );
      widget.onCashChanged();
      await _load();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _closeRegister() async {
    final summary = _summary;
    if (summary == null) return;

    final controller = TextEditingController(
      text: summary.expectedAmount.toStringAsFixed(2),
    );

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Cerrar caja'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('Monto esperado: ${_currency.format(summary.expectedAmount)}'),
            const SizedBox(height: 12),
            TextField(
              controller: controller,
              decoration: const InputDecoration(
                labelText: 'Monto contado en caja',
                border: OutlineInputBorder(),
              ),
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Cerrar caja'),
          ),
        ],
      ),
    );

    if (confirmed != true || !mounted) return;

    final closingAmount = double.tryParse(controller.text.trim()) ?? 0;
    controller.dispose();

    setState(() => _loading = true);
    try {
      final result = await context
          .read<CashRegisterService>()
          .closeRegister(closingAmount);
      if (!mounted) return;
      final diff = result.difference;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Caja cerrada. Diferencia: ${_currency.format(diff)}',
          ),
        ),
      );
      widget.onCashChanged();
      await _load();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).padding.bottom;

    if (_loading && _summary == null && _error == null) {
      return const LoadingView(message: 'Consultando caja...');
    }

    if (_error != null && _summary == null) {
      return ErrorState(message: _error!, onRetry: _load);
    }

    final summary = _summary;
    final isOpen = summary != null;

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(16, 16, 16, 16 + bottom),
        children: [
          if (!isOpen) ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Abrir caja',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'Debes abrir caja antes de registrar ventas POS.',
                    ),
                    const SizedBox(height: 12),
                    TextField(
                      controller: _openAmountController,
                      decoration: const InputDecoration(
                        labelText: 'Monto inicial',
                        border: OutlineInputBorder(),
                        prefixText: '\$ ',
                      ),
                      keyboardType: const TextInputType.numberWithOptions(
                        decimal: true,
                      ),
                    ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _loading ? null : _openRegister,
                      icon: const Icon(Icons.lock_open),
                      label: const Text('Abrir caja'),
                    ),
                  ],
                ),
              ),
            ),
          ] else ...[
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.point_of_sale, color: Colors.green),
                        const SizedBox(width: 8),
                        Text(
                          'Caja abierta #${summary.register.id}',
                          style: Theme.of(context).textTheme.titleMedium
                              ?.copyWith(fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    _MetricRow(
                      label: 'Apertura',
                      value: _currency.format(summary.register.openingAmount),
                    ),
                    _MetricRow(
                      label: 'Ventas POS',
                      value: _currency.format(summary.totalSales),
                    ),
                    _MetricRow(
                      label: 'Gastos',
                      value: _currency.format(summary.totalExpenses),
                    ),
                    _MetricRow(
                      label: 'Esperado en caja',
                      value: _currency.format(summary.expectedAmount),
                      emphasized: true,
                    ),
                    _MetricRow(
                      label: 'Ventas registradas',
                      value: '${summary.salesCount}',
                    ),
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _loading ? null : _closeRegister,
                      style: FilledButton.styleFrom(
                        backgroundColor: Theme.of(context).colorScheme.error,
                      ),
                      icon: const Icon(Icons.lock),
                      label: const Text('Cerrar caja'),
                    ),
                  ],
                ),
              ),
            ),
          ],
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(
              _error!,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ],
        ],
      ),
    );
  }
}

class _MetricRow extends StatelessWidget {
  const _MetricRow({
    required this.label,
    required this.value,
    this.emphasized = false,
  });

  final String label;
  final String value;
  final bool emphasized;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label),
          Text(
            value,
            style: emphasized
                ? Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  )
                : null,
          ),
        ],
      ),
    );
  }
}

/// Carrito POS y registro de venta.
class StaffPosSalePanel extends StatefulWidget {
  const StaffPosSalePanel({
    super.key,
    required this.cashOpen,
    required this.onSaleCreated,
  });

  final bool cashOpen;
  final VoidCallback onSaleCreated;

  @override
  State<StaffPosSalePanel> createState() => _StaffPosSalePanelState();
}

class _StaffPosSalePanelState extends State<StaffPosSalePanel> {
  final _discountController = TextEditingController(text: '0');
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);

  bool _loadingProducts = true;
  bool _processing = false;
  String? _error;
  List<Product> _products = [];
  final Map<int, int> _cart = {};
  String _paymentMethod = 'efectivo';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadProducts());
  }

  @override
  void dispose() {
    _discountController.dispose();
    super.dispose();
  }

  Future<void> _loadProducts() async {
    setState(() {
      _loadingProducts = true;
      _error = null;
    });

    try {
      final products =
          await context.read<ProductService>().fetchActiveProducts();
      if (!mounted) return;
      setState(() => _products = products.where((p) => p.hasStock).toList());
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loadingProducts = false);
    }
  }

  double get _subtotal {
    var total = 0.0;
    for (final entry in _cart.entries) {
      final product = _products.firstWhere((p) => p.id == entry.key);
      total += product.salePrice * entry.value;
    }
    return total;
  }

  double get _discount {
    return double.tryParse(_discountController.text.trim()) ?? 0;
  }

  double get _total => (_subtotal - _discount).clamp(0, double.infinity);

  void _addToCart(Product product) {
    final current = _cart[product.id] ?? 0;
    if (current >= product.stock) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Stock máximo: ${product.stock}')),
      );
      return;
    }
    setState(() => _cart[product.id] = current + 1);
  }

  void _removeFromCart(int productId) {
    final current = _cart[productId] ?? 0;
    if (current <= 1) {
      setState(() => _cart.remove(productId));
    } else {
      setState(() => _cart[productId] = current - 1);
    }
  }

  Future<void> _checkout() async {
    if (_cart.isEmpty) {
      setState(() => _error = 'Agrega productos al carrito');
      return;
    }
    if (_discount > _subtotal) {
      setState(() => _error = 'El descuento no puede superar el subtotal');
      return;
    }

    setState(() {
      _processing = true;
      _error = null;
    });

    try {
      final items = _cart.entries
          .map((e) => {'productId': e.key, 'quantity': e.value})
          .toList();

      final sale = await context.read<SalesService>().createSale(
        items: items,
        discount: _discount,
        paymentMethod: _paymentMethod,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            'Venta #${sale.id} registrada — ${_currency.format(sale.total)}',
          ),
        ),
      );
      setState(() {
        _cart.clear();
        _discountController.text = '0';
      });
      widget.onSaleCreated();
      await _loadProducts();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (!widget.cashOpen) {
      return const EmptyState(
        title: 'Caja cerrada',
        subtitle: 'Abre la caja en la pestaña Caja para vender.',
      );
    }

    if (_loadingProducts) {
      return const LoadingView(message: 'Cargando productos...');
    }

    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final safeBottom = MediaQuery.of(context).padding.bottom;

    return Column(
      children: [
        Expanded(
          child: _products.isEmpty
              ? const EmptyState(
                  title: 'Sin productos con stock',
                  subtitle: 'No hay productos activos disponibles para vender.',
                )
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: _products.length,
                  itemBuilder: (context, index) {
                    final product = _products[index];
                    final inCart = _cart[product.id] ?? 0;

                    return Card(
                      child: ListTile(
                        title: Text(product.name),
                        subtitle: Text(
                          '${_currency.format(product.salePrice)} · Stock ${product.stock}',
                        ),
                        trailing: inCart > 0
                            ? Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    onPressed: () => _removeFromCart(product.id),
                                    icon: const Icon(Icons.remove_circle_outline),
                                  ),
                                  Text('$inCart'),
                                  IconButton(
                                    onPressed: () => _addToCart(product),
                                    icon: const Icon(Icons.add_circle_outline),
                                  ),
                                ],
                              )
                            : IconButton(
                                onPressed: () => _addToCart(product),
                                icon: const Icon(Icons.add_shopping_cart),
                              ),
                      ),
                    );
                  },
                ),
        ),
        Material(
          elevation: 8,
          child: Padding(
            padding: EdgeInsets.fromLTRB(
              16,
              12,
              16,
              12 + bottomInset + safeBottom,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'Carrito (${_cart.length} productos)',
                  style: Theme.of(context).textTheme.titleSmall,
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text('Subtotal'),
                    Text(_currency.format(_subtotal)),
                  ],
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: _discountController,
                  decoration: const InputDecoration(
                    labelText: 'Descuento',
                    border: OutlineInputBorder(),
                    isDense: true,
                  ),
                  keyboardType: const TextInputType.numberWithOptions(
                    decimal: true,
                  ),
                  onChanged: (_) => setState(() {}),
                ),
                const SizedBox(height: 8),
                DropdownButtonFormField<String>(
                  initialValue: _paymentMethod,
                  decoration: const InputDecoration(
                    labelText: 'Método de pago',
                    border: OutlineInputBorder(),
                    isDense: true,
                  ),
                  items: const [
                    DropdownMenuItem(value: 'efectivo', child: Text('Efectivo')),
                    DropdownMenuItem(value: 'tarjeta', child: Text('Tarjeta')),
                    DropdownMenuItem(
                      value: 'transferencia',
                      child: Text('Transferencia'),
                    ),
                  ],
                  onChanged: (value) {
                    if (value != null) setState(() => _paymentMethod = value);
                  },
                ),
                const SizedBox(height: 8),
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      'Total',
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text(
                      _currency.format(_total),
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                if (_error != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    _error!,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ],
                const SizedBox(height: 12),
                FilledButton.icon(
                  onPressed: _processing || _cart.isEmpty ? null : _checkout,
                  icon: _processing
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.payments),
                  label: Text(_processing ? 'Procesando...' : 'Cobrar venta'),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}

/// Ventas recientes del día.
class StaffPosSalesPanel extends StatefulWidget {
  const StaffPosSalesPanel({super.key, this.refreshKey});

  final int? refreshKey;

  @override
  State<StaffPosSalesPanel> createState() => _StaffPosSalesPanelState();
}

class _StaffPosSalesPanelState extends State<StaffPosSalesPanel> {
  bool _loading = true;
  String? _error;
  List<SaleSummary> _sales = [];
  final _currency = NumberFormat.currency(symbol: '\$', decimalDigits: 2);
  final _timeFormat = DateFormat('HH:mm');

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void didUpdateWidget(covariant StaffPosSalesPanel oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.refreshKey != widget.refreshKey) {
      _load();
    }
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final today = DateFormat('yyyy-MM-dd').format(DateTime.now());

    try {
      final sales = await context.read<SalesService>().fetchSales(
        fromDate: today,
        toDate: today,
        limit: 50,
      );
      if (!mounted) return;
      setState(() => _sales = sales);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _issueSriInvoice(SaleSummary sale) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Emitir factura SRI'),
        content: Text(
          '¿Emitir factura electrónica para la venta #${sale.id}?',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          FilledButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Emitir'),
          ),
        ],
      ),
    );
    if (confirmed != true || !mounted) return;

    try {
      final result = await context.read<BillingSriService>().issueFromSale(
        sale.id,
      );
      if (!mounted) return;
      if (result.ok && result.receiptId != null) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result.description ?? 'Factura emitida')),
        );
        context.push('/staff/sri/${result.receiptId}');
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result.description ?? 'Emisión con observaciones'),
          ),
        );
      }
    } on ApiException catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.message)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).padding.bottom;

    if (_loading) return const LoadingView(message: 'Cargando ventas...');
    if (_error != null) return ErrorState(message: _error!, onRetry: _load);

    if (_sales.isEmpty) {
      return RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: const [
            SizedBox(height: 120),
            Center(child: Text('No hay ventas registradas hoy.')),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(16, 8, 16, 16 + bottom),
        itemCount: _sales.length,
        separatorBuilder: (_, _) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final sale = _sales[index];
          final time = sale.createdAt != null
              ? _timeFormat.format(sale.createdAt!.toLocal())
              : '—';

          return Card(
            child: ListTile(
              leading: const Icon(Icons.receipt_long),
              title: Text('Venta #${sale.id}'),
              subtitle: Text(
                '${sale.memberName ?? 'Cliente general'} · ${paymentMethodLabel(sale.paymentMethod)}',
              ),
              trailing: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  IconButton(
                    tooltip: 'Emitir factura SRI',
                    icon: const Icon(Icons.verified_outlined),
                    onPressed: () => _issueSriInvoice(sale),
                  ),
                  Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        _currency.format(sale.total),
                        style: const TextStyle(fontWeight: FontWeight.bold),
                      ),
                      Text(time, style: Theme.of(context).textTheme.bodySmall),
                    ],
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
