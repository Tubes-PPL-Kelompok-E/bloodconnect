@extends('layouts.bloodconnect')
@section('title', 'Detail Hasil Uji | BloodConnect')

@section('content')
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Detail Hasil Uji — {{ $result->bloodBag->bag_number }}</h1>
        <p class="text-xs text-gray-500 mt-1">Berita Acara: {{ $result->report_number }}</p>
    </div>
    <x-status-badge :status="$result->bloodBag->status" />
</div>

<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 bg-white rounded-xl border shadow-sm p-6">
        <h2 class="text-sm font-bold text-red-700 mb-3">Hasil Uji Saring</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            @foreach (\App\Models\ScreeningResult::TESTS as $key => $label)
                <div class="rounded-lg border p-3 text-center">
                    <p class="text-[11px] font-bold text-gray-500 uppercase mb-2">{{ $label }}</p>
                    <x-result-badge :value="$result->$key" />
                </div>
            @endforeach
        </div>

        <dl class="grid grid-cols-2 gap-y-3 text-sm">
            <dt class="text-gray-500">Golongan darah</dt><dd class="font-semibold">{{ $result->bloodBag->group_label }}</dd>
            <dt class="text-gray-500">Komponen</dt><dd class="font-semibold">{{ $result->bloodBag->component }}</dd>
            <dt class="text-gray-500">Tanggal uji</dt><dd class="font-semibold">{{ $result->tested_at->format('d M Y') }}</dd>
            <dt class="text-gray-500">Catatan</dt><dd>{{ $result->notes ?: '-' }}</dd>
            <dt class="text-gray-500">Berkas</dt>
            <dd>
                @if ($result->file_path)
                    <a href="{{ Storage::url($result->file_path) }}" target="_blank" class="text-red-700 underline">Lihat berkas</a>
                @else
                    -
                @endif
            </dd>
        </dl>

        @if ($result->corrected_at)
            <div class="mt-5 rounded-lg bg-yellow-50 border border-yellow-200 p-4 text-sm">
                <p class="font-bold text-yellow-800">Dikoreksi pada {{ $result->corrected_at->format('d M Y H:i') }}</p>
                <p class="text-yellow-900 mt-1">Alasan: {{ $result->correction_reason }}</p>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border shadow-sm p-6 h-fit">
        <h2 class="text-sm font-bold text-red-700 mb-3">Aksi</h2>
        <a href="{{ route('screenings.edit', $result) }}"
           class="block text-center bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold py-2.5 rounded-lg mb-2">Koreksi Hasil</a>
        <form action="{{ route('screenings.destroy', $result) }}" method="POST"
              onsubmit="return confirm('Hapus hasil uji ini? Status kantong akan kembali ke Karantina.')">
            @csrf @method('DELETE')
            <button class="w-full bg-red-600 hover:bg-red-700 text-white text-sm font-bold py-2.5 rounded-lg">Hapus (Salah Input)</button>
        </form>
        <a href="{{ route('screenings.index') }}" class="block text-center text-sm text-gray-500 mt-3">← Kembali</a>
    </div>
</div>
@endsection