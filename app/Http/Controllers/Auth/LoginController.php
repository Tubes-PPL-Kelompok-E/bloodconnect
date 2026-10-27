<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function show()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'role'     => ['required', 'in:admin_pmi,petugas_rs,pendonor'],
        ]);

        // Rate limiting per email + IP
        $key = Str::lower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $sisa = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$sisa} detik.",
            ]);
        }

        // Tolak akun nonaktif (soft deleted) dengan pesan yang jelas
        $trashed = User::onlyTrashed()->where('email', $data['email'])->first();
        if ($trashed && Hash::check($data['password'], $trashed->password)) {
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dinonaktifkan. Hubungi Admin PMI.',
            ]);
        }

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi salah.']);
        }

        // Peran pada tab yang dipilih harus sesuai dengan peran akun
        if (! Auth::user()->hasRole($data['role'])) {
            Auth::logout();
            $request->session()->invalidate();
            RateLimiter::hit($key, self::DECAY_SECONDS);
            throw ValidationException::withMessages(['email' => 'Akun ini tidak terdaftar pada peran yang dipilih.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->route(Auth::user()->homeRoute());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
