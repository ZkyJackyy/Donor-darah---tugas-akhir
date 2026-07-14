import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../shared/models/blood_request_model.dart';
import '../../../core/utils/api_error_handler.dart';

class PermintaanProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<BloodRequestModel> _permintaanList = [];
  BloodRequestModel? _selectedPermintaan;
  Map<String, dynamic>? _userCandidateInfo;
  bool _isLoading = false;
  String? _error;

  List<BloodRequestModel> get permintaanList => _permintaanList;
  BloodRequestModel? get selectedPermintaan => _selectedPermintaan;
  Map<String, dynamic>? get userCandidateInfo => _userCandidateInfo;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchPermintaanList() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      Position? currentPosition;
      try {
        currentPosition = await _determinePosition();
      } catch (e) {
        debugPrint("Location error: $e");
      }

      final response = await _apiService.get(ApiConstants.bloodRequests);
      if (response.data['status'] == true) {
        final List data = response.data['data'];
        List<BloodRequestModel> list = data.map((json) => BloodRequestModel.fromJson(json)).toList();

        // Calculate distances and sort
        if (currentPosition != null) {
          for (var item in list) {
            if (item.latitude != 0.0) {
              double distanceInMeters = Geolocator.distanceBetween(
                currentPosition.latitude,
                currentPosition.longitude,
                item.latitude,
                item.longitude,
              );
              item.distance = distanceInMeters / 1000; // Convert to km
            }
          }
          // Sort by distance (nearest first)
          list.sort((a, b) => (a.distance ?? 9999).compareTo(b.distance ?? 9999));
        }

        _permintaanList = list;
      } else {
        _error = response.data['message'];
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<Position> _determinePosition() async {
    bool serviceEnabled;
    LocationPermission permission;

    serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) return Future.error('Location services are disabled.');

    permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) return Future.error('Location permissions are denied');
    }
    
    if (permission == LocationPermission.deniedForever) {
      return Future.error('Location permissions are permanently denied.');
    } 

    try {
      return await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.medium,
          timeLimit: Duration(seconds: 15),
        ),
      );
    } catch (e) {
      // Fallback ke lokasi terakhir yang diketahui jika GPS gagal/timeout
      final lastPosition = await Geolocator.getLastKnownPosition();
      if (lastPosition != null) return lastPosition;
      rethrow;
    }
  }

  Future<void> fetchPermintaanDetail(int id) async {
    _isLoading = true;
    _error = null;
    _selectedPermintaan = null;
    _userCandidateInfo = null;
    notifyListeners();

    try {
      final response = await _apiService.get('${ApiConstants.bloodRequests}/$id');
      if (response.data['status'] == true) {
        final data = response.data['data'];
        _selectedPermintaan = BloodRequestModel.fromJson(data);
        _userCandidateInfo = data['user_candidate_info'];
      } else {
        _error = response.data['message'];
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }
}
