@props(['status'])

@php
    $map = [
        'hadir'     => 'bg-emerald-600 text-white shadow-sm shadow-emerald-600/20 border border-emerald-500',
        'terlambat' => 'bg-amber-600 text-white shadow-sm shadow-amber-600/20 border border-amber-500',
        'izin'      => 'bg-purple-600 text-white shadow-sm shadow-purple-600/20 border border-purple-500',
        'sakit'     => 'bg-purple-600 text-white shadow-sm shadow-purple-600/20 border border-purple-500',
        'alpha'     => 'bg-rose-600 text-white shadow-sm shadow-rose-600/20 border border-rose-500',
        'libur'     => 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/20 border border-indigo-500',
    ];
    $cls = $map[$status] ?? 'bg-slate-700 text-white border border-slate-600';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold font-lexend {$cls}"]) }}>
    {{ ucfirst($status) }}
</span>
