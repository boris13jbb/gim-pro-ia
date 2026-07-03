import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:gym_pro_app/app.dart';

void main() {
  testWidgets('App muestra pantalla de carga o login sin crash', (tester) async {
    await tester.pumpWidget(const GymProApp());
    await tester.pump();
    await tester.pump();

    final hasLogin = find.text('Acceso socios').evaluate().isNotEmpty;
    final hasLoader = find.byType(CircularProgressIndicator).evaluate().isNotEmpty;

    expect(hasLogin || hasLoader, isTrue);
  });
}
