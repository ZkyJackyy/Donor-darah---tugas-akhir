# Sequence Diagram Execution Matrix

This logical sequence maps the exact application lifecycle from emergency creation to final Verification limits.

```mermaid
sequenceDiagram
    actor Admin
    participant WebUI as Laravel Web Dashboard
    actor Donor as Candidate
    participant API as Laravel REST API
    participant Worker as Redis Queue (Fonnte WA)
    participant Flutter as Mobile App
    
    %% Phase 1: Request
    Admin->>WebUI: Creates Blood Request (Map Pinpointing)
    WebUI-->>API: POST /admin/blood-requests
    API->>API: Calculate Haversine 5KM (Radius)
    
    %% Phase 2: Interception
    Admin->>WebUI: Clicks "Kirim Notifikasi WA"
    WebUI->>API: POST /admin/blood-requests/{id}/notify
    API->>API: Generate donor_candidates ('status' = 'notified')
    API->>Worker: Dispatch SendDonorNotificationJob (per user)
    Worker-->>Donor: Dispatches WA Message (DeepLink Payload)
    
    %% Phase 3: Confirmation
    Donor->>Flutter: Clicks donorconnect://request/{id}
    Flutter-->>API: GET /api/blood-requests/{id}
    Donor->>Flutter: Hits "Confirm Donation"
    Flutter->>API: POST /api/donor/confirm (status: confirmed)
    
    %% Phase 4: Ticket Issuance
    API->>API: Verify constraints & generate kode_verifikasi
    API-->>Flutter: Return kode_verifikasi
    Flutter->>Flutter: Navigate to /tiket rendering digital ticket
    
    %% Phase 5: Verification
    Donor->>Admin: Reaches Hospital, Shows digital ticket (kode_verifikasi)
    Admin->>WebUI: Enters kode_verifikasi or clicks manual verify
    WebUI->>API: POST /admin/blood-requests/verify/{id} or /api/verify/code
    API->>API: Validate candidate status & request status
    
    %% Phase 6: Closure Limits
    API->>API: update donor_candidates (status = 'verified')
    API->>API: Log to donor_histories
    API->>API: Lock user account (is_available = false, cooldown = 60 days)
    API-->>Flutter: 200 OK (Verification Success)
```

## Sequence: Verifikasi Tiket Digital

Boundary/control/entity mengikuti nama kelas & pesan persis dari kode: view `resources/views/admin/verify/index.blade.php`, route `admin.verify.submit`, controller `AdminVerifyController::submit()`, model `DonorCandidate` / `DonorHistory` / `User` / `BloodRequest`. Tidak ada scan QR camera di sistem ini — kolom `qr_token` sempat ada lalu di-drop (migrasi `drop_qr_token_from_donor_candidates`), diganti kode teks 6 karakter `kode_verifikasi`.

```mermaid
sequenceDiagram
    actor Admin
    participant Boundary as admin.verify.index<br/>(Form Verifikasi Kehadiran Pendonor)
    participant Control as AdminVerifyController
    participant DonorCandidate as «entity» DonorCandidate
    participant DonorHistory as «entity» DonorHistory
    participant User as «entity» User
    participant BloodRequest as «entity» BloodRequest

    Admin->>+Boundary: Input kode_verifikasi (6 karakter)
    Boundary->>+Control: POST admin.verify.submit {kode_verifikasi}

    Control->>+DonorCandidate: where('kode_verifikasi', strtoupper($kode))->first()
    DonorCandidate-->>-Control: $candidate

    alt $candidate === null
        Control-->>Boundary: back() error "Kode verifikasi tidak valid atau sudah kadaluarsa."
    else $candidate->status === 'verified'
        Control-->>Boundary: back() error "Kandidat sudah terverifikasi sebelumnya."
    else $candidate->status !== 'confirmed'
        Control-->>Boundary: back() error "Status kandidat '{status}' — belum bisa diverifikasi.<br/>Kandidat harus mengkonfirmasi kehadiran terlebih dahulu."
    else $candidate->bloodRequest->status !== 'open'
        Control-->>Boundary: back() error "Permintaan ini berstatus '{status}' —<br/>kandidat tidak bisa diverifikasi lagi."
    else confirmed_at + expiry_minutes sudah lewat
        Control-->>Boundary: back() error "Kode verifikasi tidak valid atau sudah kadaluarsa."
    else valid
        Control->>DonorCandidate: update(status: 'verified', verified_at: now(), verification_method: 'code')
        Control->>+DonorHistory: create(user_id, blood_request_id, donor_date: today(),<br/>location_name: hospital_name, verified_by: auth()->id())
        DonorHistory-->>-Control: donor_histories row created
        Control->>User: update(last_donor_date: today(), is_available: false)
        Control->>BloodRequest: checkAndAutoFulfill()
        Control-->>-Boundary: back() success "Pendonor {nama} berhasil diverifikasi."
    end

    Boundary-->>-Admin: Tampilkan flash message
```

## Sequence: Membuat Pengajuan Permintaan (Keluarga → Admin Approve)

Sisi Flutter: boundary `AjukanPermintaanScreen`, control `PermintaanProvider.submitPermintaan()` lewat `ApiService`, endpoint `ApiConstants.bloodRequests` (`POST /user/blood-requests`), controller `UserBloodRequestController::store()`. Sisi admin: boundary halaman `blood-requests.pending`, controller `AdminBloodRequestWebController::approve()`. Entity keduanya `BloodRequest`.

