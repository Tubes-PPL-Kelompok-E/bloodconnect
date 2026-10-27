# BloodConnect - PBI Manajemen Akun & Autentikasi (Laravel 11/12 + Spatie Permission)

## 1. Persiapan project (terminal VS Code)
```bash
composer create-project laravel/laravel bloodconnect
cd bloodconnect
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
Buat database `bloodconnect` di MySQL/phpMyAdmin, lalu isi `.env` (lihat `env-snippet.txt`).

## 2. Salin file dari folder ini ke project
Timpa/tambahkan sesuai struktur: app/, bootstrap/app.php, database/, resources/, routes/web.php, tests/.
Hapus `tests/Feature/ExampleTest.php` bawaan jika error.

## 3. Jalankan
```bash
php artisan migrate
php artisan db:seed
php artisan serve
```
Buka http://127.0.0.1:8000/login

Akun contoh (kata sandi: `password123`):
- Admin PMI   : admin.pmi@bloodconnect.id
- Petugas RS  : petugas.rs@bloodconnect.id
- Pendonor    : pendonor@bloodconnect.id

Manajemen akun (khusus Admin PMI): http://127.0.0.1:8000/admin/users

## 4. Pengujian
Di `phpunit.xml`, aktifkan SQLite memori:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
Lalu jalankan: `php artisan test`
