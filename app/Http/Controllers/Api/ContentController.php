<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactDetail;
use App\Models\HeroSlide;
use App\Models\Setting;
use App\Models\Video;
use Illuminate\Http\JsonResponse;

/**
 * The one public, read-only content endpoint. Active items only; never any user data or anything
 * from the mail settings: `site` lists its fields one by one for that reason.
 */
class ContentController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $content = [
            'heroSlides' => HeroSlide::where('is_active', true)->orderBy('sort_order')->get()->map(fn (HeroSlide $slide) => [
                'alt' => $this->localized($slide->alt_en, $slide->alt_bn),
                'desktop' => $this->absolute($slide->desktop_images),
                // No mobile image: the site shows the desktop one, cropped from its centre by CSS.
                'mobile' => $this->absolute($slide->mobile_images ?? $slide->desktop_images),
            ])->all(),
            'videos' => Video::where('is_active', true)->orderBy('sort_order')->get()->map(fn (Video $video) => array_filter([
                'youtubeId' => $video->youtube_id,
                'title' => $this->localized($video->title_en, $video->title_bn),
                'description' => filled($video->description_en) ? $this->localized($video->description_en, $video->description_bn) : null,
                'poster' => $video->poster_image ? ['src' => url($video->poster_image['src']), 'w' => $video->poster_image['w'], 'h' => $video->poster_image['h']] : null,
                'uploadDate' => $video->upload_date?->toDateString(),
                'duration' => $video->isoDuration(),
            ]))->all(),
            'contacts' => $this->contacts(ContactDetail::first()),
            'site' => $this->site(Setting::current()),
        ];

        // A hash of the content, so it also changes on deletes (a newest-updated_at time would not).
        return response()
            ->json(['version' => sha1(json_encode($content))] + $content)
            ->header('Cache-Control', 'public, max-age=60');
    }

    /** `floor` and `hours` are null while "Show on website" is off, so hidden values never leave the server. */
    private function contacts(?ContactDetail $contact): ?array
    {
        if (! $contact) {
            return null;
        }

        $showHours = $contact->show_hours && $contact->opening_days && $contact->opens && $contact->closes;

        return [
            'hotline' => ['display' => $contact->hotline_display, 'e164' => $contact->hotline_e164],
            'whatsapp' => $contact->whatsapp,
            'email' => $contact->email,
            'office' => [
                'street' => $contact->street,
                'floor' => $contact->show_floor ? $contact->floor : null,
                'locality' => $contact->locality,
                'city' => $contact->city,
                'postalCode' => $contact->postal_code,
                'country' => $contact->country,
            ],
            'hours' => $showHours ? ['days' => $contact->opening_days, 'opens' => $contact->opens, 'closes' => $contact->closes] : null,
        ];
    }

    private function site(Setting $settings): array
    {
        $file = fn (?string $path) => $path ? url($path) : null;

        return [
            'companyName' => $settings->company_name,
            'legalName' => $settings->legal_name,
            'logos' => [
                'header' => $file($settings->logo_header),
                'headerLight' => $file($settings->logo_header_light),
                'footer' => $file($settings->logo_footer),
                'footerLight' => $file($settings->logo_footer_light),
            ],
            'seo' => [
                'title' => filled($settings->seo_title_en) ? $this->localized($settings->seo_title_en, $settings->seo_title_bn) : null,
                'description' => filled($settings->seo_description_en) ? $this->localized($settings->seo_description_en, $settings->seo_description_bn) : null,
                'image' => $settings->seo_image ? ['src' => url($settings->seo_image['src']), 'w' => $settings->seo_image['w'], 'h' => $settings->seo_image['h']] : null,
            ],
        ];
    }

    /** Blank Bangla is left out, which the website treats as "use English". */
    private function localized(string $en, ?string $bn): array
    {
        return array_filter(['en' => $en, 'bn' => $bn], 'filled');
    }

    private function absolute(array $image): array
    {
        return [
            'src' => url($image['src']),
            'srcset' => preg_replace_callback('#(^|, )(/\S+)#', fn ($match) => $match[1].url($match[2]), $image['srcset']),
        ];
    }
}
