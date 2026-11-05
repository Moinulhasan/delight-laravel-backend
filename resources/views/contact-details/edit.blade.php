@extends('layouts.admin', ['title' => 'Contact details'])

@push('scripts')
    @vite('resources/js/page-form.js')
@endpush

@section('content')
    <header>
        <p class="eyebrow">Whole website</p>
        <h1 class="mt-2 text-3xl font-bold">Contact details</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">
            Shown in the footer, contact page, contact tiles beside forms and the WhatsApp button. Changes reach visitors within about a minute. Search engines see them after the next website build.
        </p>
    </header>

    <form id="page-form" method="POST" action="{{ route('contact-details.update') }}" novalidate class="mt-8 flex max-w-4xl flex-col gap-4"
        data-confirm-title="Save contact details?" data-confirm-text="Visitors will see the new details within about a minute.">
        @csrf
        @method('PUT')

        <section class="panel flex flex-col gap-5">
            <h2 class="panel-title">Phone and email</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="hotline_display" label="Hotline as shown" required :value="$contact->hotline_display" hint="For example +880 1713-903025" />
                <x-field name="hotline_e164" label="Hotline for calling" required :value="$contact->hotline_e164" hint="+ and 8 to 15 digits, no spaces." />
                <x-field name="whatsapp" label="WhatsApp number" required :value="$contact->whatsapp" hint="+ and 8 to 15 digits, no spaces." />
                <x-field name="email" label="Email" type="email" required :value="$contact->email" />
            </div>
        </section>

        <section class="panel flex flex-col gap-5">
            <h2 class="panel-title">Office</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-field name="street" label="Street" required :value="$contact->street" /></div>
                <x-field name="locality" label="Locality" required :value="$contact->locality" />
                <x-field name="city" label="City" required :value="$contact->city" />
                <x-field name="postal_code" label="Postal code" required :value="$contact->postal_code" />
            </div>
            <div class="flex flex-col gap-3 border-t border-line pt-5">
                <div class="max-w-sm"><x-field name="floor" label="Floor" :value="$contact->floor" /></div>
                <x-switch name="show_floor" label="Show the floor on the website" :checked="$contact->show_floor" hint="Leave off until the client confirms it." />
            </div>
        </section>

        <section class="panel flex flex-col gap-5">
            <h2 class="panel-title">Opening hours</h2>
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 text-xs font-medium text-sand">Opening days</legend>
                <div class="flex flex-wrap gap-x-5 gap-y-2">
                    @foreach ($days as $day)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="opening_days[]" value="{{ $day }}" @checked(in_array($day, $contact->opening_days ?? [], true)) class="size-4 accent-brass">
                            {{ $day }}
                        </label>
                    @endforeach
                </div>
                <p data-error="opening_days" role="alert" hidden class="text-xs text-danger"></p>
            </fieldset>
            <div class="grid max-w-md gap-5 sm:grid-cols-2">
                <x-field name="opens" label="Opens" type="time" :value="$contact->opens" />
                <x-field name="closes" label="Closes" type="time" :value="$contact->closes" />
            </div>
            <x-switch name="show_hours" label="Show opening hours on the website" :checked="$contact->show_hours" hint="Leave off until the client confirms them." />
        </section>

        <section class="panel flex flex-col gap-2">
            <h2 class="panel-title">SEO</h2>
            <p class="text-sm text-muted">Nothing extra to fill in: Google's business details (name, address, phone, email, and hours when shown) are built from this page at the next website build.</p>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save contact details</button>
        </div>
    </form>
@endsection
