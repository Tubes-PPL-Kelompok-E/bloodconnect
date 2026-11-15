@extends('layouts.app')
@section('title', 'Detail Pemusnahan')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-extrabold">Detail Pemusnahan — {{ $pemusnahan->kantong->kode_batch }}</h1>
    <p class="text-xs text-gray-500 mt-1">Dicatat {{ $pemusnahan->created_at->format('d M Y H:i') }}</p>
</div>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 bg-white rounded-xl border shadow-sm p-6">
        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-gray-500">Golongan darah</dt><dd class="font-semibold">{{ $pemusnahan->kantong->golonganLengkap() }}</dd>
            <dt class="text-gray-500">Komponen</dt><dd class="font-semibold">{{ strtoupper($pemusnahan->kantong->komponen) }}</dd>
            <dt class="text-gray-500">Tanggal kedaluwarsa</dt><dd class="font-semibold">{{ $pemusnahan->kantong->tanggal_kedaluwarsa->format('d M Y') }}</dd>
            <dt class="text-gray-500">Tanggal pemusnahan</dt><dd class="font-semibold">{{ $pemusnahan->tanggal_pemusnahan->format('d M Y') }}</dd>
            <dt class="text-gray-500">Petugas</dt><dd class="font-semibold">{{ $pemusnahan->nama_petugas }}</dd>
            <dt class="text-gray-500">Status sebelumnya</dt><dd>{{ \App\Models\KantongDarah::STATUS[$pemusnahan->status_sebelumnya] ?? $pemusnahan->status_sebelumnya }}</dd>
            <dt class="text-gray-500">Alasan</dt><dd>{{ $pemusnahan->alasan }}</dd>
        </dl>

        @if ($pemusnahan->sebelum_kedaluwarsa)
            <div class="mt-5 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm">
                <p class="font-bold text-yellow-800">Dimusnahkan sebelum kedaluwarsa</p>
                <p class="text-yellow-900 mt-1">Alasan khusus: {{ $pemusnahan->alasan_khusus }}</p>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-red-700 mb-3">Aksi</h2>
        <a href="{{ route('admin.pemusnahan.edit', $pemusnahan) }}" class="block text-center bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold py-2.5 rounded-lg mb-2">Ubah Catatan</a>
        <form action="{{ route('admin.pemusnahan.destroy', $pemusnahan) }}" method="POST"
              onsubmit="return confirm('Batalkan catatan pemusnahan ini? Status kantong akan dikembalikan.')">
            @csrf @method('DELETE')
            <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2.5 rounded-lg">Batalkan (Salah Input)</button>
        </form>
        <a href="{{ route('admin.pemusnahan') }}" class="block text-center text-sm text-gray-500 mt-3">← Kembali</a>
    </div>
</div>
@endsection