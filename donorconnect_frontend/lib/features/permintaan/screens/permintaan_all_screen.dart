import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../providers/permintaan_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../shared/models/blood_request_model.dart';

class PermintaanAllScreen extends StatefulWidget {
  const PermintaanAllScreen({super.key});

  @override
  State<PermintaanAllScreen> createState() => _PermintaanAllScreenState();
}

class _PermintaanAllScreenState extends State<PermintaanAllScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      // Hanya fetch jika list masih kosong untuk hindari double fetch
      if (context.read<PermintaanProvider>().permintaanList.isEmpty) {
        context.read<PermintaanProvider>().fetchPermintaanList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PermintaanProvider>();
    final user = context.watch<AuthProvider>().user;

    int getPriority(BloodRequestModel item) {
      if (item.userCandidateStatus == 'verified') {
        return 2;
      }
      bool isEligibleForUser = false;
      if (user != null && item.golonganDarah == user.golonganDarah) {
         if (user.tanggalDonorTerakhir == null) {
            isEligibleForUser = true;
         } else {
            final lastDate = DateTime.parse(user.tanggalDonorTerakhir!);
            final eligibleDate = lastDate.add(const Duration(days: 56));
            if (eligibleDate.difference(DateTime.now()).inDays <= 0) {
               isEligibleForUser = true;
            }
         }
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

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Semua Permintaan', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        elevation: 0,
      ),
      body: provider.isLoading
          ? const Center(child: CircularProgressIndicator())
          : provider.permintaanList.isEmpty
              ? const Center(child: Text('Tidak ada permintaan saat ini'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: sortedList.length,
                  itemBuilder: (context, index) {
                    final item = sortedList[index];
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
                ),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: 1,
        type: BottomNavigationBarType.fixed,
        selectedItemColor: AppColors.primary,
        unselectedItemColor: AppColors.textSecondary,
        onTap: (index) {
          if (index == 0) context.go('/home');
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
