import 'package:flutter/foundation.dart';
import 'package:dio/dio.dart';
import '../../../core/constants/api_constants.dart';
import '../../../core/services/api_service.dart';
import '../../../core/utils/api_error_handler.dart';

class NotifikasiModel {
  final int id;
  final String phone;
  final String message;
  final String status;
  final String? errorMessage;
  final DateTime createdAt;

  NotifikasiModel({
    required this.id,
    required this.phone,
    required this.message,
    required this.status,
    this.errorMessage,
    required this.createdAt,
  });

  factory NotifikasiModel.fromJson(Map<String, dynamic> json) {
    return NotifikasiModel(
      id: json['id'],
      phone: json['phone'] ?? '',
      message: json['message'] ?? '',
      status: json['status'] ?? 'pending',
      errorMessage: json['error_message'],
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class NotifikasiProvider with ChangeNotifier {
  final ApiService _apiService = ApiService();

  List<NotifikasiModel> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _error;

  List<NotifikasiModel> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchNotifications() async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _apiService.get(ApiConstants.notifications);
      if (response.data['status'] == true) {
        final data = response.data['data'];
        final List items = data is Map ? (data['data'] ?? []) : data;
        _notifications = items.map((json) => NotifikasiModel.fromJson(json)).toList();
      } else {
        _error = response.data['message'];
      }
    } on DioException catch (e) {
      _error = ApiErrorHandler.getMessage(e);
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  Future<void> fetchUnreadCount() async {
    try {
      final response = await _apiService.get(ApiConstants.notificationsUnreadCount);
      if (response.data['status'] == true) {
        _unreadCount = response.data['data']['count'] ?? 0;
        notifyListeners();
      }
    } on DioException catch (_) {}
  }
}
