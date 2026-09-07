<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit User') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.users.update', $targetUser) }}"
                  x-data="{ role: '{{ old('role', $targetUser->getRoleNames()->first()) }}' }"
                  class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" class="mt-1 w-full" :value="old('name', $targetUser->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" type="email" name="email" class="mt-1 w-full" :value="old('email', $targetUser->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="password" value="Password Baru (opsional)" />
                    <x-text-input id="password" type="password" name="password" class="mt-1 w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="no_telp" value="No Telp" />
                    <x-text-input id="no_telp" name="no_telp" class="mt-1 w-full" :value="old('no_telp', $targetUser->no_telp)" />
                </div>
                <div>
                    <x-input-label for="role" value="Role" />
                    <select id="role" name="role" x-model="role" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('role')" class="mt-1" />
                </div>
                <div x-show="['Admin Fakultas', 'Admin Prodi', 'Surveyor', 'Pimpinan Fakultas'].includes(role)">
                    <x-input-label for="faculty_id" value="Fakultas" />
                    <select id="faculty_id" name="faculty_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Pilih Fakultas</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected(old('faculty_id', $targetUser->faculty_id) == $faculty->id)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="role === 'Admin Prodi'">
                    <x-input-label for="program_study_id" value="Program Studi" />
                    <select id="program_study_id" name="program_study_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                        <option value="">Pilih Program Studi</option>
                        @foreach ($programStudies as $ps)
                            <option value="{{ $ps->id }}" @selected(old('program_study_id', $targetUser->program_study_id) == $ps->id)>{{ $ps->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        <option value="active" @selected(old('status', $targetUser->status) === 'active')>Aktif</option>
                        <option value="inactive" @selected(old('status', $targetUser->status) === 'inactive')>Tidak Aktif</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-1" />
                    @if (auth()->user()->is($targetUser))
                        <p class="mt-1 text-xs text-gray-500">Anda tidak dapat menonaktifkan akun Anda sendiri.</p>
                    @endif
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
