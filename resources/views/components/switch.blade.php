@props(['name', 'label', 'checked' => false, 'hint' => null])

<div class="flex flex-col gap-1">
    <label class="flex w-fit items-center gap-2.5 text-sm text-bone">
        {{-- An unticked box sends nothing, so this says "off". --}}
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" @checked($checked) class="size-4 accent-brass">
        {{ $label }}
    </label>
    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif
    <p data-error="{{ $name }}" role="alert" hidden class="text-xs text-danger"></p>
</div>
