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
  final String? role;
  final bool emailVerified;

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
    this.role,
    this.emailVerified = false,
  });

  bool get isAdmin => role == 'admin';

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
      role: json['role'],
      emailVerified: json['email_verified'] == true,
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
      'role': role,
      'email_verified': emailVerified,
    };
  }
}
