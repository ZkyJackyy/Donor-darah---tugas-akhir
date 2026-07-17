import '../../core/utils/date_formatter.dart';

class TicketData {
  final String namaPendonor;
  final String golonganDarah;
  final String rhesus;
  final double? beratBadan;
  final String namaRS;
  final int requestId;
  final String qrToken;
  final String kodeVerifikasi;
  final String tanggal;
  final DateTime? expiresAt;
  final bool isUsed;

  TicketData({
    required this.namaPendonor,
    required this.golonganDarah,
    required this.rhesus,
    this.beratBadan,
    required this.namaRS,
    required this.requestId,
    required this.qrToken,
    required this.kodeVerifikasi,
    required this.tanggal,
    this.expiresAt,
    this.isUsed = false,
  });

  /// Tiket yang baru saja dibuat lewat alur konfirmasi kehadiran (KonfirmasiProvider).
  factory TicketData.fromConfirmResult({
    required String? donorName,
    required String? golonganDarah,
    required String? rhesus,
    required String? hospitalName,
    required int requestId,
    required String? qrToken,
    required String? kodeVerifikasi,
    required DateTime? expiresAt,
  }) {
    return TicketData(
      namaPendonor: donorName ?? '-',
      golonganDarah: golonganDarah ?? '-',
      rhesus: rhesus ?? '-',
      namaRS: hospitalName ?? '-',
      requestId: requestId,
      qrToken: qrToken ?? '',
      kodeVerifikasi: kodeVerifikasi ?? '-',
      tanggal: formatIndonesianDate(DateTime.now().toIso8601String()),
      expiresAt: expiresAt,
    );
  }

  /// Tiket yang dilihat ulang dari `user_candidate_info` (mis. saat membuka detail
  /// permintaan yang sudah confirmed/verified sebelumnya).
  factory TicketData.fromCandidateInfo({
    required Map<String, dynamic> userInfo,
    required String? donorName,
    required String golonganDarah,
    required String rhesus,
    required String? hospitalName,
    required int requestId,
    required String dateField,
    bool includeExpiry = false,
    bool isUsed = false,
  }) {
    // Backend tidak mengirim ulang expiry saat re-fetch, jadi dihitung ulang di sini.
    // Harus tetap sinkron dengan config('donorconnect.qr.expiry_minutes') di backend.
    DateTime? expiresAt;
    if (includeExpiry) {
      final confirmedAtRaw = userInfo['confirmed_at'] as String?;
      if (confirmedAtRaw != null) {
        try {
          expiresAt = DateTime.parse(confirmedAtRaw).toLocal().add(const Duration(hours: 2));
        } catch (_) {}
      }
    }

    return TicketData(
      namaPendonor: donorName ?? '-',
      golonganDarah: golonganDarah,
      rhesus: rhesus,
      namaRS: hospitalName ?? '-',
      requestId: requestId,
      qrToken: userInfo['qr_token'] ?? '',
      kodeVerifikasi: userInfo['kode_verifikasi'] ?? '-',
      tanggal: formatIndonesianDate(userInfo[dateField] as String?),
      expiresAt: expiresAt,
      isUsed: isUsed,
    );
  }
}
