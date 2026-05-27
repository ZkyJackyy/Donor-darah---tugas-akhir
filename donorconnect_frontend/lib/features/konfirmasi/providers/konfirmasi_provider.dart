import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/api_error_handler.dart';

class KonfirmasiProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  bool _isLoading = false;
  String? _error;
  String? _qrToken;

  bool get isLoading => _isLoading;
  String? get error => _error;
  String? get qrToken => _qrToken;

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
          _qrToken = response.data['data']['qr_token'];
        }
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        _error = response.data['message'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  void resetQrToken() {
    _qrToken = null;
    notifyListeners();
  }
}
