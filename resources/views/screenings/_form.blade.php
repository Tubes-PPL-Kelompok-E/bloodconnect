@php
    $v = fn ($field, $default = null) => old($field, isset($result) ? $result->$field : $default);
    $tests = \App\Models\ScreeningResult::TESTS;
    $testedAt = old('tested_at', isset($result) ? $result->tested_at->toDateString() : now()->toDateString());
@endphp

<div class="grid md:grid-cols-2 gap-5">
    @if (! isset($result))
        <div class="md:col-span-2">
            <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Kantong (Karantina)</label>
            <select name="blood_bag_id" required class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">-- Pilih kantong --</option>
                @foreach ($bags as $b)
                    <option value="{{ $b->id }}" @selected(old('blood_bag_id', $selectedBag) == $b->id)>
                        {{ $b->bag_number }} — Gol. {{ $b->group_label }} — {{ $b->component }}
                    </option>
                @endforeach
            </select>
        </div>
    @else
        <div class="md:col-span-2 rounded-lg bg-red-50 border border-red-100 px-4 py-3 text-sm">
            Kantong: <b>{{ $result->bloodBag->bag_number }}</b> • Gol. {{ $result->bloodBag->group_label }}
        </div>
    @endif

    <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">No. Berita Acara</label>
        <input type="text" name="report_number" value="{{ $v('report_number') }}" required
               class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="BA/UTD/2026/001">
    </div>

    <div>
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Tanggal Uji</label>
        <input type="date" name="tested_at" max="{{ now()->toDateString() }}" value="{{ $testedAt }}"
               required class="w-full border rounded-lg px-3 py-2 text-sm">
    </div>

    <div class="md:col-span-2 grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ($tests as $key => $label)
            <div>
                <label class="block text-xs font-bold uppercase text-gray-600 mb-1">{{ $label }}</label>
                <select name="{{ $key }}" required data-test class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="non_reaktif" @selected($v($key, 'non_reaktif') === 'non_reaktif')>Non-Reaktif</option>
                    <option value="reaktif" @selected($v($key) === 'reaktif')>Reaktif</option>
                </select>
            </div>
        @endforeach
    </div>

    <div class="md:col-span-2">
        <div id="preview" class="rounded-lg px-4 py-3 text-sm font-semibold"></div>
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Unggah Berkas Berita Acara (PDF/JPG/PNG, maks 2 MB)</label>
        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm border rounded-lg p-2">
        @isset($result)
            @if ($result->file_path)
                <p class="text-xs text-gray-500 mt-1">Berkas saat ini:
                    <a class="text-red-700 underline" target="_blank" href="{{ Storage::url($result->file_path) }}">lihat berkas</a>
                    (unggah baru untuk mengganti)
                </p>
            @endif
        @endisset
    </div>

    <div class="md:col-span-2">
        <label class="block text-xs font-bold uppercase text-gray-600 mb-1">Catatan</label>
        <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ $v('notes') }}</textarea>
    </div>

    @isset($result)
        <div class="md:col-span-2">
            <label class="block text-xs font-bold uppercase text-red-700 mb-1">Alasan Koreksi (wajib)</label>
            <textarea name="correction_reason" rows="3" required
                      class="w-full border border-red-300 rounded-lg px-3 py-2 text-sm"
                      placeholder="Contoh: salah input HBsAg, hasil sebenarnya non-reaktif sesuai lembar kerja">{{ old('correction_reason') }}</textarea>
        </div>
    @endisset
</div>

<script>
    const selects = document.querySelectorAll('[data-test]');
    const preview = document.getElementById('preview');
    function refresh() {
        const reactive = [...selects].some(s => s.value === 'reaktif');
        preview.className = 'rounded-lg px-4 py-3 text-sm font-semibold ' +
            (reactive ? 'bg-red-50 text-red-700 border border-red-200'
                      : 'bg-green-50 text-green-700 border border-green-200');
        preview.textContent = reactive
            ? '⚠ Ada hasil reaktif → status kantong akan menjadi DITAHAN.'
            : '✔ Semua non-reaktif → status kantong akan menjadi READY.';
    }
    selects.forEach(s => s.addEventListener('change', refresh));
    refresh();
</script>