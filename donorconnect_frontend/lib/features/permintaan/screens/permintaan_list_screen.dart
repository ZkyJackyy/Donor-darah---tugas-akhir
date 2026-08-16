import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../../../core/utils/session_cleanup.dart';
import '../../../shared/widgets/app_snackbar.dart';
import '../providers/permintaan_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../shared/models/blood_request_model.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../notifikasi/providers/notifikasi_provider.dart';

class PermintaanListScreen extends StatefulWidget {
  const PermintaanListScreen({super.key});

  @override
  State<PermintaanListScreen> createState() => _PermintaanListScreenState();
}

class _PermintaanListScreenState extends State<PermintaanListScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (context.read<PermintaanProvider>().permintaanList.isEmpty) {
        context.read<PermintaanProvider>().fetchPermintaanList();
      }
      if (context.read<AuthProvider>().user == null) {
        context.read<AuthProvider>().getProfile();
      }
      context.read<NotifikasiProvider>().fetchUnreadCount();
      _showLocationWarningIfAny();
    });
  }

  void _showLocationWarningIfAny() {
    final authProvider = context.read<AuthProvider>();
    final warning = authProvider.locationWarning;
    if (warning == null) return;

    authProvider.clearLocationWarning();
    if (!mounted) return;
    AppSnackbar.showError(context, warning);
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PermintaanProvider>();
    final user = context.watch<AuthProvider>().user;
    final unreadCount = context.watch<NotifikasiProvider>().unreadCount;

    // Calculate donor eligibility
    int daysRemaining = 0;
    double progress = 1.0;
    String nextDate = 'Siap Donor!';
    bool neverDonated = user?.tanggalDonorTerakhir == null;
    
    if (user?.tanggalDonorTerakhir != null) {
      final lastDate = DateTime.parse(user!.tanggalDonorTerakhir!);
      final eligibleDate = lastDate.add(const Duration(days: 56));
      nextDate = "${eligibleDate.day}/${eligibleDate.month}/${eligibleDate.year}";
      
      final now = DateTime.now();
      daysRemaining = eligibleDate.difference(now).inDays;
      if (daysRemaining < 0) daysRemaining = 0;
      
      // Progress from 0 (just donated) to 1 (can donate again)
      int daysPassed = now.difference(lastDate).inDays;
      progress = (daysPassed / 56).clamp(0.0, 1.0);
    }

    int getPriority(BloodRequestModel item) {
      if (item.userCandidateStatus == 'verified') {
        return 2;
      }
      bool isEligibleForUser = false;
      if (item.type == 'event') {
        isEligibleForUser = daysRemaining <= 0;
      } else if (user != null &&
          item.golonganDarah == user.golonganDarah &&
          item.rhesus == user.rhesus &&
          daysRemaining <= 0) {
         isEligibleForUser = true;
      }
      if (item.userCandidateStatus == 'notified' || isEligibleForUser) {
        return 0;
      }
      return 1;
    }

    List<BloodRequestModel> sortedList = List<BloodRequestModel>.from(provider.permintaanList);
    sortedList.sort((a, b) {
       int priorityA = getPriority(a);
       int priorityB = getPriority(b);
       if (priorityA != priorityB) {
          return priorityA.compareTo(priorityB);
       }
       return (a.distance ?? 9999).compareTo(b.distance ?? 9999);
    });

    final displayList = sortedList.take(5).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Sahabat Donor', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        elevation: 0,
        actions: [
          IconButton(
            icon: Stack(
              clipBehavior: Clip.none,
              children: [
                const Icon(Icons.notifications_outlined, color: Colors.white),
                if (unreadCount > 0)
                  Positioned(
                    right: -2,
                    top: -2,
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
                      decoration: const BoxDecoration(color: Colors.redAccent, shape: BoxShape.circle),
                      child: Text(
                        unreadCount > 9 ? '9+' : '$unreadCount',
                        style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
              ],
            ),
            onPressed: () async {
              await context.push('/notifikasi');
              if (!context.mounted) return;
              context.read<NotifikasiProvider>().fetchUnreadCount();
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout, color: Colors.white),
            onPressed: () async {
              await context.read<AuthProvider>().logout();
              if (!context.mounted) return;
              resetUserScopedProviders(context);
              context.go('/login');
            },
          )
        ],
      ),
      body: provider.isLoadingList
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () async {
                await context.read<PermintaanProvider>().fetchPermintaanList();
                await context.read<AuthProvider>().getProfile();
              },
              child: CustomScrollView(
                  slivers: [
                    // Dashboard Header
                    SliverToBoxAdapter(
                      child: Container(
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 30),
                        decoration: const BoxDecoration(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.only(
                            bottomLeft: Radius.circular(32),
                            bottomRight: Radius.circular(32),
                          ),
                        ),
                        child: Column(
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Flexible(
                                            child: Text(
                                              'Halo, ${user?.name ?? 'Pendonor'}!',
                                              style: const TextStyle(
                                                color: Colors.white, 
                                                fontSize: 20, 
                                                fontWeight: FontWeight.bold,
                                                overflow: TextOverflow.ellipsis
                                              ),
                                            ),
                                          ),
                                          const SizedBox(width: 4),
                                          IconButton(
                                            icon: const Icon(Icons.my_location, color: Colors.white70, size: 20),
                                            padding: EdgeInsets.zero,
                                            constraints: const BoxConstraints(),
                                            onPressed: () async {
                                              final authProvider = context.read<AuthProvider>();
                                              final permintaanProvider = context.read<PermintaanProvider>();
                                              final locationError = await authProvider.updateLocation();
                                              await permintaanProvider.fetchPermintaanList();
                                              await authProvider.getProfile();
                                              if (!mounted) return;
                                              if (locationError != null) {
                                                AppSnackbar.showError(context, locationError);
                                              } else {
                                                AppSnackbar.showSuccess(context, 'Data diperbarui...');
                                              }
                                            },
                                            tooltip: 'Refresh Lokasi & Status',
                                          ),
                                        ],
                                      ),
                                  const Text(
                                    'Siap menyelamatkan nyawa hari ini?',
                                    style: TextStyle(color: Colors.white70, fontSize: 14),
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 12),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              decoration: BoxDecoration(
                                color: Colors.white24,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                'Gol ${user?.golonganDarah ?? '-'}',
                                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 30),
                        // Circular Progress Card
                        Container(
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(24),
                            boxShadow: [
                              BoxShadow(color: Colors.black.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 5)),
                            ],
                          ),
                          child: Row(
                            children: [
                              Stack(
                                alignment: Alignment.center,
                                children: [
                                  SizedBox(
                                    width: 80,
                                    height: 80,
                                    child: CircularProgressIndicator(
                                      value: progress,
                                      strokeWidth: 8,
                                      backgroundColor: Colors.grey.shade200,
                                      valueColor: const AlwaysStoppedAnimation<Color>(AppColors.primary),
                                    ),
                                  ),
                                  Text(
                                    daysRemaining > 0 ? '$daysRemaining' : (neverDonated ? '!' : 'OK'),
                                    style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.primary),
                                  ),
                                ],
                              ),
                              const SizedBox(width: 20),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      neverDonated 
                                          ? 'Siap Mulai Donor?' 
                                          : (daysRemaining > 0 ? 'Menuju Donor Berikutnya' : 'Anda Bisa Mendonor!'),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      neverDonated
                                          ? 'Anda belum pernah mendonor sebelumnya. Yuk bantu sesama!'
                                          : (daysRemaining > 0 
                                              ? 'Tunggu $daysRemaining hari lagi untuk kembali mendonor.' 
                                              : 'Kondisi Anda sudah memenuhi syarat interval waktu.'),
                                      style: TextStyle(color: Colors.grey.shade600, fontSize: 12),
                                    ),
                                    const SizedBox(height: 8),
                                    Text(
                                      neverDonated ? 'Status: Siap Mendonor' : 'Estimasi: $nextDate',
                                      style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 12),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                
                // Quick Actions (Ajukan Permintaan Darah / Permintaan Saya)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                    child: Row(
                      children: [
                        Expanded(
                          child: _buildQuickActionCard(
                            context,
                            icon: Icons.add_circle_outline,
                            label: 'Ajukan Permintaan Darah',
                            onTap: () => context.push('/permintaan/ajukan'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildQuickActionCard(
                            context,
                            icon: Icons.assignment_outlined,
                            label: 'Permintaan Saya',
                            onTap: () => context.push('/permintaan/saya'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Section Title
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 10),
                    child: Wrap(
                      alignment: WrapAlignment.spaceBetween,
                      crossAxisAlignment: WrapCrossAlignment.center,
                      children: [
                        const Text(
                          'Permintaan Terdekat',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: AppColors.primaryDark),
                        ),
                        TextButton(
                          onPressed: () => context.push('/permintaan-all'),
                          child: const Text('Lihat Semua', style: TextStyle(color: AppColors.primary)),
                        ),
                      ],
                    ),
                  ),
                ),

                // List of requests (Limited to 5)
                provider.errorList != null
                    ? SliverToBoxAdapter(child: Center(child: Text(provider.errorList!)))
                    : SliverPadding(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        sliver: SliverList(
                          delegate: SliverChildBuilderDelegate(
                            (context, index) {
                              final item = displayList[index];
                              final priority = getPriority(item);
                              return Card(
                                clipBehavior: Clip.antiAlias,
                                margin: const EdgeInsets.only(bottom: 12),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                                elevation: 2,
                                child: Column(
                                  children: [
                                    if (priority == 0)
                                      Container(
                                        width: double.infinity,
                                        padding: const EdgeInsets.symmetric(vertical: 7),
                                        color: AppColors.primary.withValues(alpha: 0.08),
                                        child: Text(
                                          item.type == 'event' ? 'TERBUKA UNTUK SEMUA' : 'COCOK UNTUK ANDA',
                                          textAlign: TextAlign.center,
                                          style: const TextStyle(
                                            color: AppColors.primary,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 11,
                                            letterSpacing: 0.5,
                                          ),
                                        ),
                                      ),
                                    ListTile(
                                  contentPadding: const EdgeInsets.all(12),
                                  leading: Container(
                                    width: 50,
                                    height: 50,
                                    decoration: BoxDecoration(
                                      color: AppColors.primary.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Center(
                                      child: item.type == 'event'
                                          ? const Icon(Icons.event_available, color: AppColors.primary, size: 24)
                                          : Text(
                                              item.golonganDarah,
                                              style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 20),
                                            ),
                                    ),
                                  ),
                                  title: Row(
                                    children: [
                                      Expanded(
                                        child: Text(
                                          item.type == 'event' ? 'Event Donor Darah Terbuka' : 'Dibutuhkan ${item.jumlahKantong} Kantong',
                                          style: const TextStyle(fontWeight: FontWeight.bold),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      if (item.type != 'event' && item.urgencyLevel == 'critical') ...[
                                        const SizedBox(width: 6),
                                        const StatusBadge(label: 'KRITIS', color: AppColors.error),
                                      ] else if (item.type != 'event' && item.urgencyLevel == 'urgent') ...[
                                        const SizedBox(width: 6),
                                        const StatusBadge(label: 'MENDESAK', color: Colors.orange),
                                      ],
                                    ],
                                  ),
                                  subtitle: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const SizedBox(height: 4),
                                      Text(
                                        item.hospitalName ?? 'PMI Padang',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 6),
                                      Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Row(
                                            children: [
                                              const Icon(Icons.location_on, size: 14, color: AppColors.primary),
                                              const SizedBox(width: 4),
                                              Expanded(
                                                child: Text(
                                                  item.distance != null 
                                                      ? '${item.distance!.toStringAsFixed(1)} km dari Anda' 
                                                      : 'Jarak tidak diketahui',
                                                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.primary),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                            ],
                                          ),
                                          if (item.type == 'event' && item.eventStartsAt != null) ...[
                                            const SizedBox(height: 4),
                                            Row(
                                              children: [
                                                const Icon(Icons.event, size: 14, color: Colors.grey),
                                                const SizedBox(width: 4),
                                                Expanded(
                                                  child: Text(
                                                    'Mulai: ${item.eventStartsAtFormatted}',
                                                    style: const TextStyle(fontSize: 12, color: Colors.grey),
                                                    overflow: TextOverflow.ellipsis,
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                          const SizedBox(height: 4),
                                          Row(
                                            children: [
                                              const Icon(Icons.timer, size: 14, color: Colors.grey),
                                              const SizedBox(width: 4),
                                              Expanded(
                                                child: Text(
                                                  item.type == 'event' ? 'Selesai: ${item.batasWaktu}' : 'Batas: ${item.batasWaktu}',
                                                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                                                  overflow: TextOverflow.ellipsis,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                    trailing: const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                                    onTap: () => context.push('/permintaan/${item.id}'),
                                  ),
                                  if (priority == 2)
                                    Container(
                                      width: double.infinity,
                                      padding: const EdgeInsets.symmetric(vertical: 7),
                                      color: AppColors.success.withValues(alpha: 0.08),
                                      child: const Text(
                                        'SELESAI MELAKUKAN DONOR',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(
                                          color: AppColors.success,
                                          fontWeight: FontWeight.bold,
                                          fontSize: 11,
                                          letterSpacing: 0.5,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              );
                            },
                            childCount: displayList.length,
                          ),
                        ),
                      ),
                
                const SliverToBoxAdapter(child: SizedBox(height: 100)),
              ],
            ),
          ),
    );
  }

  Widget _buildQuickActionCard(
    BuildContext context, {
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return AspectRatio(
      aspectRatio: 1,
      child: Card(
        clipBehavior: Clip.antiAlias,
        elevation: 2,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        child: InkWell(
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, color: AppColors.primary, size: 24),
                ),
                const SizedBox(height: 10),
                Text(
                  label,
                  textAlign: TextAlign.center,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primaryDark,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
