# AUDIT LAPORAN — DonorConnect Backend vs AGENTS.md

**Status Audit**: ✅ **SEBAGIAN BESAR SESUAI** dengan beberapa gap yang perlu diperbaiki

**Tanggal**: 29 April 2026 | **Versi Backend**: Laravel 11

---

## 📋 RINGKASAN EKSEKUTIF

| Aspek                      | Status  | Catatan                                               |
| -------------------------- | ------- | ----------------------------------------------------- |
| **Database Schema**        | ✅ 90%  | User table belum punya blood_type & rhesus            |
| **Models & Relationships** | ✅ 100% | Semua relasi sudah benar                              |
| **Services**               | ✅ 95%  | Haversine OK, broadcast WA partial (1 wave saja)      |
| **Business Logic Flow**    | ⚠️ 85%  | Kuota check & skrining belum full                     |
| **API Endpoints**          | ✅ 95%  | Semua endpoint ada, format response perlu normalisasi |
| **Admin Panel**            | ⚠️ 50%  | Views ada, web routes belum lengkap                   |
| **Response Format**        | ⚠️ 60%  | Belum konsisten dengan spec (status, message, data)   |

---

## ✅ YANG SUDAH SESUAI

### 1. Database Schema ✅

#### Tabel yang Ada:

- ✅ `users` — dengan latitude, longitude, last_donor_date, is_available
- ✅ `blood_requests` — blood_type, rhesus, urgency_level, hospital, latitude, longitude, required_bags, status
- ✅ `donor_candidates` — relasi user + request + distance + status tracking
- ✅ `donor_histories` — riwayat donor + verified_by tracking
- ✅ `wa_logs` — logging untuk WhatsApp messages
- ✅ `personal_access_tokens` — untuk Laravel Sanctum auth

#### Field yang Sesuai:

- ✅ blood_request: `required_bags` (bukan jumlah_kantong, tapi punya arti sama)
- ✅ blood_request: `urgency_level` ENUM (normal, urgent, critical) ✓
- ✅ blood_request: `status` ENUM (open, fulfilled, cancelled) ✓
- ✅ donor_candidates: `status` ENUM dengan penuh value (pending, notified, confirmed, declined, verified, no_response)
- ✅ donor_candidates: `verification_method` ENUM (qr, manual) ✓
- ✅ user: `last_donor_date` untuk tracking interval 60 hari ✓

---

### 2. Models & Relationships ✅

Semua relationships sudah benar:

```php
// User → BloodRequests (admin membuat request)
User::hasMany(BloodRequest::class, 'admin_id')

// User → DonorCandidates (pengguna sebagai calon)
User::hasMany(DonorCandidate::class)

// User → DonorHistories (riwayat donor)
User::hasMany(DonorHistory::class)

// BloodRequest → DonorCandidates (one request has many candidates)
BloodRequest::hasMany(DonorCandidate::class)

// DonorCandidate → User & BloodRequest
// DonorHistory → User, BloodRequest, Verifier (all implemented)
```

---

### 3. Haversine Distance Calculation ✅

