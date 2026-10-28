<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors([
                'email' => 'Mohon tunggu 60 detik sebelum meminta tautan lagi.',
            ]);
        }

        // Pesan sama untuk email terdaftar maupun tidak
        return back()->with('status', 'Jika email terdaftar, tautan reset kata sandi telah dikirim. Periksa kotak masuk Anda.');
    }
}