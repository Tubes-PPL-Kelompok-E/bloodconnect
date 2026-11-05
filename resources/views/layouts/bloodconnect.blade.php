<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'BloodConnect')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif}</style>
</head>
<body class="bg-gray-100 text-gray-800">
    <nav class="bg-red-700 text-white shadow">
        <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
            <div class="flex items-center gap-8">
                <a href="{{ route('screenings.index') }}" class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-full bg-white flex items-center justify-center text-red-700 text-lg">🩸</span>
                    <span class="leading-tight">
                        <span class="block font-bold">BloodConnect</span>
                        <span class="block text-[10px] opacity-80">Supply Chain System</span>
                    </span>
                </a>
                @php
                    $links = [
                        ['Dashboard', '#', false],
                        ['Stok Darah', '#', false],
                        ['Uji Saring', route('screenings.index'), request()->routeIs('screenings.*')],
                        ['Permintaan RS', '#', false],
                        ['Laporan', '#', false],
                        ['Kegiatan Donor', '#', false],
                    ];
                @endphp
                <div class="hidden md:flex items-center gap-1 text-sm font-medium">
                    @foreach ($links as [$label, $href, $active])
                        <a href="{{ $href }}"
                           class="px-4 py-1.5 rounded-full {{ $active ? 'bg-red-900/60 ring-1 ring-white/30' : 'hover:bg-red-800' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="bg-red-900/70 px-4 py-1.5 rounded-lg text-sm">👤 Petugas Bank Darah</div>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        @if (session('success'))
            <div class="mb-5 rounded-lg border border-green-200 bg-green-50 text-green-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 text-red-800 px-4 py-3 text-sm">
                <p class="font-semibold mb-1">Periksa kembali isian Anda:</p>
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>