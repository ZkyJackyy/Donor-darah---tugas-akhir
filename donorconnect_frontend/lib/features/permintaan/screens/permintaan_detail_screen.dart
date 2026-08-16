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
import '../../riwayat/providers/riwayat_provider.dart';
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
    final result = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SkriningScreen(donorCandidateId: donorCandidateId),
      ),
    );

    // Baik lolos maupun tidak, status kandidat di backend sudah berubah
    // ('screening_passed' atau 'screening_failed') — refresh agar UI ikut update.
    if (result == null || !mounted) return;

    context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId);

    if (result == true) {
      AppSnackbar.showSuccess(context, 'Skrining selesai! Tekan "Konfirmasi Kesediaan" untuk lanjut.');
    }
  }

  // Setelah status kandidat berubah di backend (confirm/decline), semua
  // state yang menampilkan status lama harus disegarkan: layar ini sendiri
  // (supaya tombol aksi tidak nyangkut di status sebelumnya kalau user
  // kembali dari layar lain), list Beranda, dan tab "Semua Aktivitas" di
  // Riwayat. Fire-and-forget — tidak perlu ditunggu sebelum navigasi.
  void _refreshAfterStatusChange() {
    context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId);
    context.read<PermintaanProvider>().fetchPermintaanList();
    context.read<RiwayatProvider>().fetchPartisipasiList();
  }

  // Dipanggil saat status 'screening_passed' → langsung konfirmasi ke API
  void _handleKonfirmasiLangsung(int donorCandidateId) async {
    final success = await context.read<KonfirmasiProvider>().confirmDonor(
      donorCandidateId: donorCandidateId,
      status: 'confirmed',
    );

    if (!mounted) return;

    if (success) {
      _refreshAfterStatusChange();

      final konfirmasi = context.read<KonfirmasiProvider>();
      final user = context.read<AuthProvider>().user;
      final item = context.read<PermintaanProvider>().selectedPermintaan;

      if (konfirmasi.kodeVerifikasi != null) {
        context.push('/tiket', extra: TicketData.fromConfirmResult(
          donorName: user?.name,
          golonganDarah: user?.golonganDarah ?? item?.golonganDarah,
          rhesus: user?.rhesus ?? item?.rhesus,
          hospitalName: konfirmasi.hospitalName ?? item?.hospitalName,
          requestId: widget.requestId,
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
        _refreshAfterStatusChange();
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
      if (item.type == 'event') {
        return Container(
          decoration: BoxDecoration(
            color: AppColors.primary.withValues(alpha: 0.06),
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
          ),
          child: const Padding(
            padding: EdgeInsets.all(16.0),
            child: Text(
              'Event donor darah ini terbuka untuk semua golongan darah. Silakan datang langsung ke lokasi pada jadwal yang tertera — tidak perlu mendaftar di aplikasi.',
              textAlign: TextAlign.center,
              style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            ),
          ),
        );
      }

      return Container(
        decoration: BoxDecoration(
          color: AppColors.warning.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
        ),
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Maaf, Anda belum terdaftar sebagai kandidat pendonor untuk permintaan ini. Silakan tunggu notifikasi dari admin.',
            textAlign: TextAlign.center,
            style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary),
          ),
        ),
      );
    }

    final String status = userInfo['status'] ?? 'pending';
    final int candidateId = userInfo['candidate_id'];

    if (status == 'declined') {
      return Container(
        decoration: BoxDecoration(
          color: AppColors.warning.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
        ),
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Anda telah menolak permintaan ini.',
            textAlign: TextAlign.center,
            style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.warning),
          ),
        ),
      );
    }

    if (status == 'expired') {
      return Container(
        decoration: BoxDecoration(
          color: AppColors.warning.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.warning.withValues(alpha: 0.3)),
        ),
        child: const Padding(
          padding: EdgeInsets.all(16.0),
          child: Text(
            'Kode verifikasi Anda sudah kadaluarsa karena tidak digunakan tepat waktu. Silakan tunggu gelombang notifikasi berikutnya jika masih ingin mendonor.',
            textAlign: TextAlign.center,
            style: TextStyle(fontWeight: FontWeight.bold, color: AppColors.warning),
          ),
        ),
      );
    }

    if (status == 'verified') {
      final verifiedAtFormatted = formatIndonesianDate(userInfo['verified_at'] as String?);

      return Container(
        decoration: BoxDecoration(
          color: AppColors.success.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.success.withValues(alpha: 0.3)),
        ),
        child: Padding(
          padding: const EdgeInsets.all(20.0),
          child: Column(
            children: [
              const Text(
                'DONOR SELESAI & TERVERIFIKASI',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  color: AppColors.success,
                  fontSize: 14,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: 6),
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
        else if (status == 'screening_failed') ...[
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: AppColors.error.withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.error.withValues(alpha: 0.3)),
            ),
            child: const Row(
              children: [
                Icon(Icons.info_outline, color: AppColors.error),
                SizedBox(width: 12),
                Expanded(
                  child: Text(
                    'Anda belum memenuhi syarat skrining mandiri untuk mengikuti donor ini.',
                    style: TextStyle(color: AppColors.error, fontSize: 13),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          CustomButton(
            text: 'Ulangi Skrining Mandiri',
            onPressed: () => _handleKonfirmasi(candidateId),
            isLoading: isLoading,
          ),
        ] else if (status == 'confirmed') ...[
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
    final isLoading = provider.isLoadingDetail || context.watch<KonfirmasiProvider>().isLoading;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Detail Permintaan', style: TextStyle(color: Colors.white)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: provider.isLoadingDetail
          ? const Center(child: CircularProgressIndicator())
          : item == null
              ? RefreshIndicator(
                  onRefresh: () => context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId),
                  child: ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      const SizedBox(height: 200),
                      Center(child: Text(provider.errorDetail ?? 'Data tidak ditemukan')),
                    ],
                  ),
                )
              : RefreshIndicator(
                onRefresh: () => context.read<PermintaanProvider>().fetchPermintaanDetail(widget.requestId),
                child: SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(24.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Hero: golongan darah paling penting, ditonjolkan besar
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 28),
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 5),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.18),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                item.type == 'event'
                                    ? 'EVENT TERBUKA'
                                    : item.urgencyLevel == 'critical'
                                        ? 'KRITIS'
                                        : item.urgencyLevel == 'urgent'
                                            ? 'MENDESAK'
                                            : 'NORMAL',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 12,
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ),
                            const SizedBox(height: 16),
                            item.type == 'event'
                                ? const Icon(Icons.event_available, color: Colors.white, size: 48)
                                : Text(
                                    '${item.golonganDarah} ${item.rhesus}',
                                    style: const TextStyle(
                                      fontSize: 44,
                                      fontWeight: FontWeight.bold,
                                      color: Colors.white,
                                      height: 1.0,
                                    ),
                                  ),
                            const SizedBox(height: 6),
                            Text(
                              item.type == 'event' ? 'Donor Darah Terbuka untuk Semua Golongan' : 'Golongan Darah Dibutuhkan',
                              style: const TextStyle(color: Colors.white70, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Informasi permintaan, disusun sebagai daftar rapi tanpa ikon
                      const Text(
                        'Informasi Permintaan',
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                      ),
                      const SizedBox(height: 12),
                      Container(
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.grey.shade200),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            _InfoRow(
                              label: 'Kebutuhan',
                              value: item.type == 'event'
                                  ? (item.jumlahKantong > 0 ? 'Target ${item.jumlahKantong} Kantong' : 'Tanpa Batas')
                                  : '${item.jumlahKantong} Kantong',
                            ),
                            const _InfoDivider(),
                            _InfoRow(label: 'Lokasi', value: item.hospitalName ?? '-'),
                            if (item.hospitalAddress != null && item.hospitalAddress!.isNotEmpty) ...[
                              const _InfoDivider(),
                              _InfoRow(label: 'Alamat', value: item.hospitalAddress!),
                            ],
                            if (item.type == 'event' && item.eventStartsAt != null) ...[
                              const _InfoDivider(),
                              _InfoRow(label: 'Jadwal Mulai', value: item.eventStartsAtFormatted),
                            ],
                            if (item.quotaRequired != null) ...[
                              const _InfoDivider(),
                              _InfoRow(
                                label: 'Kuota Terisi',
                                value: '${item.quotaConfirmed ?? 0} dari ${item.quotaRequired} kantong',
                              ),
                            ],
                            const _InfoDivider(),
                            _InfoRow(
                              label: item.type == 'event' ? 'Jadwal Selesai' : 'Batas Waktu',
                              value: item.batasWaktu,
                              isLast: true,
                            ),
                          ],
                        ),
                      ),

                      if (item.notes != null && item.notes!.isNotEmpty) ...[
                        const SizedBox(height: 24),
                        const Text(
                          'Catatan dari Admin',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                        ),
                        const SizedBox(height: 12),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: Colors.grey.shade200),
                          ),
                          child: Text(
                            item.notes!,
                            style: const TextStyle(color: AppColors.textPrimary, fontSize: 14),
                          ),
                        ),
                      ],

                      if (item.latitude != 0.0) ...[
                        const SizedBox(height: 24),
                        const Text(
                          'Lokasi Donor',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
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
                        OutlinedButton(
                          onPressed: () => _openMaps(item.latitude, item.longitude),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.primary,
                            side: const BorderSide(color: AppColors.primary),
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                          ),
                          child: const Text('Petunjuk Arah', style: TextStyle(fontWeight: FontWeight.bold)),
                        ),
                      ],

                      const SizedBox(height: 40),
                      _buildActionSection(userInfo, isLoading, item),
                    ],
                  ),
                ),
              ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final bool isLast;

  const _InfoRow({required this.label, required this.value, this.isLast = false});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.textPrimary),
          ),
        ],
      ),
    );
  }
}

class _InfoDivider extends StatelessWidget {
  const _InfoDivider();

  @override
  Widget build(BuildContext context) {
    return Divider(height: 1, color: Colors.grey.shade200, indent: 16, endIndent: 16);
  }
}
