@props(['points', 'height' => '420px'])

<div x-data="provinceMap(@js($points))">
    <div x-ref="map" style="height: {{ $height }}; border-radius: 0.5rem;"></div>
</div>
