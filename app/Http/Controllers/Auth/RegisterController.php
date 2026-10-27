<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        // Data disimpan ke tabel users (kata sandi di-hash otomatis lewat cast 'hashed')
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'instansi' => $request->instansi ?: 'Umum',
            'password' => $request->password,
        ]);

        // Peran dipaksa pendonor, tidak bisa dipilih pendaftar
        $user->assignRole('pendonor');

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('pendonor.dashboard')
            ->with('success', 'Registrasi berhasil. Selamat datang, ' . $user->name . '!');
    }
}
