# TODO

Catatan perbaikan/fitur yang belum dikerjakan. Tandai `[x]` kalau selesai, pindahkan ke bagian "Selesai" di bawah kalau perlu jejak histori singkat.

## Admin — Menu Permintaan Darah

- [ ] **Soft-cancel blood request** — tambah status `cancelled` (bukan `DELETE` baris) supaya relasi `DonorCandidate`/`DonorHistory` yang sudah ada tidak putus/hilang.
  - Migration enum `blood_requests.status` tambah `cancelled` (pola sama seperti migration `screening_failed`).
  - Route + method `AdminBloodRequestWebController::cancel` (POST `/admin/blood-requests/{id}/cancel`), guard: hanya bisa cancel selama status masih `open`.
  - Ganti tombol "Hapus" jadi "Batalkan" di `blood-requests/index.blade.php` & `show.blade.php`, dengan konfirmasi.
  - Tambah warna badge untuk status `cancelled` di admin (menyusul perbaikan badge `screening_failed` sebelumnya).
  - Pastikan request `cancelled` tidak lagi muncul di listing "permintaan aktif" pengguna Flutter (`permintaan_provider`/`permintaan_list_screen`) kalau relevan.

- [ ] **Edit terbatas blood request** — field aman vs field terkunci.
  - Field yang selalu boleh diedit: `notes`, `deadline`, `required_bags` (hanya boleh naik, tidak boleh turun di bawah jumlah kandidat yang sudah confirmed/verified).
  - Field yang dikunci begitu status bukan `open` lagi / wave pertama sudah terkirim: `blood_type`, `rhesus`, `hospital_name`, `hospital_address` (lat/long) — karena donor yang sudah dinotifikasi WA tidak akan tahu detailnya berubah.
  - Route + method `AdminBloodRequestWebController::update` (PUT/PATCH `/admin/blood-requests/{id}`) + Form Request (`UpdateBloodRequestRequest`) untuk validasi guard di atas.
  - UI: form edit (di `show.blade.php` atau halaman terpisah), field terkunci ditampilkan disabled/readonly dengan keterangan alasan.
  - Tambah test feature untuk guard field-locking.

## Admin — Menu Baru

- [ ] **Halaman log WhatsApp (`wa_logs`)** — belum ada UI untuk melihat status kirim WA (`success`/`failed` + `error_message`) per donor/permintaan. Berguna untuk debug saat donor komplain tidak menerima notifikasi. Data model `WaLog` sudah ada, tinggal buat controller + view read-only (dengan filter status/permintaan).

## Mobile — Menu & Data

- [ ] **Menu ganti password** — backend `POST /auth/change-password` & `ApiConstants.changePassword` sudah ada, belum ada UI-nya.
  - Tambah `AuthProvider.changePassword()` (pola sama seperti `updateProfile()`).
  - Buat `change_password_screen.dart` (pola sama seperti `edit_profile_screen.dart`) + route `/profile/change-password` di `main.dart`.
  - Tambah tombol "Ganti Password" di `profile_screen.dart` di bawah tombol "Edit Profil".

> Rencana implementasi detail (file/baris spesifik) sudah disusun lengkap — lihat plan tersimpan di `polymorphic-petting-blum.md`, tinggal dieksekusi kapan siap.

## Mobile — Fitur Baru (butuh diskusi lebih lanjut)

- [ ] **User mengajukan permintaan donor pengganti (untuk keluarga)** — saat ini hanya admin PMI yang bisa membuat `BloodRequest`. Ide: user bisa mengajukan permintaan dari app untuk keluarga yang butuh darah.
  - Catatan penting: ini berlawanan dengan keputusan scope TA sebelumnya (lihat plan `langkah-dari-pihak-rumah-temporal-kurzweil.md`) yang sengaja memposisikan sistem sebagai modul internal PMI, bukan alat submit permintaan dari keluarga pasien — karena butuh verifikasi administratif (cek BDRS, surat pengantar) yang tidak bisa divalidasi otomatis dari input user awam.
  - Kalau jadi dikerjakan: JANGAN biarkan pengajuan user langsung jadi `BloodRequest` berstatus `open` yang otomatis trigger broadcast WA (rawan disalahgunakan/spam kuota donor). Perlu status baru `pending_review`, alur approval admin PMI dulu sebelum `DonorFilterService`/WA broadcast jalan.
  - Perlu: form pengajuan di mobile, endpoint API baru, halaman review/approval di admin panel, migration status baru di `blood_requests`.
  - Scope cukup besar — perlu dipikirkan/didiskusikan lagi sebelum masuk perencanaan detail.

## Backend — Perlu diverifikasi

- [ ] **`AdminSettingsController::testFonnte()`** (`app/Http/Controllers/Admin/AdminSettingsController.php`) — endpoint tes koneksi diganti ke `https://api.fonnte.com/device` tapi masih pakai method `POST`. Perlu dites langsung lewat tombol "Tes Koneksi" di panel admin untuk pastikan Fonnte menerima POST di endpoint itu (atau seharusnya GET).

---

## Selesai
_(kosong — pindahkan item di atas ke sini kalau sudah dikerjakan & diverifikasi)_
