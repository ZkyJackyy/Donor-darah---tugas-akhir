// Basic smoke test for DonorConnect: verifies the app boots to the splash
// screen without throwing, since SplashScreen owns the real auth/navigation
// decision (see lib/features/auth/screens/splash_screen.dart).

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:donorconnect_frontend/main.dart';

void main() {
  testWidgets('App boots and shows splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const DonorConnectApp());
    await tester.pump();

    expect(find.byType(MaterialApp), findsOneWidget);
  });
}
