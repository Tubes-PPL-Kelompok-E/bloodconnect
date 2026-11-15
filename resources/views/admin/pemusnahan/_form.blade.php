@php
    $tanggal = old('tanggal_pemusnahan', isset($pemusnahan) ? $pemusnahan->tanggal_pemusnahan->toDateString() : now()->toDateString());
    $alasan = old('alasan', $pemusnahan->alasan ?? '');
    $khusus = old('alasan_khusus', $pemusnahan->alasan_khusus ?? '');
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
        <ul class="list-disc ml-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<div class="grid md:grid-cols-2 gap-5">
    @if (! isset($pemusnahan))
        <div class="md:col-span-2">
            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Kantong</label>
            <select name="kantong_id" id="kantong" required class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih kantong --</option>
                @foreach ($kantongs as $k)
                    <option value="{{ $k->id }}" data-kedaluwarsa="{{ $k->tanggal_kedaluwarsa->toDateString() }}"
                            @selected(old('kantong_id', $dipilih) == $k->id)>
                        {{ $k->kode_batch }} — {{ $k->golonganLengkap() }} — exp {{ $k->tanggal_kedaluwarsa->format('d/m/Y') }}
                        ({{ \App\Models\KantongDarah::STATUS[$k->status] ?? $k->status }})
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <div id="info-kantong" data-kedaluwarsa="{{ $pemusnahan->kantong->tanggal_kedaluwarsa->toDateString() }}"
             class="md:col-span-2 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm">
            Kantong <b>{{ $pemusnahan->kantong->kode_batch }}</b> • {{ $pemusnahan->kantong->golonganLengkap() }}
            • Kedaluwarsa {{ $pemusnahan->kantong->tanggal_kedaluwarsa->format('d/m/Y') }}
        </div>
    @endif

    <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Tanggal Pemusnahan</label>
        <input type="date" name="tanggal_pemusnahan" id="tanggal" max="{{ now()->toDateString() }}"
               value="{{ $tanggal }}" required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>

    <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Petugas</label>
        <input type="text" value="{{ $pemusnahan->nama_petugas ?? auth()->user()->name }}" disabled
               class="w-full border rounded-lg px-3 py-2 text-sm bg-gray-100">
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Alasan Pemusnahan</label>
        <textarea name="alasan" rows="2" required class="w-full border rounded-lg px-3 py-2 text-sm"
                  placeholder="Contoh: melewati tanggal kedaluwarsa">{{ $alasan }}</textarea>
    </div>

    <div class="md:col-span-2" id="kotak-khusus" style="display:none">
        <div class="rounded-lg border border-yellow-300 bg-yellow-50 p-4">
            <p class="text-sm font-semibold text-yellow-800 mb-2">⚠ Kantong belum kedaluwarsa pada tanggal pemusnahan. Alasan khusus wajib diisi.</p>
            <textarea name="alasan_khusus" id="alasan_khusus" rows="3"
                      class="w-full border border-yellow-300 rounded-lg px-3 py-2 text-sm"
                      placeholder="Contoh: kantong bocor, suhu penyimpanan menyimpang">{{ $khusus }}</textarea>
        </div>
    </div>
</div>

<script>
    const sel = document.getElementById('kantong');
    const info = document.getElementById('info-kantong');
    const tgl = document.getElementById('tanggal');
    const kotak = document.getElementById('kotak-khusus');
    const khusus = document.getElementById('alasan_khusus');

    function kedaluwarsaKantong() {
        if (info) return info.dataset.kedaluwarsa;
        const o = sel && sel.options[sel.selectedIndex];
        return o ? o.dataset.kedaluwarsa : null;
    }
    function segarkan() {
        const exp = kedaluwarsaKantong();
        const dini = exp && tgl.value && exp >= tgl.value;
        kotak.style.display = dini ? 'block' : 'none';
        khusus.required = !!dini;
    }
    if (sel) sel.addEventListener('change', segarkan);
    tgl.addEventListener('change', segarkan);
    segarkan();
</script>