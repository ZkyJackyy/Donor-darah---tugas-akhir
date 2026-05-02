import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/custom_button.dart';
import '../providers/permintaan_provider.dart';
import '../../konfirmasi/providers/konfirmasi_provider.dart';
import '../../skrining/screens/skrining_screen.dart';

class PermintaanDetailScreen extends StatefulWidget {
  final int requestId;
  // Using candidate id mock for now unless fetched from backend. 
  // Normally the backend would return a donor_candidate_id for the logged-in user 
  // for this specific blood request if they are eligible.
  final int donorCandidateId; 

  const PermintaanDetailScreen({
    super.key, 
    required this.requestId,
    this.donorCandidateId = 1, // Placeholder
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

  void _handleKonfirmasi(int donorCandidateId) async {
    // 1. Skrining Mandiri
    final isScreeningPassed = await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SkriningScreen(donorCandidateId: donorCandidateId),
      ),
    );

    if (isScreeningPassed == true) {
      if (!mounted) return;
      
      // 2. Call Confirm API
      final success = await context.read<KonfirmasiProvider>().confirmDonor(
        donorCandidateId: donorCandidateId,
        status: 'confirmed',
      );

      if (!mounted) return;

      if (success) {
        final qrToken = context.read<KonfirmasiProvider>().qrToken;
        if (qrToken != null) {
          context.push('/tiket', extra: qrToken);
        } else {
           ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Gagal mendapatkan tiket digital'), backgroundColor: AppColors.error),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(context.read<KonfirmasiProvider>().error ?? 'Gagal konfirmasi'),
            backgroundColor: AppColors.error,
          ),
        );
      }
    }
  }

  Future<void> _openMaps(double lat, double lng) async {
    final url = 'https://www.google.com/maps/search/?api=1&query=$lat,$lng';
    if (await canLaunchUrl(Uri.parse(url))) {
      await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
    }
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
                      if (userInfo?['is_candidate'] == true)
                        CustomButton(
                          text: userInfo?['status'] == 'confirmed' 
                              ? 'Lihat Tiket Digital' 
                              : 'Konfirmasi Kesediaan Donor',
                          onPressed: () {
                            if (userInfo?['status'] == 'confirmed') {
                              context.push('/tiket', extra: userInfo?['qr_token']);
                            } else {
                              _handleKonfirmasi(userInfo?['candidate_id']);
                            }
                          },
                          isLoading: isLoading,
                        )
                      else
                        Card(
                          color: Colors.amber.shade100,
                          child: const Padding(
                            padding: EdgeInsets.all(16.0),
                            child: Text(
                              'Maaf, Anda belum terdaftar sebagai kandidat pendonor untuk permintaan ini. Silakan tunggu notifikasi dari admin.',
                              textAlign: TextAlign.center,
                              style: TextStyle(fontWeight: FontWeight.bold),
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }
}
