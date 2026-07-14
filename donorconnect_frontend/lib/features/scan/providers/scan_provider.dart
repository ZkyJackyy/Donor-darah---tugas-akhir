import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/api_error_handler.dart';

class ScanProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  bool _isLoading = false;
  String? _error;
  String? _resultMessage;

  bool get isLoading => _isLoading;
  String? get error => _error;
  String? get resultMessage => _resultMessage;

  /// Verifies a scanned QR token or a manually-typed verification code
  /// (admin/petugas only — backend rejects non-admin callers).
  /// QR tokens carry a `signature|payload` shape; anything else is treated
  /// as a manual `kode_verifikasi`.
  Future<bool> verify(String rawValue) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final isQrToken = rawValue.contains('|');
      final response = isQrToken
          ? await _apiService.post(ApiConstants.verifyQr, data: {'token': rawValue})
          : await _apiService.post(ApiConstants.verifyKode, data: {'kode_verifikasi': rawValue});

      final success = response.data['status'] == true;
      if (success) {
        _resultMessage = response.data['message'] as String? ?? 'Verifikasi berhasil';
      } else {
        _error = response.data['message'] as String? ?? 'Verifikasi gagal';
      }
      _isLoading = false;
      notifyListeners();
      return success;
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
      _isLoading = false;
      notifyListeners();
      return false;
    } catch (e) {
      _error = 'Terjadi kesalahan tidak terduga';
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
}
