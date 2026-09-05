@props(['name', 'value' => null, 'low' => 'Sangat Rendah', 'high' => 'Sangat Tinggi'])

<div>
    <div class="flex gap-3 items-center">
        @for ($i = 1; $i <= 5; $i++)
            <label class="flex flex-col items-center text-xs text-gray-500 gap-1">
                <input type="radio" name="{{ $name }}" value="{{ $i }}" @checked((string) old($name, $value) === (string) $i) class="text-blue-600">
                {{ $i }}
            </label>
        @endfor
    </div>
    <div class="flex justify-between text-[10px] text-gray-400 w-[190px]">
        <span>{{ $low }}</span>
        <span>{{ $high }}</span>
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
