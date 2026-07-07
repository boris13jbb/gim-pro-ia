import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';

import '../../../core/models/member_user.dart';
import '../../../core/staff_permissions.dart';
import '../../../providers/auth_provider.dart';
import '../../../services/member_service.dart';
import '../../../widgets/state_views.dart';

/// Listado de socios para staff con búsqueda y paginación.
class StaffMembersPage extends StatefulWidget {
  const StaffMembersPage({super.key});

  @override
  State<StaffMembersPage> createState() => _StaffMembersPageState();
}

class _StaffMembersPageState extends State<StaffMembersPage> {
  final _searchController = TextEditingController();
  Timer? _debounce;

  final List<MemberUser> _items = [];
  bool _loading = true;
  bool _loadingMore = false;
  String? _error;
  int _page = 1;
  bool _hasMore = false;
  String _search = '';

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_onSearchChanged);
    WidgetsBinding.instance.addPostFrameCallback((_) => _load(reset: true));
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  void _onSearchChanged() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () {
      final term = _searchController.text.trim();
      if (term == _search) return;
      _search = term;
      _load(reset: true);
    });
  }

  Future<void> _load({required bool reset}) async {
    if (reset) {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
      });
    } else {
      setState(() => _loadingMore = true);
    }

    try {
      final result = await context.read<MemberService>().fetchMembers(
        page: reset ? 1 : _page,
        search: _search.isEmpty ? null : _search,
      );
      if (!mounted) return;
      setState(() {
        if (reset) {
          _items
            ..clear()
            ..addAll(result.items);
        } else {
          _items.addAll(result.items);
        }
        _page = result.meta.page + 1;
        _hasMore = result.meta.hasNextPage;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() => _error = error.toString());
    } finally {
      if (mounted) {
        setState(() {
          _loading = false;
          _loadingMore = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final role = context.watch<AuthProvider>().user?.role;
    final canCreate = StaffPermissions.canManageMembers(role);
    final bottom = MediaQuery.of(context).padding.bottom;

    return Scaffold(
      floatingActionButton: canCreate
          ? FloatingActionButton.extended(
              onPressed: () async {
                final created = await context.push<bool>('/staff/members/new');
                if (created == true && mounted) {
                  await _load(reset: true);
                }
              },
              icon: const Icon(Icons.person_add_alt_1),
              label: const Text('Nuevo socio'),
            )
          : null,
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
            child: SearchBar(
              controller: _searchController,
              hintText: 'Buscar por nombre, DNI o email',
              leading: const Icon(Icons.search),
              trailing: _search.isNotEmpty
                  ? [
                      IconButton(
                        tooltip: 'Limpiar',
                        onPressed: () {
                          _searchController.clear();
                          _search = '';
                          _load(reset: true);
                        },
                        icon: const Icon(Icons.clear),
                      ),
                    ]
                  : null,
            ),
          ),
          Expanded(
            child: _loading
                ? const LoadingView(message: 'Cargando socios...')
                : _error != null
                ? ErrorState(message: _error!, onRetry: () => _load(reset: true))
                : _items.isEmpty
                ? const EmptyState(
                    title: 'No hay socios',
                    subtitle: 'Prueba otra búsqueda o crea un socio nuevo.',
                  )
                : RefreshIndicator(
                    onRefresh: () => _load(reset: true),
                    child: ListView.separated(
                      physics: const AlwaysScrollableScrollPhysics(),
                      padding: EdgeInsets.fromLTRB(16, 0, 16, 88 + bottom),
                      itemCount: _items.length + (_hasMore ? 1 : 0),
                      separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (context, index) {
                        if (index >= _items.length) {
                          return Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Center(
                              child: _loadingMore
                                  ? const CircularProgressIndicator()
                                  : OutlinedButton(
                                      onPressed: () => _load(reset: false),
                                      child: const Text('Cargar más'),
                                    ),
                            ),
                          );
                        }

                        final member = _items[index];
                        return Card(
                          child: ListTile(
                            leading: CircleAvatar(
                              child: Text(
                                member.name.isNotEmpty
                                    ? member.name[0].toUpperCase()
                                    : '?',
                              ),
                            ),
                            title: Text(member.name),
                            subtitle: Text('DNI ${member.dni}'),
                            trailing: _MemberStatusChip(status: member.status),
                            onTap: () => context.push('/staff/members/${member.id}'),
                          ),
                        );
                      },
                    ),
                  ),
          ),
        ],
      ),
    );
  }
}

class _MemberStatusChip extends StatelessWidget {
  const _MemberStatusChip({required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final isActive = status == 'activo';
    return Chip(
      label: Text(isActive ? 'Activo' : 'Inactivo'),
      visualDensity: VisualDensity.compact,
      backgroundColor: isActive
          ? Colors.green.withValues(alpha: 0.12)
          : Colors.grey.withValues(alpha: 0.15),
    );
  }
}
