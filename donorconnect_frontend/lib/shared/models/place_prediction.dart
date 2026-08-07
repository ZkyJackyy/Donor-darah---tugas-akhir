class PlacePrediction {
  final String placeId;
  final String description;
  final String? name;
  final double? latitude;
  final double? longitude;

  PlacePrediction({
    required this.placeId,
    required this.description,
    this.name,
    this.latitude,
    this.longitude,
  });

  factory PlacePrediction.fromJson(Map<String, dynamic> json) {
    return PlacePrediction(
      placeId: json['place_id'],
      description: json['description'],
      name: json['name'],
      latitude: json['latitude'] != null ? double.tryParse(json['latitude'].toString()) : null,
      longitude: json['longitude'] != null ? double.tryParse(json['longitude'].toString()) : null,
    );
  }
}

class PlaceDetail {
  final String? name;
  final String? address;
  final double? latitude;
  final double? longitude;

  PlaceDetail({this.name, this.address, this.latitude, this.longitude});

  factory PlaceDetail.fromJson(Map<String, dynamic> json) {
    return PlaceDetail(
      name: json['name'],
      address: json['address'],
      latitude: json['latitude'] != null ? double.tryParse(json['latitude'].toString()) : null,
      longitude: json['longitude'] != null ? double.tryParse(json['longitude'].toString()) : null,
    );
  }
}
