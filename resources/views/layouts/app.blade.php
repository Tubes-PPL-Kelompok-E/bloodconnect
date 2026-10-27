<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'BloodConnect' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
<nav class="bg-red-700 text-white px-6 py-3 flex items-center justify-between">
    <div class="flex items-center gap-6">
        <a href="/" class="font-bold">BloodConnect</a>
        @role('admin_pmi')
            <a href="{{ route('users.index') }}" class="text-sm hover:underline">Manajemen Akun</a>
        @endrole
    </div>
    <div class="flex items-center gap-3 text-sm">
        <span>{{ auth()->user()->name }} ({{ auth()->user()->getRoleNames()->first() }})</span>
        <form method="POST" action="{{ route('logout') }}">@csrf
            <button class="bg-white text-red-700 rounded px-3 py-1 font-semibold">Keluar</button>
        </form>
    </div>
</nav>
<main class="max-w-5xl mx-auto p-6">
    @if (session('success'))
        <div class="mb-4 rounded bg-green-50 border border-green-200 text-green-700 text-sm p-3">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded bg-red-50 border border-red-200 text-red-700 text-sm p-3">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
