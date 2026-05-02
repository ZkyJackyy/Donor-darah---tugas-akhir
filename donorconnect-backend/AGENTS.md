# AGENTS.md — Sistem Pencarian Pendonor Darah Sukarela

# UDD PMI Kota Padang

## Project Overview

Sistem pencarian pendonor darah sukarela berbasis **Location Based Service (LBS)** untuk
UDD PMI Kota Padang. Sistem terdiri dari dua sisi:

- **Admin Panel** (web) — digunakan petugas UDD PMI untuk membuat & mengelola permintaan donor
- **Aplikasi Mobile** (Flutter) — digunakan masyarakat umum sebagai calon pendonor

Alur utama: admin membuat permintaan → sistem menyeleksi & mengurutkan pendonor via Haversine
→ broadcast WhatsApp bertahap → pengguna konfirmasi via aplikasi → tiket digital → verifikasi admin.

---

## Tech Stack

### Backend

- **Framework**: Laravel 11
- **Language**: PHP 8.2
- **Database**: MySQL 8.0
- **Auth**: Laravel Sanctum (token-based untuk API mobile)
- **WhatsApp Gateway**: Fonnte API (atau sesuaikan dengan provider yang digunakan)
- **Koordinat PMI**: lat `-0.9471`, lng `100.4172`

### Mobile (Pengguna)

- **Framework**: Flutter (Dart)
- **Min SDK Android**: 24 (Android 7.0)
- **Maps**: Google Maps Flutter Plugin
- **State Management**: (sesuaikan: Provider / Riverpod / BLoC)
- **HTTP Client**: Dio
- **Deep Link**: `app_links` package

### Admin Panel

- **Template/View**: Laravel Blade
- **CSS**: Tailwind CSS
- **JS**: Alpine.js atau Vanilla JS

---

## Struktur Direktori (Backend Laravel)

```
app/
  Models/
    User.php              → Data pengguna / calon pendonor
    PermintaanDonor.php   → Permintaan donor yang dibuat admin
    KonfirmasiDonor.php   → Kesediaan pendonor + kode booking
    RiwayatDonor.php      → Histori donor per pengguna
  Http/
    Controllers/
      Api/
        AuthController.php
        PermintaanDonorController.php
        KonfirmasiDonorController.php
        SkriningSelfController.php
      Admin/
        PermintaanDonorController.php
        VerifikasiDonorController.php
  Services/
    HaversineService.php       → Kalkulasi jarak koordinat
    BroadcastWhatsAppService.php → Pengiriman pesan bertahap via WA Gateway
    SeleksiPendonorService.php  → Filter kriteria medis + sorting jarak
database/
  migrations/
resources/
  views/
    admin/   → Blade views panel admin
routes/
  api.php    → Endpoint untuk Flutter
  web.php    → Route admin panel
```

---

## Struktur Direktori (Flutter)

```
lib/
  main.dart
  core/
    constants/       → API base URL, deep link scheme, dsb
    services/        → API service, deep link handler
  features/
    auth/            → Login, register, OTP
    permintaan/      → List & detail permintaan donor
    skrining/        → Form checklist skrining mandiri
    konfirmasi/      → Tombol konfirmasi + tiket digital
    riwayat/         → Riwayat donor pengguna
  shared/
    widgets/
    models/
```

---

## Alur Sistem & Business Logic

### 1. Admin membuat permintaan donor

Admin mengisi form dengan field berikut:

- `golongan_darah` → ENUM: A, B, AB, O
- `rhesus` → ENUM: positif, negatif
- `jumlah_kantong` → INT (kebutuhan darah)
- `batas_waktu` → DATETIME (deadline pemenuhan)

### 2. Seleksi otomatis calon pendonor

Sistem memfilter tabel `users` dengan kriteria:

- Golongan darah & rhesus cocok dengan permintaan
- Usia antara **17–60 tahun**
- Interval sejak `tanggal_donor_terakhir` ≥ **56 hari (8 minggu)**
- Status akun aktif & tidak sedang dalam kondisi ditangguhkan

### 3. Kalkulasi & sorting jarak (Haversine)

