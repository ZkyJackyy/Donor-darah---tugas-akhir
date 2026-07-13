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
  });
}
