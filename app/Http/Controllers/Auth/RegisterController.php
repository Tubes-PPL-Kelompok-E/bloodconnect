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
        // Disimpan ke tabel users (kata sandi di-hash otomatis lewat cast 'hashed')
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'instansi' => $request->instansi ?: 'Umum',
            'password' => $request->password,
        ]);

        // Peran tersimpan di tabel model_has_roles; hanya petugas_rs / pendonor yang lolos validasi
        $user->assignRole($request->role);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->homeRoute())
            ->with('success', 'Registrasi berhasil. Selamat datang, ' . $user->name . '!');
    }
}
