import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/api_error_handler.dart';

class KonfirmasiProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  bool _isLoading = false;
  String? _error;
  String? _kodeVerifikasi;
  String? _hospitalName;
  DateTime? _expiresAt;

  bool get isLoading => _isLoading;
  String? get error => _error;
  String? get kodeVerifikasi => _kodeVerifikasi;
  String? get hospitalName => _hospitalName;
  DateTime? get expiresAt => _expiresAt;

  Future<bool> confirmDonor({
    required int donorCandidateId,
    required String status,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.post(ApiConstants.confirm, data: {
        'donor_candidate_id': donorCandidateId,
        'status': status,
      });

      if (response.data['status'] == true) {
        if (status == 'confirmed') {
          final data = response.data['data'];
          _kodeVerifikasi = data['kode_verifikasi'];
          _hospitalName = data['hospital_name'];
          _expiresAt = data['expires_at'] != null ? DateTime.parse(data['expires_at']).toLocal() : null;
        }
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
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Self-registration donor untuk permintaan tipe 'event' (donor darah
  /// terbuka) — langsung dapat tiket, tanpa melalui skrining/konfirmasi
  /// bertahap seperti permintaan darurat.
  Future<bool> joinEvent(int requestId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.post('/user/blood-requests/$requestId/join');

      if (response.data['status'] == true) {
        final data = response.data['data'];
        _kodeVerifikasi = data['kode_verifikasi'];
        _hospitalName = data['hospital_name'];
        _expiresAt = data['expires_at'] != null ? DateTime.parse(data['expires_at']).toLocal() : null;
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
      _isLoading = false;
      notifyListeners();
    }
  }

  void resetTicketData() {
    _kodeVerifikasi = null;
    _hospitalName = null;
    _expiresAt = null;
    notifyListeners();
  }
}
