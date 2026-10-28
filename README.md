# BloodConnect - Paket Lengkap (PBI-01, PBI-02, Dashboard Admin, Kantong Darah)

Isi zip ini adalah FILE TAMBAHAN untuk project Laravel 11/12 (bukan project lengkap).

## 1. Persiapan project
```powershell
composer create-project laravel/laravel bloodconnect
cd bloodconnect
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```
Jika project sudah ada (hasil clone): `composer install`, `copy .env.example .env`, `php artisan key:generate`.

## 2. Salin isi folder ini ke project (Replace jika ditanya)
app/, bootstrap/app.php, config/bloodconnect.php, database/, resources/, routes/web.php, tests/
(README.md dan env-snippet.txt hanya untuk dibaca. Commit dulu pekerjaan Anda sebelum Replace.)

## 3. Database dan .env
Nyalakan MySQL (XAMPP), buat database `bloodconnect`, isi .env sesuai env-snippet.txt.
Tambahkan juga: ADMIN_REGISTER_CODE=kode-rahasia-anda
(Jika dikosongkan di lingkungan lokal, kode bawaan: PMI-ADMIN-2026. Ganti sebelum dipublikasikan.)

## 4. Jalankan
```powershell
php artisan migrate
php artisan db:seed
php artisan config:clear
php artisan test
php artisan serve
```
Akun contoh (kata sandi `password123`): admin.pmi@bloodconnect.id, petugas.rs@bloodconnect.id, pendonor@bloodconnect.id

## Halaman
- /login, /register, /forgot-password
- /admin/dashboard, /admin/stok-darah, /admin/permintaan-rs, /admin/laporan, /admin/kegiatan-donor
- /admin/users (PBI-01), /admin/instansi (PBI-02)
- /admin/kantong-darah/create, /admin/uji-saring

## Catatan
- Dashboard, Permintaan RS, dan Laporan masih data contoh.
- Stok Darah, Input Kantong, dan Uji Saring memakai database (tabel kantong_darah).
- Berkas berita acara disimpan di storage/app/private/berita-acara.
