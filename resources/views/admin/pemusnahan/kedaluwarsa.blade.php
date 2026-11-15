@extends('layouts.app')
@section('title', 'Kantong Kedaluwarsa')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Kantong Kedaluwarsa</h1>
        <p class="text-xs text-gray-500 mt-1">Kantong yang melewati tanggal kedaluwarsa otomatis keluar dari stok siap</p>
    </div>
    <div class="flex gap-2">
        <form action="{{ route('admin.kedaluwarsa.periksa') }}" method="POST">
            @csrf
            <button class="border border-red-600 text-red-700 hover:bg-red-50 text-xs font-bold px-4 py-2.5 rounded-lg">↻ Cek Kedaluwarsa Sekarang</button>
        </form>
        <a href="{{ route('admin.pemusnahan') }}" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg">Riwayat Pemusnahan</a>
    </div>
</div>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl border-l-4 border-orange-500 bg-orange-50 p-5">
        <p class="text-[11px] font-bold text-orange-700 uppercase">Menunggu dimusnahkan</p>
        <p class="text-3xl font-extrabold text-orange-700 mt-2">{{ $kantongs->total() }}</p>
    </div>
    <div class="rounded-xl border-l-4 border-gray-500 bg-gray-50 p-5">
        <p class="text-[11px] font-bold text-gray-700 uppercase">Total dimusnahkan</p>
        <p class="text-3xl font-extrabold text-gray-700 mt-2">{{ $totalDimusnahkan }}</p>
    </div>
</div>

<div class="bg-white rounded-xl border shadow-sm p-5 overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-[11px] uppercase text-gray-500 border-b">
                <th class="py-2">Kode Batch</th><th>Gol.</th><th>Komponen</th>
                <th>Tgl Donor</th><th>Tgl Kedaluwarsa</th><th>Lewat</th><th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($kantongs as $k)
            <tr class="border-b last:border-0 hover:bg-red-50/40">
                <td class="py-3 font-semibold">{{ $k->kode_batch }}</td>
                <td>{{ $k->golonganLengkap() }}</td>
                <td>{{ strtoupper($k->komponen) }}</td>
                <td>{{ $k->tanggal_donor->format('d/m/Y') }}</td>
                <td class="text-red-700 font-semibold">{{ $k->tanggal_kedaluwarsa->format('d/m/Y') }}</td>
                <td>{{ abs($k->sisaHari()) }} hari</td>
                <td class="text-right">
                    <a href="{{ route('admin.pemusnahan.create', ['kantong_id' => $k->id]) }}"
                       class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg">Musnahkan</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="py-10 text-center text-gray-400">Tidak ada kantong kedaluwarsa.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="mt-4">{{ $kantongs->links() }}</div>
</div>
@endsection