@extends('layouts.bloodconnect')
@section('title', 'Uji Saring | BloodConnect')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold">Hasil Uji Saring &amp; Berita Acara</h1>
        <p class="text-xs text-gray-500 mt-1">Unit Transfusi Darah (UTD) PMI Kota • Hanya kantong non-reaktif yang berstatus Ready</p>
    </div>
    <a href="{{ route('screenings.create') }}"
       class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow">
        + Input Hasil Uji
    </a>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="rounded-xl border-l-4 border-blue-500 bg-blue-50 p-5">
        <p class="text-[11px] font-bold text-blue-700 uppercase">Kantong menunggu uji (Karantina)</p>
        <p class="text-3xl font-extrabold text-blue-700 mt-2">{{ $pendingCount }}</p>
    </div>
    <div class="rounded-xl border-l-4 border-red-500 bg-red-50 p-5">
        <p class="text-[11px] font-bold text-red-700 uppercase">Hasil uji tercatat</p>
        <p class="text-3xl font-extrabold text-red-700 mt-2">{{ $results->total() }}</p>
    </div>
</div>

<div class="bg-white rounded-xl border shadow-sm p-5">
    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari no. kantong / berita acara"
               class="border rounded-lg px-3 py-2 text-sm w-64 focus:ring-red-500 focus:border-red-500">
        <select name="status" class="border rounded-lg px-3 py-2 text-sm">
            <option value="">Semua status</option>
            <option value="ready" @selected(request('status') === 'ready')>Ready</option>
            <option value="ditahan" @selected(request('status') === 'ditahan')>Ditahan</option>
        </select>
        <button class="bg-red-600 text-white text-sm font-semibold px-4 rounded-lg">Cari</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[11px] uppercase text-gray-500 border-b">
                    <th class="py-2">Kantong</th>
                    <th>Gol.</th>
                    <th>No. Berita Acara</th>
                    @foreach (\App\Models\ScreeningResult::TESTS as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th>Tgl Uji</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($results as $r)
                <tr class="border-b last:border-0 hover:bg-red-50/40">
                    <td class="py-3 font-semibold">{{ $r->bloodBag->bag_number }}</td>
                    <td>{{ $r->bloodBag->group_label }}</td>
                    <td>{{ $r->report_number }}</td>
                    @foreach (array_keys(\App\Models\ScreeningResult::TESTS) as $t)
                        <td><x-result-badge :value="$r->$t" /></td>
                    @endforeach
                    <td>{{ $r->tested_at->format('d/m/Y') }}</td>
                    <td><x-status-badge :status="$r->bloodBag->status" /></td>
                    <td class="text-right whitespace-nowrap">
                        <a href="{{ route('screenings.show', $r) }}" class="text-red-700 font-semibold hover:underline">Detail</a>
                        <a href="{{ route('screenings.edit', $r) }}" class="ml-2 text-orange-600 font-semibold hover:underline">Koreksi</a>
                        <form action="{{ route('screenings.destroy', $r) }}" method="POST" class="inline"
                              onsubmit="return confirm('Hapus hasil uji ini? Status kantong akan kembali ke Karantina.')">
                            @csrf @method('DELETE')
                            <button class="ml-2 text-gray-500 font-semibold hover:text-red-700">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="py-10 text-center text-gray-400">Belum ada hasil uji saring.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $results->links() }}</div>
</div>
@endsection