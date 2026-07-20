@props(['status'])

@php
    $map = [
        'hadir' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
        'terlambat' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'izin' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'sakit' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        'alpha' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
    ];
    $cls = $map[$status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex px-2 py-0.5 rounded-full text-xs font-medium {$cls}"]) }}>
    {{ ucfirst($status) }}
</span>
