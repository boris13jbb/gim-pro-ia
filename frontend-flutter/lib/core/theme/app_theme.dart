import 'package:flutter/material.dart';

/// Sistema visual global (Material 3) de la app de socios Iron Gym.
///
/// Estilo objetivo: app fitness moderna sobre fondo oscuro, con acento
/// naranja/ámbar, botones tipo "píldora", tarjetas redondeadas y tipografía
/// marcada. El tema oscuro es el principal; se conserva un tema claro para el
/// selector de apariencia (Perfil).
///
/// Centraliza toda la identidad visual: al vivir en el tema, cualquier pantalla
/// que use `Theme.of(context)` hereda esta apariencia sin estilos duplicados.
///
/// Nota de arquitectura: Flutter solo consume la API. Aquí no hay lógica de
/// negocio, únicamente presentación. Ambos temas se generan con el mismo
/// [_buildTheme] para no duplicar estilos entre claro y oscuro.
class AppTheme {
  AppTheme._();

  /// Ámbar/naranja — color de acento y de acción (CTA, enlaces, selección).
  static const Color accent = Color(0xFFF5A524);

  /// Texto/íconos sobre el acento (naranja es claro → texto casi negro).
  static const Color _onAccent = Color(0xFF141414);

  /// Azul tinta para enlaces/textos de acción en el tema claro (legible sobre
  /// blanco; el naranja no tendría suficiente contraste como texto).
  static const Color _brandInk = Color(0xFF1B2A4A);

  // --- Superficies tema claro ---
  static const Color _scaffoldLight = Color(0xFFF4F6FA);
  static const Color _surfaceLight = Colors.white;

  // --- Superficies tema oscuro (grises neutros, estilo FITFINITY) ---
  static const Color _scaffoldDark = Color(0xFF0E0F13);
  static const Color _surfaceDark = Color(0xFF1A1B21);
  static const Color _elevatedDark = Color(0xFF26272E);
  static const Color _onDark = Color(0xFFF2F3F5);
  static const Color _mutedDark = Color(0xFF9FA2AA);

  // Radios reutilizados para consistencia visual.
  static const double _radiusInput = 14;
  static const double _radiusCard = 20;
  static const double _radiusPill = 30;

  /// Tema claro (disponible desde el selector de apariencia).
  static ThemeData light() {
    final base = ColorScheme.fromSeed(
      seedColor: accent,
      brightness: Brightness.light,
    );
    final colorScheme = base.copyWith(
      primary: _brandInk,
      onPrimary: Colors.white,
      secondary: accent,
      onSecondary: _onAccent,
      surface: _surfaceLight,
    );
    return _buildTheme(colorScheme, _scaffoldLight);
  }

  /// Tema oscuro (principal). El acento naranja actúa como color primario para
  /// enlaces, foco y selección, legible sobre fondos oscuros.
  static ThemeData dark() {
    final base = ColorScheme.fromSeed(
      seedColor: accent,
      brightness: Brightness.dark,
    );
    final colorScheme = base.copyWith(
      primary: accent,
      onPrimary: _onAccent,
      secondary: accent,
      onSecondary: _onAccent,
      surface: _surfaceDark,
      onSurface: _onDark,
      onSurfaceVariant: _mutedDark,
      surfaceContainerHighest: _elevatedDark,
      outline: const Color(0xFF3A3B42),
      outlineVariant: const Color(0xFF2C2D34),
    );
    return _buildTheme(colorScheme, _scaffoldDark);
  }

  /// Generador único de tema a partir de un [ColorScheme] y el color de fondo
  /// del `Scaffold`. El acento ([accent]) se usa fijo en el botón primario,
  /// FAB y progreso; el resto de primeros planos usan tokens del esquema para
  /// mantener contraste en claro y oscuro.
  static ThemeData _buildTheme(ColorScheme colorScheme, Color scaffoldBackground) {
    final textTheme = _buildTextTheme(colorScheme);

    return ThemeData(
      useMaterial3: true,
      colorScheme: colorScheme,
      scaffoldBackgroundColor: scaffoldBackground,
      textTheme: textTheme,
      visualDensity: VisualDensity.adaptivePlatformDensity,

      // Barra superior integrada con el fondo (sin barra de color), estilo app
      // fitness: título marcado y sin sombra.
      appBarTheme: AppBarTheme(
        centerTitle: false,
        elevation: 0,
        scrolledUnderElevation: 0,
        backgroundColor: scaffoldBackground,
        foregroundColor: colorScheme.onSurface,
        titleTextStyle: textTheme.titleLarge?.copyWith(
          color: colorScheme.onSurface,
          fontWeight: FontWeight.w700,
        ),
      ),

      // Tarjetas redondeadas con borde muy sutil; sin sombras pesadas.
      cardTheme: CardThemeData(
        elevation: 0,
        color: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        margin: EdgeInsets.zero,
        clipBehavior: Clip.antiAlias,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusCard),
          side: BorderSide(
            color: colorScheme.outlineVariant.withValues(alpha: 0.5),
          ),
        ),
      ),

