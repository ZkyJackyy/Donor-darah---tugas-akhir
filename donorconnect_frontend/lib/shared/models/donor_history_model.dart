import 'package:intl/intl.dart';

class DonorHistoryModel {
  final int id;
  final String donorDate;
  final String locationName;
  final String? verifierName;

  DonorHistoryModel({
    required this.id,
    required this.donorDate,
    required this.locationName,
    this.verifierName,
  });

  static String _formatDate(String? raw) {
    if (raw == null || raw.isEmpty) return '-';
    try {
      final dt = DateTime.parse(raw);
      return DateFormat('dd MMMM yyyy', 'id_ID').format(dt);
    } catch (e) {
      return raw;
    }
  }

  factory DonorHistoryModel.fromJson(Map<String, dynamic> json) {
    return DonorHistoryModel(
      id: json['id'],
      donorDate: _formatDate(json['donor_date']),
      locationName: json['location_name'] ?? '',
      verifierName: json['verified_by']?['name'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'donor_date': donorDate,
      'location_name': locationName,
      'verified_by': {'name': verifierName},
    };
  }
}
