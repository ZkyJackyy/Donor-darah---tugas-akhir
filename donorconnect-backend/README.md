# DonorConnect Full Stack Application

## Backend Architecture (Laravel 11)

### Environment Prerequisites
- PHP ^8.3
- Composer 2+ 
- Docker Engine & Docker Compose (Production Strategy)
- MySQL 8.0 & Redis 7.0

### Setup Local Development
1. `composer install`
2. `php -r "file_exists('.env') || copy('.env.example', '.env');"`
3. `php artisan key:generate`
4. Setup `DB_CONNECTION=sqlite` for easy local dev, OR connect MySQL bounds.
5. Setup `REDIS_HOST=127.0.0.1`.
6. `php artisan migrate --seed`
7. Start standard HTTP instance: `php artisan serve`
8. Start Scheduler: `php artisan schedule:work`
9. Start Fonnte WA Background Worker: `php artisan queue:work`

### Docker Production Setup
All infrastructure is neatly orchestrated out of the box using compose definitions:
1. `docker-compose build`
2. `docker-compose up -d` 
*(This boots Alpine NGINX on port 8000, MySQL on 3306, a daemonized Queue Worker, an automated App Scheduler wrapper, and Redis caching!)*

---

## Frontend Engine (Flutter 3.x)

### Requirements
Ensure Android Studio / Xcode are compiled and flutter is available on the `$PATH`.

### Local Setup
1. `flutter pub get` *(If on Windows lacking Dev Mode, manually run as Administrator to enforce symlink generations)*
2. Target your simulator: `flutter run --dart-define=ENV=dev` (Targets host loopback `10.0.2.2:8000`).

### Production Flag Deployments
Simply inject the `prod` config flag to redirect deep API mappings natively bypassing code commits:
`flutter build apk --dart-define=ENV=prod`
