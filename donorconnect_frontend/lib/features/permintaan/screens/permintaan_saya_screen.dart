import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/status_badge.dart';
import '../providers/permintaan_provider.dart';
import '../../../shared/models/blood_request_model.dart';

class PermintaanSayaScreen extends StatefulWidget {
  const PermintaanSayaScreen({super.key});

  @override
  State<PermintaanSayaScreen> createState() => _PermintaanSayaScreenState();
}

class _PermintaanSayaScreenState extends State<PermintaanSayaScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<PermintaanProvider>().fetchMySubmissions();
    });
  }

  (String, Color) _statusInfo(String? status) {
    switch (status) {
      case 'pending_review':
        return ('Menunggu Persetujuan', AppColors.warning);
      case 'open':
        return ('Sedang Dicari Pendonor', AppColors.primary);
      case 'fulfilled':
        return ('Terpenuhi', AppColors.success);
      case 'rejected':
        return ('Ditolak', AppColors.error);
      case 'cancelled':
        return ('Dibatalkan', AppColors.textSecondary);
      default:
        return ('-', AppColors.textSecondary);
    }
  }

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<PermintaanProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Permintaan Saya',
            style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        automaticallyImplyLeading: true,
      ),
      body: RefreshIndicator(
        onRefresh: () => context.read<PermintaanProvider>().fetchMySubmissions(),
        child: provider.isLoading && provider.mySubmissions.isEmpty
            ? const Center(child: CircularProgressIndicator())
            : provider.mySubmissions.isEmpty
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: const [
                      SizedBox(height: 120),
                      Center(
                        child: Text(
                          'Belum ada pengajuan permintaan.',
                          style: TextStyle(color: AppColors.textSecondary),
                        ),
                      ),
                    ],
                  )
                : ListView.builder(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    itemCount: provider.mySubmissions.length,
                    itemBuilder: (context, index) {
                      final item = provider.mySubmissions[index];
                      return _buildCard(item);
                    },
                  ),
      ),
    );
  }

  Widget _buildCard(BloodRequestModel item) {
    final (label, color) = _statusInfo(item.status);

    return Card(
      color: Colors.white,
      elevation: 2,
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    item.patientName?.isNotEmpty == true
                        ? '${item.patientName} (${item.patientRelationship ?? '-'})'
                        : 'Pasien',
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold),
                  ),
                ),
                StatusBadge(label: label, color: color),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.bloodtype_outlined, size: 16, color: AppColors.textSecondary),
                const SizedBox(width: 6),
                Text(
                  '${item.golonganDarah} ${item.rhesus} • ${item.jumlahKantong} kantong',
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                ),
              ],
            ),
            if (item.hospitalName != null && item.hospitalName!.isNotEmpty) ...[
              const SizedBox(height: 4),
              Row(
                children: [
                  const Icon(Icons.local_hospital_outlined, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      item.hospitalName!,
                      style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                    ),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 4),
            Row(
              children: [
                const Icon(Icons.access_time, size: 16, color: AppColors.textSecondary),
                const SizedBox(width: 6),
                Text(
                  item.batasWaktu,
                  style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                ),
              ],
            ),
            if (item.status == 'rejected' && item.rejectionReason != null && item.rejectionReason!.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppColors.error.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Alasan ditolak: ${item.rejectionReason}',
                  style: const TextStyle(fontSize: 12, color: AppColors.error),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
