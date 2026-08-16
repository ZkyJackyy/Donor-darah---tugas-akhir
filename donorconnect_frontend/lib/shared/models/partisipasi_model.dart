import 'package:intl/intl.dart';

class PartisipasiModel {
  final int id;
  final int bloodRequestId;
  final String hospitalName;
  final String? bloodType;
  final String? rhesus;
  final String status;
  final double? distanceKm;
  final DateTime? date;
  final String? verifiedBy;
  final String? rawDateLabel;

  PartisipasiModel({
    required this.id,
    required this.bloodRequestId,
    required this.hospitalName,
    this.bloodType,
    this.rhesus,
    required this.status,
    this.distanceKm,
    this.date,
    this.verifiedBy,
    this.rawDateLabel,
  });

  String get formattedDate {
    if (date != null) return DateFormat('dd MMMM yyyy, HH:mm', 'id_ID').format(date!);
    return rawDateLabel ?? '-';
  }

  factory PartisipasiModel.fromCandidateJson(Map<String, dynamic> json) {
    return PartisipasiModel(
      id: json['id'],
      bloodRequestId: json['blood_request_id'],
      hospitalName: json['hospital_name'] ?? '-',
      bloodType: json['blood_type'],
      rhesus: json['rhesus'],
      status: json['status'] ?? '-',
      distanceKm: json['distance_km'] != null ? double.tryParse(json['distance_km'].toString()) : null,
      date: json['created_at'] != null ? DateTime.tryParse(json['created_at']) : null,
    );
  }

  // Backend formats donor_date as 'd M Y' (e.g. "14 Aug 2026"), not ISO —
  // DateTime.tryParse can't read that, so parse with the matching pattern
  // and fall back to showing the raw label if it ever changes shape.
  factory PartisipasiModel.fromHistoryJson(Map<String, dynamic> json) {
    final rawDate = json['donor_date'] as String?;
    DateTime? parsedDate;
    if (rawDate != null) {
      try {
        parsedDate = DateFormat('d MMM y', 'en_US').parse(rawDate);
      } catch (e) {
        parsedDate = null;
      }
    }

    return PartisipasiModel(
      id: json['id'],
      bloodRequestId: json['blood_request_id'],
      hospitalName: json['hospital_name'] ?? '-',
      status: 'verified',
      verifiedBy: json['verified_by'],
      date: parsedDate,
      rawDateLabel: rawDate,
    );
  }
}
