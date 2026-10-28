<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public const ROLES = ['admin_pmi', 'petugas_rs', 'pendonor'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role'     => ['required', 'in:' . implode(',', self::ROLES)],
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            // Admin PMI dan Petugas RS wajib mengisi instansi, pendonor opsional
            'instansi' => ['required_if:role,admin_pmi,petugas_rs', 'nullable', 'string', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],

            // Pengaman: Admin PMI hanya bisa mendaftar dengan kode rahasia dari .env
            'kode_admin' => [
                'required_if:role,admin_pmi',
                'nullable',
                'string',
                function ($attribute, $value, $fail) {
                    if ($this->input('role') !== 'admin_pmi') {
                        return;
                    }
                    $kode = (string) config('bloodconnect.admin_register_code');
                    if ($kode === '') {
                        $fail('Registrasi Admin PMI sedang dinonaktifkan.');
                    } elseif (! hash_equals($kode, (string) $value)) {
                        $fail('Kode registrasi Admin PMI salah.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.in'                => 'Peran tidak valid untuk registrasi.',
            'instansi.required_if'   => 'Instansi wajib diisi untuk Admin PMI dan Petugas RS.',
            'kode_admin.required_if' => 'Kode registrasi Admin PMI wajib diisi.',
            'email.unique'           => 'Email sudah terdaftar. Silakan login.',
            'password.confirmed'     => 'Konfirmasi kata sandi tidak sama.',
        ];
    }
}
