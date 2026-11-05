@extends('layouts.bloodconnect')
@section('title', 'Input Hasil Uji | BloodConnect')

@section('content')
<h1 class="text-2xl font-extrabold mb-6">Input Hasil Uji Saring</h1>
<form action="{{ route('screenings.store') }}" method="POST" enctype="multipart/form-data"
      class="bg-white rounded-xl border shadow-sm p-6">
    @csrf
    @include('screenings._form')
    <div class="mt-6 flex gap-2">
        <button class="bg-red-600 hover:bg-red-700 text-white text-sm font-bold px-5 py-2.5 rounded-lg">Simpan</button>
        <a href="{{ route('screenings.index') }}" class="px-5 py-2.5 text-sm font-semibold text-gray-600">Batal</a>
    </div>
</form>
@endsection