@extends('layouts.app')
@section('content')
    <h1 class="text-2xl font-bold text-gray-800">{{ $title }}</h1>
    <p class="text-gray-600 mt-2">Selamat datang, {{ auth()->user()->name }}. Anda login sebagai
        <b>{{ auth()->user()->getRoleNames()->first() }}</b> ({{ auth()->user()->instansi }}).</p>
@endsection
