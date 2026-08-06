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
  List<BloodRequestModel> _mySubmissions = [];
  bool _isLoading = false;
  bool _isSubmitting = false;
  String? _error;

  List<BloodRequestModel> get permintaanList => _permintaanList;
  BloodRequestModel? get selectedPermintaan => _selectedPermintaan;
  Map<String, dynamic>? get userCandidateInfo => _userCandidateInfo;
  List<BloodRequestModel> get mySubmissions => _mySubmissions;
  bool get isLoading => _isLoading;
  bool get isSubmitting => _isSubmitting;
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

  void clear() {
    _permintaanList = [];
    _selectedPermintaan = null;
    _userCandidateInfo = null;
    _mySubmissions = [];
    _isLoading = false;
    _isSubmitting = false;
    _error = null;
    notifyListeners();
  }

  Future<bool> submitPermintaan({
    required String bloodType,
    required String rhesus,
    required int requiredBags,
    required String patientName,
    required String patientRelationship,
    required String hospitalName,
    required String hospitalAddress,
    required String urgencyLevel,
    required String deadline,
    String? notes,
  }) async {
    _isSubmitting = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.post(ApiConstants.bloodRequests, data: {
        'blood_type': bloodType,
        'rhesus': rhesus,
        'required_bags': requiredBags,
        'patient_name': patientName,
        'patient_relationship': patientRelationship,
        'hospital_name': hospitalName,
        'hospital_address': hospitalAddress,
        'urgency_level': urgencyLevel,
        'deadline': deadline,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      });

      if (response.data['status'] == true) {
        return true;
      } else {
        _error = response.data['message'];
        return false;
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.isNotEmpty) {
          _error = errors.values.first[0].toString();
        } else {
          _error = ApiErrorHandler.getMessage(e);
        }
      } else {
        _error = ApiErrorHandler.getMessage(e);
      }
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
      return false;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> fetchMySubmissions() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConstants.bloodRequestsMySubmissions);
      if (response.data['status'] == true) {
        final List data = response.data['data'];
        _mySubmissions = data.map((json) => BloodRequestModel.fromJson(json)).toList();
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
