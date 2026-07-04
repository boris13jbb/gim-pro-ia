import 'package:flutter/material.dart';

import '../services/theme_storage.dart';

/// Estado global de la preferencia de tema de la app.
///
/// La UI solo lee [themeMode] y dispara [setThemeMode]; la persistencia vive en
/// [ThemeStorage]. Por defecto sigue el tema del sistema operativo hasta que se
/// carga la preferencia guardada o el usuario la cambia manualmente.
class ThemeProvider extends ChangeNotifier {
  ThemeProvider({ThemeStorage? themeStorage})
    : _themeStorage = themeStorage ?? ThemeStorage();

  final ThemeStorage _themeStorage;

  // Oscuro como valor inicial para evitar parpadeo claro antes de cargar la
  // preferencia guardada (el diseño principal es oscuro).
  ThemeMode _themeMode = ThemeMode.dark;
  ThemeMode get themeMode => _themeMode;

  /// Carga la preferencia guardada al iniciar la app.
  Future<void> load() async {
    _themeMode = await _themeStorage.readThemeMode();
    notifyListeners();
  }

  /// Cambia el tema y lo persiste. No hace trabajo si no hubo cambio real.
  Future<void> setThemeMode(ThemeMode mode) async {
    if (mode == _themeMode) return;
    _themeMode = mode;
    notifyListeners();
    await _themeStorage.saveThemeMode(mode);
  }
}
