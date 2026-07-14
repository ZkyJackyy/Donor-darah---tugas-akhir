import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../shared/models/user_model.dart';
import '../../../core/services/location_service.dart';
import '../../../core/utils/api_error_handler.dart';

class AuthProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  final LocationService _locationService = LocationService();
  
  UserModel? _user;
  bool _isLoading = false;
  String? _error;
  String? _locationWarning;

  UserModel? get user => _user;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get isAuthenticated => _user != null;
  String? get locationWarning => _locationWarning;

  void clearLocationWarning() {
    _locationWarning = null;
  }

  Future<bool> login(String email, String password) async {
    _setLoading(true);
    try {
      final response = await _apiService.post(ApiConstants.login, data: {
        'email': email,
        'password': password,
      });

      if (response.data['status'] == true) {
        final token = response.data['data']['access_token'];
        _user = UserModel.fromJson(response.data['data']['user']);

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);

        // Update location after successful login
        updateLocation();

        return true;
      } else {
        _error = response.data['message'];
        return false;
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
      return false;
    } finally {
      _setLoading(false);
    }
  }

  Future<bool> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String phone,
    required String birthDate,
    required double weight,
    required String bloodType,
    required String rhesus,
  }) async {
    _setLoading(true);
    try {
      final response = await _apiService.post(ApiConstants.register, data: {
        'name': name,
        'email': email,
        'password': password,
        'password_confirmation': passwordConfirmation,
        'phone': phone,
        'birth_date': birthDate,
        'weight': weight,
        'blood_type': bloodType,
        'rhesus': rhesus,
      });

      if (response.data['status'] == true) {
        final token = response.data['data']['access_token'];
        _user = UserModel.fromJson(response.data['data']['user']);

        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('auth_token', token);

        // Update location after successful registration
        updateLocation();

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
      _setLoading(false);
    }
  }

  Future<String?> updateLocation() async {
    try {
      final position = await _locationService.getCurrentPosition();
      
      final response = await _apiService.put(ApiConstants.updateLocation, data: {
        'latitude': position.latitude,
        'longitude': position.longitude,
      });

      if (response.data['status'] == true) {
        _user = UserModel.fromJson(response.data['data']);
        _locationWarning = null;
        notifyListeners();
        return null; // Success
      } else {
        _locationWarning = response.data['message'] ?? 'Gagal memperbarui lokasi';
        notifyListeners();
        return _locationWarning;
      }
    } catch (e) {
      debugPrint("Update location error: $e");
      _locationWarning = 'Gagal memperbarui lokasi. Anda mungkin tidak muncul di pencarian pendonor.';
      notifyListeners();
      return _locationWarning;
    }
  }

  Future<void> logout() async {
    try {
      await _apiService.post(ApiConstants.logout);
    } catch (e) {
      debugPrint("Logout error: $e");
    } finally {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('auth_token');
      _user = null;
      notifyListeners();
    }
  }

  Future<bool> tryAutoLogin() async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('auth_token');
    
    if (token == null || token.isEmpty) {
      return false;
    }

    // Jika token ada, panggil getProfile() untuk memverifikasi apakah token masih valid
    final success = await getProfile();
    if (success) {
      // Jika valid, update lokasi di background
      updateLocation();
      return true;
    } else {
      // Jika tidak valid/kadaluarsa, hapus token
      await prefs.remove('auth_token');
      return false;
    }
  }

  Future<bool> getProfile() async {
    try {
      final response = await _apiService.get('/profile');
      if (response.data['status'] == true) {
        _user = UserModel.fromJson(response.data['data']);
        notifyListeners();
        return true;
      }
      return false;
    } catch (e) {
      debugPrint("Get profile error: $e");
      return false;
    }
  }

  Future<bool> updateProfile({
    String? name,
    String? phone,
    double? weight,
    String? birthDate,
    bool? isAvailable,
  }) async {
    _setLoading(true);
    try {
      final response = await _apiService.put('/profile/update', data: {
        if (name != null) 'name': name,
        if (phone != null) 'phone': phone,
        if (weight != null) 'weight': weight,
        if (birthDate != null) 'birth_date': birthDate,
        if (isAvailable != null) 'is_available': isAvailable,
      });

      if (response.data['status'] == true) {
        _user = UserModel.fromJson(response.data['data']);
        return true;
      } else {
        _error = response.data['message'];
        return false;
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
      return false;
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool value) {
    _isLoading = value;
    if (value) _error = null;
    notifyListeners();
  }
}
