@extends('layouts.app')
@section('title', 'Ubah Catatan Pemusnahan')

@section('content')
<h1 class="text-2xl font-extrabold mb-6">Ubah Catatan Pemusnahan</h1>
<form action="{{ route('admin.pemusnahan.update', $pemusnahan) }}" method="POST" class="bg-white rounded-xl border shadow-sm p-6">
    @csrf @method('PUT')
    @include('admin.pemusnahan._form')
    <div class="mt-6 flex gap-2">
        <button class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2.5 rounded-lg">Simpan Perubahan</button>
        <a href="{{ route('admin.pemusnahan.show', $pemusnahan) }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600">Batal</a>
    </div>
</form>
@endsection