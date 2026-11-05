<?php

namespace App\Http\Controllers;

use App\Mail\SettingsTestMail;
use App\Models\Setting;
use App\Services\SiteMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    private const LOGOS = ['logo_header', 'logo_header_light', 'logo_footer', 'logo_footer_light', 'favicon'];

    public function edit(): View
    {
        return view('settings.edit', ['settings' => Setting::current()]);
    }

    public function update(Request $request): JsonResponse
    {
        // SVG is refused: it can carry scripts, and these files are served from the admin's own domain.
        $logoRule = ['nullable', 'file', 'image', 'mimes:png,webp,jpg,jpeg', 'max:2048', Rule::dimensions()->maxWidth(4000)->maxHeight(4000)];

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['required', 'string', 'max:160'],
            'logo_header' => $logoRule,
            'logo_header_light' => $logoRule,
            'logo_footer' => $logoRule,
            'logo_footer_light' => $logoRule,
            'favicon' => ['nullable', 'file', 'image', 'mimes:png', 'max:512', Rule::dimensions()->ratio(1)->minWidth(48)->maxWidth(1024)],
            'remove' => ['array'],
            'remove.*' => [Rule::in([...self::LOGOS, 'seo_image'])],

            'lead_recipient' => ['nullable', 'email', 'max:255'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'smtp_host' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_encryption' => ['required', Rule::in(['ssl', 'tls'])],
            'smtp_username' => ['nullable', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],

            'seo_title_en' => ['nullable', 'string', 'max:60'],
            'seo_title_bn' => ['nullable', 'string', 'max:60'],
            'seo_description_en' => ['nullable', 'string', 'max:160'],
            'seo_description_bn' => ['nullable', 'string', 'max:160'],
            'seo_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120', Rule::dimensions()->minWidth(1200)->minHeight(630)],
        ], [
            'smtp_host.regex' => 'Enter just the server name, such as mail.delightre.com.',
            'favicon.mimes' => 'The favicon must be a PNG.',
            'favicon.dimensions' => 'The favicon must be square, 48 to 1024 px.',
            'logo_header.mimes' => 'Use PNG, WebP or JPG. SVG files are not accepted because they can contain scripts.',
            'logo_header_light.mimes' => 'Use PNG, WebP or JPG. SVG files are not accepted because they can contain scripts.',
            'logo_footer.mimes' => 'Use PNG, WebP or JPG. SVG files are not accepted because they can contain scripts.',
            'logo_footer_light.mimes' => 'Use PNG, WebP or JPG. SVG files are not accepted because they can contain scripts.',
            'seo_image.dimensions' => 'The share image must be at least 1200 x 630 px.',
        ]);

        $settings = Setting::current();
        $changes = collect($data)->except([...self::LOGOS, 'seo_image', 'remove', 'smtp_password'])->all();
        $oldFiles = [];

        // A blank password box means "keep the saved one": it is never sent back to the browser.
        if ($request->filled('smtp_password')) {
            $changes['smtp_password'] = $request->input('smtp_password');
        }

        foreach (self::LOGOS as $logo) {
            if ($request->hasFile($logo) || in_array($logo, $data['remove'] ?? [], true)) {
                $oldFiles[] = $settings->{$logo};
                $changes[$logo] = $request->hasFile($logo) ? $this->storeFile($request->file($logo)) : null;
            }
        }

        if ($request->hasFile('seo_image') || in_array('seo_image', $data['remove'] ?? [], true)) {
            $oldFiles[] = $settings->seo_image['src'] ?? null;
            $changes['seo_image'] = $request->hasFile('seo_image') ? $this->storeShareImage($request->file('seo_image')) : null;
        }

        $settings->update($changes);

        foreach (array_filter($oldFiles) as $file) {
            @unlink(public_path($file));
        }

        session()->flash('status', 'Settings saved.');

        return response()->json(['message' => 'Settings saved.']);
    }

    /** Sends to the saved recipient with the saved details, and reports exactly what happened. */
    public function testMail(SiteMailer $mailer): JsonResponse
    {
        try {
            $mailer->send(new SettingsTestMail(Setting::current()->company_name));
        } catch (Throwable $e) {
            return response()->json(['message' => 'The test email was not sent: '.$e->getMessage()], 422);
        }

        return response()->json(['message' => 'Test email sent to '.Setting::current()->lead_recipient.'.']);
    }

    /** The extension comes from the file's real contents, never from its uploaded name. */
    private function storeFile(UploadedFile $file): string
    {
        $name = Str::lower(Str::random(16)).'.'.$file->extension();
        $file->move(public_path('uploads/brand'), $name);

        return "/uploads/brand/{$name}";
    }

    private function storeShareImage(UploadedFile $file): array
    {
        [$width, $height] = getimagesize($file->getRealPath());

        return ['src' => $this->storeFile($file), 'w' => $width, 'h' => $height];
    }
}
