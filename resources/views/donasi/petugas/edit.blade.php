@extends('donasi.layout')
@section('title', 'Koreksi Data Donasi')

@section('content')
<div class="card" style="padding:16px">
    @include('donasi.petugas._form', [
        'action' => route('petugas.donasi.update', $donation),
        'method' => 'PUT',
        'donation' => $donation,
    ])
</div>
@endsection