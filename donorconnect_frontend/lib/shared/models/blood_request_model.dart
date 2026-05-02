class BloodRequestModel {
  final int id;
  final String golonganDarah;
  final String rhesus;
  final int jumlahKantong;
  final String batasWaktu;
  final int? jumlahTerkonfirmasi;
  final String? urgencyLevel;
  final String? hospitalName;
  final double latitude;
  final double longitude;
  double? distance;

  BloodRequestModel({
    required this.id,
    required this.golonganDarah,
    required this.rhesus,
    required this.jumlahKantong,
    required this.batasWaktu,
    this.jumlahTerkonfirmasi,
    this.urgencyLevel,
    this.hospitalName,
    this.latitude = 0.0,
    this.longitude = 0.0,
    this.distance,
  });

  factory BloodRequestModel.fromJson(Map<String, dynamic> json) {
    return BloodRequestModel(
      id: json['id'],
      golonganDarah: json['blood_type'] ?? '',
      rhesus: json['rhesus'] ?? '',
      jumlahKantong: json['required_bags'] ?? 0,
      batasWaktu: json['deadline'] ?? json['created_at'] ?? '',
      jumlahTerkonfirmasi: json['confirmed_donors_count'],
      urgencyLevel: json['urgency_level'],
      hospitalName: json['hospital_name'],
      latitude: json['latitude'] != null ? double.parse(json['latitude'].toString()) : 0.0,
      longitude: json['longitude'] != null ? double.parse(json['longitude'].toString()) : 0.0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'blood_type': golonganDarah,
      'rhesus': rhesus,
      'required_bags': jumlahKantong,
      'deadline': batasWaktu,
      'confirmed_donors_count': jumlahTerkonfirmasi,
      'urgency_level': urgencyLevel,
      'hospital_name': hospitalName,
      'latitude': latitude,
      'longitude': longitude,
    };
  }
}
