import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';

class SkriningProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  bool _isLoading = false;
  String? _error;

  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<bool> submitScreening({
    required int donorCandidateId,
    required bool healthStatus,
    required bool minWeight,
    required bool noMedicine,
    required bool notPregnant,
  }) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.post(ApiConstants.screening, data: {
        'donor_candidate_id': donorCandidateId,
        'health_status': healthStatus,
        'min_weight': minWeight,
        'no_medicine': noMedicine,
        'not_pregnant': notPregnant,
      });

      if (response.data['status'] == true) {
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
      if (e.response?.statusCode == 422) {
        final errors = e.response?.data['errors'] as Map<String, dynamic>?;
        if (errors != null && errors.isNotEmpty) {
          _error = errors.values.first[0].toString();
        } else {
          _error = e.response?.data['message'] ?? 'Validation Error';
        }
      } else {
        _error = e.response?.data['message'] ?? e.message;
      }
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }
}
