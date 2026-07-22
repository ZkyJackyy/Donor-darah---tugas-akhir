import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../../auth/providers/auth_provider.dart';
import '../../scan/providers/scan_provider.dart';

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _isUpdating = false;

  Future<void> _openScanVerification() async {
    final result = await context.push<String>('/scan');
    if (result == null || !mounted) return;

    final scanProvider = context.read<ScanProvider>();
    final success = await scanProvider.verify(result);
    if (!mounted) return;

    if (success) {
      AppSnackbar.showSuccess(context, scanProvider.resultMessage ?? 'Verifikasi berhasil');
    } else {
      AppSnackbar.showError(context, scanProvider.error ?? 'Verifikasi gagal');
    }
  }

  void _toggleAvailability(bool newValue) async {
    setState(() => _isUpdating = true);
    final success = await context.read<AuthProvider>().updateProfile(isAvailable: newValue);
    setState(() => _isUpdating = false);
    
    if (mounted) {
      if (success) {
        AppSnackbar.showSuccess(context, 'Status ketersediaan diperbarui');
      } else {
        AppSnackbar.showError(context, context.read<AuthProvider>().error ?? 'Gagal memperbarui status');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final user = context.watch<AuthProvider>().user;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Profil Saya', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        automaticallyImplyLeading: false,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            onPressed: () async {
              await context.read<AuthProvider>().logout();
              if (context.mounted) context.go('/login');
            },
          )
        ],
      ),
      body: user == null
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  CircleAvatar(
                    radius: 50,
                    backgroundColor: AppColors.primaryLight,
                    backgroundImage: user.photoUrl != null ? NetworkImage(user.photoUrl!) : null,
                    child: user.photoUrl == null
                        ? const Icon(Icons.person, size: 50, color: Colors.white)
                        : null,
                  ),
                  const SizedBox(height: 24),
                  _buildInfoCard(
                    title: 'Informasi Pribadi',
                    children: [
                      _buildInfoRow(Icons.person_outline, 'Nama', user.name),
                      _buildInfoRow(Icons.email_outlined, 'Email', user.email),
                      _buildInfoRow(Icons.phone_outlined, 'Nomor HP', user.phone ?? '-'),
                      _buildInfoRow(Icons.calendar_today_outlined, 'Tanggal Lahir', user.birthDate ?? '-'),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildInfoCard(
                    title: 'Informasi Medis',
                    children: [
                      _buildInfoRow(Icons.bloodtype_outlined, 'Golongan Darah', '${user.golonganDarah ?? '-'} ${user.rhesus ?? ''}'),
                      _buildInfoRow(Icons.monitor_weight_outlined, 'Berat Badan', user.weight != null ? '${user.weight} kg' : '-'),
                      _buildInfoRow(Icons.history_outlined, 'Donor Terakhir', user.tanggalDonorTerakhir ?? 'Belum pernah'),
                    ],
                  ),
                  const SizedBox(height: 16),
                  _buildInfoCard(
                    title: 'Status Ketersediaan',
                    children: [
                      SwitchListTile(
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          user.isAvailable ? 'Tersedia untuk donor' : 'Sedang tidak tersedia',
                          style: TextStyle(
                            fontSize: 16,
                            color: user.isAvailable ? AppColors.success : AppColors.error,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        subtitle: const Text(
                          'Jika dinonaktifkan, Anda tidak akan muncul dalam daftar pencarian pendonor.',
                          style: TextStyle(fontSize: 12),
                        ),
                        value: user.isAvailable,
                        activeColor: AppColors.success,
                        onChanged: _isUpdating ? null : _toggleAvailability,
                        secondary: _isUpdating 
                            ? const SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))
                            : Icon(
                                user.isAvailable ? Icons.check_circle_outline : Icons.cancel_outlined,
                                color: user.isAvailable ? AppColors.success : AppColors.error,
                              ),
                      ),
                    ],
                  ),
                  if (user.isAdmin) ...[
                    const SizedBox(height: 16),
                    _buildInfoCard(
                      title: 'Menu Admin',
                      children: [
                        CustomButton(
                          text: 'Verifikasi Donor (Scan/Kode)',
                          onPressed: _openScanVerification,
                        ),
                      ],
                    ),
                  ],
                  const SizedBox(height: 32),
                  CustomButton(
                    text: 'Edit Profil',
                    onPressed: () => context.push('/profile/edit'),
                  ),
                  const SizedBox(height: 100),
                ],
              ),
            ),
    );
  }

  Widget _buildInfoCard({required String title, required List<Widget> children}) {
    return Card(
      color: Colors.white,
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.primary,
              ),
            ),
            const Divider(height: 24),
            ...children,
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 20, color: AppColors.textSecondary),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppColors.textSecondary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
