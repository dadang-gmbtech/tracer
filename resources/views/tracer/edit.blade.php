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

                {{-- Q1: Status --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">1. Jelaskan status Anda saat ini? *</h3>
                    <div class="space-y-2 text-sm">
                        @foreach ([1 => 'Bekerja (full time / part time)', 2 => 'Belum memungkinkan bekerja', 3 => 'Wiraswasta', 4 => 'Melanjutkan Pendidikan', 5 => 'Tidak kerja tetapi sedang mencari kerja'] as $val => $label)
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f8" value="{{ $val }}" x-model="f8" required>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('f8')" class="mt-2" />
                </section>

                {{-- Q2-8: Bekerja / Wiraswasta --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5" x-show="f8 === '1' || f8 === '3'" x-cloak>
                    <div>
                        <x-input-label value="2. Dalam berapa bulan Anda mendapatkan pekerjaan pertama / memulai wiraswasta setelah lulus? *" />
                        <x-text-input type="number" min="0" name="f502" class="mt-1 w-40" :value="$v('f502')" />
                        <x-input-error :messages="$errors->get('f502')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="3. Berapa rata-rata pendapatan Anda per bulan (take home pay)?" />
                        <x-text-input type="number" min="0" step="0.01" name="f505" class="mt-1 w-60" :value="$v('f505')" />
                        <x-input-error :messages="$errors->get('f505')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="4. Provinsi tempat bekerja *" />
                            <select name="work_province_id" x-model="provinceId" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Pilih Provinsi</option>
                                @foreach ($provinces as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('work_province_id')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Kabupaten/Kota tempat bekerja *" />
                            <select name="work_city_id" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Pilih Kabupaten/Kota</option>
                                <template x-for="city in (cities[provinceId] || [])" :key="city.id">
                                    <option :value="city.id" x-text="city.name" :selected="city.id == {{ $v('work_city_id') ?: 0 }}"></option>
                                </template>
                            </select>
                            <x-input-error :messages="$errors->get('work_city_id')" class="mt-1" />
                        </div>
                    </div>

                    <div x-show="f8 === '1'">
                        <x-input-label value="5. Jenis perusahaan/instansi/institusi tempat Anda bekerja sekarang?" />
                        <select name="f1101" x-model="f1101" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Instansi pemerintah', 2 => 'Organisasi non-profit/LSM', 3 => 'Perusahaan swasta', 4 => 'Wiraswasta/perusahaan sendiri', 6 => 'BUMN/BUMD', 7 => 'Institusi/Organisasi Multilateral', 5 => 'Lainnya, tuliskan'] as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="mt-2" x-show="f1101 === '5'">
                            <x-text-input name="f1102" class="w-full" placeholder="Sebutkan jenis lainnya" :value="$v('f1102')" />
                        </div>
                        <x-input-error :messages="$errors->get('f1101')" class="mt-1" />
                        <x-input-error :messages="$errors->get('f1102')" class="mt-1" />
                    </div>

                    <div x-show="f8 === '1'">
                        <x-input-label value="6. Nama perusahaan/kantor tempat Anda bekerja?" />
                        <x-text-input name="f5b" class="mt-1 w-full" :value="$v('f5b')" />
                    </div>

                    <div x-show="f8 === '3'">
                        <x-input-label value="7. Bila berwiraswasta, apa posisi/jabatan Anda saat ini? *" />
                        <select name="f5c" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Founder', 2 => 'Co-Founder', 3 => 'Staff', 4 => 'Freelance/Kerja Lepas'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f5c') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f5c')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="8. Tingkat tempat kerja Anda?" />
                        <select name="f5d" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Lokal/Wilayah/Wiraswasta tidak berbadan hukum', 2 => 'Nasional/Wiraswasta berbadan hukum', 3 => 'Multinasional/Internasional'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f5d') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('f5d')" class="mt-1" />
                    </div>
                </section>

                {{-- Q9: Studi lanjut --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-4" x-show="f8 === '4'" x-cloak>
                    <h3 class="font-semibold text-gray-800">9. Pertanyaan studi lanjut</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Sumber biaya" />
                            <select name="f18a" class="mt-1 w-full rounded-md border-gray-300 text-sm">
                                <option value="">Pilih</option>
                                <option value="1" @selected($v('f18a') == 1)>Biaya Sendiri</option>
                                <option value="2" @selected($v('f18a') == 2)>Beasiswa</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label value="Perguruan Tinggi" />
                            <x-text-input name="f18b" class="mt-1 w-full" :value="$v('f18b')" />
                        </div>
                        <div>
                            <x-input-label value="Program Studi" />
                            <x-text-input name="f18c" class="mt-1 w-full" :value="$v('f18c')" />
                        </div>
                        <div>
                            <x-input-label value="Tanggal Masuk" />
                            <x-text-input type="date" name="f18d" class="mt-1 w-full" :value="$v('f18d')" />
                        </div>
                    </div>
                </section>

                {{-- Q10: Sumber dana kuliah --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">10. Sumber dana dalam pembiayaan kuliah? *</h3>
                    <select name="f1201" required class="rounded-md border-gray-300 text-sm">
                        <option value="">Pilih</option>
                        @foreach ([1 => 'Biaya Sendiri/Keluarga', 2 => 'Beasiswa ADIK', 3 => 'Beasiswa BIDIKMISI', 4 => 'Beasiswa PPA', 5 => 'Beasiswa AFIRMASI', 6 => 'Beasiswa Perusahaan/Swasta', 7 => 'Lainnya'] as $val => $label)
                            <option value="{{ $val }}" @selected($v('f1201') == $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="f1202" class="mt-2 w-full" placeholder="Sebutkan sumber dana lainnya" :value="$v('f1202')" />
                    <x-input-error :messages="$errors->get('f1201')" class="mt-1" />
                </section>

                {{-- Q11-12: Relevansi (bekerja) --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-4" x-show="f8 === '1'" x-cloak>
                    <div>
                        <x-input-label value="11. Seberapa erat hubungan bidang studi dengan pekerjaan Anda?" />
                        <select name="f14" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Sangat Erat', 2 => 'Erat', 3 => 'Cukup Erat', 4 => 'Kurang Erat', 5 => 'Tidak Sama Sekali'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f14') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label value="12. Tingkat pendidikan yang paling tepat untuk pekerjaan Anda saat ini?" />
                        <select name="f15" class="mt-1 rounded-md border-gray-300 text-sm">
                            <option value="">Pilih</option>
                            @foreach ([1 => 'Setingkat Lebih Tinggi', 2 => 'Tingkat yang Sama', 3 => 'Setingkat Lebih Rendah', 4 => 'Tidak Perlu Pendidikan Tinggi'] as $val => $label)
                                <option value="{{ $val }}" @selected($v('f15') == $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                {{-- Q13: Kompetensi --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-1">13. Kompetensi *</h3>
                    <p class="text-xs text-gray-500 mb-4">A = tingkat kuasai saat lulus, B = tingkat dibutuhkan pekerjaan saat ini.</p>
                    <div class="space-y-5">
                        @foreach ([
                            'Etika' => [1761, 1762], 'Keahlian berdasarkan bidang ilmu' => [1763, 1764],
                            'Bahasa Inggris' => [1765, 1766], 'Penggunaan Teknologi Informasi' => [1767, 1768],
                            'Komunikasi' => [1769, 1770], 'Kerja sama tim' => [1771, 1772], 'Pengembangan' => [1773, 1774],
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

                {{-- Q14: Metode pembelajaran --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">14. Penekanan metode pembelajaran di program studi Anda</h3>
                    <div class="space-y-4">
                        @foreach (['Perkuliahan' => 21, 'Demonstrasi' => 22, 'Partisipasi dalam proyek riset' => 23, 'Magang' => 24, 'Praktikum' => 25, 'Kerja Lapangan' => 26, 'Diskusi' => 27] as $label => $code)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-center">
                                <div class="text-sm font-medium text-gray-700">{{ $label }}</div>
                                <div class="md:col-span-2">
                                    <x-rating-scale :name="'f'.$code" :value="$v('f'.$code)" low="Sangat Besar" high="Tidak Sama Sekali" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Q15-19: Pencarian kerja --}}
                <section class="bg-white shadow-sm rounded-lg p-6 space-y-5">
                    <div>
                        <x-input-label value="15. Kapan Anda mulai mencari pekerjaan? *" />
                        <div class="space-y-2 text-sm mt-2">
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="1" x-model="f301" required>
                                Kira-kira
                                <input type="number" min="0" name="f302" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '1'" value="{{ $v('f302') }}">
                                bulan sebelum lulus
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="2" x-model="f301">
                                Kira-kira
                                <input type="number" min="0" name="f303" class="w-20 rounded-md border-gray-300 text-sm" :disabled="f301 !== '2'" value="{{ $v('f303') }}">
                                bulan sesudah lulus
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="radio" name="f301" value="3" x-model="f301">
                                Saya tidak mencari kerja
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('f301')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="16. Bagaimana Anda mencari pekerjaan tersebut? (bisa lebih dari satu)" />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-1 mt-2 text-sm">
                            @foreach ([
                                401 => 'Iklan di koran/majalah/brosur', 402 => 'Melamar tanpa mengetahui lowongan',
                                403 => 'Bursa/pameran kerja', 404 => 'Internet/iklan online/milis',
                                405 => 'Dihubungi oleh perusahaan', 406 => 'Menghubungi Kemenakertrans',
                                407 => 'Agen tenaga kerja komersial/swasta', 408 => 'Pusat karir fakultas/universitas',
                                409 => 'Kantor kemahasiswaan/hubungan alumni', 410 => 'Membangun jejaring sejak kuliah',
                                411 => 'Relasi (dosen, orang tua, dll.)', 412 => 'Membangun bisnis sendiri',
                                413 => 'Penempatan kerja/magang', 414 => 'Tempat kerja sama dengan saat kuliah',
                            ] as $code => $label)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="f{{ $code }}" value="1" @checked($v('f'.$code)) class="rounded">
                                    {{ $label }}
                                </label>
                            @endforeach
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="f415" value="1" x-model="f415" class="rounded">
                                Lainnya
                            </label>
                        </div>
                        <x-text-input name="f416" class="mt-2 w-full" x-show="f415" placeholder="Sebutkan cara lainnya" :value="$v('f416')" />
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <x-input-label value="17. Jumlah dilamar" />
                            <x-text-input type="number" min="0" name="f6" class="mt-1" :value="$v('f6')" />
                        </div>
                        <div>
                            <x-input-label value="18. Jumlah merespons" />
                            <x-text-input type="number" min="0" name="f7" class="mt-1" :value="$v('f7')" />
                        </div>
                        <div>
                            <x-input-label value="19. Jumlah wawancara" />
                            <x-text-input type="number" min="0" name="f7a" class="mt-1" :value="$v('f7a')" />
                        </div>
                    </div>
                </section>

                {{-- Q20: Aktif mencari kerja --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">20. Apakah Anda aktif mencari pekerjaan dalam 4 minggu terakhir?</h3>
                    <select name="f1001" x-model="f1001" class="rounded-md border-gray-300 text-sm">
                        <option value="">Pilih</option>
                        @foreach ([1 => 'Tidak', 2 => 'Tidak, tapi sedang menunggu hasil lamaran', 3 => 'Ya, akan mulai bekerja dalam 2 minggu', 4 => 'Ya, tapi belum pasti dalam 2 minggu', 5 => 'Lainnya'] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-text-input name="f1002" class="mt-2 w-full" x-show="f1001 === '5'" placeholder="Sebutkan" :value="$v('f1002')" />
                </section>

                {{-- Q21: Ketidaksesuaian --}}
                <section class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-3">21. Jika pekerjaan Anda tidak sesuai pendidikan, mengapa Anda mengambilnya? (bisa lebih dari satu)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-1 text-sm">
                        @foreach ([
                            1601 => 'Pekerjaan sekarang sudah sesuai pendidikan', 1602 => 'Belum mendapat yang lebih sesuai',
                            1603 => 'Prospek karir yang baik', 1604 => 'Lebih suka area di luar bidang pendidikan',
                            1605 => 'Dipromosikan ke posisi kurang berhubungan', 1606 => 'Pendapatan lebih tinggi',
                            1607 => 'Lebih aman/terjamin', 1608 => 'Lebih menarik',
                            1609 => 'Lebih fleksibel', 1610 => 'Lokasi lebih dekat rumah',
                            1611 => 'Lebih menjamin kebutuhan keluarga', 1612 => 'Harus menerima di awal karir',
                        ] as $code => $label)
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="f{{ $code }}" value="1" @checked($v('f'.$code)) class="rounded">
                                {{ $label }}
                            </label>
                        @endforeach
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="f1613" value="1" x-model="f1613" class="rounded">
                            Lainnya
                        </label>
                    </div>
                    <x-text-input name="f1614" class="mt-2 w-full" x-show="f1613" placeholder="Sebutkan alasan lainnya" :value="$v('f1614')" />
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
