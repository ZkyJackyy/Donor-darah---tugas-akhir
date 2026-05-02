# DESIGN.md — Panduan Tampilan Admin Panel
# Sistem Pencarian Pendonor Darah Sukarela — UDD PMI Kota Padang

File ini berisi design system dan prompt siap pakai untuk mempercantik
tampilan admin panel. Terpisah dari AGENTS.md agar lebih mudah dikelola.

---

## Cara Menggunakan File Ini

1. Buka sesi baru di Claude Code, Cursor, atau claude.ai
2. Kirim isi AGENTS.md terlebih dahulu sebagai konteks project
3. Lalu kirim salah satu prompt di bawah ini sesuai kebutuhan

---

## Design System

### Warna
| Elemen               | Kode Warna | Keterangan              |
|----------------------|------------|-------------------------|
| Sidebar background   | `#0F1C2E`  | Navy gelap              |
| Sidebar teks         | `#94A3B8`  | Abu muda                |
| Sidebar active       | `#E53E3E`  | Merah PMI + teks putih  |
| Accent / tombol      | `#E53E3E`  | Merah PMI               |
| Background halaman   | `#F7FAFC`  | Abu sangat terang       |
| Card / panel         | `#FFFFFF`  | Putih + shadow tipis    |
| Teks utama           | `#1A202C`  | Hampir hitam            |
| Teks sekunder        | `#718096`  | Abu medium              |
| Garis pemisah        | `#E2E8F0`  | Abu terang              |

### Tipografi
- **Font**: Inter (Google Fonts)
- **Heading halaman**: `font-semibold text-xl text-gray-800`
- **Sub-heading / label tabel**: `font-medium text-sm text-gray-500 uppercase tracking-wide`
- **Body**: `text-sm text-gray-700`

### Aturan Border Radius
- Maksimal `rounded-lg` (8px) untuk card dan tombol
- `rounded-full` hanya untuk badge status dan avatar kecil
- **Tidak menggunakan** `rounded-xl`, `rounded-2xl`, atau lebih besar

### Komponen Standar

**Topbar (tinggi 60px)**
```
bg-white border-b border-gray-200 shadow-sm
Kiri  : nama halaman aktif (font-semibold)
Kanan : nama admin yang sedang login + role-nya
```

**Card / Panel**
```
bg-white rounded-lg shadow-sm border border-gray-100 p-6
```

**Tombol Primer**
```
bg-red-600 hover:bg-red-700 text-white
rounded-md px-4 py-2 text-sm font-medium transition duration-150
```

**Tombol Sekunder**
```
border border-gray-300 text-gray-700
hover:bg-gray-50 rounded-md px-4 py-2 text-sm
```

**Tombol Hapus / Bahaya**
```
bg-red-50 text-red-600 hover:bg-red-100
rounded-md px-3 py-1 text-sm
```

**Tabel**
```
Header : bg-gray-50 text-xs font-medium text-gray-500 uppercase tracking-wide
Baris  : border-b border-gray-100 hover:bg-gray-50
Zebra  : odd:bg-white even:bg-gray-50/50
```

**Badge Status**
```
Aktif    → bg-green-100 text-green-700  rounded-full px-2 py-0.5 text-xs
Menunggu → bg-yellow-100 text-yellow-700
Ditutup  → bg-gray-100 text-gray-500
Urgent   → bg-red-100 text-red-700
```

**Form Input**
```
border border-gray-300 rounded-md px-3 py-2 text-sm w-full
focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent
```

**Notifikasi Flash**
```
Sukses → bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded-md
Error  → bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-md
Info   → bg-blue-50 border-l-4 border-blue-500 text-blue-800 p-4 rounded-md
```

---

## Prompt 1 — Desain Tampilan Admin (Lengkap)

> Gunakan ini untuk membangun ulang tampilan admin dari awal.

