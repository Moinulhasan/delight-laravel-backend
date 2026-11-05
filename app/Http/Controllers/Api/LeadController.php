<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LeadReceived;
use App\Models\Setting;
use App\Services\SiteMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Website enquiries, emailed with the SMTP details and recipient from Settings. Same rules and
 * response shape as api/lead.php in the website repo (LeadResult in src/lib/leads/schema.ts), so
 * switching means only pointing NEXT_PUBLIC_LEADS_ENDPOINT here.
 */
class LeadController extends Controller
{
    private const KINDS = ['general' => 'General enquiry', 'project' => 'Project enquiry', 'landowner' => 'Landowner (joint venture)', 'abroad' => 'Buying from abroad'];

    private const COUNTRIES = ['bd' => 'Bangladesh', 'me' => 'Middle East', 'uk' => 'United Kingdom / Europe', 'na' => 'North America', 'au' => 'Australia', 'other' => 'Other'];

    private const TIMES = ['any' => 'Any time', 'morning' => 'Morning', 'afternoon' => 'Afternoon', 'evening' => 'Evening'];

    private const INTERESTS = [
        'project' => 'A specific project', 'upcoming' => 'Upcoming projects', 'jointVenture' => 'Joint venture (land)',
        'general' => 'General question', 'visit' => 'Site visit', 'priceList' => 'Price list', 'videoTour' => 'Video site tour',
        'buyingAbroad' => 'Buying from abroad',
    ];

    public function __invoke(Request $request, SiteMailer $mailer): JsonResponse
    {
        $text = function (string $key, int $max = 200) use ($request): string {
            $value = $request->input($key);
            $pattern = $key === 'message' ? '/[\x00-\x09\x0B-\x1F\x7F]/u' : '/[\x00-\x1F\x7F]/u';

            return Str::substr(preg_replace($pattern, ' ', is_scalar($value) ? trim((string) $value) : '') ?? '', 0, $max);
        };

        // Honeypot: bots get the normal answer and learn nothing.
        if ($text('website', 500) !== '') {
            return response()->json(['ok' => true, 'delivered' => 'logged', 'name' => $text('name', 120), 'phone' => $text('phone', 40)]);
        }

        $lead = [
            'kind' => $text('kind', 20),
            'name' => $text('name', 120),
            'phone' => $text('phone', 40),
            'phoneE164' => $text('phoneE164', 20),
            'country' => $text('country', 10) ?: 'bd',
            'interest' => $text('interest', 30),
            'project' => $text('project', 80),
            'time' => $text('time', 20) ?: 'any',
            'message' => $text('message', 2000),
            'landLocation' => $text('landLocation'),
            'landSize' => $text('landSize', 20),
            'roadWidth' => $text('roadWidth', 20),
            'documents' => $text('documents', 300),
            'timezone' => $text('timezone', 64),
            'slot' => $text('slot', 64),
            'utmSource' => $text('utmSource', 100),
            'utmMedium' => $text('utmMedium', 100),
            'utmCampaign' => $text('utmCampaign', 100),
            'pageUrl' => $text('pageUrl', 500),
        ];

        $errors = $this->errors($lead, $request);

        if ($errors) {
            return response()->json(['ok' => false, 'reason' => 'invalid', 'fields' => $errors], 422);
        }

        $phone = preg_match('/^\+[1-9]\d{7,14}$/', $lead['phoneE164']) ? $lead['phoneE164'] : $lead['phone'];

        try {
            $mailer->send($this->mail($lead, $phone));
        } catch (Throwable $e) {
            // Never the lead's details: only that delivery failed, and why.
            Log::error('Lead email failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'reason' => 'failed'], 502);
        }

        return response()->json(['ok' => true, 'delivered' => 'email', 'name' => $lead['name'], 'phone' => $phone]);
    }

    private function errors(array $lead, Request $request): array
    {
        return array_filter([
            'kind' => isset(self::KINDS[$lead['kind']]) ? null : 'generic',
            'name' => Str::length($lead['name']) >= 2 ? null : 'name',
            'phone' => $lead['phone'] !== '' ? null : 'phone',
            'country' => isset(self::COUNTRIES[$lead['country']]) ? null : 'generic',
            'interest' => isset(self::INTERESTS[$lead['interest']]) ? null : 'generic',
            'time' => isset(self::TIMES[$lead['time']]) ? null : 'generic',
            'project' => $lead['project'] === '' || preg_match('/^[a-z0-9-]+$/', $lead['project']) ? null : 'generic',
            'consent' => $request->input('consent') === true ? null : 'consent',
            'landLocation' => $lead['kind'] === 'landowner' && $lead['landLocation'] === '' ? 'required' : null,
            'landSize' => $lead['landSize'] === '' || is_numeric($lead['landSize']) ? null : 'number',
        ]);
    }

    private function mail(array $lead, string $phone): LeadReceived
    {
        $project = $lead['project'] !== '' ? Str::title(str_replace('-', ' ', $lead['project'])) : '';
        $filled = fn (array $values) => array_filter($values, fn ($value) => $value !== '');

        // Only links back to the website itself appear in the email.
        $fromOurSite = collect(config('cors.allowed_origins'))->contains(fn ($origin) => str_starts_with($lead['pageUrl'], "{$origin}/"));

        return new LeadReceived(
            leadName: $lead['name'],
            phone: $phone,
            rows: $filled([
                'Form' => self::KINDS[$lead['kind']],
                'Name' => $lead['name'],
                'Phone' => $phone,
                'Lives in' => self::COUNTRIES[$lead['country']],
                'Interested in' => self::INTERESTS[$lead['interest']],
                'Project' => $project,
                'Best time to call' => self::TIMES[$lead['time']],
                'Time zone' => $lead['timezone'],
                'Preferred slot' => $lead['slot'],
                'Land location' => $lead['landLocation'],
                'Land size' => $lead['landSize'] !== '' ? "{$lead['landSize']} katha" : '',
                'Road width' => $lead['roadWidth'] !== '' ? "{$lead['roadWidth']} ft" : '',
                'Documents' => $lead['documents'],
                'Message' => $lead['message'],
            ]),
            source: $filled([
                'Page' => $fromOurSite ? $lead['pageUrl'] : '',
                'UTM source' => $lead['utmSource'],
                'UTM medium' => $lead['utmMedium'],
                'UTM campaign' => $lead['utmCampaign'],
            ]),
            received: now()->format('j M Y, g:i A').' (Dhaka)',
            company: Setting::current()->company_name,
            mailSubject: "New enquiry: {$lead['name']} · ".($project ?: self::KINDS[$lead['kind']]),
        );
    }
}