Gunakan `HaversineService` untuk menghitung jarak dari koordinat pengguna
ke koordinat UDD PMI Padang (`lat: -0.9471, lng: 100.4172`).
Hasil diurutkan dari jarak terdekat ke terjauh.

**Rumus Haversine (referensi implementasi):**

```php
public function hitungJarak(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371; // radius bumi dalam km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $r * 2 * asin(sqrt($a));
}
```

### 4. Broadcast WhatsApp bertahap

Pengiriman dilakukan secara bertahap berdasarkan radius untuk mencegah penumpukan antrean:

- **Gelombang 1**: pendonor dalam radius 0–5 km
- **Gelombang 2**: 5–10 km (dikirim jika kuota belum terpenuhi)
- **Gelombang 3**: 10–20 km (dikirim jika kuota masih belum terpenuhi)

Pesan WhatsApp memuat:

- Informasi urgensi kebutuhan darah
- Deep link ke halaman detail permintaan di aplikasi Flutter

**Format deep link**: `donorpmi://permintaan/{id_permintaan}`

### 5. Skrining mandiri (Flutter)

Sebelum konfirmasi, pengguna wajib mengisi checklist:

- [ ] Kondisi tubuh sehat hari ini
- [ ] Berat badan ≥ 45 kg
- [ ] Tidak sedang mengonsumsi obat-obatan tertentu
- [ ] Tidak sedang hamil (khusus perempuan)

Checklist ini bersifat self-assessment. Sistem tidak memvalidasi secara medis,
hanya memastikan semua item dicentang sebelum tombol konfirmasi aktif.

### 6. Pengecekan kuota real-time

Saat pengguna menekan tombol konfirmasi:

1. Backend mengecek jumlah `konfirmasi_donor` dengan status `terkonfirmasi`
   pada permintaan tersebut
2. Jika `jumlah_terkonfirmasi >= jumlah_kantong` → kembalikan response **"kuota penuh"**
3. Jika masih tersedia → buat record `konfirmasi_donor` + generate `kode_booking`

### 7. Tiket digital

- `kode_booking` berupa string unik (misal: UUID pendek atau format `PMI-YYYYMMDD-XXXX`)
- Ditampilkan di layar Flutter sebagai kartu digital
- Bisa menampilkan QR code dari kode tersebut untuk kemudahan scan petugas

### 8. Verifikasi & update riwayat donor

Setelah pengguna selesai mendonor secara fisik:

- Admin mengubah status `konfirmasi_donor` menjadi `selesai`
- Sistem otomatis mengupdate `tanggal_donor_terakhir` di tabel `users`
- Interval jeda 56 hari dihitung ulang dari tanggal ini untuk permintaan berikutnya

---

## Format Response API

Semua endpoint API menggunakan format response yang konsisten:

```json
{
  "status": true,
  "message": "Deskripsi singkat hasil",
  "data": { }
}
```

Untuk error:

```json
{
  "status": false,
  "message": "Pesan error yang informatif",
  "data": null
}
```

---

## Konvensi Koding

### Umum

- Gunakan **Bahasa Indonesia** untuk nama variabel, method, dan komentar yang bersifat domain bisnis
- Gunakan **Bahasa Inggris** untuk istilah teknis standar Laravel/Flutter
- Semua API key, credential, dan URL eksternal wajib disimpan di `.env` — **tidak boleh hardcode**

### Laravel (Backend)

- Penamaan controller: `NamaDomainController.php`
- Penamaan method: camelCase, deskriptif — contoh: `getListPendonorTerdekat()`
- Gunakan **Form Request** untuk validasi input
- Gunakan **Resource** (`JsonResource`) untuk transformasi data API
- Logic bisnis kompleks masuk ke folder `Services/`, bukan di Controller

### Flutter (Mobile)

- Penamaan file: `snake_case.dart`
- Penamaan class: `PascalCase`
- Setiap fitur memiliki folder sendiri di `lib/features/`
- Model API response dibuat sebagai Dart class dengan `fromJson()`

---

## Aturan yang TIDAK Boleh Dilakukan AI

