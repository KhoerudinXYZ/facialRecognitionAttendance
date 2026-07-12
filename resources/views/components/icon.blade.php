@props(['name'])

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"
     {{ $attributes->merge(['class' => 'w-6 h-6']) }}>
    @switch($name)
        @case('home')
            <path d="M3 11.5L12 4l9 7.5" />
            <path d="M5 10v9a1 1 0 0 0 1 1h4v-5a2 2 0 0 1 2-2 2 2 0 0 1 2 2v5h4a1 1 0 0 0 1-1v-9" />
            @break

        @case('camera')
            <path d="M4 8a2 2 0 0 1 2-2h1.5l1-1.5h7l1 1.5H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z" />
            <circle cx="12" cy="13" r="3.25" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
            @break

        @case('user-circle')
            <circle cx="12" cy="12" r="8.5" />
            <circle cx="12" cy="10" r="2.5" />
            <path d="M6.5 18.5a5.5 5.5 0 0 1 11 0" />
            @break

        @case('logout')
            <path d="M13 4H7a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h6" />
            <path d="M11 12h9m0 0-3-3m3 3-3 3" />
            @break

        @case('check-circle')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M8.5 12.5l2.2 2.2 4.8-5" />
            @break

        @case('check')
            <path d="M5 12.5l4.5 4.5L19 7" />
            @break

        @case('sparkles')
            <path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5L12 3z" />
            <path d="M19 15l.75 2.25L22 18l-2.25.75L19 21l-.75-2.25L16 18l2.25-.75L19 15z" />
            @break

        @case('users')
            <circle cx="9" cy="9" r="3" />
            <path d="M4 19a5 5 0 0 1 10 0" />
            <circle cx="17.5" cy="9.5" r="2.25" />
            <path d="M14.5 19a4 4 0 0 1 6.5-3.1" />
            @break

        @case('clipboard')
            <rect x="6" y="4" width="12" height="17" rx="2" />
            <path d="M9 4a1.5 1.5 0 0 1 1.5-1.5h3A1.5 1.5 0 0 1 15 4" />
            <path d="M9 11h6M9 15h4" />
            @break

        @case('cog')
            <circle cx="12" cy="12" r="3" />
            <path d="M12 3v2.5M12 18.5V21M4.5 12H3M21 12h-1.5M6.5 6.5 5.3 5.3M18.7 18.7l-1.2-1.2M6.5 17.5l-1.2 1.2M18.7 5.3l-1.2 1.2" />
            @break

        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break

        @case('pencil')
            <path d="M4 20h4l10.5-10.5a2 2 0 0 0 0-2.83l-1.17-1.17a2 2 0 0 0-2.83 0L4 16v4z" />
            <path d="M13.5 6.5l4 4" />
            @break

        @case('trash')
            <path d="M5 7h14" />
            <path d="M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
            <path d="M7 7l1 13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1l1-13" />
            <path d="M10 11v6M14 11v6" />
            @break

        @case('key')
            <circle cx="7.5" cy="15.5" r="3.25" />
            <path d="M9.8 13.2L18 5m0 0v4m0-4h-4" />
            @break

        @case('search')
            <circle cx="10.5" cy="10.5" r="6.5" />
            <path d="M20 20l-4.35-4.35" />
            @break

        @case('upload')
            <path d="M12 15V4m0 0-4 4m4-4 4 4" />
            <path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" />
            @break

        @case('download')
            <path d="M12 4v11m0 0-4-4m4 4 4-4" />
            <path d="M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" />
            @break

        @case('sun')
            <circle cx="12" cy="12" r="4" />
            <path d="M12 3v1.5M12 19.5V21M4.6 4.6l1.1 1.1M18.3 18.3l1.1 1.1M3 12h1.5M19.5 12H21M4.6 19.4l1.1-1.1M18.3 5.7l1.1-1.1" />
            @break

        @case('moon')
            <path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5z" />
            @break

        @case('x-circle')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M9 9l6 6M15 9l-6 6" />
            @break

        @case('bell')
            <path d="M6 10a6 6 0 1 1 12 0c0 3.2 1 5 1.5 5.5H4.5C5 15 6 13.2 6 10z" />
            <path d="M10 18.5a2 2 0 0 0 4 0" />
            @break
    @endswitch
</svg>
