@extends('layouts.app')
@section('content')
<h1 class="text-2xl font-bold mb-4">Tambah Akun</h1>
<form method="POST" action="{{ route('users.store') }}" class="bg-white rounded shadow p-6 space-y-4 max-w-lg">
    @csrf
    @include('users._fields')
    <div>
        <label class="block text-sm font-semibold mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">Kata Sandi</label>
        <input type="password" name="password" required class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">Konfirmasi Kata Sandi</label>
        <input type="password" name="password_confirmation" required class="w-full border rounded px-3 py-2 text-sm">
    </div>
    <button class="bg-red-600 text-white font-semibold rounded px-4 py-2 text-sm">Simpan akun</button>
    <a href="{{ route('users.index') }}" class="text-sm text-gray-600 ml-2">Batal</a>
</form>
@endsection