- **Jangan** mengubah struktur tabel `users` yang sudah ada tanpa konfirmasi eksplisit
- **Jangan** membuat migration baru yang memodifikasi tabel yang sudah di-migrate
- **Jangan** menghapus atau menimpa file migration yang sudah ada
- **Jangan** menggunakan package/library baru tanpa disebutkan di prompt
- **Jangan** mengganti format response API dari standar yang sudah ditetapkan di atas
- **Jangan** menyimpan data sensitif (token, password, API key) di dalam kode secara langsung
- **Jangan** melewati pengecekan kuota real-time saat proses konfirmasi kesediaan

---

## Status Progres Fitur

### Backend (Laravel)

- [ ] Setup project & konfigurasi database
- [ ] Migrasi tabel (users, permintaan_donor, konfirmasi_donor, riwayat_donor)
- [ ] Auth admin (login panel)
- [ ] Auth pengguna via API (register, login, OTP)
- [ ] CRUD permintaan donor (admin)
- [ ] HaversineService — kalkulasi jarak
- [ ] SeleksiPendonorService — filter kriteria medis
- [ ] BroadcastWhatsAppService — kirim WA bertahap
- [ ] Endpoint konfirmasi kesediaan + generate kode booking
- [ ] Endpoint verifikasi donor selesai (admin)
- [ ] Update otomatis tanggal_donor_terakhir

### Mobile (Flutter)

- [ ] Setup project & konfigurasi deep link
- [ ] Halaman auth (register, login)
- [ ] Halaman list & detail permintaan donor
- [ ] Form skrining mandiri
- [ ] Halaman konfirmasi + tampil tiket digital
- [ ] Halaman riwayat donor

---
 
## Commands
 
### Menjalankan Project
```bash
# Backend Laravel
php artisan serve                           # jalankan server lokal port 8000
php artisan migrate                         # jalankan migration terbaru
php artisan migrate:fresh --seed            # reset database + isi data dummy
php artisan route:list                      # lihat semua route terdaftar
php artisan storage:link                    # buat symlink storage
 
# Mobile Flutter
flutter pub get                             # install dependencies
flutter run                                 # jalankan di emulator/device
flutter build apk --debug                  # build APK debug
flutter analyze                             # cek error & warning
```
 
### Testing
```bash
# Laravel
php artisan test                            # jalankan semua test
php artisan test --filter=NamaTest         # jalankan test tertentu
 
# Flutter
flutter test                               # jalankan semua unit test
flutter test test/nama_file_test.dart      # jalankan test file tertentu
```
 
### Yang BOLEH dijalankan agent secara otomatis
- `php artisan migrate`
- `php artisan test`
- `php artisan route:list`
- `flutter pub get`
- `flutter test`
- `flutter analyze`
- `composer install`
- `composer dump-autoload`
### Yang TIDAK BOLEH dijalankan agent tanpa konfirmasi eksplisit
- `php artisan migrate:fresh` → menghapus semua data
- `php artisan db:seed` → mengubah isi database
- `php artisan down` → mematikan aplikasi
- `rm`, `del`, atau perintah hapus file apapun
- `git push` → mendorong perubahan ke repository
- `git reset --hard` → membatalkan perubahan secara permanen
---
 
## Health Check — Prompt untuk Pengecekan Mandiri
 
Salin salah satu prompt berikut dan berikan ke agent untuk memulai pengecekan otomatis.
 
---
 
### Cek Cepat (Quick Check)
> Gunakan ini setiap kali selesai mengerjakan satu fitur.
 
```
Lakukan health check cepat pada project ini:
1. Jalankan `php artisan route:list` — pastikan tidak ada error
2. Jalankan `php artisan test` — catat berapa test yang lulus dan gagal
3. Jalankan `flutter analyze` — catat jumlah error dan warning
4. Laporkan hasilnya dalam format ringkas:
   - Backend: [OK / ERROR] — (detail singkat)
   - Test: X lulus, Y gagal — (nama test yang gagal jika ada)
   - Flutter: X error, Y warning — (daftar jika ada)
Jangan perbaiki apapun dulu, hanya laporkan.
```
 
