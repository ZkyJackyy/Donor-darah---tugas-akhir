class ApiConstants {
  // Use --dart-define=API_BASE_URL=http://YOUR_IP:8000/api when running
  // Defaults to Android emulator loopback (10.0.2.2) for dev convenience
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://172.21.212.246:8000/api',
    // defaultValue: 'http://10.0.2.2:8000/api',
  );

  // Auth Endpoints
  static const String login = '/auth/login';
  static const String register = '/auth/register';
  static const String logout = '/auth/logout';
  static const String forgotPassword = '/auth/forgot-password';
  static const String resetPassword = '/auth/reset-password';
  static const String changePassword = '/auth/change-password';

  // Profile
  static const String profile = '/profile';
  static const String updateLocation = '/location/update';

  // Blood Requests
  static const String bloodRequests = '/user/blood-requests';
  static const String bloodRequestsHistory = '/user/blood-requests/history';

  // Donor Actions
  static const String screening = '/donor/screening';
  static const String confirm = '/donor/confirm';
  static const String history = '/donor/history';

  // Notifications
  static const String notifications = '/user/notifications';
  static const String notificationsUnreadCount =
      '/user/notifications/unread-count';

  // Verification
  static const String verifyKode = '/verify/code';
}
