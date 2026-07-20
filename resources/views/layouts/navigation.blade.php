<nav x-data="{ open: false }" class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border-b border-slate-200/50 dark:border-slate-800/50 sticky top-0 z-40 transition-colors duration-300">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-100" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        <x-icon name="home" class="w-4 h-4 mr-1.5" />
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    <x-nav-dropdown title="{{ __('Presensi') }}" icon="check-circle" :active="request()->routeIs(['absensi.*', 'laporan.*', 'pengajuan-izin.*'])">
                        <x-dropdown-link :href="route('absensi.index')">
                            {{ __('Rekap') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('laporan.index')">
                            {{ __('Laporan') }}
                        </x-dropdown-link>
                        <x-dropdown-link :href="route('pengajuan-izin.index')">
                            {{ __('Pengajuan Izin/Sakit') }}
                        </x-dropdown-link>
                    </x-nav-dropdown>

                    @if (Auth::user()->isAdmin())
                        <x-nav-dropdown title="{{ __('Data Master') }}" icon="database" :active="request()->routeIs(['siswa.*', 'kelas.*'])">
                            <x-dropdown-link :href="route('siswa.index')">
                                {{ __('Siswa') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('kelas.index')">
                                {{ __('Kelas') }}
                            </x-dropdown-link>
                        </x-nav-dropdown>
                    @else
                        <x-nav-link :href="route('siswa.index')" :active="request()->routeIs('siswa.*')">
                            <x-icon name="user-circle" class="w-4 h-4 mr-1.5" />
                            {{ __('Siswa') }}{{ $kelasBinaanNav->count() === 1 ? ' ' . $kelasBinaanNav->first()->nama_kelas : '' }}
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->isAdmin())
                        <x-nav-dropdown title="{{ __('Administrasi') }}" icon="cog" :active="request()->routeIs(['staff.*', 'pengaturan.*', 'hari-libur.*', 'absensi.audit', 'notifikasi-absensi.*'])">
                            <x-dropdown-link :href="route('staff.index')">
                                {{ __('Staff') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('pengaturan.edit')">
                                {{ __('Pengaturan') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('hari-libur.index')">
                                {{ __('Hari Libur') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('absensi.audit')">
                                {{ __('Riwayat Hapus Absensi') }}
                            </x-dropdown-link>
                            <x-dropdown-link :href="route('notifikasi-absensi.index')">
                                {{ __('Notifikasi Orang Tua') }}
                            </x-dropdown-link>
                        </x-nav-dropdown>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 gap-1">
                @if (Auth::user()->isWaliKelas())
                    @php $reminderTotal = $reminderPerluPerhatian->count() + $reminderBelumWajah->count(); @endphp
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button class="relative w-9 h-9 flex items-center justify-center rounded-full text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-white transition"
                                    title="Pengingat">
                                <x-icon name="bell" class="w-5 h-5" />
                                @if ($reminderTotal > 0)
                                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-semibold">{{ min(9, $reminderTotal) }}{{ $reminderTotal > 9 ? '+' : '' }}</span>
                                @endif
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pengingat</p>
                            </div>
                            @if ($reminderTotal === 0)
                                <p class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">Tidak ada pengingat saat ini.</p>
                            @else
                                <div class="max-h-80 overflow-y-auto">
                                    @if ($reminderPerluPerhatian->isNotEmpty())
                                        <div class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Belum absen 3 hari terakhir</div>
                                        @foreach ($reminderPerluPerhatian as $s)
                                            <a href="{{ route('siswa.show', $s) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">{{ $s->nama }}</a>
                                        @endforeach
                                    @endif
                                    @if ($reminderBelumWajah->isNotEmpty())
                                        <div class="px-4 pt-3 pb-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Belum daftar wajah</div>
                                        @foreach ($reminderBelumWajah as $s)
                                            <a href="{{ route('siswa.enroll', $s) }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">{{ $s->nama }}</a>
                                        @endforeach
                                    @endif
                                </div>
                            @endif
                        </x-slot>
                    </x-dropdown>
                @endif

                <button id="theme-toggle" type="button"
                        class="w-9 h-9 flex items-center justify-center rounded-full text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-white transition"
                        title="Ganti tema">
                    <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                    <x-icon name="moon" class="w-5 h-5 block dark:hidden" />
                </button>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-300 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-white focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden gap-1">
                <button id="theme-toggle-mobile" type="button"
                        class="w-9 h-9 flex items-center justify-center rounded-md text-gray-400 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-500 dark:hover:text-white transition"
                        title="Ganti tema">
                    <x-icon name="sun" class="w-5 h-5 hidden dark:block" />
                    <x-icon name="moon" class="w-5 h-5 block dark:hidden" />
                </button>
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-300 hover:text-gray-500 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="inline-flex items-center gap-1.5"><x-icon name="home" class="w-4 h-4" />{{ __('Dashboard') }}</span>
            </x-responsive-nav-link>

            <div class="px-4 pt-2 pb-1 flex items-center gap-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">
                <x-icon name="check-circle" class="w-3.5 h-3.5" /> {{ __('Presensi') }}
            </div>
            <x-responsive-nav-link :href="route('absensi.index')" :active="request()->routeIs('absensi.index')">
                {{ __('Rekap') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('laporan.index')" :active="request()->routeIs('laporan.*')">
                {{ __('Laporan') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('pengajuan-izin.index')" :active="request()->routeIs('pengajuan-izin.*')">
                {{ __('Pengajuan Izin/Sakit') }}
            </x-responsive-nav-link>

            @if (Auth::user()->isAdmin())
                <div class="px-4 pt-2 pb-1 flex items-center gap-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">
                    <x-icon name="database" class="w-3.5 h-3.5" /> {{ __('Data Master') }}
                </div>
                <x-responsive-nav-link :href="route('siswa.index')" :active="request()->routeIs('siswa.*')">
                    {{ __('Siswa') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('kelas.index')" :active="request()->routeIs('kelas.*')">
                    {{ __('Kelas') }}
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('siswa.index')" :active="request()->routeIs('siswa.*')">
                    <span class="inline-flex items-center gap-1.5">
                        <x-icon name="user-circle" class="w-4 h-4" />
                        {{ __('Siswa') }}{{ $kelasBinaanNav->count() === 1 ? ' ' . $kelasBinaanNav->first()->nama_kelas : '' }}
                    </span>
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->isWaliKelas() && ($reminderPerluPerhatian->isNotEmpty() || $reminderBelumWajah->isNotEmpty()))
                <div class="px-4 pt-2 pb-1 flex items-center gap-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">
                    <x-icon name="bell" class="w-3.5 h-3.5" /> Pengingat
                </div>
                @foreach ($reminderPerluPerhatian as $s)
                    <x-responsive-nav-link :href="route('siswa.show', $s)">{{ $s->nama }} — belum absen 3 hari</x-responsive-nav-link>
                @endforeach
                @foreach ($reminderBelumWajah as $s)
                    <x-responsive-nav-link :href="route('siswa.enroll', $s)">{{ $s->nama }} — belum daftar wajah</x-responsive-nav-link>
                @endforeach
            @endif

            @if (Auth::user()->isAdmin())
                <div class="px-4 pt-2 pb-1 flex items-center gap-1.5 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">
                    <x-icon name="cog" class="w-3.5 h-3.5" /> {{ __('Administrasi') }}
                </div>
                <x-responsive-nav-link :href="route('staff.index')" :active="request()->routeIs('staff.*')">
                    {{ __('Staff') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('pengaturan.edit')" :active="request()->routeIs('pengaturan.*')">
                    {{ __('Pengaturan') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('hari-libur.index')" :active="request()->routeIs('hari-libur.*')">
                    {{ __('Hari Libur') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('absensi.audit')" :active="request()->routeIs('absensi.audit')">
                    {{ __('Riwayat Hapus Absensi') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notifikasi-absensi.index')" :active="request()->routeIs('notifikasi-absensi.*')">
                    {{ __('Notifikasi Orang Tua') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
