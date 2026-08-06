import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../../auth/providers/auth_provider.dart';
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

  @override
  void initState() {
    super.initState();
    // Pernyataan "tidak hamil" tidak relevan untuk pengguna laki-laki —
    // otomatis dianggap terpenuhi tanpa perlu ditampilkan.
    if (context.read<AuthProvider>().user?.gender == 'male') {
      _notPregnant = true;
    }
  }

  void _submit() async {
    final provider = context.read<SkriningProvider>();
    final success = await provider.submitScreening(
          donorCandidateId: widget.donorCandidateId,
          healthStatus: _healthStatus,
          minWeight: _minWeight,
          noMedicine: _noMedicine,
          notPregnant: _notPregnant,
        );

    if (!mounted) return;

    if (!success) {
      AppSnackbar.showError(context, provider.error ?? 'Gagal memproses skrining.');
      return;
    }

    if (provider.eligible == true) {
      AppSnackbar.showSuccess(context, 'Skrining berhasil. Lanjutkan konfirmasi kesediaan donor.');
      context.pop(true); // Return true to indicate screening passed
    } else {
      AppSnackbar.showError(
        context,
        'Berdasarkan jawaban Anda, Anda belum memenuhi syarat untuk mendonor saat ini.',
      );
      context.pop(false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final isLoading = context.watch<SkriningProvider>().isLoading;
    final isMale = context.watch<AuthProvider>().user?.gender == 'male';

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
              'Centang sesuai kondisi Anda yang sebenarnya. Jika ada pernyataan yang tidak sesuai, Anda mungkin belum bisa mendonor saat ini.',
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
                    if (!isMale) ...[
                      const Divider(height: 1),
                      CheckboxListTile(
                        activeColor: AppColors.primary,
                        title: const Text('Saya tidak sedang hamil / haid'),
                        value: _notPregnant,
                        onChanged: (val) => setState(() => _notPregnant = val ?? false),
                      ),
                    ],
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
