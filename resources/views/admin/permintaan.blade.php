@extends('layouts.app')
@section('content')
@php
    $tone = ['purple' => 'bg-purple-100 text-purple-700', 'green' => 'bg-green-100 text-green-700', 'amber' => 'bg-amber-100 text-amber-700'];
@endphp

<section class="rounded-xl border border-gray-200 bg-white p-5 mb-4 max-w-4xl">
    <h1 class="text-xl font-bold text-gray-900">Permintaan Darah Rumah Sakit &amp; Alokasi FEFO
        <span class="ml-2 align-middle rounded-full bg-red-50 border border-red-200 px-2 py-0.5 text-[10px] font-semibold text-red-600">Prioritas CITO Otomatis</span></h1>
    <p class="mt-2 text-xs text-gray-500">FR-15, FR-16, &amp; FR-17: Verifikasi permintaan RS, alokasi batch kedaluwarsa terdekat (FEFO), dan penugasan kurir cold chain.</p>
</section>

<div class="space-y-4 max-w-4xl">
@foreach ($requests as $r)
    <article class="rounded-xl border bg-white p-5 {{ $r['cito'] ? 'border-red-300' : 'border-gray-200' }}">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="rounded px-2 py-1 text-[10px] font-bold {{ $r['cito'] ? 'bg-red-600 text-white' : 'bg-gray-100 text-gray-700' }}">{{ $r['cito'] ? 'CITO / DARURAT' : 'RUTIN / TERENCANA' }}</span>
            <span class="font-bold">Req #{{ $r['id'] }}</span>
            <span class="text-gray-400">Diajukan: {{ $r['jam'] }}</span>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $tone[$r['tone']] }}">{{ $r['status'] }}</span>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
            <div class="space-y-2 text-xs">
                <p class="font-bold text-gray-800">{{ $r['rs'] }} • Pasien: <span class="text-red-600">{{ $r['pasien'] }}</span>
                    <span class="ml-2 font-normal text-gray-400">(No. RM: {{ $r['rm'] }})</span></p>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded border bg-gray-50 px-2 py-1">Dibutuhkan: {{ $r['butuh'] }}</span>
                    <span class="rounded border border-red-200 bg-red-50 px-2 py-1 font-semibold text-red-600">Golongan {{ $r['gol'] }}</span>
                    <span class="rounded border bg-gray-50 px-2 py-1 text-green-700">Stok UTD Siap (Ready): {{ $r['stok'] }}</span>
                </div>
                @if ($r['batch'])
                    <p class="rounded border bg-gray-50 px-3 py-2 text-gray-600">Batch Teralokasi FEFO: {{ $r['batch'] }}</p>
                    <p class="text-gray-600">Kurir: <b>{{ $r['kurir'] }}</b>
                        <span class="ml-3 text-green-700">Suhu Cold Box: {{ $r['suhu'] }}</span>
                        @if ($r['posisi'])<span class="ml-3 text-gray-400">Posisi: {{ $r['posisi'] }}</span>@endif</p>
                @endif
            </div>

            <div class="flex gap-2">
                @if ($r['aksi'] === 'jalan')
                    <span class="rounded-lg border border-purple-300 bg-purple-50 px-3 py-2 text-xs font-semibold text-purple-700">Sedang Dalam Perjalanan</span>
                @elseif ($r['aksi'] === 'selesai')
                    <span class="rounded-lg border border-green-300 bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">&#10003; Selesai Diterima RS</span>
                @else
                    <button class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Tolak</button>
                    <button class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">Validasi &amp; Alokasi FEFO</button>
                @endif
            </div>
        </div>
    </article>
@endforeach
</div>
@endsection
