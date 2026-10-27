@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">Edit Akun: {{ $user->email }}</h1>

<form method="POST" action="{{ route('users.update', $user) }}" class="bg-white rounded shadow p-6 space-y-4 max-w-lg mb-6">
    @csrf @method('PUT')
    @include('users._fields')
    <button class="bg-red-600 text-white font-semibold rounded px-4 py-2 text-sm">Simpan perubahan</button>
    <a href="{{ route('users.index') }}" class="text-sm text-gray-600 ml-2">Batal</a>
</form>

<form method="POST" action="{{ route('users.reset-password', $user) }}" class="bg-white rounded shadow p-6 space-y-4 max-w-lg">
    @csrf @method('PUT')
    <h2 class="font-bold">Reset kata sandi</h2>
    <input type="password" name="password" placeholder="Kata sandi baru" required class="w-full border rounded px-3 py-2 text-sm">
    <input type="password" name="password_confirmation" placeholder="Konfirmasi kata sandi" required class="w-full border rounded px-3 py-2 text-sm">
    <button class="bg-gray-800 text-white font-semibold rounded px-4 py-2 text-sm">Reset kata sandi</button>
</form>
@endsection