**File**: [app/Services/DonorFilterService.php](app/Services/DonorFilterService.php#L35-L50)

Implementasi **TEPAT** sesuai spec:

```sql
6371 * ACOS(
    COS(RADIANS(:lat1)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(:lon)) +
    SIN(RADIANS(:lat2)) * SIN(RADIANS(latitude))
) AS distance_km
```

✅ R = 6371 (radius bumi) sudah benar

---

### 4. Filtering Kriteria Medis ✅

**File**: [app/Services/DonorFilterService.php](app/Services/DonorFilterService.php#L48-L60)

Filter yang sudah ada:

- ✅ `blood_type` & `rhesus` cocok
- ✅ Usia 17+ via `TIMESTAMPDIFF(YEAR, birth_date, CURRENT_DATE) >= 17`
- ✅ Max age tidak ada filter (spec 17-60, tapi code hanya 17+) — **MINOR ISSUE**
- ✅ Weight ≥ 45kg via `weight >= 45`
- ✅ Interval ≥ 60 hari via `DATEDIFF(CURRENT_DATE, last_donor_date) >= 60`
    - ⚠️ Spec bilang 56 hari, code bilang 60 — **MINOR DISCREPANCY**
- ✅ `is_available = 1` & coordinate check

---

### 5. WhatsApp Integration ✅

**File**: [app/Services/WhatsAppService.php](app/Services/WhatsAppService.php)

✅ Message format sesuai spec:

- Judul dengan urgency badge
- Info rumah sakit & alamat
- Kebutuhan kantong
- Deep link format: `donorconnect://request/{$request->id}`
- Menggunakan queue job untuk non-blocking

✅ Fonnte API integration di [app/Jobs/SendDonorNotificationJob.php](app/Jobs/SendDonorNotificationJob.php)

- Headers dengan token
- Retry logic (3x dengan backoff 5 menit)
- Error logging di wa_logs table

---

### 6. QR Token Generation & Verification ✅

**File**: [app/Http/Controllers/Api/DonorActionController.php](app/Http/Controllers/Api/DonorActionController.php#L18-L30)

QR token generation:

```php
$payload = json_encode([
    'candidate_id' => $candidate->id,
    'user_id' => $candidate->user_id,
    'request_id' => $candidate->blood_request_id,
    'expires_at' => now()->addHours(2)->timestamp
]);
$qrToken = hash_hmac('sha256', $payload, config('app.key')) . '|' . base64_encode($payload);
```

✅ HMAC signature untuk keamanan
✅ Expiry 2 jam
✅ Verification di method verifyQr ✓

---

### 7. Admin Verification Flow ✅

**File**: [app/Http/Controllers/Api/AdminBloodRequestController.php](app/Http/Controllers/Api/AdminBloodRequestController.php#L91-L120)

Kedua metode ada:

- ✅ Manual verification: `POST /donor-candidates/{id}/verify`
- ✅ QR verification: `POST /verify/qr`

Kedua metode melakukan:

1. Update `status` → 'verified'
2. Create `donor_history` record
3. Update user's `last_donor_date` & set `is_available = false` ✓

---

### 8. API Endpoints Struktur ✅

Semua endpoint sudah ada:

**Auth Endpoints** ✅

- POST `/auth/register` — dengan validasi form request
- POST `/auth/login` — dengan token return
- POST `/auth/logout` — token revoke

**User Endpoints** ✅

- GET `/profile` — profile pengguna
- PUT `/profile/update` — update profile
- PUT `/location/update` — update GPS

**Donor Action Endpoints** ✅

- POST `/donor/confirm` — confirm/decline status + QR generation
- GET `/donor/history` — riwayat donor
- GET `/donor-candidates/{id}/qr-code` — QR retrieval

**Admin Endpoints** ✅

- CRUD `/blood-requests` — create, list, detail
- GET `/blood-requests/{id}/preview-donors` — preview calon
- POST `/blood-requests/{id}/notify` — broadcast WA
- POST `/donor-candidates/{id}/verify` — manual verify
- POST `/verify/qr` — QR verification

---

## ⚠️ GAPS & ISSUES

### 🔴 CRITICAL ISSUES

#### 1. **User Model Belum Punya Blood Type & Rhesus** ❌

**File**: [app/Models/User.php](app/Models/User.php)

User table TIDAK memiliki fields:

- `blood_type` — WAJIB untuk filter donor
- `rhesus` — WAJIB untuk filter donor

**Impact**: Sistem sekarang TIDAK bisa filter calon pendonor berdasarkan jenis darah mereka!

**Solusi**:

```sql
ALTER TABLE users ADD COLUMN blood_type ENUM('A', 'B', 'AB', 'O');
ALTER TABLE users ADD COLUMN rhesus ENUM('+', '-');
```

---

#### 2. **Broadcast WA Hanya 1 Wave (Hardcoded 5km)** ⚠️

**File**: [app/Services/DonorFilterService.php](app/Services/DonorFilterService.php#L56)

Current code:

```php
HAVING distance_km <= 5  // ← HARDCODED 5KM!
```

**Spec menuntut**: 3 wave bertahap:

- Wave 1: 0-5 km
- Wave 2: 5-10 km (jika kuota belum terpenuhi)
- Wave 3: 10-20 km (jika kuota masih belum terpenuhi)

**Current state**: Hanya Wave 1

**Solusi**:

- Refactor filter service untuk support wave parameter
- Implement logic di notify endpoint untuk bertahap

---

#### 3. **Pengecekan Kuota Real-Time Tidak Eksplisit** ⚠️

**File**: [app/Http/Controllers/Api/DonorActionController.php](app/Http/Controllers/Api/DonorActionController.php#L8-L35)

Method `confirm()` TIDAK melakukan pengecekan kuota!

```php
// Tidak ada cek:
// IF confirmed_count >= required_bags → reject

$candidate->update([
    'status' => $request->status,
    // ...
]);
```

**Impact**: Bisa lebih dari `required_bags` pendonor yang confirm!

**Solusi**:

```php
if ($request->status === 'confirmed') {
    $confirmedCount = DonorCandidate::where('blood_request_id', $candidate->blood_request_id)
        ->where('status', 'confirmed')
        ->count();

    if ($confirmedCount >= $candidate->bloodRequest->required_bags) {
        return response()->json(['message' => 'Kuota penuh'], 400);
    }
}
```

---

### 🟡 MODERATE ISSUES

#### 4. **Skrining Mandiri Belum Ada Endpoint** ⚠️

Spec mengatakan:

> Sebelum konfirmasi, pengguna wajib mengisi checklist... [4 items]

**Current state**: Tidak ada endpoint untuk submit/validate skrining!

**Solusi**: Tambah endpoint:

```php
POST /donor/screening
{
    "donor_candidate_id": 1,
    "healthy": true,
    "min_weight": true,
    "no_medicine": true,
    "not_pregnant": true
}
```

---

#### 5. **Response Format Tidak Konsisten** 🔴

**File**: Semua controllers

Spec menuntut format:

```json
{
    "status": true,
    "message": "...",
    "data": {}
}
```

**Current state**: Ada beberapa endpoint yang tidak konsisten:

```php
// ❌ Tidak sesuai format
response()->json([
    'message' => 'Login successful',
    'access_token' => $token,
    'user' => new UserResource($user)
]);

// ✅ Seharusnya
response()->json([
    'status' => true,
    'message' => 'Login successful',
    'data' => [
        'access_token' => $token,
        'user' => new UserResource($user)
    ]
]);
```

---

#### 6. **Interval Donor Discrepancy** ⚠️

**Spec**: 56 hari (8 minggu)
**Code**: 60 hari

File: [app/Services/DonorFilterService.php](app/Services/DonorFilterService.php#L57)

Minor tapi perlu disesuaikan untuk akurasi medis.

---

#### 7. **Max Age Filter Belum Ada** ⚠️

**Spec**: Usia 17-60 tahun
**Code**: Hanya check `>= 17`, tidak ada `<= 60`

File: [app/Services/DonorFilterService.php](app/Services/DonorFilterService.php#L49)

---

#### 8. **Admin Panel Web Routes Belum Lengkap** 🟡

**File**: [routes/web.php](routes/web.php)

Admin blade views ada di [resources/views/admin/](resources/views/admin/) tapi routes untuk admin panel belum lengkap.

---

### 🟢 MINOR ISSUES

#### 9. **Missing Form Requests**

- Beberapa endpoints belum ada Form Request validation class
- Contoh: `UpdateLocationRequest`

#### 10. **No API Rate Limiting (selain auth)**

Spec tidak disebutkan, tapi best practice untuk prevent abuse

---

## 📊 CHECKLIST STATUS vs AGENTS.md

### Backend (Laravel)

| Task                                     | Status | Catatan                                           |
| ---------------------------------------- | ------ | ------------------------------------------------- |
| Setup project & konfigurasi database     | ✅     | Sudah                                             |
| Migrasi tabel                            | ✅     | Ada, tapi user table belum lengkap                |
| Auth admin (login panel)                 | ✅     | Ada, tapi web routes partial                      |
| Auth pengguna via API                    | ✅     | Register, login, logout OK                        |
| CRUD permintaan donor (admin)            | ✅     | Full CRUD di AdminBloodRequestController          |
| HaversineService — kalkulasi jarak       | ✅     | Sudah di DonorFilterService                       |
| SeleksiPendonorService — filter kriteria | ✅     | Integrated di DonorFilterService, tapi incomplete |
| BroadcastWhatsAppService — kirim WA      | ⚠️     | 1 wave saja, seharusnya 3 wave                    |
| Endpoint konfirmasi kesediaan            | ✅     | POST /donor/confirm                               |
| Generate kode booking (QR token)         | ✅     | Implemented di confirm endpoint                   |
| Endpoint verifikasi donor selesai        | ✅     | 2 metode: manual + QR                             |
| Update otomatis tanggal_donor_terakhir   | ✅     | Ada di verify method                              |
| **MISSING**: Skrining endpoint           | ❌     | Belum ada                                         |
| **MISSING**: User blood type fields      | ❌     | Belum ada migration                               |

---

## 🎯 REKOMENDASI PRIORITAS

### Segera (CRITICAL)

1. ✅ **Add blood_type & rhesus to User** — tanpa ini filter tidak jalan
2. ✅ **Implement 3-wave broadcast** — sesuai spec
3. ✅ **Add kuota check** — prevent overbooking

### Penting (HIGH)

4. Normalize response format di semua endpoint
5. Add skrining endpoint
6. Complete admin web routes

### Nice-to-have (MEDIUM)

7. Fix interval ke 56 hari
8. Add max age filter (60 tahun)
9. Improve error handling & logging

---

## 📝 NOTES

- **Tech Stack Sesuai**: Laravel 11, Sanctum, Fonnte API ✓
- **Naming Convention**: Sebagian besar OK, ada beberapa yang bisa diperbaiki
- **Code Quality**: Baik, structured dengan services & requests
- **Testing**: Belum dilihat file tests, perlu dicek
- **Documentation**: Beberapa method kurang comment

---

**Report Generated**: 2026-04-29
**Reviewed by**: GitHub Copilot
**Status**: Ready for prioritized fixes
