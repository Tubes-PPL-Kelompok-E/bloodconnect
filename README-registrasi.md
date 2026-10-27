# Tambahan: Registrasi Pendonor (database)

Salin isi folder ini ke project (Replace jika ditanya):
- app/Http/Controllers/Auth/RegisterController.php   (baru)
- app/Http/Requests/RegisterRequest.php               (baru)
- resources/views/auth/register.blade.php             (baru)
- resources/views/auth/login.blade.php                (diperbarui: link Daftar + pesan sukses)
- routes/web.php                                      (diperbarui: route /register)
- tests/Feature/RegisterTest.php                      (baru)

Tidak ada migrasi baru. Data tersimpan di tabel `users` (kolom name, email, instansi, password)
dan peran di tabel `model_has_roles`.

Cek: php artisan route:list --name=register
Uji: php artisan test --filter=RegisterTest
Coba: http://127.0.0.1:8000/register