      // Campos de formulario: relleno elevado, borde neutro y foco en acento.
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: colorScheme.surfaceContainerHighest,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 16,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
          borderSide: BorderSide(color: colorScheme.outlineVariant),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
          borderSide: BorderSide(color: colorScheme.outlineVariant),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
          borderSide: BorderSide(color: colorScheme.primary, width: 1.6),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
          borderSide: BorderSide(color: colorScheme.error),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
          borderSide: BorderSide(color: colorScheme.error, width: 1.6),
        ),
      ),

      // Botón primario tipo píldora en acento naranja con texto oscuro.
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: accent,
          foregroundColor: _onAccent,
          minimumSize: const Size(0, 52),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 22),
          textStyle: textTheme.labelLarge,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(_radiusPill),
          ),
        ),
      ),

      // Botón secundario con contorno tipo píldora.
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: colorScheme.onSurface,
          minimumSize: const Size(0, 52),
          padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 22),
          textStyle: textTheme.labelLarge,
          side: BorderSide(color: colorScheme.outline),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(_radiusPill),
          ),
        ),
      ),

      // Botón de texto (acciones terciarias / enlaces) en color de acento.
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: colorScheme.primary,
          textStyle: textTheme.labelLarge,
        ),
      ),

      // Barra de navegación inferior: fondo de superficie, indicador y elemento
      // seleccionado en acento; etiquetas siempre visibles.
      navigationBarTheme: NavigationBarThemeData(
        backgroundColor: colorScheme.surface,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        height: 70,
        indicatorColor: accent.withValues(alpha: 0.20),
        labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
        iconTheme: WidgetStateProperty.resolveWith(
          (states) => IconThemeData(
            color: states.contains(WidgetState.selected)
                ? accent
                : colorScheme.onSurfaceVariant,
          ),
        ),
        labelTextStyle: WidgetStateProperty.resolveWith(
          (states) => textTheme.labelMedium?.copyWith(
            fontWeight: states.contains(WidgetState.selected)
                ? FontWeight.w700
                : FontWeight.w500,
            color: states.contains(WidgetState.selected)
                ? accent
                : colorScheme.onSurfaceVariant,
          ),
        ),
      ),

      // Chips (etiquetas de estado): fondo elevado y forma redondeada.
      chipTheme: ChipThemeData(
        backgroundColor: colorScheme.surfaceContainerHighest,
        side: BorderSide.none,
        labelStyle: textTheme.labelMedium,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusPill),
        ),
      ),

      // Listas: esquinas redondeadas e íconos en acento.
      listTileTheme: ListTileThemeData(
        iconColor: accent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
        ),
      ),

      // Avisos flotantes con acción en acento (colores M3 por contraste).
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: colorScheme.inverseSurface,
        contentTextStyle: textTheme.bodyMedium?.copyWith(
          color: colorScheme.onInverseSurface,
        ),
        actionTextColor: accent,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusInput),
        ),
      ),

      // Diálogos y hojas inferiores con formas coherentes.
      dialogTheme: DialogThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(_radiusCard),
        ),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(_radiusCard)),
        ),
      ),

      // Separadores, FAB y progreso alineados con la paleta.
      dividerTheme: DividerThemeData(
        color: colorScheme.outlineVariant,
        thickness: 1,
        space: 1,
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: accent,
        foregroundColor: _onAccent,
      ),
      progressIndicatorTheme: const ProgressIndicatorThemeData(
        color: accent,
      ),
    );
  }

  /// Construye la tipografía a partir de la escala Material 3, reforzando pesos
  /// y espaciados en títulos. Selecciona base clara u oscura según el brillo
  /// del esquema para asegurar contraste.
  static TextTheme _buildTextTheme(ColorScheme colorScheme) {
    final typography = Typography.material2021(colorScheme: colorScheme);
    final base = colorScheme.brightness == Brightness.dark
        ? typography.white
        : typography.black;

    return base.copyWith(
      headlineMedium: base.headlineMedium?.copyWith(
        fontWeight: FontWeight.w700,
        letterSpacing: -0.5,
      ),
      headlineSmall: base.headlineSmall?.copyWith(
        fontWeight: FontWeight.w700,
        letterSpacing: -0.3,
      ),
      titleLarge: base.titleLarge?.copyWith(fontWeight: FontWeight.w700),
      titleMedium: base.titleMedium?.copyWith(fontWeight: FontWeight.w600),
      labelLarge: base.labelLarge?.copyWith(
        fontWeight: FontWeight.w700,
        letterSpacing: 0.2,
      ),
    );
  }
}
