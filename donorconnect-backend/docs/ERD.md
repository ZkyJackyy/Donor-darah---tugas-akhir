# DonorConnect ERD Definition

## 1. users
The core table containing all actors (Admins & Donors).
- **id**: Primary Key, Unsigned BigInt
- **name**: String
- **email**: String
- **password**: String
- **role**: Enum ('admin', 'user') - Controls System UI mappings.
- **phone**: String
- **blood_type**: Enum ('A', 'B', 'AB', 'O')
- **rhesus**: Enum ('+', '-')
- **weight**: Decimal (Math locks trigger if < 45)
- **birth_date**: Date (Math locks trigger if < 17yo)
- **latitude, longitude**: Decimals (Bounded to Indonesia matrix)
- **last_donor_date**: Date (Haversine queries lock if datediff < 60)
- **is_available**: Boolean

## 2. blood_requests
Created by Admins marking an open emergency.
- **id**: Primary Key
- **admin_id**: Foreign Key mapping to `users.id`
- **hospital_name**: String
- **blood_type, rhesus**: Enum (Stringent candidate matchers)
- **urgency_level**: Enum ('normal', 'urgent', 'critical')
- **required_bags**: Integer
- **latitude, longitude**: Geometry center for Haversine matching
- **status**: Enum ('open', 'fulfilled', 'cancelled')

## 3. donor_candidates
The bridge linking requested emergencies against verified nearby users.
- **id**: Primary Key
- **blood_request_id**: Foreign Key -> `blood_requests.id`
- **user_id**: Foreign Key -> `users.id`
- **distance_km**: Float (Calculated at API generation)
- **status**: Enum ('pending', 'notified', 'confirmed', 'declined', 'verified', 'no_response')
- **qr_token**: String (HMAC-Sha256 secure signed verification code)
- **notified_at, confirmed_at, verified_at**: Timestamps

## 4. donor_histories
A permanent, un-editable ledger confirming finalized physical donations.
- **id**: Primary Key
- **user_id**: Foreign Key -> `users.id`
- **blood_request_id**: Foreign Key -> `blood_requests.id`
- **donor_date**: Date
- **verified_by**: Foreign Key -> `users.id` (The admin who ran the check)
