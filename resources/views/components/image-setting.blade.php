@props(['name', 'label', 'current' => null, 'fallback' => null, 'hint' => null, 'accept' => 'image/png,image/webp,image/jpeg', 'light' => false])

<div class="flex min-w-0 flex-col gap-2">
    <label for="{{ $name }}" class="text-xs font-medium text-sand">{{ $label }}</label>
    <div @class(['flex h-24 items-center justify-center overflow-hidden rounded-lg border border-line p-4', 'bg-bone' => $light, 'bg-ink' => ! $light])>
        <img id="{{ $name }}-preview" src="{{ $current ?: $fallback }}" alt="" @if (! $current && ! $fallback) hidden @endif class="max-h-full min-h-0 max-w-full object-contain">
    </div>
    <input id="{{ $name }}" name="{{ $name }}" type="file" accept="{{ $accept }}" data-preview="{{ $name }}-preview" class="file-input">
    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif
    @if ($current)
        <label class="flex w-fit items-center gap-2 text-xs text-muted">
            <input type="checkbox" name="remove[]" value="{{ $name }}" class="size-4 accent-brass">
            Remove and use the default
        </label>
    @endif
    <p data-error="{{ $name }}" role="alert" hidden class="text-xs text-danger"></p>
</div>