```mermaid
sequenceDiagram
    actor Keluarga as Keluarga (role: user)
    participant Boundary1 as AjukanPermintaanScreen
    participant Control1 as PermintaanProvider
    participant API as UserBloodRequestController
    participant Entity as «entity» BloodRequest
    actor Admin
    participant Boundary2 as blood-requests.pending<br/>(Halaman Pengajuan Keluarga)
    participant Control2 as AdminBloodRequestWebController

    Keluarga->>+Boundary1: Isi form & tekan "Kirim Pengajuan"
    Boundary1->>+Control1: submitPermintaan(bloodType, rhesus, requiredBags,<br/>patientName, patientRelationship, hospitalName,<br/>hospitalAddress, urgencyLevel, deadline, notes, lat, lng)
    Control1->>+API: POST /user/blood-requests

    alt validasi gagal (StoreBloodRequestRequest rules)
        API-->>Control1: 422 Unprocessable Entity
        Control1-->>Boundary1: error ?? "Gagal mengirim pengajuan"
    else validasi lolos
        API->>Entity: create(..., type: 'emergency', status: 'pending_review',<br/>requested_by_user_id: user.id, admin_id: null)
        API-->>-Control1: 201 "Pengajuan berhasil dikirim, menunggu persetujuan admin PMI"
        Control1-->>-Boundary1: success = true
        Boundary1-->>-Keluarga: Snackbar "Pengajuan berhasil dikirim,<br/>menunggu persetujuan admin"
    end

    Note over Admin,Control2: Async — tidak ada trigger langsung dari pengajuan;<br/>admin memantau lewat pollPendingCount()

    Admin->>+Boundary2: Buka menu "Pengajuan Keluarga"
    Boundary2->>Control2: GET blood-requests.pending
    Control2->>Entity: where('status', 'pending_review')->paginate(10)
    Entity-->>Boundary2: daftar pengajuan
    Admin->>Boundary2: Review detail & tekan "Approve"
    Boundary2->>+Control2: POST blood-requests.approve {id}

    alt !$bloodRequest->isPendingReview()
        Control2-->>Boundary2: back() error "Pengajuan ini sudah berstatus '{status}' —<br/>tidak bisa disetujui lagi."
    else pending_review
        Control2->>Entity: update(status: 'open', admin_id: auth()->id())
        Control2-->>-Boundary2: redirect "Pengajuan disetujui dan masuk<br/>alur pencarian pendonor."
    end

    Boundary2-->>-Admin: Tampilkan hasil
```

## Sequence: Skrining Mandiri dan Konfirmasi Donor

Koreksi dari diagram lama yang menyebut QR code (`qr_token`, `hash_hmac('sha256', ...)`, `expires_at now()+2h`) — kolom itu **sudah di-drop dari sistem** (migrasi `drop_qr_token_from_donor_candidates`). Yang berjalan sekarang: dua endpoint terpisah — `POST /api/donor/screening` (self-assessment) lalu `POST /api/donor/confirm` (baru di sinilah `kode_verifikasi` 6-karakter acak diterbitkan, bukan hash/QR), keduanya di `DonorActionController`.

```mermaid
sequenceDiagram
    actor Pendonor
    participant Boundary as SkriningScreen /<br/>KonfirmasiScreen
    participant Control as DonorActionController
    participant DonorScreening as «entity» DonorScreening
    participant DonorCandidate as «entity» DonorCandidate
    participant BloodRequest as «entity» BloodRequest

    Pendonor->>+Boundary: Jawab 4 pertanyaan skrining & tekan "Selesai Skrining"
    Boundary->>+Control: POST /api/donor/screening {donor_candidate_id, jawaban}

    alt status kandidat bukan notified/pending/screening_failed
        Control-->>Boundary: error "Kandidat tidak dapat melakukan<br/>skrining dengan status saat ini"
    else valid
        Control->>DonorScreening: updateOrCreate(donor_candidate_id, jawaban, screened_at: now())
        alt salah satu jawaban tidak memenuhi syarat
            Control->>DonorCandidate: update(status: 'screening_failed')
            Control-->>-Boundary: "Anda belum memenuhi syarat untuk mendonor<br/>saat ini berdasarkan hasil skrining mandiri"
        else semua jawaban memenuhi syarat
            Control->>DonorCandidate: update(status: 'screening_passed')
            Control-->>Boundary: "Self-assessment screening completed successfully"
        end
    end

    Note over Pendonor,Boundary: Hanya lanjut jika screening_passed

    Pendonor->>+Boundary: Tekan "Konfirmasi Kesediaan"
    Boundary->>+Control: POST /api/donor/confirm {donor_candidate_id, status: confirmed}

    alt status kandidat bukan screening_passed
        Control-->>Boundary: error "Anda harus menyelesaikan skrining mandiri<br/>terlebih dahulu sebelum konfirmasi kesediaan."
    else screening_passed — masuk DB::transaction + lockForUpdate
        Control->>+BloodRequest: lockForUpdate()->find(blood_request_id)
        BloodRequest-->>-Control: $bloodRequest

        alt $bloodRequest->status !== 'open'
            Control-->>Boundary: error "Permintaan ini sudah tidak menerima<br/>konfirmasi baru (status saat ini bukan open)."
        else hitung kandidat confirmed+verified >= required_bags
            Control-->>Boundary: error "Kuota pendonor sudah penuh<br/>untuk permintaan ini"
        else kuota tersedia
            Control->>Control: $kode = DonorCandidate::generateVerificationCode()<br/>(6 karakter acak, retry jika bentrok unique index)
            Control->>DonorCandidate: update(status: 'confirmed', confirmed_at: now(),<br/>kode_verifikasi: $kode)
            Control->>BloodRequest: checkAndAutoFulfill()
            Control-->>-Boundary: 200 "Donor status updated successfully"<br/>{kode_verifikasi, expires_at: now()+120menit}
        end
    end

    Boundary-->>-Pendonor: Tampilkan halaman Tiket Digital (kode_verifikasi)
```
