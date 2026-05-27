import 'package:dio/dio.dart';

class ApiErrorHandler {
  static String getMessage(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return 'Koneksi terlalu lama. Periksa jaringan internet Anda.';
        case DioExceptionType.connectionError:
          return 'Gagal terhubung ke server. Pastikan perangkat Anda terhubung ke internet.';
        case DioExceptionType.badResponse:
          final statusCode = error.response?.statusCode;
          final responseData = error.response?.data;
          
          if (responseData != null && responseData is Map && responseData['message'] != null) {
            return responseData['message'];
          }

          switch (statusCode) {
            case 400:
              return 'Permintaan tidak valid.';
            case 401:
              return 'Sesi Anda telah berakhir. Silakan login kembali.';
            case 403:
              return 'Anda tidak memiliki akses ke fitur ini.';
            case 404:
              return 'Data tidak ditemukan.';
            case 422:
              return 'Data yang dikirim tidak sesuai (Validasi gagal).';
            case 500:
              return 'Terjadi masalah pada server. Coba lagi nanti.';
            case 502:
            case 503:
            case 504:
              return 'Server sedang sibuk atau tidak tersedia. Coba lagi nanti.';
            default:
              return 'Terjadi kesalahan. Kode error: $statusCode';
          }
        case DioExceptionType.cancel:
          return 'Permintaan dibatalkan.';
        case DioExceptionType.badCertificate:
          return 'Masalah keamanan koneksi (Sertifikat tidak valid).';
        case DioExceptionType.unknown:
          return 'Terjadi kesalahan tidak terduga. Pastikan perangkat Anda memiliki koneksi internet.';
      }
    }
    return error.toString();
  }
}
