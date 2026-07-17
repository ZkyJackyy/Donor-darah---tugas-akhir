import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';

// Constants & Services
import 'core/constants/app_colors.dart';
import 'core/services/deep_link_service.dart';
import 'core/services/api_service.dart';

// Providers
import 'features/auth/providers/auth_provider.dart';
import 'features/permintaan/providers/permintaan_provider.dart';
import 'features/skrining/providers/skrining_provider.dart';
import 'features/konfirmasi/providers/konfirmasi_provider.dart';
import 'features/riwayat/providers/riwayat_provider.dart';
import 'features/notifikasi/providers/notifikasi_provider.dart';
import 'features/scan/providers/scan_provider.dart';

// Screens
import 'features/auth/screens/login_screen.dart';
import 'features/auth/screens/register_screen.dart';
import 'features/auth/screens/splash_screen.dart';
import 'features/auth/screens/forgot_password_screen.dart';
import 'features/auth/screens/verify_email_screen.dart';
import 'features/permintaan/screens/permintaan_list_screen.dart';
import 'features/permintaan/screens/permintaan_detail_screen.dart';
import 'features/permintaan/screens/permintaan_all_screen.dart';
import 'features/konfirmasi/screens/tiket_digital_screen.dart';
import 'shared/models/ticket_data.dart';
import 'features/riwayat/screens/riwayat_screen.dart';
import 'features/notifikasi/screens/notifikasi_screen.dart';
import 'features/scan/screens/scan_screen.dart';
import 'features/profile/screens/profile_screen.dart';
import 'features/profile/screens/edit_profile_screen.dart';


void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID', null);
  runApp(const DonorConnectApp());
}

const _publicPaths = ['/splash', '/login', '/register', '/forgot-password', '/verify-email'];

// Centralized auth guard: protects every route not in _publicPaths from
// being reached without a stored token, regardless of navigation path
// (splash/deep-link already check this too, but this is the safety net
// for any future direct context.go(...) call that skips those).
Future<String?> _authGuard(BuildContext context, GoRouterState state) async {
  if (_publicPaths.contains(state.matchedLocation)) return null;

  final prefs = await SharedPreferences.getInstance();
  final token = prefs.getString('auth_token');
  if (token == null || token.isEmpty) {
    return '/login';
  }

  final user = context.read<AuthProvider>().user;
  if (user != null && !user.emailVerified) {
    return '/verify-email';
  }
  return null;
}

class _InvalidRouteScreen extends StatelessWidget {
  final String message;

  const _InvalidRouteScreen({required this.message});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Tautan Tidak Valid')),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.link_off, size: 64, color: AppColors.textSecondary),
              const SizedBox(height: 16),
              Text(message, textAlign: TextAlign.center),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: () => context.go('/home'),
                child: const Text('Kembali ke Beranda'),
              ),
            ],
          ),
        ),
      ),
    );
  }
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

    ApiService.onUnauthorized = () {
      _router.go('/login');
    };

    _deepLinkService = DeepLinkService(
      onDeepLinkReceived: (uri) async {
        debugPrint("Received DeepLink: $uri");
        
        String path = '';
        if (uri.host == 'permintaan' && uri.pathSegments.isNotEmpty) {
           path = '/permintaan/${uri.pathSegments.first}';
        }

        if (path.isEmpty) return;

        final prefs = await SharedPreferences.getInstance();
        final token = prefs.getString('auth_token');
        
        if (token != null && token.isNotEmpty) {
           // Sudah login, langsung navigasi
           _router.push(path);
        } else {
           // Belum login, simpan untuk dibuka nanti setelah login
           await prefs.setString('pending_deep_link', path);
        }
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
    initialLocation: '/splash',
    redirect: _authGuard,
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashScreen(),
      ),
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterScreen(),
      ),
      GoRoute(
        path: '/forgot-password',
        builder: (context, state) => const ForgotPasswordScreen(),
      ),
      GoRoute(
        path: '/verify-email',
        builder: (context, state) => VerifyEmailScreen(email: state.extra as String?),
      ),
      GoRoute(
        path: '/home',
        builder: (context, state) => const PermintaanListScreen(),
      ),
      GoRoute(
        path: '/permintaan/:id',
        builder: (context, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '');
          if (id == null) {
            return const _InvalidRouteScreen(
              message: 'Tautan permintaan donor tidak valid.',
            );
          }
          return PermintaanDetailScreen(requestId: id);
        },
      ),
      GoRoute(
        path: '/tiket',
        builder: (context, state) {
          final ticket = state.extra as TicketData?;
          if (ticket == null) {
            return const Scaffold(body: Center(child: Text('Tiket tidak ditemukan')));
          }
          return TiketDigitalScreen(ticket: ticket);
        },
      ),
      GoRoute(
        path: '/riwayat',
        builder: (context, state) => const RiwayatScreen(),
      ),
      GoRoute(
        path: '/profile',
        builder: (context, state) => const ProfileScreen(),
      ),
      GoRoute(
        path: '/profile/edit',
        builder: (context, state) => const EditProfileScreen(),
      ),
      GoRoute(
        path: '/permintaan-all',
        builder: (context, state) => const PermintaanAllScreen(),
      ),
      GoRoute(
        path: '/notifikasi',
        builder: (context, state) => const NotifikasiScreen(),
      ),
      GoRoute(
        path: '/scan',
        builder: (context, state) => const ScanScreen(),
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
        ChangeNotifierProvider(create: (_) => NotifikasiProvider()),
        ChangeNotifierProvider(create: (_) => ScanProvider()),
      ],
      child: MaterialApp.router(
        title: 'Sahabat Donor',
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
