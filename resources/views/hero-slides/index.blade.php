@extends('layouts.admin', ['title' => 'Hero slides'])

@push('scripts')
    @vite('resources/js/hero-slides.js')
@endpush

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Home page</p>
            <h1 class="mt-2 text-3xl font-bold">Hero slides</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">
                Drag to change the order{{ $slides->hasPages() ? ' within this page' : '' }}. Changes reach visitors within about a minute. Search engines see them after the next website build.
            </p>
        </div>
        <button id="add-item" type="button" class="btn-primary">Add slide</button>
    </header>

    @if ($slides->isEmpty())
        <p class="panel mt-8 text-center text-sm text-muted">No slides yet. Add the first one.</p>
    @else
        <ul id="item-list" class="mt-8 flex flex-col gap-3">
            @foreach ($slides as $slide)
                <li class="panel flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap"
                    data-id="{{ $slide->id }}"
                    data-active="{{ $slide->is_active ? '1' : '0' }}"
                    data-record="{{ json_encode([
                        'id' => $slide->id,
                        'alt_en' => $slide->alt_en,
                        'alt_bn' => $slide->alt_bn,
                        'desktop' => $slide->desktop_images['src'],
                        'mobile' => $slide->mobile_images['src'] ?? null,
                    ]) }}">
                    @include('partials.drag-handle', ['label' => 'Move slide '.$slides->firstItem() + $loop->index])

                    <img src="{{ $slide->desktop_images['src'] }}" alt="" loading="lazy"
                        @class(['aspect-video w-32 shrink-0 rounded-lg object-cover sm:w-40', 'opacity-40 grayscale' => ! $slide->is_active])>

                    <div class="min-w-0 basis-full sm:flex-1 sm:basis-0">
                        <p class="line-clamp-2 text-sm font-medium">{{ $slide->alt_en }}</p>
                        <p class="mt-1.5 text-xs text-muted">
                            Mobile image: {{ $slide->mobile_images ? 'own crop' : 'from desktop' }}
                            · Bangla: {{ filled($slide->alt_bn) ? 'added' : 'uses English' }}
                        </p>
                    </div>

                    @include('partials.row-actions', ['active' => $slide->is_active])
                </li>
            @endforeach
        </ul>
    @endif

    {{ $slides->links() }}

    {{-- Create and edit share this pop-up --}}
    <dialog id="item-dialog" aria-labelledby="item-dialog-title" class="admin-dialog">
        <form id="item-form" data-url="{{ route('hero-slides.index') }}" novalidate class="flex flex-col gap-5 p-6">
            @include('partials.dialog-title', ['title' => 'Add slide'])

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="flex min-w-0 flex-col gap-2">
                    <label for="desktop_image" class="text-xs font-medium text-sand">Desktop image <span class="text-brass">*</span></label>
                    <img id="desktop_image-preview" alt="" hidden class="aspect-video w-full rounded-lg object-cover">
                    <input id="desktop_image" name="desktop_image" type="file" accept="image/jpeg,image/png,image/webp" class="file-input">
                    <p class="text-xs text-muted">Landscape, 1920 to 6000 px wide, up to 10 MB.</p>
                    <p data-error="desktop_image" role="alert" hidden class="text-xs text-danger"></p>
                </div>

                <div class="flex min-w-0 flex-col gap-2">
                    <label for="mobile_image" class="text-xs font-medium text-sand">Mobile image</label>
                    <img id="mobile_image-preview" alt="" hidden class="mx-auto h-32 rounded-lg object-cover">
                    <input id="mobile_image" name="mobile_image" type="file" accept="image/jpeg,image/png,image/webp" class="file-input">
                    <p class="text-xs text-muted">Optional. Portrait. If blank, the desktop image is cropped from its centre.</p>
                    <p data-error="mobile_image" role="alert" hidden class="text-xs text-danger"></p>
                    <label id="remove-mobile-row" hidden class="flex items-center gap-2 text-xs text-muted">
                        <input type="checkbox" name="remove_mobile" value="1" class="size-4 accent-brass">
                        Remove the mobile image
                    </label>
                </div>
            </div>

            <fieldset class="flex flex-col gap-4 rounded-xl border border-line p-4">
                <legend class="eyebrow px-2">SEO</legend>
                <p class="text-xs text-muted">Google reads this description to understand the image, and screen readers read it aloud. The image file name is also built from the English text.</p>
                <x-field name="alt_en" label="Image description, English" required maxlength="255" hint='Include "(architectural rendering)" for buildings.' />
                <x-field name="alt_bn" label="Image description, Bangla" maxlength="255" lang="bn" hint="Optional. If blank, the English text is used." />
            </fieldset>

            @include('partials.dialog-actions', ['save' => 'Add slide'])
        </form>
    </dialog>
@endsection
