import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:go_router/go_router.dart';
import '../../../core/constants/app_colors.dart';
import '../providers/permintaan_provider.dart';
import '../../auth/providers/auth_provider.dart';
import '../../../shared/models/blood_request_model.dart';
import '../../../shared/widgets/status_badge.dart';

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
      bool isDueToDonate() {
        if (user == null || user.tanggalDonorTerakhir == null) return true;
        final lastDate = DateTime.parse(user.tanggalDonorTerakhir!);
        final eligibleDate = lastDate.add(const Duration(days: 56));
        return eligibleDate.difference(DateTime.now()).inDays <= 0;
      }

      bool isEligibleForUser = false;
      if (item.type == 'event') {
        isEligibleForUser = isDueToDonate();
      } else if (user != null &&
          item.golonganDarah == user.golonganDarah &&
          item.rhesus == user.rhesus) {
        isEligibleForUser = isDueToDonate();
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
      body: provider.isLoadingList
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: () => context.read<PermintaanProvider>().fetchPermintaanList(),
              child: provider.permintaanList.isEmpty
                  ? ListView(
                      physics: const AlwaysScrollableScrollPhysics(),
                      children: const [
                        SizedBox(height: 200),
                        Center(child: Text('Tidak ada permintaan saat ini')),
                      ],
                    )
                  : ListView.builder(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(16),
                  itemCount: sortedList.length,
                  itemBuilder: (context, index) {
                    final item = sortedList[index];
                    final priority = getPriority(item);
                    final isDone = priority == 2;

                    final card = Card(
                      clipBehavior: Clip.antiAlias,
                      margin: const EdgeInsets.only(bottom: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      elevation: isDone ? 0 : 2,
                      color: isDone ? AppColors.background : null,
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
                            color: isDone
                                ? AppColors.success.withValues(alpha: 0.1)
                                : AppColors.primary.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Center(
                            child: isDone
                                ? const Icon(Icons.check_circle, color: AppColors.success, size: 26)
                                : item.type == 'event'
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
                            if (!isDone && item.type != 'event' && item.urgencyLevel == 'critical') ...[
                              const SizedBox(width: 6),
                              const StatusBadge(label: 'KRITIS', color: AppColors.error),
                            ] else if (!isDone && item.type != 'event' && item.urgencyLevel == 'urgent') ...[
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
                            if (isDone)
                              Row(
                                children: [
                                  const Icon(Icons.event_available, size: 14, color: AppColors.textSecondary),
                                  const SizedBox(width: 4),
                                  Expanded(
                                    child: Text(
                                      'Donor selesai • ${item.verifiedAtFormatted}',
                                      style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  const StatusBadge(label: 'SELESAI'),
                                ],
                              )
                            else
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
                      ],
                    ),
                  );

                    return isDone ? Opacity(opacity: 0.7, child: card) : card;
                },
                ),
            ),
    );
  }
}
