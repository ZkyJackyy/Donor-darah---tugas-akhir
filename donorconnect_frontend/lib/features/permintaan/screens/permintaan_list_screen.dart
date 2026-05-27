import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../providers/permintaan_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../shared/models/blood_request_model.dart';

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
      context.read<PermintaanProvider>().fetchPermintaanList();
      context.read<AuthProvider>().getProfile();
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PermintaanProvider>();
    final user = context.watch<AuthProvider>().user;

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
      if (item.userCandidateStatus == 'verified' || item.userCandidateStatus == 'completed') {
        return 2;
      }
      bool isEligibleForUser = false;
      if (user != null && item.golonganDarah == user.golonganDarah && daysRemaining <= 0) {
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
        title: const Text('DonorConnect', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        elevation: 0,
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
      body: provider.isLoading
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
                                              await context.read<PermintaanProvider>().fetchPermintaanList();
                                              await context.read<AuthProvider>().getProfile();
                                              if (mounted) {
                                                ScaffoldMessenger.of(context).showSnackBar(
                                                  const SnackBar(
                                                    content: Text('Data diperbarui...'),
                                                    duration: Duration(seconds: 1),
                                                  ),
                                                );
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
                
                // Section Title
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 30, 20, 10),
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
                provider.error != null
                    ? SliverToBoxAdapter(child: Center(child: Text(provider.error!)))
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
                                        padding: const EdgeInsets.symmetric(vertical: 6),
                                        color: Colors.red.shade50,
                                        child: const Text(
                                          '🌟 Cocok Untuk Anda!',
                                          textAlign: TextAlign.center,
                                          style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 12),
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
                                      child: Text(
                                        item.golonganDarah,
                                        style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 20),
                                      ),
                                    ),
                                  ),
                                  title: Text(
                                    'Dibutuhkan ${item.jumlahKantong} Kantong',
                                    style: const TextStyle(fontWeight: FontWeight.bold),
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
                                          const SizedBox(height: 4),
                                          Row(
                                            children: [
                                              const Icon(Icons.timer, size: 14, color: Colors.grey),
                                              const SizedBox(width: 4),
                                              Expanded(
                                                child: Text(
                                                  'Batas: ${item.batasWaktu}',
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
                                      padding: const EdgeInsets.symmetric(vertical: 6),
                                      color: Colors.green.shade50,
                                      child: const Text(
                                        '✅ Selesai Melakukan Donor',
                                        textAlign: TextAlign.center,
                                        style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold, fontSize: 12),
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
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 0,
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textSecondary,
        onTap: (index) {
          if (index == 1) context.go('/permintaan-all');
          if (index == 2) context.go('/riwayat');
          if (index == 3) context.go('/profile');
        },
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Beranda',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.list_alt),
            label: 'Permintaan',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.history),
            label: 'Riwayat',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person),
            label: 'Profil',
          ),
        ],
      ),
    );
  }
}
