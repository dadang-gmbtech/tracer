@if (! empty($lines))
    <div class="analysis">
        <div class="analysis-title">Analisis</div>
        {{ implode(' ', $lines) }}
    </div>
@endif
