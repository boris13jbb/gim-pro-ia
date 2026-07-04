import 'package:flutter/material.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Persistencia de la preferencia de tema (claro/oscuro/sistema).
///
/// Reutiliza `flutter_secure_storage` (ya presente en el proyecto) para no
/// añadir dependencias nuevas. El valor no es sensible; se guarda como el
/// nombre del enum (`system` | `light` | `dark`).
class ThemeStorage {
  ThemeStorage({FlutterSecureStorage? storage})
    : _storage = storage ?? const FlutterSecureStorage();

  static const _themeModeKey = 'app_theme_mode';

  final FlutterSecureStorage _storage;

  /// Lee la preferencia guardada. Si no existe (primer arranque), la app abre
  /// en modo oscuro por defecto (estilo principal del diseño).
  Future<ThemeMode> readThemeMode() async {
    final value = await _storage.read(key: _themeModeKey);
    switch (value) {
      case 'light':
        return ThemeMode.light;
      case 'dark':
        return ThemeMode.dark;
      case 'system':
        return ThemeMode.system;
      default:
        return ThemeMode.dark;
    }
  }

  /// Guarda la preferencia seleccionada por el usuario.
  Future<void> saveThemeMode(ThemeMode mode) async {
    await _storage.write(key: _themeModeKey, value: mode.name);
  }
}