---
 
### Cek & Perbaiki Otomatis (Auto Fix)
> Gunakan ini ketika kamu ingin agent bekerja mandiri sampai semua beres.
 
```
Lakukan health check menyeluruh dan perbaiki semua masalah yang ditemukan:
1. Jalankan `php artisan test` — jika ada yang gagal, baca error-nya dan perbaiki kodenya
2. Jalankan ulang test setelah perbaikan — ulangi sampai semua test lulus
3. Jalankan `flutter analyze` — perbaiki semua error (bukan warning)
4. Jalankan `php artisan route:list` — pastikan semua route terdaftar dengan benar
5. Setelah semua beres, buat laporan singkat:
   - Apa saja yang diperbaiki
   - Berapa test yang sekarang lulus
   - Apakah masih ada warning yang perlu diperhatikan
```
 
---
 
### Cek Fitur Spesifik
> Gunakan ini untuk menguji satu bagian tertentu dari sistem.
 
```
Lakukan health check khusus untuk fitur [nama fitur, contoh: HaversineService]:
1. Periksa apakah file implementasinya sudah ada dan sesuai konvensi di AGENTS.md
2. Jalankan test yang berkaitan: `php artisan test --filter=[NamaTest]`
3. Jika belum ada test-nya, buatkan unit test yang mencakup:
   - Skenario normal (input valid, output sesuai)
   - Skenario edge case (input ekstrem atau tidak terduga)
4. Jalankan test yang baru dibuat dan perbaiki jika gagal
5. Laporkan hasilnya
```
 
---
 
### Cek Koneksi & Environment
> Gunakan ini di awal sesi kerja atau setelah pindah komputer/environment.
 
```
Periksa apakah environment project sudah siap digunakan:
1. Cek apakah file `.env` ada dan memiliki key berikut (tanpa membaca nilainya):
   DB_DATABASE, DB_USERNAME, DB_PASSWORD, APP_KEY, FONNTE_API_KEY
2. Jalankan `php artisan migrate:status` — pastikan semua migration sudah berjalan
3. Jalankan `php artisan route:list` — pastikan tidak ada error routing
4. Jalankan `flutter pub get` — pastikan semua dependency Flutter terinstall
5. Jalankan `flutter analyze` — pastikan tidak ada error
6. Laporkan status masing-masing langkah: [SIAP] atau [PERLU PERHATIAN]
```
 
---
 
### Cek Sebelum Presentasi / Submit
> Gunakan ini sebelum demo ke dosen pembimbing atau sebelum submit laporan TA.
 
```
Lakukan pengecekan menyeluruh sebelum presentasi. Pastikan semua poin berikut terpenuhi:
 
Backend Laravel:
- Semua migration sudah berjalan tanpa error (`php artisan migrate:status`)
- Semua route terdaftar dengan benar (`php artisan route:list`)
- Semua test lulus (`php artisan test`)
- Tidak ada syntax error di seluruh file PHP (`php artisan config:clear && php artisan cache:clear`)
- Format response API konsisten sesuai standar di AGENTS.md
 
Mobile Flutter:
- Tidak ada error di `flutter analyze`
- Semua dependency terinstall (`flutter pub get`)
- Deep link scheme `donorpmi://` terdaftar dengan benar di AndroidManifest.xml
- Tidak ada hardcoded URL atau API key di kode Flutter
 
Laporan akhir:
- Buat ringkasan status dalam bentuk checklist
- Tandai mana yang [LULUS], [PERINGATAN], atau [GAGAL]
- Berikan saran prioritas perbaikan jika ada yang gagal
```
 
---
 
## Catatan Tambahan
 
- Project ini merupakan **Tugas Akhir** Program Studi Manajemen Informatika,
  Politeknik Negeri Padang. Implementasi dilakukan di UDD PMI Kota Padang.
- Prioritas utama adalah **fungsionalitas dan kejelasan kode** untuk keperluan dokumentasi akademik.
- Setiap perubahan besar pada arsitektur harus didokumentasikan di file ini.