import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../shared/models/donor_history_model.dart';
import '../../../shared/models/partisipasi_model.dart';
import '../../../core/utils/api_error_handler.dart';

class RiwayatProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<DonorHistoryModel> _riwayatList = [];
  bool _isLoading = false;
  String? _error;

  List<PartisipasiModel> _partisipasiList = [];
  bool _isLoadingPartisipasi = false;
  String? _partisipasiError;

  List<DonorHistoryModel> get riwayatList => _riwayatList;
  bool get isLoading => _isLoading;
  String? get error => _error;

  List<PartisipasiModel> get partisipasiList => _partisipasiList;
  bool get isLoadingPartisipasi => _isLoadingPartisipasi;
  String? get partisipasiError => _partisipasiError;

  void clear() {
    _riwayatList = [];
    _isLoading = false;
    _error = null;
    _partisipasiList = [];
    _isLoadingPartisipasi = false;
    _partisipasiError = null;
    notifyListeners();
  }

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

  Future<void> fetchPartisipasiList() async {
    _isLoadingPartisipasi = true;
    _partisipasiError = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConstants.bloodRequestsHistory);
      if (response.data['status'] == true) {
        final data = response.data['data'];
        final List candidates = data['candidates'] ?? [];
        final List histories = data['histories'] ?? [];

        final merged = [
          ...candidates.map((json) => PartisipasiModel.fromCandidateJson(json)),
          ...histories.map((json) => PartisipasiModel.fromHistoryJson(json)),
        ];
        merged.sort((a, b) {
          if (a.date == null && b.date == null) return 0;
          if (a.date == null) return 1;
          if (b.date == null) return -1;
          return b.date!.compareTo(a.date!);
        });

        _partisipasiList = merged;
      } else {
        _partisipasiError = response.data['message'];
      }
    } on DioException catch (e) {
      _partisipasiError = ApiErrorHandler.getMessage(e);
    } catch (e) {
      _partisipasiError = 'Terjadi kesalahan tidak terduga';
    } finally {
      _isLoadingPartisipasi = false;
      notifyListeners();
    }
  }
}
