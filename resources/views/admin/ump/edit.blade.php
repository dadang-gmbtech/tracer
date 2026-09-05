<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit UMP') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.ump.update', $ump) }}" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <x-input-label for="province_id" value="Provinsi" />
                    <select id="province_id" name="province_id" class="mt-1 w-full rounded-md border-gray-300 text-sm" required>
                        @foreach ($provinces as $province)
                            <option value="{{ $province->id }}" @selected(old('province_id', $ump->province_id) == $province->id)>{{ $province->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('province_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="year" value="Tahun" />
                    <x-text-input id="year" type="number" name="year" class="mt-1 w-full" :value="old('year', $ump->year)" required />
                    <x-input-error :messages="$errors->get('year')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="amount" value="Nominal (Rp)" />
                    <x-text-input id="amount" type="number" step="0.01" name="amount" class="mt-1 w-full" :value="old('amount', $ump->amount)" required />
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>
                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
