@props(['value'])
@if ($value === 'reaktif')
    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-red-600 text-white">Reaktif</span>
@else
    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-green-100 text-green-700">Non-Reaktif</span>
@endif
