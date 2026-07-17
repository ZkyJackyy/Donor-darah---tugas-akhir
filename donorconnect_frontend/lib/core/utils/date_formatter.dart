import 'package:intl/intl.dart';

/// Parse a nullable ISO8601 string (from the backend) to local time and
/// format it as "25 Mei 2025". Returns '-' if [raw] is null or unparseable.
String formatIndonesianDate(String? raw) {
  if (raw == null) return '-';
  try {
    return DateFormat('dd MMMM yyyy', 'id_ID').format(DateTime.parse(raw).toLocal());
  } catch (_) {
    return '-';
  }
}
