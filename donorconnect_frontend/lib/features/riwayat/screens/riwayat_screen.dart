import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/models/partisipasi_model.dart';
import '../providers/riwayat_provider.dart';

class RiwayatScreen extends StatefulWidget {
  const RiwayatScreen({super.key});

  @override
  State<RiwayatScreen> createState() => _RiwayatScreenState();
}

class _RiwayatScreenState extends State<RiwayatScreen> with SingleTickerProviderStateMixin {
  late final TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final provider = context.read<RiwayatProvider>();
      if (provider.riwayatList.isEmpty) {
        provider.fetchRiwayatList();
      }
      if (provider.partisipasiList.isEmpty) {
        provider.fetchPartisipasiList();
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  (String, Color) _statusInfo(String status) {
    switch (status) {
      case 'notified':
        return ('Menunggu Respons', AppColors.warning);
      case 'pending':
        return ('Menunggu Respons', AppColors.warning);
      case 'screening_passed':
        return ('Lolos Skrining', AppColors.primary);
      case 'screening_failed':
        return ('Tidak Lolos Skrining', AppColors.error);
      case 'confirmed':
        return ('Dikonfirmasi', AppColors.primary);
      case 'declined':
        return ('Ditolak Donor', AppColors.textSecondary);
      case 'no_response':
        return ('Tidak Merespons', AppColors.textSecondary);
      case 'expired':
        return ('Kadaluarsa', AppColors.warning);
      case 'verified':
        return ('Terverifikasi', AppColors.success);
      default:
        return (status, AppColors.textSecondary);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('Riwayat Donor', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: AppColors.primary,
        iconTheme: const IconThemeData(color: Colors.white),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'Terverifikasi'),
            Tab(text: 'Semua Aktivitas'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          _buildVerifiedTab(),
          _buildPartisipasiTab(),
        ],
      ),
    );
  }

  Widget _buildVerifiedTab() {
    final provider = context.watch<RiwayatProvider>();

    return provider.isLoading
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: () => context.read<RiwayatProvider>().fetchRiwayatList(),
            child: provider.error != null
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [Center(child: Text(provider.error!))],
                  )
                : provider.riwayatList.isEmpty
                    ? ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: const [
                          SizedBox(height: 200),
                          Center(child: Text('Belum ada riwayat donor.')),
                        ],
                      )
                    : ListView.builder(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        itemCount: provider.riwayatList.length,
                        itemBuilder: (context, index) {
                          final item = provider.riwayatList[index];
                          return Card(
                            margin: const EdgeInsets.only(bottom: 12),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
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
                                          item.locationName,
                                          style: const TextStyle(
                                            fontWeight: FontWeight.bold,
                                            fontSize: 16,
                                          ),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: AppColors.success.withValues(alpha: 0.1),
                                          borderRadius: BorderRadius.circular(12),
                                          border: Border.all(color: AppColors.success),
                                        ),
                                        child: const Text(
                                          'VERIFIED',
                                          style: TextStyle(
                                            color: AppColors.success,
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 12),
                                  Row(
                                    children: [
                                      const Icon(Icons.calendar_today, size: 16, color: AppColors.textSecondary),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Text(
                                          item.donorDate,
                                          style: const TextStyle(color: AppColors.textSecondary),
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (item.verifierName != null) ...[
                                    const SizedBox(height: 8),
                                    Row(
                                      children: [
                                        const Icon(Icons.person, size: 16, color: AppColors.textSecondary),
                                        const SizedBox(width: 8),
                                        Flexible(
                                          child: Text(
                                            'Diverifikasi oleh: ${item.verifierName}',
                                            style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
                                            overflow: TextOverflow.ellipsis,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          );
                        },
                      ),
          );
  }

  Widget _buildPartisipasiTab() {
    final provider = context.watch<RiwayatProvider>();

    return provider.isLoadingPartisipasi
        ? const Center(child: CircularProgressIndicator())
        : RefreshIndicator(
            onRefresh: () => context.read<RiwayatProvider>().fetchPartisipasiList(),
            child: provider.partisipasiError != null
                ? ListView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [Center(child: Text(provider.partisipasiError!))],
                  )
                : provider.partisipasiList.isEmpty
                    ? ListView(
                        physics: const AlwaysScrollableScrollPhysics(),
                        children: const [
                          SizedBox(height: 200),
                          Center(child: Text('Belum ada aktivitas donor.')),
                        ],
                      )
                    : ListView.builder(
                        physics: const AlwaysScrollableScrollPhysics(),
                        padding: const EdgeInsets.all(16),
                        itemCount: provider.partisipasiList.length,
                        itemBuilder: (context, index) {
                          final item = provider.partisipasiList[index];
                          return _buildPartisipasiCard(item);
                        },
                      ),
          );
  }

  Widget _buildPartisipasiCard(PartisipasiModel item) {
    final (label, color) = _statusInfo(item.status);

    return Card(
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
                    item.hospitalName,
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: color),
                  ),
                  child: Text(
                    label,
                    style: TextStyle(color: color, fontSize: 10, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            if (item.bloodType != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.bloodtype_outlined, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 8),
                  Text(
                    '${item.bloodType} ${item.rhesus ?? ''}',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ],
            const SizedBox(height: 8),
            Row(
              children: [
                const Icon(Icons.calendar_today, size: 16, color: AppColors.textSecondary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    item.formattedDate,
                    style: const TextStyle(color: AppColors.textSecondary),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            if (item.distanceKm != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.social_distance_outlined, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 8),
                  Text(
                    '${item.distanceKm!.toStringAsFixed(1)} km',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ],
            if (item.verifiedBy != null) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  const Icon(Icons.person, size: 16, color: AppColors.textSecondary),
                  const SizedBox(width: 8),
                  Flexible(
                    child: Text(
                      'Diverifikasi oleh: ${item.verifiedBy}',
                      style: const TextStyle(color: AppColors.textSecondary, fontSize: 12),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),
            ],
          ],
        ),
      ),
    );
  }
}
