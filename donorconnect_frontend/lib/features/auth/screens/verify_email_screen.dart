import 'dart:async';

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../../../shared/widgets/custom_text_field.dart';
import '../providers/auth_provider.dart';

class VerifyEmailScreen extends StatefulWidget {
  final String? email;

  const VerifyEmailScreen({super.key, this.email});

  @override
  State<VerifyEmailScreen> createState() => _VerifyEmailScreenState();
}

class _VerifyEmailScreenState extends State<VerifyEmailScreen> {
  final _formKey = GlobalKey<FormState>();
  final _codeController = TextEditingController();

  Timer? _cooldownTimer;
  int _resendCooldown = 0;

  String? get _email => widget.email ?? context.read<AuthProvider>().user?.email;

  @override
  void dispose() {
    _codeController.dispose();
    _cooldownTimer?.cancel();
    super.dispose();
  }

  void _startCooldown() {
    setState(() => _resendCooldown = 60);
    _cooldownTimer?.cancel();
    _cooldownTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_resendCooldown <= 1) {
        timer.cancel();
        setState(() => _resendCooldown = 0);
      } else {
        setState(() => _resendCooldown -= 1);
      }
    });
  }

  void _submit() async {
    if (_formKey.currentState!.validate()) {
      final email = _email;
      if (email == null) {
        AppSnackbar.showError(context, 'Email tidak ditemukan, silakan login ulang');
        return;
      }

      final success = await context.read<AuthProvider>().verifyEmail(
            email,
            _codeController.text,
          );

      if (!mounted) return;

      if (success) {
        AppSnackbar.showSuccess(context, 'Email berhasil diverifikasi');
        context.go('/home');
      } else {
        AppSnackbar.showError(context, context.read<AuthProvider>().error ?? 'Verifikasi gagal');
      }
    }
  }

  void _resend() async {
    final email = _email;
    if (email == null) {
      AppSnackbar.showError(context, 'Email tidak ditemukan, silakan login ulang');
      return;
    }

    final success = await context.read<AuthProvider>().resendVerificationCode(email);

    if (!mounted) return;

    if (success) {
      AppSnackbar.showSuccess(context, 'Kode verifikasi telah dikirim ulang');
      _startCooldown();
    } else {
      AppSnackbar.showError(context, context.read<AuthProvider>().error ?? 'Gagal mengirim ulang kode');
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = context.watch<AuthProvider>().isLoading;
    final email = _email;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: AppColors.primary),
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24.0),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Icon(Icons.mark_email_read_outlined, size: 80, color: AppColors.primary),
                  const SizedBox(height: 24),
                  const Text(
                    'Verifikasi Email',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primaryDark,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    email != null
                        ? 'Kami telah mengirim kode 6 digit ke $email'
                        : 'Masukkan kode 6 digit yang dikirim ke email Anda',
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 16, color: AppColors.textSecondary),
                  ),
                  const SizedBox(height: 32),
                  CustomTextField(
                    label: 'Kode Verifikasi',
                    hintText: 'Masukkan 6 digit kode',
                    controller: _codeController,
                    prefixIcon: Icons.pin_outlined,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    validator: (value) {
                      if (value == null || value.isEmpty) return 'Kode wajib diisi';
                      if (value.length != 6) return 'Kode harus 6 digit';
                      return null;
                    },
                  ),
                  const SizedBox(height: 32),
                  CustomButton(
                    text: 'Verifikasi',
                    onPressed: _submit,
                    isLoading: isLoading,
                  ),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text(
                        "Tidak menerima kode? ",
                        style: TextStyle(color: AppColors.textSecondary),
                      ),
                      TextButton(
                        onPressed: _resendCooldown > 0 || isLoading ? null : _resend,
                        child: Text(
                          _resendCooldown > 0 ? 'Kirim ulang (${_resendCooldown}s)' : 'Kirim ulang',
                          style: const TextStyle(
                            color: AppColors.primary,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
