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

                <div class="bg-white shadow-sm rounded-lg p-6 space-y-4"
                     x-data="{ on: {{ old('show_reports', $content->show_reports) ? 'true' : 'false' }} }">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <h3 class="font-semibold text-gray-800">Laporan Tracer Studi</h3>
                            <p class="text-sm text-gray-600">Daftar laporan yang bisa diunduh pengunjung halaman depan.</p>
                        </div>
                        <input type="hidden" name="show_reports" value="{{ old('show_reports', $content->show_reports) ? 1 : 0 }}" :value="on ? 1 : 0">
                        <button type="button" role="switch" :aria-checked="on.toString()" @click="on = ! on"
                                style="display: inline-flex; align-items: center; gap: 0.6rem; background: none; border: 0; padding: 0; cursor: pointer;">
                            <span :style="on ? 'background: #1d4ed8' : 'background: #d1d5db'"
                                  style="position: relative; width: 2.75rem; height: 1.5rem; border-radius: 9999px; transition: background .15s;">
                                <span :style="on ? 'transform: translateX(1.25rem)' : 'transform: translateX(0)'"
                                      style="position: absolute; top: 0.125rem; left: 0.125rem; width: 1.25rem; height: 1.25rem; border-radius: 9999px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.2); transition: transform .15s;"></span>
                            </span>
                            <span class="text-sm font-medium" :style="on ? 'color: #1d4ed8' : 'color: #6b7280'" x-text="on ? 'Aktif' : 'Tidak Aktif'"></span>
                        </button>
                    </div>

                    <p class="text-sm" :style="on ? 'color: #1e40af' : 'color: #6b7280'"
                       x-text="on ? 'Bagian Laporan Tracer Studi tampil di halaman depan setelah disimpan.' : 'Bagian Laporan Tracer Studi tidak tampil di halaman depan.'"></p>

                    <div>
                        <p class="text-sm font-medium text-gray-700">Laporan yang akan ditampilkan ({{ $universityReports->count() }}):</p>
                        <p class="text-xs text-gray-500">
                            Hanya laporan yang diunggah Super Admin atau Admin Universitas. Laporan dari admin fakultas/prodi
                            tidak ditampilkan. Tambah atau hapus laporan lewat menu
                            <a href="{{ route('reports.index') }}" class="text-blue-600 hover:underline">Laporan</a>.
                        </p>
                        @if ($universityReports->isEmpty())
                            <p class="text-sm text-gray-400" style="margin-top: 0.5rem;">
                                Belum ada laporan tingkat universitas. Bagian ini tidak akan tampil walau saklarnya aktif.
                            </p>
                        @else
                            <ul class="text-sm text-gray-700" style="margin-top: 0.5rem; padding-left: 1.25rem; list-style: disc;">
                                @foreach ($universityReports as $report)
                                    <li>{{ $report->title }} ({{ $report->year }})</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
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
