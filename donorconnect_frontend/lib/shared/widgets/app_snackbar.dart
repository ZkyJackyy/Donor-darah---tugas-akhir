import 'package:flutter/material.dart';
import 'package:donorconnect_frontend/core/constants/app_colors.dart';

enum _AppSnackbarType { success, error, warning }

class AppSnackbar {
  AppSnackbar._();

  static void showSuccess(BuildContext context, String message) =>
      _show(context, message, _AppSnackbarType.success);

  static void showError(BuildContext context, String message) =>
      _show(context, message, _AppSnackbarType.error);

  static void showWarning(BuildContext context, String message) =>
      _show(context, message, _AppSnackbarType.warning);

  static void _show(BuildContext context, String message, _AppSnackbarType type) {
    if (!context.mounted) return;

    final (color, icon) = switch (type) {
      _AppSnackbarType.success => (AppColors.success, Icons.check_circle_outline),
      _AppSnackbarType.error => (AppColors.error, Icons.error_outline),
      _AppSnackbarType.warning => (AppColors.warning, Icons.warning_amber_outlined),
    };

    ScaffoldMessenger.of(context)
      ..hideCurrentSnackBar()
      ..showSnackBar(
        SnackBar(
          content: Row(
            children: [
              Icon(icon, color: Colors.white),
              const SizedBox(width: 12),
              Expanded(child: Text(message, style: const TextStyle(color: Colors.white))),
            ],
          ),
          backgroundColor: color,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          margin: const EdgeInsets.all(12),
          duration: Duration(seconds: type == _AppSnackbarType.error ? 4 : 3),
        ),
      );
  }
}
