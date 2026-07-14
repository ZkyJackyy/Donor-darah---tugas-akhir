import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../shared/models/donor_history_model.dart';
import '../../../core/utils/api_error_handler.dart';

class RiwayatProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();
  
  List<DonorHistoryModel> _riwayatList = [];
  bool _isLoading = false;
  String? _error;

  List<DonorHistoryModel> get riwayatList => _riwayatList;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchRiwayatList() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConstants.history);
      if (response.data['status'] == true) {
        final List data = response.data['data'];
        _riwayatList = data.map((json) => DonorHistoryModel.fromJson(json)).toList();
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
