@extends('layouts.admin', ['title' => 'Leadership'])

@push('scripts')
    @vite('resources/js/leaders.js')
@endpush

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Home and About pages</p>
            <h1 class="mt-2 text-3xl font-bold">Leadership</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">
                Drag to change the order{{ $leaders->hasPages() ? ' within this page' : '' }}. Changes reach visitors within about a minute. Search engines see them after the next website build.
            </p>
        </div>
        <button id="add-item" type="button" class="btn-primary">Add leader</button>
    </header>

    @if ($leaders->isEmpty())
        <p class="panel mt-8 text-center text-sm text-muted">No leaders yet. Add the first one.</p>
    @else
        <ul id="item-list" class="mt-8 flex flex-col gap-3">
            @foreach ($leaders as $leader)
                <li class="panel flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap"
                    data-id="{{ $leader->id }}"
                    data-active="{{ $leader->is_active ? '1' : '0' }}"
                    data-record="{{ json_encode($leader->only(['id', 'name', 'initials', 'role_en', 'role_bn', 'quote_en', 'quote_bn', 'photo_alt_en', 'photo_alt_bn']) + ['photo' => $leader->photo['src'] ?? null]) }}">
                    @include('partials.drag-handle', ['label' => 'Move '.$leader->name])

                    @if ($leader->photo)
                        <img src="{{ $leader->photo['src'] }}" alt="" loading="lazy"
                            @class(['aspect-[4/5] w-16 shrink-0 rounded-lg object-cover', 'opacity-40 grayscale' => ! $leader->is_active])>
                    @else
                        <span aria-hidden="true" class="grid aspect-[4/5] w-16 shrink-0 place-items-center rounded-lg border border-brass/50 bg-navy text-sm font-bold text-brass">{{ $leader->initials }}</span>
                    @endif

                    <div class="min-w-0 basis-full sm:flex-1 sm:basis-0">
                        <p class="text-sm font-semibold">{{ $leader->name }} <span class="font-normal text-muted">· {{ $leader->role_en }}</span></p>
                        <p class="mt-1 line-clamp-2 text-xs text-muted">{{ $leader->quote_en }}</p>
                        <p class="mt-1.5 text-xs text-muted">
                            Bangla: {{ filled($leader->role_bn) && filled($leader->quote_bn) ? 'added' : 'uses English' }}
                            · Photo: {{ $leader->photo ? 'added' : 'initials shown' }}
                        </p>
                    </div>

                    @include('partials.row-actions', ['active' => $leader->is_active])
                </li>
            @endforeach
        </ul>
    @endif

    {{ $leaders->links() }}

    <dialog id="item-dialog" aria-labelledby="item-dialog-title" class="admin-dialog">
        <form id="item-form" data-url="{{ route('leaders.index') }}" novalidate class="flex flex-col gap-5 p-6">
            @include('partials.dialog-title', ['title' => 'Add leader'])

            <div class="grid gap-5 sm:grid-cols-[1fr_8rem]">
                <x-field name="name" label="Name" required maxlength="120" />
                <x-field name="initials" label="Initials" maxlength="3" hint="Blank: built from the name." />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="role_en" label="Role, English" required maxlength="120" placeholder="Chairman" />
                <x-field name="role_bn" label="Role, Bangla" lang="bn" maxlength="120" hint="Optional. If blank, the English text is used." />
                <x-field name="quote_en" label="Message, English" required :rows="4" maxlength="1000" />
                <x-field name="quote_bn" label="Message, Bangla" lang="bn" :rows="4" maxlength="1000" hint="Optional. If blank, the English text is used." />
            </div>

            <div class="flex min-w-0 flex-col gap-2">
                <label for="photo_image" class="text-xs font-medium text-sand">Photo</label>
                <img id="photo_image-preview" alt="" hidden class="aspect-[4/5] w-28 rounded-lg object-cover">
                <input id="photo_image" name="photo_image" type="file" accept="image/jpeg,image/png,image/webp" class="file-input">
                <p class="text-xs text-muted">Portrait 4:5, at least 800 px wide, up to 10 MB. If blank, the initials are shown.</p>
                <p data-error="photo_image" role="alert" hidden class="text-xs text-danger"></p>
                <label id="remove-photo-row" hidden class="flex items-center gap-2 text-xs text-muted">
                    <input type="checkbox" name="remove_photo" value="1" class="size-4 accent-brass">
                    Remove the photo and show the initials
                </label>
            </div>

            <fieldset class="flex flex-col gap-4 rounded-xl border border-line p-4">
                <legend class="eyebrow px-2">SEO</legend>
                <p class="text-xs text-muted">Google reads this description to understand the photo, and screen readers read it aloud. The name and role also go into Google's details about this person at the next website build.</p>
                <x-field name="photo_alt_en" label="Photo description, English" maxlength="255" placeholder="Shimu Akter, Chairman of Delight Real Estate" hint="Needed when you add a photo." />
                <x-field name="photo_alt_bn" label="Photo description, Bangla" lang="bn" maxlength="255" />
            </fieldset>

            @include('partials.dialog-actions', ['save' => 'Add leader'])
        </form>
    </dialog>
@endsection
