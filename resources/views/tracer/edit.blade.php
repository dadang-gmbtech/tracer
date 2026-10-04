@php
    $v = fn ($field) => old($field, $response->{$field});
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kuesioner Tracer Studi — {{ $alumni->nama }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="bg-red-50 text-red-700 text-sm rounded-md p-4 mb-6">
                    <p class="font-medium mb-1">Periksa kembali isian berikut:</p>
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @can('view', $alumni)
                <section class="bg-white shadow-sm rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div>
                            <h3 class="font-semibold text-gray-800">Form Pengguna Alumni</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Buat tautan untuk dikirim ke atasan/perusahaan tempat Anda bekerja, agar mereka
                                bisa mengisi penilaian tanpa perlu login.
                            </p>
                        </div>
                        <form method="POST" action="{{ route('tracer.share-link', $alumni) }}">
                            @csrf
                            <x-secondary-button type="submit">Buat Tautan Form Pengguna Alumni</x-secondary-button>
                        </form>
                    </div>

                    @if (session('employerLink'))
                        <div class="mt-4 bg-blue-50 text-blue-800 text-sm rounded-md p-4"
                             x-data="{ copied: false, link: @js(session('employerLink')) }">
                            <p class="font-medium mb-2">Tautan Form Pengguna Alumni (berlaku 30 hari):</p>
                            <div class="flex items-center gap-2">
                                <a :href="link" x-text="link" target="_blank" rel="noopener"
                                   class="flex-1 truncate underline hover:text-blue-900"></a>
                                <button type="button"
                                        @click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="shrink-0 px-3 py-1.5 rounded-md border border-blue-300 text-blue-700 hover:bg-blue-100 text-xs font-medium">
                                    <span x-show="!copied">Salin Link</span>
                                    <span x-show="copied" x-cloak>Tersalin!</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </section>
            @endcan

            <section class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="font-semibold text-gray-800 mb-1">Identitas</h3>
                <p class="text-xs text-gray-500 mb-4">Diambil otomatis dari Sistem Informasi Akademik — tidak dapat diubah di sini.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    @foreach ([
                        'NIM Mahasiswa' => $identity->nim,
                        'Kode PT' => $identity->kodePt,
                        'Tahun Lulus' => $identity->tahunLulus,
                        'Kode Prodi' => $identity->kodeProdi,
                        'Nama Mahasiswa' => $identity->namaMahasiswa,
                        'Nomor Telepon/HP' => $identity->noTelp,
                        'Alamat Email' => $identity->email,
                        'NIK' => $identity->nik,
                        'NPWP' => $identity->npwp,
                    ] as $label => $value)
                        <div>
                            <x-input-label :value="$label" />
                            <input type="text" value="{{ $value ?? '-' }}" disabled
                                   class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 text-sm">
                        </div>
                    @endforeach
                </div>
            </section>

            <form method="POST" action="{{ route('tracer.update', $alumni) }}"
                  x-data="{
                      f8: '{{ $v('f8') }}',
                      f301: '{{ $v('f301') }}',
                      f1101: '{{ $v('f1101') }}',
                      f1001: '{{ $v('f1001') }}',
                      f415: {{ $v('f415') ? 'true' : 'false' }},
                      f1613: {{ $v('f1613') ? 'true' : 'false' }},
                      provinceId: '{{ old('work_province_id', $response->work_province_id) }}',
                      cities: @js($provinces->keyBy('id')->map->cities),
                  }"
                  class="space-y-6">
                @csrf
                @method('PUT')

                {{-- F8: Status aktivitas --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Apakah status aktivitas Anda saat ini? *</h3>
                    <div class="space-y-2 text-sm">
                        @foreach ([
                            1 => 'Bekerja (penuh waktu/paruh waktu)',
                            3 => 'Wiraswasta/wirausaha/pekerja lepas',
                            4 => 'Melanjutkan pendidikan',
                            5 => 'Tidak bekerja tetapi sedang mencari pekerjaan',
                            2 => 'Belum memungkinkan bekerja (sakit, mengurus keluarga, dan/atau alasan lainnya)',
                        ] as $val => $label)
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f8" value="{{ $val }}" x-model="f8" required>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('f8')" class="mt-2" />
                </section>

                {{-- ===================== F8 = 1: BEKERJA ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5" x-show="f8 === '1'" x-cloak>
                    <h3 class="font-semibold text-gray-800">Pertanyaan bagi yang bekerja</h3>

                    <div>
                        <x-input-label value="Dalam berapa bulan Anda mendapatkan pekerjaan pertama? *" />
                        <p class="text-xs text-gray-500 mb-1">Kelulusan dihitung berdasarkan bulan yudisium dan masa tunggu kerja dihitung 0, jika bekerja sebelum lulus.</p>
                        <x-text-input type="number" min="0" name="f502" class="mt-1 w-40" :value="$v('f502')" />
                        <x-input-error :messages="$errors->get('f502')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Berapa rata-rata pendapatan (take home pay) Anda per bulan? *" />
                        <p class="text-xs text-gray-500 mb-1">Take home pay adalah jumlah total penghasilan yang diterima ditambah tunjangan/bonus.</p>
                        <x-text-input type="number" min="0" step="0.01" name="f505" class="mt-1 w-60" :value="$v('f505')" />
                        <x-input-error :messages="$errors->get('f505')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Di mana lokasi tempat Anda bekerja?" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-1">
                            <div>
                                <x-input-label value="Negara" class="text-xs" />
                                <input type="text" value="Indonesia" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 text-sm">
                            </div>
                            <div>
                                <x-input-label value="Provinsi *" class="text-xs" />
                                <select name="work_province_id" x-model="provinceId" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach ($provinces as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Kabupaten/Kota *" class="text-xs" />
                                <select name="work_city_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Kabupaten/Kota</option>
                                    <template x-for="city in (cities[provinceId] || [])" :key="city.id">
                                        <option :value="city.id" x-text="city.name" :selected="city.id == {{ $v('work_city_id') ?: 0 }}"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('work_province_id')" class="mt-1" />
                        <x-input-error :messages="$errors->get('work_city_id')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apa jenis perusahaan/instansi/institusi tempat Anda bekerja sekarang? *" />
                        <select name="f1101" x-model="f1101" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([
                                1 => 'Instansi/lembaga pemerintah',
                                6 => 'Badan Usaha Milik Negara (BUMN)/Badan Usaha Milik Daerah (BUMD)',
                                7 => 'Institusi/organisasi multilateral',
                                2 => 'Institusi/organisasi nirlaba',
                                3 => 'Perusahaan swasta (perusahaan nasional, multinasional, rintisan/startup, UMKM, dan lain-lain)',
                                5 => 'Lainnya, tuliskan',
                            ] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="mt-2" x-show="f1101 === '5'">
                            <x-text-input name="f1102" class="w-full" placeholder="Sebutkan jenis lainnya" :value="$v('f1102')" />
                        </div>
                        <x-input-error :messages="$errors->get('f1101')" class="mt-1" />
                        <x-input-error :messages="$errors->get('f1102')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apa nama perusahaan/kantor tempat Anda bekerja? *" />
                        <x-text-input name="f5b" class="mt-1 w-full" :value="$v('f5b')" />
                        <x-input-error :messages="$errors->get('f5b')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apa ruang lingkup tempat Anda bekerja? *" />
                        <select name="f5d" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Lokal/Wilayah', 2 => 'Nasional', 3 => 'Multinasional/Internasional'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f5d') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f5d')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Seberapa relevan bidang studi dengan pekerjaan Anda saat ini? *" />
                        <select name="f14" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Sangat relevan', 2 => 'Relevan', 3 => 'Cukup relevan', 4 => 'Kurang relevan', 5 => 'Tidak relevan'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f14') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f14')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Tingkat kesesuaian jenjang pendidikan untuk pekerjaan Anda saat ini? *" />
                        <select name="f15" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Setingkat lebih tinggi', 2 => 'Tingkat yang sama', 3 => 'Setingkat lebih rendah', 4 => 'Tidak perlu pendidikan tinggi'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f15') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f15')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Kapan Anda mulai mencari pekerjaan? *" />
                        <p class="text-xs text-gray-500 mb-1">Kelulusan dihitung berdasarkan bulan yudisium dan pekerjaan sambilan tidak dimasukkan.</p>
                        <div class="space-y-2 text-sm mt-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="1" x-model="f301">
                                Perkiraan
                                <input type="number" min="0" name="f302" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '1'" value="{{ $v('f302') }}">
                                bulan sebelum lulus
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="2" x-model="f301">
                                Perkiraan
                                <input type="number" min="0" name="f303" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '2'" value="{{ $v('f303') }}">
                                bulan sesudah lulus
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="3" x-model="f301">
                                Saya tidak mencari pekerjaan (misalnya ditawari/ikatan dinas)
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('f301')" class="mt-1" />
                    </div>
                </section>

                {{-- ===================== F8 = 3: WIRAUSAHA ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5" x-show="f8 === '3'" x-cloak>
                    <h3 class="font-semibold text-gray-800">Pertanyaan bagi yang berwiraswasta/berwirausaha</h3>

                    <div>
                        <x-input-label value="Dalam berapa bulan setelah lulus Anda memulai wiraswasta/wirausaha? *" />
                        <x-text-input type="number" min="0" name="f502" class="mt-1 w-40" :value="$v('f502')" />
                        <x-input-error :messages="$errors->get('f502')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Berapa rata-rata penghasilan Anda per bulan? *" />
                        <x-text-input type="number" min="0" step="0.01" name="f505" class="mt-1 w-60" :value="$v('f505')" />
                        <x-input-error :messages="$errors->get('f505')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Jika Anda sedang berwiraswasta/berwirausaha, apa posisi/jabatan Anda saat ini? *" />
                        <select name="f5c" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Founder', 2 => 'Co-Founder', 4 => 'Freelance/Kerja Lepas'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f5c') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f5c')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apa nama wiraswasta/wirausaha Anda? *" />
                        <x-text-input name="f5b" class="mt-1 w-full" :value="$v('f5b')" />
                        <x-input-error :messages="$errors->get('f5b')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apakah wiraswasta/wirausaha Anda sudah berizin/terdaftar? *" />
                        <div class="flex gap-4 text-sm mt-1">
                            @foreach ([0 => 'Belum', 1 => 'Sudah'] as $val => $label)
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="f5e" value="{{ $val }}" @checked((string) $v('f5e') === (string) $val)>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$errors->get('f5e')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Apa ruang lingkup wiraswasta/wirausaha Anda? *" />
                        <select name="f5d" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Lokal/Wilayah', 2 => 'Nasional', 3 => 'Multinasional/Internasional'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f5d') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f5d')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Di mana lokasi tempat Anda berwiraswasta/berwirausaha? *" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-1">
                            <div>
                                <x-input-label value="Negara" class="text-xs" />
                                <input type="text" value="Indonesia" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 text-sm">
                            </div>
                            <div>
                                <x-input-label value="Provinsi *" class="text-xs" />
                                <select name="work_province_id" x-model="provinceId" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach ($provinces as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Kabupaten/Kota *" class="text-xs" />
                                <select name="work_city_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Kabupaten/Kota</option>
                                    <template x-for="city in (cities[provinceId] || [])" :key="city.id">
                                        <option :value="city.id" x-text="city.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Seberapa relevan bidang studi dengan bidang wiraswasta/wirausaha Anda? *" />
                        <select name="f14" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Sangat relevan', 2 => 'Relevan', 3 => 'Cukup relevan', 4 => 'Kurang relevan', 5 => 'Tidak relevan'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f14') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f14')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Tingkat kesesuaian jenjang pendidikan untuk wiraswasta/wirausaha Anda saat ini? *" />
                        <select name="f15" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Setingkat lebih tinggi', 2 => 'Tingkat yang sama', 3 => 'Setingkat lebih rendah', 4 => 'Tidak perlu pendidikan tinggi'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f15') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f15')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Kapan Anda mulai wiraswasta/wirausaha? *" />
                        <div class="space-y-2 text-sm mt-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="1" x-model="f301">
                                Perkiraan
                                <input type="number" min="0" name="f302" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '1'" value="{{ $v('f302') }}">
                                bulan sebelum lulus (bulan yudisium)
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="2" x-model="f301">
                                Perkiraan
                                <input type="number" min="0" name="f303" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '2'" value="{{ $v('f303') }}">
                                bulan sesudah lulus (bulan yudisium)
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('f301')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Jika menurut Anda bidang wirausaha saat ini tidak relevan dengan latar belakang pendidikan Anda, mengapa Anda mengambilnya? (bisa lebih dari satu) *" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-1 text-sm mt-2">
                            @foreach ([
                                1601 => 'Bidang wirausaha saya saat ini sudah relevan dengan pendidikan saya.',
                                1602 => 'Saya belum mendapatkan bidang wirausaha yang lebih relevan.',
                                1603 => 'Di bidang wirausaha ini saya memperoleh prospek karier yang baik.',
                                1604 => 'Saya lebih suka berwirausaha di area yang tidak ada hubungannya dengan pendidikan saya.',
                                1606 => 'Saya dapat memperoleh pendapatan yang lebih tinggi pada bidang wirausaha saya saat ini.',
                                1607 => 'Bidang wirausaha saya saat ini lebih aman.',
                                1608 => 'Bidang wirausaha saya saat ini lebih menarik.',
                                1609 => 'Bidang wirausaha saya saat ini lebih memungkinkan saya memiliki jadwal yang fleksibel.',
                                1610 => 'Lokasi wirausaha saya saat ini lebih dekat dari rumah.',
                                1612 => 'Pada awal merintis wirausaha ini, saya harus memulai bidang wirausaha yang tidak berhubungan dengan pendidikan saya.',
                            ] as $code => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="f{{ $code }}" value="1" @checked($v('f'.$code)) class="rounded">
                                    {{ $label }}
                                </label>
                            @endforeach
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="f1613" value="1" x-model="f1613" class="rounded">
                                Lainnya, tuliskan ...
                            </label>
                        </div>
                        <x-text-input name="f1614" class="mt-2 w-full" x-show="f1613" placeholder="Sebutkan alasan lainnya" :value="$v('f1614')" />
                        <x-input-error :messages="$errors->get('f1601')" class="mt-1" />
                    </div>
                </section>

                {{-- ===================== F8 = 4: MELANJUTKAN PENDIDIKAN ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5" x-show="f8 === '4'" x-cloak>
                    <h3 class="font-semibold text-gray-800">Pertanyaan studi lanjut</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Sumber biaya *" />
                            <select name="f18a" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Pilih</option>
                                <option value="1" @selected($v('f18a') == 1)>Biaya Sendiri</option>
                                <option value="2" @selected($v('f18a') == 2)>Beasiswa</option>
                            </select>
                            <x-input-error :messages="$errors->get('f18a')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Tanggal Masuk *" />
                            <x-text-input type="date" name="f18d" class="mt-1 w-full" :value="$v('f18d')" />
                            <x-input-error :messages="$errors->get('f18d')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Perguruan Tinggi *" />
                            <x-text-input name="f18b" class="mt-1 w-full" :value="$v('f18b')" />
                            <x-input-error :messages="$errors->get('f18b')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Program Studi *" />
                            <x-text-input name="f18c" class="mt-1 w-full" :value="$v('f18c')" />
                            <x-input-error :messages="$errors->get('f18c')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Di mana lokasi tempat Anda melanjutkan studi lanjut? *" />
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-1">
                            <div>
                                <x-input-label value="Negara" class="text-xs" />
                                <input type="text" value="Indonesia" disabled class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-gray-500 text-sm">
                            </div>
                            <div>
                                <x-input-label value="Provinsi *" class="text-xs" />
                                <select name="work_province_id" x-model="provinceId" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Provinsi</option>
                                    @foreach ($provinces as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label value="Kabupaten/Kota *" class="text-xs" />
                                <select name="work_city_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                    <option value="">Pilih Kabupaten/Kota</option>
                                    <template x-for="city in (cities[provinceId] || [])" :key="city.id">
                                        <option :value="city.id" x-text="city.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Seberapa relevan hubungan antara bidang studi lanjut dengan bidang studi pada jenjang sebelumnya? *" />
                        <select name="f14" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Sangat relevan', 2 => 'Relevan', 3 => 'Cukup relevan', 4 => 'Kurang relevan', 5 => 'Tidak relevan'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f14') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f14')" class="mt-1" />
                    </div>
                </section>

                {{-- ===================== F8 = 5: KAPAN MULAI MENCARI (sebelum grup F4 yang sama dengan F8=1) ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6" x-show="f8 === '5'" x-cloak>
                    <x-input-label value="Kapan Anda mulai mencari pekerjaan? *" />
                    <p class="text-xs text-gray-500 mb-1">Kelulusan dihitung berdasarkan bulan yudisium dan pekerjaan sambilan tidak dimasukkan.</p>
                    <div class="space-y-2 text-sm mt-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="f301" value="1" x-model="f301">
                            Perkiraan
                            <input type="number" min="0" name="f302" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '1'" value="{{ $v('f302') }}">
                            bulan sebelum lulus
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="f301" value="2" x-model="f301">
                            Perkiraan
                            <input type="number" min="0" name="f303" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '2'" value="{{ $v('f303') }}">
                            bulan sesudah lulus
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('f301')" class="mt-1" />
                </section>

                {{-- ===================== F4/F6/F7/F7A/F1001: dibagikan antara F8=1 (bekerja) dan F8=5 (mencari kerja) ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5" x-show="f8 === '1' || f8 === '5'" x-cloak>
                    <div>
                        <x-input-label value="Bagaimana Anda mencari pekerjaan tersebut? (Jawaban bisa lebih dari satu) *" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-1 mt-2 text-sm">
                            @foreach ([
                                401 => 'Melalui iklan di koran/majalah/brosur',
                                402 => 'Melamar ke perusahaan tanpa mengetahui lowongan yang ada',
                                403 => 'Pergi ke bursa/pameran kerja',
                                404 => 'Mencari lewat internet/media sosial/job portal',
                                405 => 'Dihubungi oleh perusahaan',
                                406 => 'Menghubungi Kementerian Ketenagakerjaan',
                                407 => 'Menghubungi agen tenaga kerja komersial/swasta',
                                408 => 'Memperoleh informasi karir dari fakultas/universitas',
                                409 => 'Menghubungi kantor kemahasiswaan/hubungan alumni',
                                411 => 'Melalui jejaring/relasi (misalnya dosen, orang tua, saudara, teman, dll)',
                                413 => 'Melalui penempatan kerja atau magang',
                                414 => 'Bekerja di tempat yang sama dengan tempat kerja semasa kuliah',
                            ] as $code => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="f{{ $code }}" value="1" @checked($v('f'.$code)) class="rounded">
                                    {{ $label }}
                                </label>
                            @endforeach
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="f415" value="1" x-model="f415" class="rounded">
                                Lainnya, tuliskan ...
                            </label>
                        </div>
                        <x-text-input name="f416" class="mt-2 w-full" x-show="f415" placeholder="Sebutkan cara lainnya" :value="$v('f416')" />
                        <x-input-error :messages="$errors->get('f401')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <x-input-label value="Jumlah dilamar (lewat surat/email) sebelum pekerjaan pertama *" />
                            <x-text-input type="number" min="0" name="f6" class="mt-1" :value="$v('f6')" />
                            <x-input-error :messages="$errors->get('f6')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Jumlah yang merespons lamaran Anda *" />
                            <x-text-input type="number" min="0" name="f7" class="mt-1" :value="$v('f7')" />
                            <x-input-error :messages="$errors->get('f7')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Jumlah yang mengundang Anda untuk wawancara *" />
                            <x-text-input type="number" min="0" name="f7a" class="mt-1" :value="$v('f7a')" />
                            <x-input-error :messages="$errors->get('f7a')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Apakah Anda aktif mencari pekerjaan dalam 4 minggu terakhir? *" />
                        <select name="f1001" x-model="f1001" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([
                                1 => 'Tidak',
                                2 => 'Tidak, tapi saya sedang menunggu hasil lamaran kerja',
                                3 => 'Ya, saya akan mulai bekerja dalam 2 minggu ke depan',
                                4 => 'Ya, tapi saya belum pasti akan bekerja dalam 2 minggu ke depan',
                                5 => 'Lainnya, tuliskan ...',
                            ] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="f1002" class="mt-2 w-full" x-show="f1001 === '5'" placeholder="Sebutkan" :value="$v('f1002')" />
                        <x-input-error :messages="$errors->get('f1001')" class="mt-1" />
                    </div>
                </section>

                {{-- ===================== F16 khusus bekerja (wirausaha punya versinya sendiri di atas) ===================== --}}
                <section class="bg-white shadow-sm rounded-lg p-6" x-show="f8 === '1'" x-cloak>
                    <x-input-label value="Jika menurut Anda pekerjaan saat ini tidak relevan dengan latar belakang pendidikan Anda, mengapa Anda mengambilnya? (Jawaban bisa lebih dari satu) *" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-1 text-sm mt-2">
                        @foreach ([
                            1601 => 'Pekerjaan saya saat ini sudah relevan dengan pendidikan saya.',
                            1602 => 'Saya belum mendapatkan pekerjaan yang lebih relevan.',
                            1603 => 'Di pekerjaan ini saya memperoleh prospek karier yang baik.',
                            1604 => 'Saya lebih suka bekerja di area pekerjaan yang tidak ada hubungannya dengan pendidikan saya.',
                            1605 => 'Saya dipromosikan ke posisi yang kurang berhubungan dengan pendidikan saya dibanding posisi sebelumnya.',
                            1606 => 'Saya dapat memperoleh pendapatan yang lebih tinggi pada pekerjaan saya saat ini.',
                            1607 => 'Pekerjaan saya saat ini lebih aman/terjamin.',
                            1608 => 'Pekerjaan saya saat ini lebih menarik.',
                            1609 => 'Pekerjaan saya saat ini lebih memungkinkan saya mengambil pekerjaan tambahan/jadwal yang fleksibel, dll.',
                            1610 => 'Pekerjaan saya saat ini lokasinya lebih dekat dari rumah saya.',
                            1612 => 'Pada awal meniti karir ini, saya harus menerima pekerjaan yang tidak berhubungan dengan pendidikan saya.',
                        ] as $code => $label)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="f{{ $code }}" value="1" @checked($v('f'.$code)) class="rounded">
                                {{ $label }}
                            </label>
                        @endforeach
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="f1613" value="1" x-model="f1613" class="rounded">
                            Lainnya, tuliskan ...
                        </label>
                    </div>
                    <x-text-input name="f1614" class="mt-2 w-full" x-show="f1613" placeholder="Sebutkan alasan lainnya" :value="$v('f1614')" />
                    <x-input-error :messages="$errors->get('f1601')" class="mt-1" />
                </section>

                {{-- ===================== Selalu tampil, apa pun jawaban F8 ===================== --}}

                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">Sebutkan sumber dana dalam pembiayaan kuliah? (bukan ketika studi lanjut) *</h3>
                    <select name="f1201" required class="rounded-md border-gray-300 text-sm">
                        <option value="">Pilih</option>
                        @foreach ([
                            1 => 'Biaya sendiri/keluarga', 2 => 'Beasiswa ADIK', 3 => 'Beasiswa KIP-Kuliah (KIP-K)',
                            4 => 'Beasiswa PPA', 5 => 'Beasiswa AFIRMASI', 6 => 'Beasiswa perusahaan/swasta',
                            8 => 'Beasiswa pemerintah daerah', 7 => 'Lainnya, tuliskan ...',
                        ] as $val => $label)
                            <option value="{{ $val }}" @selected($v('f1201') == $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="f1202" class="mt-2 w-full" placeholder="Sebutkan sumber dana lainnya" :value="$v('f1202')" />
                    <x-input-error :messages="$errors->get('f1201')" class="mt-1" />
                    <x-input-error :messages="$errors->get('f1202')" class="mt-1" />
                </section>

                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Kompetensi *</h3>
                    <p class="text-xs text-gray-500 mb-4">A = tingkat kuasai saat lulus, B = tingkat dibutuhkan dalam pekerjaan saat ini.</p>
                    <div class="space-y-5">
                        @foreach ([
                            'Etika' => [1761, 1762],
                            'Keahlian berdasarkan bidang ilmu' => [1763, 1764],
                            'Bahasa Inggris' => [1765, 1766],
                            'Penggunaan teknologi informasi' => [1767, 1768],
                            'Komunikasi' => [1769, 1770],
                            'Kerja sama tim' => [1771, 1772],
                            'Pengembangan diri' => [1773, 1774],
                            'Berpikir kritis' => [1775, 1776],
                            'Kreativitas' => [1777, 1778],
                            'Kewirausahaan' => [1779, 1780],
                            'Adaptasi' => [1781, 1782],
                        ] as $label => $codes)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-start border-t pt-4 first:border-t-0 first:pt-0">
                                <div class="text-sm font-medium text-gray-700 md:col-span-1">{{ $label }}</div>
                                <div class="text-xs text-gray-500 mb-1 md:hidden">A (saat lulus)</div>
                                <x-rating-scale :name="'f'.$codes[0]" :value="$v('f'.$codes[0])" />
                                <div class="text-xs text-gray-500 mb-1 md:hidden">B (dibutuhkan)</div>
                                <x-rating-scale :name="'f'.$codes[1]" :value="$v('f'.$codes[1])" />
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Menurut Anda seberapa besar penekanan pada bentuk/metode pembelajaran di bawah ini dilaksanakan di program studi tempat Anda kuliah? *</h3>
                    <div class="space-y-4 mt-4">
                        @foreach ([
                            'Perkuliahan' => 21, 'Mendemonstrasikan' => 22, 'Penelitian/partisipasi dalam proyek riset' => 23,
                            'Magang/praktik kerja' => 24, 'Praktikum' => 25, 'Kerja lapangan' => 26, 'Diskusi' => 27,
                            'Responsi/tutorial' => 28, 'Seminar' => 29, 'Studio' => 30, 'Perancangan' => 31,
                            'Pengembangan (misal produk/karya seni)' => 32, 'Tugas akhir' => 33, 'Pelatihan bela negara' => 34,
                            'Pertukaran pelajar' => 35, 'Wirausaha' => 36, 'Pengabdian kepada masyarakat' => 37,
                        ] as $label => $code)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
                                <div class="text-sm font-medium text-gray-700">{{ $label }}</div>
                                <div class="md:col-span-2">
                                    <x-rating-scale :name="'f'.$code" :value="$v('f'.$code)" low="Sangat Besar" high="Tidak Ada" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                @if ($extraQuestions->isNotEmpty())
                    <section class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                        <h3 class="font-semibold text-gray-800">Pertanyaan Tambahan</h3>
                        @foreach ($extraQuestions as $q)
                            <div>
                                <x-input-label :value="$q->label" />
                                @if ($q->type === 'text')
                                    <x-text-input name="extra[{{ $q->id }}]" class="mt-1 w-full" />
                                @elseif ($q->type === 'number')
                                    <x-text-input type="number" name="extra[{{ $q->id }}]" class="mt-1 w-full" />
                                @else
                                    @foreach ($q->options ?? [] as $option)
                                        <label class="flex items-center gap-2 text-sm mt-1">
                                            <input type="{{ $q->type === 'checkbox' ? 'checkbox' : 'radio' }}" name="extra[{{ $q->id }}]{{ $q->type === 'checkbox' ? '[]' : '' }}" value="{{ $option }}">
                                            {{ $option }}
                                        </label>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                <div class="flex justify-end">
                    <x-primary-button>Simpan Data Tracer Studi</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
