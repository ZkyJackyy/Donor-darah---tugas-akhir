import 'package:flutter/material.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/api_error_handler.dart';
import '../../../shared/widgets/custom_button.dart';
import '../../../shared/widgets/custom_text_field.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _tokenController = TextEditingController();
  final _passwordController = TextEditingController();
  final _passwordConfirmController = TextEditingController();
  final _apiService = ApiService();

  bool _isLoading = false;
  bool _tokenSent = false;
  String? _error;
  String? _success;

  @override
  void dispose() {
    _emailController.dispose();
    _tokenController.dispose();
    _passwordController.dispose();
    _passwordConfirmController.dispose();
    super.dispose();
  }

  Future<void> _requestToken() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _error = null;
      _success = null;
    });

    try {
      final response = await _apiService.post(
        ApiConstants.forgotPassword,
        data: {'email': _emailController.text.trim()},
      );

      if (response.data['status'] == true) {
        setState(() {
          _tokenSent = true;
          _success = 'Token reset telah dikirim. Silakan cek WhatsApp Anda.';
        });
      } else {
        _error = response.data['message'];
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _resetPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _error = null;
      _success = null;
    });

    try {
      final response = await _apiService.post(
        ApiConstants.resetPassword,
        data: {
          'email': _emailController.text.trim(),
          'token': _tokenController.text.trim(),
          'password': _passwordController.text,
          'password_confirmation': _passwordConfirmController.text,
        },
      );

      if (response.data['status'] == true) {
        setState(() => _success = 'Password berhasil diubah. Silakan login.');
        await Future.delayed(const Duration(seconds: 2));
        if (mounted) Navigator.of(context).pop();
      } else {
        _error = response.data['message'];
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Lupa Password', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const SizedBox(height: 24),
              const Icon(Icons.lock_reset, size: 64, color: AppColors.primary),
              const SizedBox(height: 16),
              const Text(
                'Masukkan email Anda untuk menerima token reset password',
                textAlign: TextAlign.center,
                style: TextStyle(color: AppColors.textSecondary),
              ),
              const SizedBox(height: 32),

              if (_error != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: AppColors.error.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.error.withOpacity(0.3)),
                  ),
                  child: Text(_error!, style: const TextStyle(color: AppColors.error, fontSize: 13)),
                ),

              if (_success != null)
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: AppColors.success.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: AppColors.success.withOpacity(0.3)),
                  ),
                  child: Text(_success!, style: const TextStyle(color: AppColors.success, fontSize: 13)),
                ),

              CustomTextField(
                controller: _emailController,
                label: 'Email',
                keyboardType: TextInputType.emailAddress,
                prefixIcon: Icons.email_outlined,
              ),

              if (_tokenSent) ...[
                const SizedBox(height: 16),
                CustomTextField(
                  controller: _tokenController,
                  label: 'Token Reset',
                  prefixIcon: Icons.vpn_key,
                ),
                const SizedBox(height: 16),
                CustomTextField(
                  controller: _passwordController,
                  label: 'Password Baru',
                  isPassword: true,
                  prefixIcon: Icons.lock_outline,
                ),
                const SizedBox(height: 16),
                CustomTextField(
                  controller: _passwordConfirmController,
                  label: 'Konfirmasi Password',
                  isPassword: true,
                  prefixIcon: Icons.lock_outline,
                ),
              ],

              const SizedBox(height: 24),
              CustomButton(
                text: _tokenSent ? 'Reset Password' : 'Kirim Token',
                isLoading: _isLoading,
                onPressed: _tokenSent ? _resetPassword : _requestToken,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
