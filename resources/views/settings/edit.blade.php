@extends('layouts.admin', ['title' => 'Settings'])

@push('scripts')
    @vite('resources/js/page-form.js')
@endpush

@section('content')
    <header>
        <p class="eyebrow">Administration</p>
        <h1 class="mt-2 text-3xl font-bold">Settings</h1>
        <p class="mt-2 max-w-2xl text-sm text-muted">
            Company name, logos and SEO reach the website at the next website build. Email settings work as soon as they are saved. The admin panel updates straight away.
        </p>
    </header>

    <form id="page-form" method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" novalidate class="mt-8 flex max-w-4xl flex-col gap-4"
        data-confirm-title="Save settings?" data-confirm-text="This replaces the current settings.">
        @csrf
        @method('PUT')

        <section class="panel flex flex-col gap-5">
            <h2 class="panel-title">Company</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="company_name" label="Company name" required :value="$settings->company_name" hint="For example Delight Real Estate" />
                <x-field name="legal_name" label="Registered name" required :value="$settings->legal_name" hint="For example Delight Real Estate Ltd. Used in the footer and for Google." />
            </div>
        </section>

        <section class="panel flex flex-col gap-5">
            <div>
                <h2 class="panel-title">Logos</h2>
                <p class="panel-note">PNG, WebP or JPG, up to 2 MB. A transparent PNG looks best. Until you upload one, the website's built-in logo is used.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-image-setting name="logo_header" label="Header logo, for dark backgrounds" :current="$settings->logo_header" fallback="/brand/delight-logo-horizontal-white.svg" hint="Also used in this admin panel." />
                <x-image-setting name="logo_header_light" label="Header logo, for light mode" light :current="$settings->logo_header_light" fallback="/brand/delight-logo-horizontal.svg" />
                <x-image-setting name="logo_footer" label="Footer logo, for dark backgrounds" :current="$settings->logo_footer" fallback="/brand/delight-logo-white.svg" />
                <x-image-setting name="logo_footer_light" label="Footer logo, for light mode" light :current="$settings->logo_footer_light" fallback="/brand/delight-logo.svg" />
                <x-image-setting name="favicon" label="Favicon, for this admin panel" accept="image/png" :current="$settings->favicon" fallback="/brand/icon-192.png" hint="A square PNG, 48 to 1024 px. The website's own favicon is part of its build." />
            </div>
        </section>

        <section class="panel flex flex-col gap-5">
            <div>
                <h2 class="panel-title">Email for website enquiries</h2>
                <p class="panel-note">Your mail host shows these details. In cPanel: Email Accounts, then Connect Devices.</p>
            </div>

            <x-field name="lead_recipient" label="Send enquiries to" type="email" :value="$settings->lead_recipient" hint="Every enquiry from the website's forms goes to this address." />

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="mail_from_address" label="Sender email" type="email" :value="$settings->mail_from_address" hint="A mailbox on your own domain, so the mail is not marked as spam." />
                <x-field name="mail_from_name" label="Sender name" :value="$settings->mail_from_name" />
                <x-field name="smtp_host" label="SMTP server" :value="$settings->smtp_host" placeholder="mail.delightre.com" />
                <div class="grid grid-cols-2 gap-5">
                    <x-field name="smtp_port" label="Port" type="number" :value="$settings->smtp_port" placeholder="465" />
                    <div class="flex flex-col gap-2">
                        <label for="smtp_encryption" class="text-xs font-medium text-sand">Security</label>
                        <select id="smtp_encryption" name="smtp_encryption" class="field">
                            <option value="ssl" @selected($settings->smtp_encryption === 'ssl')>SSL (port 465)</option>
                            <option value="tls" @selected($settings->smtp_encryption === 'tls')>TLS (port 587)</option>
                        </select>
                        <p data-error="smtp_encryption" role="alert" hidden class="text-xs text-danger"></p>
                    </div>
                </div>
                <x-field name="smtp_username" label="SMTP username" :value="$settings->credential('smtp_username')" autocomplete="off" hint="Stored encrypted." />
                <x-field name="smtp_password" label="SMTP password" type="password" autocomplete="new-password"
                    :placeholder="$settings->hasSmtpPassword() ? '••••••••  (saved)' : ''"
                    :hint="$settings->hasSmtpPassword() ? 'Stored encrypted and never shown. Leave blank to keep it.' : 'Stored encrypted and never shown again.'" />
            </div>

            <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
                <button type="button" id="test-mail" data-url="{{ route('settings.test-mail') }}" data-recipient="{{ $settings->lead_recipient }}" class="btn-ghost">Send test email</button>
                <p class="text-xs text-muted">Uses the saved settings, so save any changes first.</p>
            </div>
        </section>

        <section class="panel flex flex-col gap-5">
            <div>
                <h2 class="panel-title">SEO for the home page</h2>
                <p class="panel-note">What Google and link previews show for the site. If blank, the website's current wording is used. Blank Bangla uses English.</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="seo_title_en" label="Title, English" :value="$settings->seo_title_en" maxlength="60" hint="Up to 60 characters, or Google cuts it short." />
                <x-field name="seo_title_bn" label="Title, Bangla" lang="bn" :value="$settings->seo_title_bn" maxlength="60" />
                <x-field name="seo_description_en" label="Description, English" :rows="3" :value="$settings->seo_description_en" maxlength="160" hint="Up to 160 characters." />
                <x-field name="seo_description_bn" label="Description, Bangla" :rows="3" lang="bn" :value="$settings->seo_description_bn" maxlength="160" />
            </div>
            <div class="max-w-sm">
                <x-image-setting name="seo_image" label="Share image" accept="image/jpeg,image/png" :current="$settings->seo_image['src'] ?? null" hint="JPG or PNG, at least 1200 x 630 px. Shown when the site is shared on Facebook or WhatsApp." />
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Save settings</button>
        </div>
    </form>
@endsection
