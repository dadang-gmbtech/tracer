<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-gray-800">Login Sistem Tracer Studi</h2>
        <p class="text-sm text-gray-600">Universitas Jenderal Soedirman</p>
    </div>

    <div x-data="{ tab: 'alumni' }">
        <div class="flex mb-6 border border-gray-200 rounded-md overflow-hidden text-sm font-medium">
            <button type="button" @click="tab = 'alumni'"
                :class="tab === 'alumni' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600'"
                class="flex-1 py-2">Alumni</button>
            <button type="button" @click="tab = 'staf'"
                :class="tab === 'staf' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600'"
                class="flex-1 py-2">Staf / Admin</button>
        </div>

        <div x-show="tab === 'alumni'">
            <div class="mb-4">
                <a href="{{ route('login.sso') }}" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Login Dengan Akun UNSOED (SSO)
                </a>
            </div>

            <div class="relative mb-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Atau</span>
                </div>
            </div>

            <form method="POST" action="{{ route('login.nim') }}">
                @csrf

                <div>
                    <x-input-label for="nim" value="NIM" />
                    <x-text-input id="nim" class="block mt-1 w-full" type="text" name="nim" :value="old('nim')" autofocus />
                    <x-input-error :messages="$errors->get('nim')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="tanggal_lahir" value="Tanggal Lahir" />
                    <x-text-input id="tanggal_lahir" class="block mt-1 w-full" type="date" name="tanggal_lahir" />
                    <x-input-error :messages="$errors->get('tanggal_lahir')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <x-primary-button class="bg-green-600 hover:bg-green-700">
                        Login Dengan NIM & Tanggal Lahir
                    </x-primary-button>
                </div>
            </form>
        </div>

        <div x-show="tab === 'staf'" style="display: none">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mt-4">
                    <x-input-label for="password" value="Password" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="block mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm" name="remember">
                        <span class="ms-2 text-sm text-gray-600">Ingat saya</span>
                    </label>
                </div>

                <div class="flex items-center justify-between mt-4">
                    @if (Route::has('password.request'))
                        <a class="text-sm text-gray-600 hover:text-gray-900" href="{{ route('password.request') }}">
                            Lupa password?
                        </a>
                    @endif

                    <x-primary-button>Login</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-guest-layout>
