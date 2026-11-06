@extends('layouts.admin', ['title' => 'Projects'])

@push('scripts')
    @vite('resources/js/projects.js')
@endpush

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Project lists</p>
            <h1 class="mt-2 text-3xl font-bold">Projects</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted">
                Drag to change the order{{ $projects->hasPages() ? ' within this page' : '' }}. Changes reach the project lists within about a minute. A new project shows "Details coming soon" until its detail page is added to the website.
            </p>
        </div>
        <button id="add-item" type="button" class="btn-primary">Add project</button>
    </header>

    @if ($projects->isEmpty())
        <p class="panel mt-8 text-center text-sm text-muted">No projects yet. Add the first one.</p>
    @else
        <ul id="item-list" class="mt-8 flex flex-col gap-3">
            @foreach ($projects as $project)
                <li class="panel flex flex-wrap items-center gap-4 p-4 sm:flex-nowrap"
                    data-id="{{ $project->id }}"
                    data-active="{{ $project->is_active ? '1' : '0' }}"
                    data-record="{{ json_encode($project->only([
                        'id', 'name_en', 'name_bn', 'slug', 'tagline_en', 'tagline_bn', 'status', 'type', 'handover', 'area_name_en', 'area_name_bn', 'area_slug',
                        'address_en', 'address_bn', 'cover_alt_en', 'cover_alt_bn', 'card_stats', 'is_featured', 'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn',
                    ]) + ['cover' => $project->cover_image['src']]) }}">
                    @include('partials.drag-handle', ['label' => 'Move '.$project->name_en])

                    <img src="{{ $project->cover_image['src'] }}" alt="" loading="lazy"
                        @class(['aspect-[4/3] w-28 shrink-0 rounded-lg object-cover sm:w-32', 'opacity-40 grayscale' => ! $project->is_active])>

                    <div class="min-w-0 basis-full sm:flex-1 sm:basis-0">
                        <p class="text-sm font-semibold">
                            {{ $project->name_en }}
                            @if ($project->is_featured)
                                <span class="ml-1 rounded-full bg-brass/15 px-2 py-0.5 text-[11px] font-semibold text-brass">Featured</span>
                            @endif
                        </p>
                        <p class="mt-1 text-xs text-muted">{{ Str::ucfirst($project->status) }} · {{ Str::ucfirst($project->type) }} · Handover {{ $project->handover }} · {{ $project->area_name_en }}</p>
                        <p class="mt-1.5 text-xs text-muted">
                            /projects/{{ $project->slug }}
                            · Bangla: {{ filled($project->name_bn) ? 'added' : 'uses English' }}
                        </p>
                    </div>

                    @include('partials.row-actions', ['active' => $project->is_active])
                </li>
            @endforeach
        </ul>
    @endif

    {{ $projects->links() }}

    <dialog id="item-dialog" aria-labelledby="item-dialog-title" class="admin-dialog max-w-3xl!">
        <form id="item-form" data-url="{{ route('projects.index') }}" novalidate class="flex flex-col gap-5 p-6">
            @include('partials.dialog-title', ['title' => 'Add project'])

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name_en" label="Name, English" required maxlength="120" />
                <x-field name="name_bn" label="Name, Bangla" lang="bn" maxlength="120" />
                <x-field name="tagline_en" label="Tagline, English" required maxlength="200" />
                <x-field name="tagline_bn" label="Tagline, Bangla" lang="bn" maxlength="200" />
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div class="flex flex-col gap-2">
                    <label for="status" class="text-xs font-medium text-sand">Status <span class="text-brass">*</span></label>
                    <select id="status" name="status" class="field">
                        <option value="ongoing">Ongoing</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                    </select>
                    <p data-error="status" role="alert" hidden class="text-xs text-danger"></p>
                </div>
                <div class="flex flex-col gap-2">
                    <label for="type" class="text-xs font-medium text-sand">Type <span class="text-brass">*</span></label>
                    <select id="type" name="type" class="field">
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                    </select>
                    <p data-error="type" role="alert" hidden class="text-xs text-danger"></p>
                </div>
                <x-field name="handover" label="Handover year" required inputmode="numeric" maxlength="4" placeholder="2029" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="area_name_en" label="Area name, English" required maxlength="120" placeholder="Bashundhara R/A" />
                <x-field name="area_name_bn" label="Area name, Bangla" lang="bn" maxlength="120" />
                <x-field name="address_en" label="Address, English" required maxlength="255" hint="One line." />
                <x-field name="address_bn" label="Address, Bangla" lang="bn" maxlength="255" />
            </div>

            <div class="flex min-w-0 flex-col gap-2">
                <label for="cover_file" class="text-xs font-medium text-sand">Cover image <span class="text-brass">*</span></label>
                <img id="cover_file-preview" alt="" hidden class="aspect-[4/3] w-48 rounded-lg object-cover">
                <input id="cover_file" name="cover_file" type="file" accept="image/jpeg,image/png,image/webp" class="file-input">
                <p class="text-xs text-muted">At least 1200 px wide, up to 10 MB.</p>
                <p data-error="cover_file" role="alert" hidden class="text-xs text-danger"></p>
            </div>

            <fieldset class="flex flex-col gap-3 rounded-xl border border-line p-4">
                <legend class="eyebrow px-2">Card stats</legend>
                <p class="text-xs text-muted">Up to 3, such as 4 / Katha. Leave a row blank to skip it.</p>
                <div class="hidden grid-cols-4 gap-3 text-xs font-medium text-sand sm:grid" aria-hidden="true">
                    <span>Value, English</span><span>Label, English</span><span>Value, Bangla</span><span>Label, Bangla</span>
                </div>
                @foreach ([0, 1, 2] as $row)
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <input name="stats[{{ $row }}][value_en]" aria-label="Stat {{ $row + 1 }} value, English" maxlength="20" class="field" placeholder="4">
                        <input name="stats[{{ $row }}][label_en]" aria-label="Stat {{ $row + 1 }} label, English" maxlength="30" class="field" placeholder="Katha">
                        <input name="stats[{{ $row }}][value_bn]" aria-label="Stat {{ $row + 1 }} value, Bangla" maxlength="20" lang="bn" class="field">
                        <input name="stats[{{ $row }}][label_bn]" aria-label="Stat {{ $row + 1 }} label, Bangla" maxlength="30" lang="bn" class="field">
                    </div>
                @endforeach
                <p data-error="stats" role="alert" hidden class="text-xs text-danger"></p>
            </fieldset>

            <label class="flex w-fit items-center gap-2.5 text-sm">
                <input type="checkbox" name="is_featured" value="1" class="size-4 accent-brass">
                Featured: show first on the home page
            </label>

            <fieldset class="flex flex-col gap-4 rounded-xl border border-line p-4">
                <legend class="eyebrow px-2">SEO</legend>
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="slug" label="Web address" maxlength="120" placeholder="delwar-garden" hint="Blank: built from the name. Changing it later breaks existing links." />
                    <x-field name="area_slug" label="Area web address" maxlength="120" placeholder="bashundhara" hint="Blank: built from the area name." />
                    <x-field name="cover_alt_en" label="Cover description, English" required maxlength="255" hint='Include "(architectural rendering)" for buildings.' />
                    <x-field name="cover_alt_bn" label="Cover description, Bangla" lang="bn" maxlength="255" />
                    <x-field name="seo_title_en" label="Page title, English" maxlength="60" hint="Up to 60 characters. Blank: built from the name and area." />
                    <x-field name="seo_title_bn" label="Page title, Bangla" lang="bn" maxlength="60" />
                    <x-field name="seo_description_en" label="Page description, English" :rows="2" maxlength="160" hint="Up to 160 characters. Used on the detail page." />
                    <x-field name="seo_description_bn" label="Page description, Bangla" lang="bn" :rows="2" maxlength="160" />
                </div>
            </fieldset>

            @include('partials.dialog-actions', ['save' => 'Add project'])
        </form>
    </dialog>
@endsection
