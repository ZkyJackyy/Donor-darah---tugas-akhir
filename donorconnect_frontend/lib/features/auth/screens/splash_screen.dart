import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../providers/auth_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  void _checkAuth() async {
    final authProvider = context.read<AuthProvider>();
    // Coba auto login
    final isLoggedIn = await authProvider.tryAutoLogin();
    
    // Beri sedikit jeda agar logo terlihat (opsional)
    await Future.delayed(const Duration(seconds: 1));
    
    if (!mounted) return;

    if (isLoggedIn) {
      if (!mounted) return;
      context.go('/home'); // Langsung ke beranda jika token valid
    } else {
      context.go('/login'); // Ke halaman login jika token tidak ada/kadaluarsa
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Image.asset('assets/images/logo_apk.png', height: 100),
            const SizedBox(height: 24),
            const Text(
              'Sahabat Donor',
              style: TextStyle(
                fontSize: 28,
                fontWeight: FontWeight.bold,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 48),
            const CircularProgressIndicator(color: AppColors.primary),
          ],
        ),
      ),
    );
  }
}
