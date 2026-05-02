import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:google_fonts/google_fonts.dart';

// Constants & Services
import 'core/constants/app_colors.dart';
import 'core/services/deep_link_service.dart';

// Providers
import 'features/auth/providers/auth_provider.dart';
import 'features/permintaan/providers/permintaan_provider.dart';
import 'features/skrining/providers/skrining_provider.dart';
import 'features/konfirmasi/providers/konfirmasi_provider.dart';
import 'features/riwayat/providers/riwayat_provider.dart';

// Screens
import 'features/auth/screens/login_screen.dart';
import 'features/auth/screens/register_screen.dart';
import 'features/permintaan/screens/permintaan_list_screen.dart';
import 'features/permintaan/screens/permintaan_detail_screen.dart';
import 'features/permintaan/screens/permintaan_all_screen.dart';
import 'features/konfirmasi/screens/tiket_digital_screen.dart';
import 'features/riwayat/screens/riwayat_screen.dart';
import 'features/profile/screens/edit_profile_screen.dart';

void main() {
  runApp(const DonorConnectApp());
}

class DonorConnectApp extends StatefulWidget {
  const DonorConnectApp({super.key});

  @override
  State<DonorConnectApp> createState() => _DonorConnectAppState();
}

class _DonorConnectAppState extends State<DonorConnectApp> {
  late DeepLinkService _deepLinkService;

  @override
  void initState() {
    super.initState();
    _deepLinkService = DeepLinkService(
      onDeepLinkReceived: (uri) {
        // Handle deep links like donorpmi://permintaan/1
        // router.go(uri.path); // depending on setup
        debugPrint("Received DeepLink: $uri");
      },
    );
  }

  @override
  void dispose() {
    _deepLinkService.dispose();
    super.dispose();
  }

  // GoRouter configuration
  final _router = GoRouter(
    initialLocation: '/login',
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => const PermintaanListScreen(),
      ),
      GoRoute(
        path: '/permintaan/:id',
        builder: (context, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '1') ?? 1;
          return PermintaanDetailScreen(requestId: id);
        },
      ),
      GoRoute(
        path: '/tiket',
        builder: (context, state) {
          final qrToken = state.extra as String? ?? 'invalid_token';
          return TiketDigitalScreen(qrToken: qrToken);
        },
      ),
      GoRoute(
        path: '/riwayat',
        builder: (context, state) => const RiwayatScreen(),
      ),
      GoRoute(
        path: '/profile/edit',
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: '/permintaan-all',
        builder: (context, state) => const PermintaanAllScreen(),
      ),
    ],
  );

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => PermintaanProvider()),
        ChangeNotifierProvider(create: (_) => SkriningProvider()),
        ChangeNotifierProvider(create: (_) => KonfirmasiProvider()),
        ChangeNotifierProvider(create: (_) => RiwayatProvider()),
      ],
      child: MaterialApp.router(
        title: 'DonorConnect',
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: AppColors.primary),
          scaffoldBackgroundColor: AppColors.background,
          textTheme: GoogleFonts.interTextTheme(
            Theme.of(context).textTheme,
          ),
          useMaterial3: true,
        ),
        routerConfig: _router,
        debugShowCheckedModeBanner: false,
      ),
    );
  }
}
