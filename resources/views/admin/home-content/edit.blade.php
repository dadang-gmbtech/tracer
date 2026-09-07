<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Konten Halaman Depan') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-green-50 text-green-700 text-sm rounded-md p-4">{{ session('status') }}</div>
            @endif

            <p class="text-sm text-gray-600">
                Mengubah teks yang tampil di <a href="{{ route('home') }}" target="_blank" class="text-blue-600 hover:underline">halaman depan publik</a>
                (sebelum login). Tata letak halaman tidak diatur di sini.
            </p>

            <form method="POST" action="{{ route('admin.home-content.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">Bagian Utama (Hero)</h3>
                    <div>
                        <x-input-label for="hero_title" value="Judul" />
                        <x-text-input id="hero_title" name="hero_title" class="mt-1 w-full" :value="old('hero_title', $content->hero_title)" required />
                        <x-input-error :messages="$errors->get('hero_title')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="hero_subtitle" value="Sub Judul" />
                        <x-text-input id="hero_subtitle" name="hero_subtitle" class="mt-1 w-full" :value="old('hero_subtitle', $content->hero_subtitle)" required />
                        <x-input-error :messages="$errors->get('hero_subtitle')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="hero_description" value="Deskripsi" />
                        <textarea id="hero_description" name="hero_description" rows="3" required
                                  class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('hero_description', $content->hero_description) }}</textarea>
                        <x-input-error :messages="$errors->get('hero_description')" class="mt-1" />
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">Bagian Tentang</h3>
                    <div>
                        <x-input-label for="tentang_description" value="Deskripsi" />
                        <textarea id="tentang_description" name="tentang_description" rows="3" required
                                  class="mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('tentang_description', $content->tentang_description) }}</textarea>
                        <x-input-error :messages="$errors->get('tentang_description')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        @foreach ($content->tentang_cards as $i => $card)
                            <div class="space-y-2 border border-gray-100 rounded-md p-3">
                                <x-input-label :for="'tentang_cards_'.$i.'_title'" :value="'Kartu '.($i + 1).' — Judul'" />
                                <x-text-input :id="'tentang_cards_'.$i.'_title'" name="tentang_cards[{{ $i }}][title]" class="w-full text-sm"
                                              :value="old('tentang_cards.'.$i.'.title', $card['title'])" required />
                                <x-input-error :messages="$errors->get('tentang_cards.'.$i.'.title')" />

                                <x-input-label :for="'tentang_cards_'.$i.'_description'" value="Deskripsi" />
                                <textarea :id="'tentang_cards_'.$i.'_description'" name="tentang_cards[{{ $i }}][description]" rows="2" required
                                          class="w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('tentang_cards.'.$i.'.description', $card['description']) }}</textarea>
                                <x-input-error :messages="$errors->get('tentang_cards.'.$i.'.description')" />
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">Langkah Mudah Mengisi Kuesioner</h3>
                    @foreach ($content->alur_steps as $i => $step)
                        <div>
                            <x-input-label :for="'alur_steps_'.$i" :value="'Langkah '.($i + 1)" />
                            <x-text-input :id="'alur_steps_'.$i" name="alur_steps[{{ $i }}]" class="mt-1 w-full"
                                          :value="old('alur_steps.'.$i, $step)" required />
                            <x-input-error :messages="$errors->get('alur_steps.'.$i)" class="mt-1" />
                        </div>
                    @endforeach
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                    <h3 class="font-semibold text-gray-800">Footer</h3>
                    <div>
                        <x-input-label for="footer_address" value="Alamat" />
                        <x-text-input id="footer_address" name="footer_address" class="mt-1 w-full" :value="old('footer_address', $content->footer_address)" required />
                        <x-input-error :messages="$errors->get('footer_address')" class="mt-1" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
