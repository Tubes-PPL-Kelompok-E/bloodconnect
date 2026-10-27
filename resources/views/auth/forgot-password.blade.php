<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Kata Sandi - BloodConnect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',system-ui,sans-serif}</style>
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background:linear-gradient(135deg,#8b1a1a 0%,#b91c1c 55%,#1a1a2e 100%)">

<div class="w-full max-w-sm bg-white rounded-2xl shadow-2xl overflow-hidden">
    <div class="bg-red-700 text-white text-center py-7 px-6">
        <div class="mx-auto mb-3 w-10 h-10 rounded-full bg-white text-red-700 flex items-center justify-center">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5c3.2 4.2 6 7.4 6 11a6 6 0 0 1-12 0c0-3.6 2.8-6.8 6-11z"/></svg>
        </div>
        <h1 class="text-xl font-bold">Lupa Kata Sandi</h1>
        <p class="text-xs text-red-100 mt-1">Kami kirim tautan untuk membuat kata sandi baru</p>
    </div>

    <form method="POST" action="{{ route('password.email') }}" class="p-6 space-y-4">
        @csrf

        @if (session('status'))
            <div class="rounded-lg bg-green-50 border border-green-200 text-green-700 text-xs p-3">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs p-3">{{ $errors->first() }}</div>
        @endif

        <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   placeholder="nama@bloodconnect.id"
                   class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
        </div>

        <button class="w-full rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-semibold py-2.5">
            Kirim Tautan Reset &rarr;
        </button>

        <p class="text-center text-xs text-gray-500">
            Ingat kata sandi? <a href="{{ route('login') }}" class="font-semibold text-red-600">Kembali ke Login</a>
        </p>
    </form>
    <div class="bg-gray-50 text-center text-[10px] text-gray-400 py-3">Mendukung Target SDG 3: Good Health and Well-being</div>
</div>
</body>
</html>
