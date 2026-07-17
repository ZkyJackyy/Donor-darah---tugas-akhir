import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import '../../../core/constants/app_colors.dart';
import '../../../shared/widgets/custom_button.dart';
import '../../../shared/models/ticket_data.dart';

class TiketDigitalScreen extends StatelessWidget {
  final TicketData ticket;

  const TiketDigitalScreen({super.key, required this.ticket});

  @override
  Widget build(BuildContext context) {
    final now = DateTime.now();
    final isExpired = ticket.expiresAt != null && now.isAfter(ticket.expiresAt!);
    final isInactive = ticket.isUsed || isExpired;
    final inactiveMessage = ticket.isUsed ? 'Tiket ini sudah digunakan' : 'QR Code sudah kadaluarsa';

    return Scaffold(
      backgroundColor: AppColors.primary,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () => context.go('/home'),
        ),
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.verified, color: Colors.white, size: 56),
              const SizedBox(height: 16),
              const Text(
                'Tiket Digital Donor',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                ticket.isUsed
                    ? 'Terima kasih, donasi Anda untuk permintaan ini sudah selesai.'
                    : 'Tunjukkan QR Code ini kepada petugas PMI saat Anda tiba di lokasi.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.white70, fontSize: 16),
              ),
              const SizedBox(height: 32),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(24),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: const [
                    BoxShadow(
                      color: Colors.black12,
                      blurRadius: 10,
                      offset: Offset(0, 5),
                    )
                  ],
                ),
                child: Column(
                  children: [
                    Opacity(
                      opacity: isInactive ? 0.3 : 1.0,
                      child: QrImageView(
                        data: ticket.qrToken,
                        version: QrVersions.auto,
                        size: 180.0,
                      ),
                    ),
                    if (isInactive) ...[
                      const SizedBox(height: 8),
                      Text(
                        inactiveMessage,
                        style: const TextStyle(color: AppColors.error, fontWeight: FontWeight.bold, fontSize: 12),
                      ),
                    ],
                    const SizedBox(height: 16),
                    const Divider(),
                    const SizedBox(height: 8),
                    _TicketRow(label: 'Nama Pendonor', value: ticket.namaPendonor),
                    _TicketRow(label: 'Golongan Darah', value: '${ticket.golonganDarah} ${ticket.rhesus}'),
                    _TicketRow(label: 'Tujuan', value: ticket.namaRS),
                    _TicketRow(label: 'Tanggal', value: ticket.tanggal),
                    const SizedBox(height: 12),
                    const Text(
                      'Kode Verifikasi Cadangan',
                      style: TextStyle(fontSize: 11, color: AppColors.textSecondary, fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: Colors.grey.shade300),
                      ),
                      child: Text(
                        ticket.kodeVerifikasi,
                        style: const TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 4,
                          color: AppColors.primaryDark,
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Sebutkan kode ini ke petugas jika QR tidak bisa dipindai',
                      textAlign: TextAlign.center,
                      style: TextStyle(fontSize: 10, color: AppColors.textSecondary),
                    ),
                    if (ticket.expiresAt != null && !isExpired) ...[
                      const SizedBox(height: 12),
                      Text(
                        'Berlaku hingga ${DateFormat('HH:mm', 'id_ID').format(ticket.expiresAt!)} WIB',
                        style: const TextStyle(fontSize: 11, color: AppColors.warning, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 48),
              CustomButton(
                text: 'Kembali ke Beranda',
                color: Colors.white,
                textColor: AppColors.primary,
                onPressed: () => context.go('/home'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _TicketRow extends StatelessWidget {
  final String label;
  final String value;

  const _TicketRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontSize: 13, color: AppColors.textSecondary)),
          Flexible(
            child: Text(
              value,
              textAlign: TextAlign.right,
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}
