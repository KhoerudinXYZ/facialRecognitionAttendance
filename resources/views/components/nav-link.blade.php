@props(['active'])

@php
$classes = \App\View\NavTabStyle::classes($active ?? false);
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
