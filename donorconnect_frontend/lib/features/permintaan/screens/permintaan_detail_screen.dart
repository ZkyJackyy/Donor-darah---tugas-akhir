import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/date_formatter.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../../../shared/widgets/custom_button.dart';
import '../providers/permintaan_provider.dart';
import '../../konfirmasi/providers/konfirmasi_provider.dart';
import '../../skrining/screens/skrining_screen.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../shared/models/blood_request_model.dart';
import '../../../shared/models/ticket_data.dart';

class PermintaanDetailScreen extends StatefulWidget {
  final int requestId;
  const PermintaanDetailScreen({
    super.key, 
    required this.requestId,
  });

  @override
  State<PermintaanDetailScreen> createState() => _PermintaanDetailScreenState();
}

class _PermintaanDetailScreenState extends State<PermintaanDetailScreen> {
  GoogleMapController? _mapController;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId);
    });
  }

  // Dipanggil saat status 'notified' → buka skrining dulu
  void _handleKonfirmasi(int donorCandidateId) async {
    final isScreeningPassed = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SkriningScreen(donorCandidateId: donorCandidateId),
      ),
    );

    if (isScreeningPassed == true) {
      if (!mounted) return;
      // Setelah skrining berhasil, status sudah jadi 'screening_passed' di backend.
      // Refresh data agar tombol berubah ke 'Konfirmasi Kesediaan'
      context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId);
      AppSnackbar.showSuccess(context, 'Skrining selesai! Tekan "Konfirmasi Kesediaan" untuk lanjut.');
    }
  }

  // Dipanggil saat status 'screening_passed' → langsung konfirmasi ke API
  void _handleKonfirmasiLangsung(int donorCandidateId) async {
    final success = await context.read<KonfirmasiProvider>().confirmDonor(
      donorCandidateId: donorCandidateId,
      status: 'confirmed',
    );

    if (!mounted) return;

    if (success) {
      final konfirmasi = context.read<KonfirmasiProvider>();
      final user = context.read<AuthProvider>().user;
      final item = context.read<PermintaanProvider>().selectedPermintaan;

      if (konfirmasi.qrToken != null) {
        context.push('/tiket', extra: TicketData.fromConfirmResult(
          donorName: user?.name,
          golonganDarah: user?.golonganDarah ?? item?.golonganDarah,
          rhesus: user?.rhesus ?? item?.rhesus,
          hospitalName: konfirmasi.hospitalName ?? item?.hospitalName,
          requestId: widget.requestId,
          qrToken: konfirmasi.qrToken,
          kodeVerifikasi: konfirmasi.kodeVerifikasi,
          expiresAt: konfirmasi.expiresAt,
        ));
      } else {
        AppSnackbar.showError(context, 'Gagal mendapatkan tiket digital');
      }
    } else {
      AppSnackbar.showError(context, context.read<KonfirmasiProvider>().error ?? 'Gagal konfirmasi');
    }
  }

  TicketData _buildTicketData({
    required Map<String, dynamic> userInfo,
    required BloodRequestModel item,
    required String dateField,
    bool includeExpiry = false,
    bool isUsed = false,
  }) {
    final user = context.read<AuthProvider>().user;

    return TicketData.fromCandidateInfo(
      userInfo: userInfo,
      donorName: user?.name,
      golonganDarah: user?.golonganDarah ?? item.golonganDarah,
      rhesus: user?.rhesus ?? item.rhesus,
      hospitalName: item.hospitalName,
      requestId: item.id,
      dateField: dateField,
      includeExpiry: includeExpiry,
      isUsed: isUsed,
    );
  }

  void _handleTolak(int donorCandidateId) async {
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Tolak Permintaan?'),
        content: const Text('Yakin ingin membatalkan keikutsertaan Anda untuk mendonor pada permintaan ini?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Kembali'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            style: TextButton.styleFrom(foregroundColor: AppColors.error),
            child: const Text('Ya, Tolak'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      if (!mounted) return;
      final success = await context.read<KonfirmasiProvider>().confirmDonor(
        donorCandidateId: donorCandidateId,
        status: 'declined',
      );

      if (!mounted) return;
      if (success) {
        AppSnackbar.showSuccess(context, 'Penolakan berhasil dicatat');
        context.pop(); // Kembali ke halaman sebelumnya
      } else {
        AppSnackbar.showError(context, context.read<KonfirmasiProvider>().error ?? 'Gagal menolak');
      }
    }
  }

  Future<void> _openMaps(double lat, double lng) async {
    final url = 'https://www.google.com/maps/search/?api=1&query=$lat,$lng';
    if (await canLaunchUrl(Uri.parse(url))) {
      await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    }
  }

  Widget _buildActionSection(Map<String, dynamic>? userInfo, bool isLoading, BloodRequestModel item) {
    if (userInfo == null || userInfo['is_candidate'] != true) {
      return Card(
        color: Colors.amber.shade100,
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Maaf, Anda belum terdaftar sebagai kandidat pendonor untuk permintaan ini. Silakan tunggu notifikasi dari admin.',
            textAlign: TextAlign.center,
            style: TextStyle(fontWeight: FontWeight.bold),
          ),
        ),
      );
    }

    final String status = userInfo['status'] ?? 'pending';
    final int candidateId = userInfo['candidate_id'];

    if (status == 'declined') {
      return Card(
        color: Colors.amber.shade100,
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Anda telah menolak permintaan ini.',
            textAlign: TextAlign.center,
            style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange),
          ),
        ),
      );
    }

    if (status == 'verified') {
      final verifiedAtFormatted = formatIndonesianDate(userInfo['verified_at'] as String?);

      return Card(
        color: AppColors.success.withValues(alpha: 0.1),
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            children: [
              const Icon(Icons.check_circle, color: AppColors.success, size: 48),
              const SizedBox(height: 12),
              const Text(
                'Donor Selesai & Terverifikasi',
                textAlign: TextAlign.center,
                style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.success, fontSize: 16),
              ),
              const SizedBox(height: 4),
              Text(
                'Terima kasih atas donasi Anda pada $verifiedAtFormatted',
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
              ),
              const SizedBox(height: 16),
              CustomButton(
                text: 'Lihat Tiket Digital',
                onPressed: () => context.push('/tiket', extra: _buildTicketData(
                  userInfo: userInfo,
                  item: item,
                  dateField: 'verified_at',
                  isUsed: true,
                )),
                isLoading: isLoading,
                color: AppColors.success,
              ),
            ],
          ),
        ),
      );
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (status == 'notified')
          CustomButton(
            text: 'Ikuti Donor',
            onPressed: () => _handleKonfirmasi(candidateId),
            isLoading: isLoading,
          )
        else if (status == 'screening_passed')
          CustomButton(
            text: 'Konfirmasi Kesediaan',
            onPressed: () => _handleKonfirmasiLangsung(candidateId),
            isLoading: isLoading,
          )
        else if (status == 'confirmed') ...[
          CustomButton(
            text: 'Lihat Tiket Digital',
            onPressed: () => context.push('/tiket', extra: _buildTicketData(
              userInfo: userInfo,
              item: item,
              dateField: 'confirmed_at',
              includeExpiry: true,
            )),
            isLoading: isLoading,
          ),
          const SizedBox(height: 12),
          OutlinedButton(
            onPressed: isLoading ? null : () => _handleTolak(candidateId),
            style: OutlinedButton.styleFrom(
              foregroundColor: AppColors.error,
              side: const BorderSide(color: AppColors.error),
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
            child: const Text('Tidak Bisa Hadir', style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ] else // Default
          CustomButton(
            text: 'Konfirmasi Kesediaan Donor',
            onPressed: () => _handleKonfirmasi(candidateId),
            isLoading: isLoading,
          ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PermintaanProvider>();
    final item = provider.selectedPermintaan;
    final userInfo = provider.userCandidateInfo;
    final isLoading = provider.isLoading || context.watch<KonfirmasiProvider>().isLoading;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Detail Permintaan', style: TextStyle(color: Colors.white)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : item == null
              ? Center(child: Text(provider.error ?? 'Data tidak ditemukan'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: AppColors.primaryLight.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.primaryLight.withOpacity(0.3)),
                        ),
                        child: Column(
                          children: [
                            const Icon(Icons.water_drop, size: 64, color: AppColors.primary),
                            const SizedBox(height: 16),
                            const Text(
                              'Dibutuhkan Pendonor Darah',
                              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                            ),
                            const SizedBox(height: 8),
                            Text(
                              item.urgencyLevel == 'urgent' || item.urgencyLevel == 'critical' 
                                  ? 'Segera Dibutuhkan' 
                                  : 'Permintaan Normal',
                              style: TextStyle(
                                color: item.urgencyLevel == 'normal' ? AppColors.success : AppColors.error, 
                                fontWeight: FontWeight.bold
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 32),
                      const Text(
                        'Informasi Permintaan',
                        style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                      ),
                      const Divider(),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.bloodtype, color: AppColors.primary),
                        title: const Text('Golongan Darah & Rhesus'),
                        subtitle: Text('${item.golonganDarah} / ${item.rhesus}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.shopping_bag, color: AppColors.primary),
                        title: const Text('Kebutuhan'),
                        subtitle: Text('${item.jumlahKantong} Kantong', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.location_on, color: AppColors.primary),
                        title: const Text('Lokasi'),
                        subtitle: Text(item.hospitalName ?? '-', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.timer, color: AppColors.primary),
                        title: const Text('Batas Waktu'),
                        subtitle: Text(item.batasWaktu, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      ),
                      
                      const SizedBox(height: 16),
                      if (item.latitude != 0.0) ...[
                        const Text(
                          'Lokasi Donor',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          height: 200,
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.grey.shade300),
                          ),
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(16),
                            child: GoogleMap(
                              initialCameraPosition: CameraPosition(
                                target: LatLng(item.latitude, item.longitude),
                                zoom: 15,
                              ),
                              markers: {
                                Marker(
                                  markerId: const MarkerId('pmi'),
                                  position: LatLng(item.latitude, item.longitude),
                                  infoWindow: InfoWindow(title: item.hospitalName),
                                ),
                              },
                              onMapCreated: (controller) => _mapController = controller,
                              myLocationButtonEnabled: false,
                              zoomControlsEnabled: false,
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        OutlinedButton.icon(
                          onPressed: () => _openMaps(item.latitude, item.longitude),
                          icon: const Icon(Icons.directions),
                          label: const Text('Petunjuk Arah'),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.primary,
                            side: const BorderSide(color: AppColors.primary),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                        ),
                      ],
                      
                      const SizedBox(height: 48),
                      _buildActionSection(userInfo, isLoading, item),
                    ],
                  ),
                ),
    );
  }
}
