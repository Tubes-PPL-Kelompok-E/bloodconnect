# BloodConnect - PBI-01 Manajemen Akun & Autentikasi (Laravel 11/12 + Spatie Permission)

Isi zip ini adalah file tambahan untuk project Laravel (bukan project lengkap).

## 1. Persiapan project (terminal VS Code)
```powershell
composer create-project laravel/laravel bloodconnect
cd bloodconnect
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
Jika project sudah ada (hasil clone): `composer install`, `copy .env.example .env`, `php artisan key:generate`.

## 2. Salin isi folder ini ke project (Replace jika ditanya)
app/, bootstrap/app.php, database/, resources/, routes/web.php, tests/
(README.md dan env-snippet.txt hanya untuk dibaca)

## 3. Database
Nyalakan MySQL di XAMPP, buat database `bloodconnect`, lalu isi `.env` (lihat env-snippet.txt).

## 4. Jalankan
```powershell
php artisan migrate
php artisan db:seed
php artisan serve
```
- Login      : http://127.0.0.1:8000/login
- Registrasi : http://127.0.0.1:8000/register   (Admin PMI dengan kode / Petugas RS / Pendonor)
- Kelola akun: http://127.0.0.1:8000/admin/users (khusus Admin PMI)

Akun contoh (kata sandi `password123`):
- Admin PMI  : admin.pmi@bloodconnect.id
- Petugas RS : petugas.rs@bloodconnect.id
- Pendonor   : pendonor@bloodconnect.id

## 5. Pengujian
Di `phpunit.xml` aktifkan SQLite memori:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```
Lalu: `php artisan test`

## Pemetaan PBI
- PBI-01.2 migrasi users/roles + Spatie 3 peran : database/, app/Models/User.php, bootstrap/app.php
- PBI-01.3 login, rate limiting, redirect peran : Auth/LoginController, auth/login.blade.php, routes/web.php
- PBI-01.4 [C] simpan akun baru                 : UserController@store, StoreUserRequest, RegisterController, RegisterRequest
- PBI-01.5 [R] daftar akun                      : UserController@index, users/index.blade.php
- PBI-01.6 [U] ubah peran/instansi/reset sandi  : UserController@update/resetPassword
- PBI-01.7 [D] soft delete + tolak login nonaktif: UserController@destroy, LoginController
- PBI-01.8 pengujian                            : tests/Feature (AuthTest, UserCrudTest, RegisterTest)

## Registrasi Admin PMI
Tab Admin PMI di halaman registrasi meminta "Kode Registrasi Admin".
Tambahkan di file .env (jangan di-commit):
```
ADMIN_REGISTER_CODE=PMI-ADMIN-2026
```
Lalu `php artisan config:clear`. Jika ADMIN_REGISTER_CODE kosong, registrasi Admin PMI dinonaktifkan.
