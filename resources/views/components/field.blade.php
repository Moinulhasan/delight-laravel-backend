@props(['name', 'label', 'value' => null, 'type' => 'text', 'hint' => null, 'required' => false, 'rows' => null])

<div class="flex min-w-0 flex-col gap-2">
    <label for="{{ $name }}" class="text-xs font-medium text-sand">
        {{ $label }} @if ($required)<span class="text-brass">*</span>@endif
    </label>
    @if ($rows)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->merge(['class' => 'field']) }}>{{ $value }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" {{ $attributes->merge(['class' => 'field']) }}>
    @endif
    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif
    <p data-error="{{ $name }}" role="alert" hidden class="text-xs text-danger"></p>
</div>
