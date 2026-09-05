<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Manajemen User') }}</h2>
            <div class="space-x-3 text-sm">
                <a href="{{ route('admin.users.import.form') }}" class="text-blue-600 hover:underline">Impor Surveyor</a>
                <a href="{{ route('admin.users.create') }}" class="text-blue-600 hover:underline">+ Tambah User</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            @if (session('importedCredentials'))
                <div class="bg-blue-50 text-blue-800 text-sm rounded-md p-4">
                    <p class="font-medium mb-2">Password sementara — catat/salin sekarang, tidak akan ditampilkan lagi:</p>
                    <table class="text-xs w-full">
                        @foreach (session('importedCredentials') as $cred)
                            <tr><td class="pr-4">{{ $cred['name'] }}</td><td class="pr-4">{{ $cred['email'] }}</td><td class="font-mono">{{ $cred['password'] }}</td></tr>
                        @endforeach
                    </table>
                </div>
            @endif

            <form method="GET" class="flex items-center gap-3">
                <select name="role" onchange="this.form.submit()" class="rounded-md border-gray-300 text-sm">
                    <option value="">Semua Role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </form>

            <form method="POST" action="{{ route('admin.users.bulk-destroy') }}" onsubmit="return confirm('Hapus user terpilih?')">
                @csrf
                <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3"><input type="checkbox" onclick="document.querySelectorAll('.user-check').forEach(c => c.checked = this.checked)"></th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Cakupan</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($users as $u)
                                <tr>
                                    <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $u->id }}" class="user-check"></td>
                                    <td class="px-4 py-3">{{ $u->name }}</td>
                                    <td class="px-4 py-3">{{ $u->email }}</td>
                                    <td class="px-4 py-3">{{ $u->getRoleNames()->join(', ') }}</td>
                                    <td class="px-4 py-3">{{ $u->faculty?->name }} {{ $u->studyProgram?->name }}</td>
                                    <td class="px-4 py-3 space-x-3">
                                        <a href="{{ route('admin.users.edit', $u) }}" class="text-blue-600 hover:underline">Edit</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">Tidak ada data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="submit" class="text-sm text-red-600 hover:underline">Hapus User Terpilih</button>
                </div>
            </form>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>
