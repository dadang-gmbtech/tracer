@props(['selected' => []])

{{-- Inline styles on purpose: deploys don't rebuild the Tailwind bundle, so
     spacing classes that weren't already compiled silently do nothing. --}}
<div style="display: flex; flex-wrap: wrap; align-items: center; column-gap: 1.25rem; row-gap: 0.5rem; padding: 0.5rem 0;" {{ $attributes }}>
    @foreach (\App\Models\StudyProgram::LEVELS as $level)
        <label style="display: inline-flex; align-items: center; gap: 0.4rem; white-space: nowrap;" class="text-sm">
            <input type="checkbox" name="jenjang[]" value="{{ $level }}" class="rounded" style="margin: 0;"
                   @checked(in_array($level, (array) $selected, true))>
            <span>{{ $level }}</span>
        </label>
    @endforeach
</div>
