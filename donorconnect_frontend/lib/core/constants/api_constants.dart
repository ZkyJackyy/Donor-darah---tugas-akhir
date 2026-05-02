class ApiConstants {
  // Use 10.0.2.2 for Android emulator, localhost for iOS simulator
  static const String baseUrl = 'http://10.152.164.246:8000/api';

  // Auth Endpoints
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String logout = '/auth/logout';

  // Profile
  static const String profile = '/profile';
  static const String updateLocation = '/location/update';

  // Blood Requests
  static const String bloodRequests = '/user/blood-requests';

  // Donor Actions
  static const String screening = '/donor/screening';
  static const String confirm = '/donor/confirm';
  static const String history = '/donor/history';
}
