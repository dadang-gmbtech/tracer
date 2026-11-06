<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Log Aktivitas') }}</h2>
    </x-slot>

    @php
        $tabs = [
            \App\Models\ActivityLog::CATEGORY_ADMIN => 'Admin',
            \App\Models\ActivityLog::CATEGORY_ALUMNI => 'Alumni & Pengguna Alumni',
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid #e5e7eb;">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('admin.activity-logs.index', ['kategori' => $key]) }}"
                       style="padding: 0.6rem 1rem; font-size: 0.875rem; font-weight: 600; margin-bottom: -1px; text-decoration: none;
                              {{ $category === $key ? 'border-bottom: 2px solid #1d4ed8; color: #1d4ed8;' : 'border-bottom: 2px solid transparent; color: #6b7280;' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <p class="text-xs text-gray-500">
                @if ($category === \App\Models\ActivityLog::CATEGORY_ADMIN)
                    Aktivitas semua user di Manajemen User (Super Admin, Admin, Pimpinan, Surveyor).
                @else
                    Aktivitas alumni dan Pengguna Alumni (yang mengisi form lewat tautan, tanpa login).
                @endif
                Dicatat: login/logout, setiap perubahan data, impor, dan unduhan ekspor/laporan/template.
            </p>

            <form method="GET" class="bg-white shadow-sm rounded-lg p-4"
                  style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 1rem;">
                <input type="hidden" name="kategori" value="{{ $category }}">
                <div>
                    <x-input-label for="q" value="Nama / email / NIM" />
                    <x-text-input id="q" name="q" class="mt-1" :value="request('q')" />
                </div>
                <div>
                    <x-input-label for="aktivitas" value="Aktivitas" />
                    <select id="aktivitas" name="aktivitas" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        @foreach ($activities as $activity)
                            <option value="{{ $activity }}" @selected(request('aktivitas') === $activity)>{{ $activity }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 rounded-md border-gray-300 text-sm">
                        <option value="">Semua</option>
                        <option value="gagal" @selected(request('status') === 'gagal')>Gagal saja</option>
                    </select>
                </div>
                <div>
                    <x-input-label for="dari" value="Dari tanggal" />
                    <x-text-input id="dari" name="dari" type="date" class="mt-1" :value="request('dari')" />
                </div>
                <div>
                    <x-input-label for="sampai" value="Sampai tanggal" />
                    <x-text-input id="sampai" name="sampai" type="date" class="mt-1" :value="request('sampai')" />
                </div>
                <x-primary-button>Filter</x-primary-button>
            </form>

            <div class="bg-white shadow-sm rounded-lg" style="overflow-x: auto;">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3">User</th>
                            <th class="px-4 py-3">Aktivitas</th>
                            <th class="px-4 py-3">Detail</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse ($logs as $log)
                            @php
                                $props = $log->properties ?? [];
                                $details = array_filter([
                                    isset($props['target']) ? implode(', ', $props['target']) : null,
                                    $log->alumni && ! isset($props['target']['alumni']) ? 'Alumni: '.$log->alumni->nama.' ('.$log->alumni->nim.')' : null,
                                    isset($props['file']) ? 'File: '.$props['file'] : null,
                                    isset($props['jumlah']) ? $props['jumlah'].' data' : null,
                                    isset($props['filter']) ? 'Filter: '.collect($props['filter'])->map(fn ($v, $k) => $k.'='.(is_array($v) ? implode('/', $v) : $v))->join(', ') : null,
                                ]);
                            @endphp
                            <tr style="vertical-align: top;">
                                <td class="px-4 py-3" style="white-space: nowrap;">{{ $log->created_at->translatedFormat('d M Y H:i:s') }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $log->actor_name ?? '-' }}</div>
                                    @if ($log->actor_role)
                                        <div class="text-xs text-gray-500">{{ $log->actor_role }}</div>
                                    @endif
                                    @if ($log->impersonator)
                                        <div class="text-xs" style="color: #b45309;">via Login-as oleh {{ $log->impersonator->name }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $log->description }}</td>
                                <td class="px-4 py-3 text-xs text-gray-600">{{ implode(' · ', $details) ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    @if ($log->succeeded)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Berhasil</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium" style="background: #fee2e2; color: #b91c1c;">Gagal</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500" style="white-space: nowrap;">{{ $log->ip_address }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Belum ada aktivitas tercatat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
