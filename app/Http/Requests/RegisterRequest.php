<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Peran TIDAK diterima dari input: pendaftar publik selalu "pendonor"
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:150', 'unique:users,email'],
            'instansi' => ['nullable', 'string', 'max:150'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'       => 'Email sudah terdaftar. Silakan login.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
    }
}
