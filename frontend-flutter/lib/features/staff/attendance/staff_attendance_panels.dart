import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import 'package:provider/provider.dart';

import '../../../core/models/api_response.dart';
import '../../../core/models/attendance_staff.dart';
import '../../../services/attendance_service.dart';
import '../../../widgets/membership_status_chip.dart';

/// Escáner QR del carnet. El QR codifica el DNI del socio (compatible PHP).
class StaffQrScannerPanel extends StatefulWidget {
  const StaffQrScannerPanel({super.key, required this.onRegistered});

  final VoidCallback onRegistered;

  @override
  State<StaffQrScannerPanel> createState() => _StaffQrScannerPanelState();
}

class _StaffQrScannerPanelState extends State<StaffQrScannerPanel> {
  final MobileScannerController _controller = MobileScannerController(
    detectionSpeed: DetectionSpeed.noDuplicates,
    facing: CameraFacing.back,
  );

  bool _processing = false;
  String? _lastMessage;
  bool _isSuccess = false;
  DateTime? _cooldownUntil;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _onDetect(BarcodeCapture capture) async {
    if (_processing) return;
    if (_cooldownUntil != null && DateTime.now().isBefore(_cooldownUntil!)) {
      return;
    }

    final value = capture.barcodes.firstOrNull?.rawValue?.trim();
    if (value == null || value.isEmpty) return;

    setState(() {
      _processing = true;
      _lastMessage = null;
    });

    try {
      final record = await context.read<AttendanceService>().scanAndRegister(
        dni: value,
        method: 'qr',
      );
      if (!mounted) return;
      setState(() {
        _isSuccess = true;
        _lastMessage = 'Ingreso registrado: ${record.memberName ?? value}';
        _cooldownUntil = DateTime.now().add(const Duration(seconds: 3));
      });
      widget.onRegistered();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _isSuccess = false;
        _lastMessage = error.message;
        _cooldownUntil = DateTime.now().add(const Duration(seconds: 2));
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _isSuccess = false;
        _lastMessage = error.toString();
        _cooldownUntil = DateTime.now().add(const Duration(seconds: 2));
      });
    } finally {
      if (mounted) setState(() => _processing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottom = MediaQuery.of(context).padding.bottom;

    return Column(
      children: [
        Expanded(
          child: ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Stack(
              fit: StackFit.expand,
              children: [
                MobileScanner(
                  controller: _controller,
                  onDetect: _onDetect,
                ),
                if (_processing)
                  Container(
                    color: Colors.black45,
                    child: const Center(child: CircularProgressIndicator()),
                  ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 12),
        Text(
          'Apunta al código QR del carnet del socio.',
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        if (_lastMessage != null) ...[
          const SizedBox(height: 12),
          MaterialBanner(
            content: Text(_lastMessage!),
            leading: Icon(
              _isSuccess ? Icons.check_circle : Icons.error_outline,
              color: _isSuccess
                  ? Colors.green
                  : Theme.of(context).colorScheme.error,
            ),
            actions: [
              TextButton(
                onPressed: () => setState(() => _lastMessage = null),
                child: const Text('Cerrar'),
              ),
            ],
          ),
        ],
        SizedBox(height: 8 + bottom),
      ],
    );
  }
}

/// Panel DNI: validar (paso 1) y registrar (paso 2), compatible flujo PHP.
class StaffDniAttendancePanel extends StatefulWidget {
  const StaffDniAttendancePanel({super.key, required this.onRegistered});

  final VoidCallback onRegistered;

  @override
  State<StaffDniAttendancePanel> createState() => _StaffDniAttendancePanelState();
}

class _StaffDniAttendancePanelState extends State<StaffDniAttendancePanel> {
  final _dniController = TextEditingController();

  bool _loading = false;
  bool _registering = false;
  String? _error;
  AttendanceAccessPreview? _preview;

  @override
  void dispose() {
    _dniController.dispose();
    super.dispose();
  }

  Future<void> _validate() async {
    final dni = _dniController.text.trim();
    if (dni.isEmpty) {
      setState(() => _error = 'Ingresa el DNI del socio');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
      _preview = null;
    });

    try {
      final preview =
          await context.read<AttendanceService>().validateAccess(dni);
      if (!mounted) return;
      setState(() => _preview = preview);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _register() async {
    final preview = _preview;
    final member = preview?.member;
    if (member == null || !preview!.canAccess) return;

    setState(() {
      _registering = true;
      _error = null;
    });

    try {
      await context.read<AttendanceService>().registerAttendance(
        memberId: member.id,
        method: 'dni',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Ingreso registrado: ${member.name}')),
      );
      setState(() {
        _preview = null;
        _dniController.clear();
      });
      widget.onRegistered();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _registering = false);
    }
  }

  Future<void> _quickRegister() async {
    final dni = _dniController.text.trim();
    if (dni.isEmpty) {
      setState(() => _error = 'Ingresa el DNI del socio');
      return;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final record = await context.read<AttendanceService>().scanAndRegister(
        dni: dni,
        method: 'dni',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Ingreso registrado: ${record.memberName ?? dni}'),
        ),
      );
      setState(() {
        _preview = null;
        _dniController.clear();
      });
      widget.onRegistered();
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() => _error = error.message);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;
    final safeBottom = MediaQuery.of(context).padding.bottom;
    final preview = _preview;

    return ListView(
      padding: EdgeInsets.fromLTRB(16, 8, 16, 16 + bottomInset + safeBottom),
      children: [
        TextField(
          controller: _dniController,
          decoration: const InputDecoration(
            labelText: 'DNI del socio',
            border: OutlineInputBorder(),
            prefixIcon: Icon(Icons.badge_outlined),
          ),
          keyboardType: TextInputType.number,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _validate(),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: OutlinedButton.icon(
                onPressed: _loading ? null : _validate,
                icon: _loading
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.verified_user_outlined),
                label: const Text('Validar'),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: FilledButton.icon(
                onPressed: _loading ? null : _quickRegister,
                icon: const Icon(Icons.how_to_reg),
                label: const Text('Rápido'),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Text(
          'Validar muestra los datos antes de registrar. Rápido valida y registra en un paso.',
          style: Theme.of(context).textTheme.bodySmall?.copyWith(
            color: Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 12),
          Text(
            _error!,
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        if (preview != null) ...[
          const SizedBox(height: 16),
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (preview.member != null) ...[
                    Text(
                      preview.member!.name,
                      style: Theme.of(context).textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    Text('DNI ${preview.member!.dni}'),
                  ],
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Icon(
                        preview.canAccess ? Icons.check_circle : Icons.cancel,
                        color: preview.canAccess ? Colors.green : Colors.red,
                      ),
                      const SizedBox(width: 8),
                      Expanded(child: Text(preview.message)),
                    ],
                  ),
                  if (preview.effectiveStatus != null) ...[
                    const SizedBox(height: 8),
                    MembershipStatusChip(status: preview.effectiveStatus!),
                  ],
                  if (preview.daysRemaining != null && preview.canAccess) ...[
                    const SizedBox(height: 8),
                    Text('Días restantes: ${preview.daysRemaining}'),
                  ],
                  if (preview.endDate != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      'Vence: ${DateFormat('dd/MM/yyyy').format(preview.endDate!.toLocal())}',
                    ),
                  ],
                  if (preview.canAccess && preview.member != null) ...[
                    const SizedBox(height: 16),
                    FilledButton.icon(
                      onPressed: _registering ? null : _register,
                      icon: _registering
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.login),
                      label: Text(
                        _registering ? 'Registrando...' : 'Confirmar ingreso',
                      ),
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
      ],
    );
  }
}

/// Listado de asistencias registradas hoy.
class StaffTodayAttendancePanel extends StatefulWidget {
  const StaffTodayAttendancePanel({super.key, this.refreshKey});

  /// Cambia cuando otro panel registra asistencia para forzar recarga.
  final int? refreshKey;

  @override
  State<StaffTodayAttendancePanel> createState() =>
      _StaffTodayAttendancePanelState();
}

class _StaffTodayAttendancePanelState extends State<StaffTodayAttendancePanel> {
  bool _loading = true;
  String? _error;
  List<StaffAttendanceRecord> _items = [];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _load());
  }

  @override
  void didUpdateWidget(covariant StaffTodayAttendancePanel oldWidget) {
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

    try {
      final items = await context.read<AttendanceService>().fetchTodayList();
      if (!mounted) return;
      setState(() => _items = items);
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final timeFormat = DateFormat('HH:mm');
    final bottom = MediaQuery.of(context).padding.bottom;

    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_error != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_error!, textAlign: TextAlign.center),
              const SizedBox(height: 12),
              FilledButton(onPressed: _load, child: const Text('Reintentar')),
            ],
          ),
        ),
      );
    }

    if (_items.isEmpty) {
      return RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: const [
            SizedBox(height: 120),
            Center(child: Text('Aún no hay asistencias registradas hoy.')),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _load,
      child: ListView.separated(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: EdgeInsets.fromLTRB(16, 8, 16, 16 + bottom),
        itemCount: _items.length,
        separatorBuilder: (_, _) => const SizedBox(height: 8),
        itemBuilder: (context, index) {
          final item = _items[index];
          final time = item.checkedInAt != null
              ? timeFormat.format(item.checkedInAt!.toLocal())
              : '—';

          return Card(
            child: ListTile(
              leading: CircleAvatar(
                child: Text(
                  (item.memberName?.isNotEmpty ?? false)
                      ? item.memberName![0].toUpperCase()
                      : '?',
                ),
              ),
              title: Text(item.memberName ?? 'Socio #${item.memberId}'),
              subtitle: Text(
                'DNI ${item.memberDni ?? '—'} · ${methodLabel(item.method)}',
              ),
              trailing: Text(
                time,
                style: Theme.of(context).textTheme.titleMedium,
              ),
            ),
          );
        },
      ),
    );
  }
}