```
Kamu adalah UI designer dan Laravel Blade developer.
Tugasmu adalah mempercantik tampilan admin panel project ini
TANPA mengubah apapun selain file di folder resources/views/admin/
dan file CSS/JS yang terkait.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ATURAN KETAT — TIDAK BOLEH DILANGGAR
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- JANGAN ubah nama variable PHP ($pendonor, $permintaan, dll)
- JANGAN ubah nama method atau nama route di controller
- JANGAN ubah logika PHP di dalam Blade (if, foreach, @auth, dll)
  kamu hanya boleh menambahkan atau mengubah class HTML di sekitarnya
- JANGAN ubah atribut name="..." pada form input
  karena terikat langsung ke validasi dan request backend
- JANGAN tambah, ubah, atau hapus apapun di routes/web.php dan routes/api.php
- JANGAN install package baru — gunakan hanya Tailwind CSS yang sudah ada
- JANGAN ubah file apapun di luar resources/views/admin/
- Jika ragu apakah sesuatu aman untuk diubah, JANGAN ubah dan tanyakan dulu

Sebelum mulai, jalankan:
  php artisan route:list > routes_before.txt
Setelah selesai, jalankan:
  php artisan route:list > routes_after.txt
Bandingkan — jika ada perbedaan, rollback perubahan tersebut.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
DESIGN SYSTEM
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Ikuti semua spesifikasi warna, tipografi, dan komponen
yang sudah didefinisikan di file DESIGN.md project ini.

Gaya: clean, simple, tidak membosankan. Rapi, mudah dibaca, profesional.

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
YANG HARUS DIKERJAKAN (urut, satu per satu)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

LANGKAH 1 — Buat layout utama
File: resources/views/admin/layouts/app.blade.php
Berisi:
  - Sidebar fixed kiri (256px) dengan logo/nama sistem di atas
  - Menu navigasi (lihat daftar menu di bawah)
  - Topbar dengan nama halaman aktif + info admin login
  - Slot @yield('content') untuk konten tiap halaman
  - @yield('scripts') di bawah body untuk script tambahan
  - Load font Inter dari Google Fonts di <head>

Menu sidebar (sesuaikan href dengan route yang sudah ada,
cek terlebih dulu dengan php artisan route:list):
  🩸 Dashboard
  📋 Permintaan Donor
  👥 Data Pendonor
  ✅ Verifikasi Donor
  ⚙️  Pengaturan

Active state: gunakan request()->routeIs('admin.nama-route.*')

Laporkan hasilnya dan tunggu konfirmasi sebelum lanjut.

---

LANGKAH 2 — Terapkan layout ke semua halaman yang sudah ada
Untuk setiap file di resources/views/admin/:
  - Tambahkan @extends('admin.layouts.app') di baris pertama
  - Tambahkan @section('content') dan @endsection
  - Wrap tabel dengan card
  - Terapkan class Tailwind sesuai design system
  - INGAT: jangan sentuh variable PHP atau logika Blade

Kerjakan satu file, laporkan, lalu lanjut ke file berikutnya.

---

LANGKAH 3 — Tambahkan detail visual
  - Flash message untuk session 'success' dan 'error' di layout utama
  - Tabel responsive: bungkus dengan overflow-x-auto
  - Empty state jika data kosong: ikon + teks "Belum ada data" di tengah
  - Pastikan semua form memiliki label yang jelas

---

LANGKAH 4 — Verifikasi akhir
  - Bandingkan routes_before.txt dan routes_after.txt
  - Jalankan php artisan test — pastikan tidak ada test baru yang gagal
  - Laporkan: file apa saja yang diubah dan apa yang tidak disentuh

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
MULAI dari Langkah 1.
Tunggu konfirmasi saya sebelum lanjut ke langkah berikutnya.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## Prompt 2 — Perbaiki Satu Halaman Saja

> Gunakan ini jika ingin mempercantik satu halaman tertentu tanpa menyentuh yang lain.

```
Perbaiki tampilan halaman [sebutkan nama halaman, contoh: daftar permintaan donor]
yang ada di resources/views/admin/[nama-file].blade.php

Ikuti design system di DESIGN.md.

Aturan:
- JANGAN ubah variable PHP, logika Blade, atau atribut name pada form
- Hanya ubah class HTML dan struktur visual (wrapper div, card, dll)
- Gunakan hanya Tailwind CSS yang sudah ada

Yang perlu diperbaiki:
- Terapkan card wrapper untuk konten utama
- Perbaiki tampilan tabel menggunakan standar di DESIGN.md
- Tambahkan empty state jika data kosong
- Pastikan tombol menggunakan class standar dari DESIGN.md
- Tambahkan flash message jika belum ada

Setelah selesai, jalankan php artisan route:list
dan pastikan tidak ada yang berubah.
```

---

## Prompt 3 — Perbaiki Tampilan Form Saja

> Gunakan ini jika form terasa berantakan atau tidak rapi.

```
Perbaiki tampilan form di halaman [nama halaman]
file: resources/views/admin/[nama-file].blade.php

Ikuti design system di DESIGN.md.

Aturan KETAT:
- JANGAN SEKALI-KALI ubah atribut name="..." pada input, select, atau textarea
- JANGAN ubah atribut method, action, atau @csrf pada form
- JANGAN ubah variable PHP atau logika validasi @error
- Hanya ubah class HTML dan tambahkan elemen visual (label, wrapper, ikon)

Yang perlu diperbaiki:
- Setiap input harus punya label di atasnya
- Terapkan class input standar dari DESIGN.md
- Tampilkan pesan error validasi @error dengan style merah di bawah input
- Tombol submit menggunakan class tombol primer dari DESIGN.md
- Bungkus form dalam card dengan padding yang cukup

Setelah selesai, tunjukkan perbandingan before/after untuk setiap input
agar saya bisa memverifikasi tidak ada name="" yang berubah.
```

---

## Prompt 4 — Tambah Halaman Baru

> Gunakan ini saat perlu membuat halaman admin baru yang belum ada.

```
Buatkan halaman admin baru untuk [sebutkan fitur, contoh: detail permintaan donor].

Ikuti design system di DESIGN.md dan struktur project di AGENTS.md.

Yang dibutuhkan:
1. File Blade: resources/views/admin/[nama-folder]/[nama-file].blade.php
   - Extend layout: @extends('admin.layouts.app')
   - Gunakan komponen card, tabel, badge sesuai DESIGN.md
   - Gunakan variable PHP dengan nama yang konsisten dengan controller yang sudah ada

2. Sebelum membuat, jalankan php artisan route:list
   untuk melihat nama route dan variable yang sudah dipakai di halaman lain
   agar penamaan konsisten

3. Jangan buat controller atau route baru kecuali saya minta secara eksplisit

Laporkan nama file yang dibuat dan variable PHP yang digunakan.
```

---

## Catatan

- File ini hanya berisi panduan visual dan prompt — tidak ada logika bisnis
- Jika design system berubah, update bagian "Design System" di atas
- Semua prompt di sini sudah dilengkapi perlindungan agar tidak mengubah
  variable, route, atau logika backend yang sudah berjalan
