class UserModel {
  final int id;
  final String name;
  final String email;
  final String? phone;
  final double? weight;
  final String? birthDate;
  final bool isAvailable;
  final String? golonganDarah;
  final String? rhesus;
  final String? tanggalDonorTerakhir;

  UserModel({
    required this.id,
    required this.name,
    required this.email,
    this.phone,
    this.weight,
    this.birthDate,
    this.isAvailable = true,
    this.golonganDarah,
    this.rhesus,
    this.tanggalDonorTerakhir,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      phone: json['phone'],
      weight: json['weight'] != null ? double.tryParse(json['weight'].toString()) : null,
      birthDate: json['birth_date'],
      isAvailable: json['is_available'] == true,
      golonganDarah: json['blood_type'],
      rhesus: json['rhesus'],
      tanggalDonorTerakhir: json['last_donor_date'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'phone': phone,
      'weight': weight,
      'birth_date': birthDate,
      'is_available': isAvailable,
      'blood_type': golonganDarah,
      'rhesus': rhesus,
      'last_donor_date': tanggalDonorTerakhir,
    };
  }
}
