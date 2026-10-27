<div>
    <label class="block text-sm font-semibold mb-1">Nama</label>
    <input name="name" value="{{ old('name', $user->name ?? '') }}" required class="w-full border rounded px-3 py-2 text-sm">
</div>
<div>
    <label class="block text-sm font-semibold mb-1">Peran</label>
    <select name="role" class="w-full border rounded px-3 py-2 text-sm">
        @foreach (['admin_pmi' => 'Admin PMI', 'petugas_rs' => 'Petugas RS', 'pendonor' => 'Pendonor'] as $v => $l)
            <option value="{{ $v }}" @selected(old('role', isset($user) ? $user->getRoleNames()->first() : '') === $v)>{{ $l }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="block text-sm font-semibold mb-1">Instansi</label>
    <input name="instansi" value="{{ old('instansi', $user->instansi ?? '') }}" required class="w-full border rounded px-3 py-2 text-sm">
</div>
