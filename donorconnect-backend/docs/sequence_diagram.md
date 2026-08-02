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
