@extends('layouts.admin', ['title' => 'Videos'])

@push('scripts')
    @vite('resources/js/videos.js')
@endpush

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Home page</p>
            <h1 class="mt-2 text-3xl font-bold">Videos</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">
                Drag to change the order{{ $videos->hasPages() ? ' within this page' : '' }}. Changes reach visitors within about a minute. Search engines see them after the next website build.
            </p>
        </div>
        <button id="add-item" type="button" class="btn-primary">Add video</button>
    </header>

    @if ($videos->isEmpty())
        <p class="panel mt-8 text-center text-sm text-muted">No videos yet. With none active, the video section is hidden on the website.</p>
    @else
        <ul id="item-list" class="mt-8 flex flex-col gap-3">
            @foreach ($videos as $video)
                <li class="panel flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap"
                    data-id="{{ $video->id }}"
                    data-active="{{ $video->is_active ? '1' : '0' }}"
                    data-record="{{ json_encode([
                        'id' => $video->id,
                        'youtube_id' => $video->youtube_id,
                        'title_en' => $video->title_en,
                        'title_bn' => $video->title_bn,
                        'description_en' => $video->description_en,
                        'description_bn' => $video->description_bn,
                        'upload_date' => $video->upload_date?->toDateString(),
                        'duration' => $video->clockDuration(),
                        'poster' => $video->poster_image['src'] ?? null,
                    ]) }}">
                    @include('partials.drag-handle', ['label' => 'Move video '.$videos->firstItem() + $loop->index])

                    <img src="{{ $video->poster_image['src'] ?? "https://i.ytimg.com/vi/{$video->youtube_id}/hqdefault.jpg" }}" alt="" loading="lazy"
                        @class(['aspect-video w-32 shrink-0 rounded-lg object-cover sm:w-40', 'opacity-40 grayscale' => ! $video->is_active])>

                    <div class="min-w-0 basis-full sm:flex-1 sm:basis-0">
                        <p class="line-clamp-2 text-sm font-medium">{{ $video->title_en }}</p>
                        <p class="mt-1.5 text-xs text-muted">
                            <a href="https://youtu.be/{{ $video->youtube_id }}" target="_blank" rel="noopener noreferrer" class="underline hover:text-brass">youtu.be/{{ $video->youtube_id }}</a>
                            · Bangla: {{ filled($video->title_bn) ? 'added' : 'uses English' }}
                            · SEO: {{ $video->upload_date ? 'complete' : 'needs upload date' }}
                        </p>
                    </div>

                    @include('partials.row-actions', ['active' => $video->is_active])
                </li>
            @endforeach
        </ul>
    @endif

    {{ $videos->links() }}

    <dialog id="item-dialog" aria-labelledby="item-dialog-title" class="admin-dialog">
        <form id="item-form" data-url="{{ route('videos.index') }}" novalidate class="flex flex-col gap-5 p-6">
            @include('partials.dialog-title', ['title' => 'Add video'])

            <div class="flex flex-col gap-3">
                <x-field name="youtube_link" label="YouTube link" required placeholder="https://youtu.be/mJVuZiK9a6I" hint="On YouTube, press Share, copy the link and paste it here." />
                <img id="youtube-preview" alt="Video thumbnail from YouTube" hidden class="aspect-video w-48 rounded-lg object-cover">
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="title_en" label="Title, English" required maxlength="255" />
                <x-field name="title_bn" label="Title, Bangla" maxlength="255" lang="bn" hint="Optional. If blank, the English text is used." />
            </div>

            <div class="flex min-w-0 flex-col gap-2">
                <label for="poster_image" class="text-xs font-medium text-sand">Poster image</label>
                <img id="poster_image-preview" alt="" hidden class="aspect-video w-48 rounded-lg object-cover">
                <input id="poster_image" name="poster_image" type="file" accept="image/jpeg,image/png,image/webp" class="file-input">
                <p class="text-xs text-muted">Optional. 16:9, at least 1280 px wide. If blank, YouTube's thumbnail is used.</p>
                <p data-error="poster_image" role="alert" hidden class="text-xs text-danger"></p>
                <label id="remove-poster-row" hidden class="flex items-center gap-2 text-xs text-muted">
                    <input type="checkbox" name="remove_poster" value="1" class="size-4 accent-brass">
                    Remove the poster and use YouTube's thumbnail
                </label>
            </div>

            <fieldset class="flex flex-col gap-4 rounded-xl border border-line p-4">
                <legend class="eyebrow px-2">SEO</legend>
                <p class="text-xs text-muted">Google can show this video in its results once it has an upload date. The description also appears under the video on the website.</p>
                <x-field name="description_en" label="Description, English" :rows="2" maxlength="500" />
                <x-field name="description_bn" label="Description, Bangla" :rows="2" maxlength="500" lang="bn" />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="upload_date" label="Upload date" type="date" hint="The date it went up on YouTube." />
                    <x-field name="duration" label="Length" placeholder="2:30" hint="Minutes and seconds." />
                </div>
            </fieldset>

            @include('partials.dialog-actions', ['save' => 'Add video'])
        </form>
    </dialog>
@endsection
