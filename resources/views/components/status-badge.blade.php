@props(['status'])
@php
    $map = [
        'ready' => ['Ready', 'bg-blue-100 text-blue-700'],
        'ditahan' => ['Ditahan', 'bg-red-100 text-red-700'],
        'karantina' => ['Karantina', 'bg-yellow-100 text-yellow-700'],
    ];
    [$label, $cls] = $map[$status] ?? [$status, 'bg-gray-100 text-gray-700'];
@endphp
<span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $cls }}">{{ $label }}</span>