@extends('layouts.admin', ['title' => 'Dashboard'])

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush

@section('content')
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">Overview</p>
            <h1 class="mt-2 text-3xl font-bold">Website content</h1>
        </div>
        <p class="text-xs text-muted">
            Last edit: <span class="font-medium text-bone">{{ $lastEdit?->format('j M Y, g:i a') ?? 'None yet' }}</span>
        </p>
    </header>

    {{-- Stat cards --}}
    <section aria-label="Content totals" class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($types as $type)
            <article class="panel relative overflow-hidden">
                <div class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-brass to-transparent"></div>
                <h2 class="text-xs font-medium text-muted">{{ $type['label'] }}</h2>
                <p class="mt-3 text-4xl font-bold tabular-nums">{{ $type['live'] }}</p>
                <p class="mt-2 text-xs text-muted">
                    live on the site
                    @if ($type['hidden'] > 0)
                        · {{ $type['hidden'] }} hidden
                    @endif
                </p>
            </article>
        @endforeach
    </section>

    {{-- Charts --}}
    <section aria-label="Content charts" class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
        <article class="panel">
            <h2 class="panel-title">Projects by status</h2>
            <p class="panel-note">Every project, live or hidden</p>

            <div class="relative mt-5 h-48">
                <canvas id="status-chart" role="img" aria-label="Projects by status: @foreach ($charts['status'] as $i => $status){{ $charts['statusCounts'][$i] }} {{ $status }}{{ $loop->last ? '' : ', ' }}@endforeach"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-3xl font-bold tabular-nums">{{ $types['Projects']['total'] }}</span>
                    <span class="text-[10px] tracking-widest text-muted uppercase">Total</span>
                </div>
            </div>

            <ul class="mt-5 flex justify-center gap-5 text-xs text-muted">
                @foreach ($charts['status'] as $i => $status)
                    <li class="flex items-center gap-2">
                        <span @class(['size-2 rounded-full', 'bg-[#b58a3e]' => $i === 0, 'bg-[#4a92cc]' => $i === 1, 'bg-[#d4614f]' => $i === 2])></span>
                        <span class="capitalize">{{ $status }}</span>
                        <span class="font-semibold text-bone tabular-nums">{{ $charts['statusCounts'][$i] }}</span>
                    </li>
                @endforeach
            </ul>
        </article>

        <article class="panel">
            <h2 class="panel-title">Live and hidden items</h2>
            <p class="panel-note">What visitors can see in each section</p>
            <div class="relative mt-5 h-64">
                <canvas id="visibility-chart" role="img" aria-label="Live and hidden items per content type. The table below lists every item."></canvas>
            </div>
        </article>

        <article class="panel lg:col-span-2 xl:col-span-1">
            <h2 class="panel-title">Bangla coverage</h2>
            <p class="panel-note">Blank Bangla fields fall back to English on the site</p>
            <div class="relative mt-5 h-64">
                <canvas id="bangla-chart" role="img" aria-label="Share of Bangla fields filled per content type. The table below shows each item."></canvas>
            </div>
        </article>
    </section>

    <section class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
        <article class="panel xl:col-span-2">
            <h2 class="panel-title">Recent edits</h2>
            <p class="panel-note">Items by the day they were last changed, past 14 days</p>
            <div class="relative mt-5 h-64">
                <canvas id="edits-chart" role="img" aria-label="Line chart of items last edited per day over the past 14 days."></canvas>
            </div>
        </article>

        <article class="panel">
            <h2 class="panel-title">Contact details</h2>
            <p class="panel-note">Shown in the footer, contact page and WhatsApp button</p>

            @if ($contact)
                <dl class="mt-5 flex flex-col gap-3.5 text-sm">
                    <div>
                        <dt class="text-xs text-muted">Hotline</dt>
                        <dd class="mt-0.5 font-medium">{{ $contact->hotline_display }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">WhatsApp</dt>
                        <dd class="mt-0.5 font-medium">{{ $contact->whatsapp }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Email</dt>
                        <dd class="mt-0.5 font-medium break-all">{{ $contact->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted">Office</dt>
                        <dd class="mt-0.5 font-medium">{{ $contact->street }}, {{ $contact->locality }}, {{ $contact->city }} {{ $contact->postal_code }}</dd>
                    </div>
                    <div class="flex flex-wrap gap-x-6 gap-y-2 border-t border-line pt-3.5 text-xs">
                        <div class="flex gap-1.5">
                            <dt class="text-muted">Floor</dt>
                            <dd class="font-medium">{{ $contact->show_floor ? 'Shown' : 'Hidden' }}</dd>
                        </div>
                        <div class="flex gap-1.5">
                            <dt class="text-muted">Opening hours</dt>
                            <dd class="font-medium">{{ $contact->show_hours ? 'Shown' : 'Hidden' }}</dd>
                        </div>
                    </div>
                </dl>
            @else
                <p class="mt-5 text-sm text-muted">No contact details have been added yet.</p>
            @endif
        </article>
    </section>

    {{-- Data table --}}
    <section class="panel mt-4">
        <h2 class="panel-title">All content</h2>
        <p class="panel-note mb-5">Search and sort every item across the site</p>

        <table id="inventory-table">
            <thead>
                <tr>
                    <th>Section</th>
                    <th>Item</th>
                    <th>Details</th>
                    <th>Bangla %</th>
                    <th>Visibility</th>
                    <th>Last edited</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="whitespace-nowrap text-muted">{{ $row['type'] }}</td>
                        <td class="font-medium">{{ $row['title'] }}</td>
                        <td class="text-muted">{{ $row['detail'] }}</td>
                        <td class="tabular-nums">{{ $row['bangla'] }}</td>
                        <td>{{ $row['active'] ? 'Live' : 'Hidden' }}</td>
                        <td class="whitespace-nowrap text-muted tabular-nums">{{ $row['edited'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <script type="application/json" id="dashboard-data">@json($charts)</script>
@endsection
