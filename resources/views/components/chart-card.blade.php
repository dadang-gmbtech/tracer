@props(['title', 'config', 'height' => '320px'])

<div class="bg-white shadow-sm rounded-lg p-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">{{ $title }}</h3>
    <div x-data="chartCanvas(@js($config))" style="height: {{ $height }}">
        <canvas x-ref="canvas"></canvas>
    </div>
</div>
