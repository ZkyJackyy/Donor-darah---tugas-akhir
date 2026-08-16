import 'dart:io';
import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import 'package:geolocator/geolocator.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../shared/models/blood_request_model.dart';
import '../../../shared/models/place_prediction.dart';
import '../../../core/utils/api_error_handler.dart';

class PermintaanProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<BloodRequestModel> _permintaanList = [];
  BloodRequestModel? _selectedPermintaan;
  Map<String, dynamic>? _userCandidateInfo;
  List<BloodRequestModel> _mySubmissions = [];

  // Each fetch keeps its own loading/error flag — sharing one pair across
  // fetchPermintaanList/fetchPermintaanDetail/fetchMySubmissions meant an
  // error on one screen (e.g. opening a detail while offline) overwrote the
  // state every other screen reads, replacing an unrelated list with an
  // error message until a manual refresh.
  bool _isLoadingList = false;
  String? _errorList;
  bool _isLoadingDetail = false;
  String? _errorDetail;
  bool _isLoadingMySubmissions = false;
  String? _errorMySubmissions;
  bool _isSubmitting = false;
  String? _submitError;

  List<BloodRequestModel> get permintaanList => _permintaanList;
  BloodRequestModel? get selectedPermintaan => _selectedPermintaan;
  Map<String, dynamic>? get userCandidateInfo => _userCandidateInfo;
  List<BloodRequestModel> get mySubmissions => _mySubmissions;

  bool get isLoadingList => _isLoadingList;
  String? get errorList => _errorList;
  bool get isLoadingDetail => _isLoadingDetail;
  String? get errorDetail => _errorDetail;
  bool get isLoadingMySubmissions => _isLoadingMySubmissions;
  String? get errorMySubmissions => _errorMySubmissions;
  bool get isSubmitting => _isSubmitting;
  String? get submitError => _submitError;

  Future<void> fetchPermintaanList() async {
    _isLoadingList = true;
    _errorList = null;
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
        _errorList = response.data['message'];
      }
    } on DioException catch (e) {
      _errorList = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _errorList = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoadingList = false;
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
    _isLoadingList = false;
    _errorList = null;
    _isLoadingDetail = false;
    _errorDetail = null;
    _isLoadingMySubmissions = false;
    _errorMySubmissions = null;
    _isSubmitting = false;
    _submitError = null;
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
    required File referralLetter,
    String? notes,
    double? latitude,
    double? longitude,
  }) async {
    _isSubmitting = true;
    _submitError = null;
    notifyListeners();

    try {
      final response = await _apiService.postMultipart(
        ApiConstants.bloodRequests,
        fieldName: 'referral_letter',
        file: referralLetter,
        data: {
          'blood_type': bloodType,
          'rhesus': rhesus,
          'required_bags': requiredBags.toString(),
          'patient_name': patientName,
          'patient_relationship': patientRelationship,
          'hospital_name': hospitalName,
          'hospital_address': hospitalAddress,
          'urgency_level': urgencyLevel,
          'deadline': deadline,
          if (notes != null && notes.isNotEmpty) 'notes': notes,
          if (latitude != null) 'latitude': latitude.toString(),
          if (longitude != null) 'longitude': longitude.toString(),
        },
      );

      if (response.data['status'] == true) {
        return true;
      } else {
        _submitError = response.data['message'];
        return false;
      }
    } on DioException catch (e) {
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.isNotEmpty) {
          _submitError = errors.values.first[0].toString();
        } else {
          _submitError = ApiErrorHandler.getMessage(e);
        }
      } else {
        _submitError = ApiErrorHandler.getMessage(e);
      }
      return false;
    } catch (e) {
      _submitError = 'Terjadi kesalahan tidak terduga';
      return false;
    } finally {
      _isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> fetchMySubmissions() async {
    _isLoadingMySubmissions = true;
    _errorMySubmissions = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConstants.bloodRequestsMySubmissions);
      if (response.data['status'] == true) {
        final List data = response.data['data'];
        _mySubmissions = data.map((json) => BloodRequestModel.fromJson(json)).toList();
      } else {
        _errorMySubmissions = response.data['message'];
      }
    } on DioException catch (e) {
      _errorMySubmissions = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _errorMySubmissions = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoadingMySubmissions = false;
      notifyListeners();
    }
  }

  Future<List<PlacePrediction>> searchHospitalPlaces(String query) async {
    if (query.trim().length < 3) return [];

    try {
      final response = await _apiService.get(
        ApiConstants.placesAutocomplete,
        queryParameters: {'input': query.trim()},
      );
      if (response.data['status'] == true) {
        final List data = response.data['data'];
        return data.map((json) => PlacePrediction.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  Future<PlaceDetail?> reverseGeocode(double latitude, double longitude) async {
    try {
      final response = await _apiService.get(
        ApiConstants.placesReverseGeocode,
        queryParameters: {'latitude': latitude, 'longitude': longitude},
      );
      if (response.data['status'] == true) {
        return PlaceDetail.fromJson(response.data['data']);
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  Future<void> fetchPermintaanDetail(int id) async {
    _isLoadingDetail = true;
    _errorDetail = null;
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
        _errorDetail = response.data['message'];
      }
    } on DioException catch (e) {
      _errorDetail = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _errorDetail = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoadingDetail = false;
      notifyListeners();
    }
  }
}
