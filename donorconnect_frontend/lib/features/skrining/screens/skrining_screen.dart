import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../providers/skrining_provider.dart';

class SkriningScreen extends StatefulWidget {
  final int donorCandidateId;
  const SkriningScreen({super.key, required this.donorCandidateId});

  @override
  State<SkriningScreen> createState() => _SkriningScreenState();
}

class _SkriningScreenState extends State<SkriningScreen> {
  bool _healthStatus = false;
  bool _minWeight = false;
  bool _noMedicine = false;
  bool _notPregnant = false;

  void _submit() async {
    if (!_healthStatus || !_minWeight || !_noMedicine || !_notPregnant) {
      AppSnackbar.showError(context, 'Anda harus mencentang semua pernyataan skrining mandiri.');
      return;
    }

    final success = await context.read<SkriningProvider>().submitScreening(
          donorCandidateId: widget.donorCandidateId,
          healthStatus: _healthStatus,
          minWeight: _minWeight,
          noMedicine: _noMedicine,
          notPregnant: _notPregnant,
        );

    if (!mounted) return;

    if (success) {
      // Proceed to Konfirmasi / Detail Request
      AppSnackbar.showSuccess(context, 'Skrining berhasil. Lanjutkan konfirmasi kesediaan donor.');
      context.pop(true); // Return true to indicate screening passed
    } else {
      AppSnackbar.showError(context, context.read<SkriningProvider>().error ?? 'Gagal memproses skrining.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = context.watch<SkriningProvider>().isLoading;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Skrining Mandiri', style: TextStyle(color: Colors.white)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Icon(Icons.medical_information, size: 64, color: AppColors.primary),
            const SizedBox(height: 16),
            const Text(
              'Self-Assessment Donor Darah',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: AppColors.primaryDark,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Pastikan Anda memenuhi syarat-syarat di bawah ini sebelum mengkonfirmasi kesediaan mendonor.',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 14, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 32),
            Card(
              elevation: 0,
              shape: RoundedRectangleBorder(
                side: BorderSide(color: Colors.grey.shade300),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Padding(
                padding: const EdgeInsets.all(8.0),
                child: Column(
                  children: [
                    CheckboxListTile(
                      activeColor: AppColors.primary,
                      title: const Text('Kondisi tubuh saya sehat hari ini'),
                      value: _healthStatus,
                      onChanged: (val) => setState(() => _healthStatus = val ?? false),
                    ),
                    const Divider(height: 1),
                    CheckboxListTile(
                      activeColor: AppColors.primary,
                      title: const Text('Berat badan saya lebih dari atau sama dengan 45 kg'),
                      value: _minWeight,
                      onChanged: (val) => setState(() => _minWeight = val ?? false),
                    ),
                    const Divider(height: 1),
                    CheckboxListTile(
                      activeColor: AppColors.primary,
                      title: const Text('Saya tidak sedang mengonsumsi obat-obatan tertentu secara rutin'),
                      value: _noMedicine,
                      onChanged: (val) => setState(() => _noMedicine = val ?? false),
                    ),
                    const Divider(height: 1),
                    CheckboxListTile(
                      activeColor: AppColors.primary,
                      title: const Text('Saya tidak sedang hamil / haid (khusus wanita)'),
                      value: _notPregnant,
                      onChanged: (val) => setState(() => _notPregnant = val ?? false),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 32),
            CustomButton(
              text: 'Selesai Skrining',
              onPressed: _submit,
              isLoading: isLoading,
            ),
          ],
        ),
      ),
    );
  }
}
