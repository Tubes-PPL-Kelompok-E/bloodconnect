@extends('layouts.app')
@section('title', 'Riwayat Pemusnahan')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Riwayat Pemusnahan Kantong</h1>
        <p class="text-xs text-gray-500 mt-1">Catatan tanggal, alasan, dan petugas pemusnahan</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.kedaluwarsa') }}" class="border border-red-600 text-red-700 hover:bg-red-50 text-xs font-bold px-4 py-2.5 rounded-lg">Daftar Kedaluwarsa</a>
        <a href="{{ route('admin.pemusnahan.create') }}" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg">+ Catat Pemusnahan</a>
    </div>
</div>

@if (session('success'))
    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white rounded-xl border shadow-sm p-5">
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode batch / petugas / alasan" class="border rounded-lg px-3 py-2 text-sm w-64">
        <select name="filter" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">Semua</option>
            <option value="dini" @selected(request('filter') === 'dini')>Sebelum kedaluwarsa</option>
        </select>
        <button class="bg-red-600 text-white text-sm font-semibold px-4 rounded-lg">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase text-gray-500 border-b">
                    <th class="py-2">Tgl Musnah</th><th>Kode Batch</th><th>Gol.</th><th>Alasan</th>
                    <th>Petugas</th><th>Keterangan</th><th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($riwayat as $r)
                <tr class="border-b last:border-0 hover:bg-red-50/40">
                    <td class="py-3">{{ $r->tanggal_pemusnahan->format('d/m/Y') }}</td>
                    <td class="font-semibold">{{ $r->kantong->kode_batch }}</td>
                    <td>{{ $r->kantong->golonganLengkap() }}</td>
                    <td class="max-w-xs truncate">{{ $r->alasan }}</td>
                    <td>{{ $r->nama_petugas }}</td>
                    <td>
                        @if ($r->sebelum_kedaluwarsa)
                            <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-yellow-100 text-yellow-800">Sebelum kedaluwarsa</span>
                        @else
                            <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-700">Kedaluwarsa</span>
                        @endif
                    </td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('admin.pemusnahan.show', $r) }}" class="text-red-700 font-semibold hover:underline">Detail</a>
                        <a href="{{ route('admin.pemusnahan.edit', $r) }}" class="ml-2 text-orange-600 font-semibold hover:underline">Ubah</a>
                        <form action="{{ route('admin.pemusnahan.destroy', $r) }}" method="POST" class="inline"
                              onsubmit="return confirm('Batalkan catatan pemusnahan ini? Status kantong akan dikembalikan.')">
                            @csrf @method('DELETE')
                            <button class="ml-2 text-gray-500 font-semibold hover:text-red-700">Batalkan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-gray-400">Belum ada riwayat pemusnahan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $riwayat->links() }}</div>
</div>
@endsection