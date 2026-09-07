@php
    $user = auth()->user();
    $canManageUsers = $user->can('manage-users');
    $canManageMaster = $user->can('manage-master-data');
    $canManageQuestions = $user->can('manage-questions');
    $canManageHomeContent = $user->can('manage-home-content');
    $canExport = $user->can('export-data');
    $canImportTracer = $user->can('fill-tracer');
    $canViewAlumni = $user->hasAnyRole(['Admin Universitas', 'Admin Fakultas', 'Admin Prodi', 'Surveyor', 'Pimpinan Universitas', 'Pimpinan Fakultas']);
    $isAlumni = $user->hasRole('Alumni');
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ $user->postLoginUrl() }}">
                        <x-application-logo class="block h-9 w-auto" />
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    @unless ($isAlumni)
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            {{ __('Dashboard') }}
                        </x-nav-link>
                        <x-nav-link :href="route('employer.dashboard')" :active="request()->routeIs('employer.dashboard')">
                            {{ __('Pengguna Alumni') }}
                        </x-nav-link>
                    @endunless

                    @if ($canViewAlumni)
                        <x-nav-link :href="route('alumni.index')" :active="request()->routeIs('alumni.*')">
                            {{ __('Alumni') }}
                        </x-nav-link>
                    @endif

                    @unless ($isAlumni)
                        <x-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">
                            {{ __('Laporan') }}
                        </x-nav-link>
                    @endunless

                    @if ($canExport || $canImportTracer)
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                                    {{ __('Data Tracer') }}
                                    <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if ($canImportTracer)
                                    <x-dropdown-link :href="route('tracer.import.form')">Impor Data Tracer (Excel)</x-dropdown-link>
                                    <x-dropdown-link :href="route('employer.import.form')">Impor Data Pengguna Alumni (Excel)</x-dropdown-link>
                                @endif
                                @if ($canExport)
                                    <x-dropdown-link :href="route('export.tracer.form')">Ekspor Data Tracer (Excel)</x-dropdown-link>
                                    <x-dropdown-link :href="route('export.alumni')">Ekspor Data Alumni (Excel)</x-dropdown-link>
                                    <x-dropdown-link :href="route('export.dashboard')">Ekspor Ringkasan Dashboard (Excel)</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif

                    @if ($canManageUsers || $canManageMaster || $canManageQuestions || $canImportTracer || $canManageHomeContent)
                        <x-dropdown align="left" width="56">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-1 pt-1 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent">
                                    {{ __('Administrasi') }}
                                    <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                @if ($canManageUsers)
                                    <x-dropdown-link :href="route('admin.users.index')">Manajemen User</x-dropdown-link>
                                @endif
                                @if ($canImportTracer)
                                    <x-dropdown-link :href="route('alumni.import.form')">Impor Data Alumni</x-dropdown-link>
                                @endif
                                @if ($canManageMaster)
                                    <x-dropdown-link :href="route('admin.faculties.index')">Fakultas</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.program-studies.index')">Program Studi</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.provinces.index')">Provinsi</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.cities.index')">Kabupaten/Kota</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.ump.index')">Upah Minimum Provinsi</x-dropdown-link>
                                @endif
                                @if ($canManageQuestions)
                                    <x-dropdown-link :href="route('admin.questions.index')">Pertanyaan Tambahan</x-dropdown-link>
                                @endif
                                @if ($canManageHomeContent)
                                    <x-dropdown-link :href="route('admin.home-content.edit')">Konten Halaman Depan</x-dropdown-link>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ $user->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 text-xs text-gray-400">{{ $user->getRoleNames()->join(', ') ?: 'Tanpa role' }}</div>
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @unless ($isAlumni)
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employer.dashboard')" :active="request()->routeIs('employer.dashboard')">{{ __('Pengguna Alumni') }}</x-responsive-nav-link>
            @endunless
            @if ($canViewAlumni)
                <x-responsive-nav-link :href="route('alumni.index')" :active="request()->routeIs('alumni.*')">{{ __('Alumni') }}</x-responsive-nav-link>
            @endif
            @unless ($isAlumni)
                <x-responsive-nav-link :href="route('reports.index')" :active="request()->routeIs('reports.*')">{{ __('Laporan') }}</x-responsive-nav-link>
            @endunless
            @if ($canImportTracer)
                <x-responsive-nav-link :href="route('tracer.import.form')">Impor Data Tracer</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('alumni.import.form')">Impor Data Alumni</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('employer.import.form')">Impor Data Pengguna Alumni</x-responsive-nav-link>
            @endif
            @if ($canManageUsers)
                <x-responsive-nav-link :href="route('admin.users.index')">Manajemen User</x-responsive-nav-link>
            @endif
            @if ($canManageMaster)
                <x-responsive-nav-link :href="route('admin.faculties.index')">Fakultas</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.provinces.index')">Provinsi</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.cities.index')">Kabupaten/Kota</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.ump.index')">UMP</x-responsive-nav-link>
            @endif
            @if ($canManageHomeContent)
                <x-responsive-nav-link :href="route('admin.home-content.edit')">Konten Halaman Depan</x-responsive-nav-link>
            @endif
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $user->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $user->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
