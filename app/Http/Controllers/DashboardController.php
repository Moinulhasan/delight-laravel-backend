<?php

namespace App\Http\Controllers;

use App\Models\ContactDetail;
use App\Models\HeroSlide;
use App\Models\Leader;
use App\Models\Project;
use App\Models\Video;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The optional Bangla fields of each content type, for the translation chart. */
    private const BANGLA_FIELDS = [
        'Projects' => ['name_bn', 'tagline_bn', 'area_name_bn', 'address_bn', 'cover_alt_bn'],
        'Hero slides' => ['alt_bn'],
        'Videos' => ['title_bn'],
        'Leaders' => ['role_bn', 'quote_bn', 'photo_alt_bn'],
    ];

    public function __invoke(): View
    {
        // shortcut: loads whole tables, fine for the few dozen rows a site like this holds.
        $content = collect([
            'Projects' => Project::orderBy('sort_order')->get(),
            'Hero slides' => HeroSlide::orderBy('sort_order')->get(),
            'Videos' => Video::orderBy('sort_order')->get(),
            'Leaders' => Leader::orderBy('sort_order')->get(),
        ]);
        $contact = ContactDetail::first();

        $types = $content->map(fn ($items, $label) => [
            'label' => $label,
            'total' => $items->count(),
            'live' => $items->where('is_active', true)->count(),
            'hidden' => $items->where('is_active', false)->count(),
            'bangla' => $this->banglaPercent($items, self::BANGLA_FIELDS[$label]),
        ]);

        $edited = $content->flatten(1)->when($contact, fn ($all) => $all->push($contact))->filter();
        $lastEdit = $edited->max('updated_at');
        $editsByDay = $edited->countBy(fn ($item) => $item->updated_at->toDateString());
        $days = collect(range(13, 0))->map(fn ($ago) => now()->subDays($ago));

        return view('dashboard', [
            'types' => $types,
            'contact' => $contact,
            'lastEdit' => $lastEdit,
            'rows' => $this->inventory($content),
            'charts' => [
                'status' => ['ongoing', 'upcoming', 'completed'],
                'statusCounts' => collect(['ongoing', 'upcoming', 'completed'])
                    ->map(fn ($status) => $content['Projects']->where('status', $status)->count()),
                'types' => $types->values(),
                'editLabels' => $days->map(fn ($day) => $day->format('j M')),
                'editCounts' => $days->map(fn ($day) => $editsByDay->get($day->toDateString(), 0)),
            ],
        ]);
    }

    private function banglaPercent($items, array $fields): int
    {
        $possible = $items->count() * count($fields);

        if ($possible === 0) {
            return 0;
        }

        $filled = $items->sum(fn ($item) => collect($fields)->filter(fn ($field) => filled($item->{$field}))->count());

        return (int) round($filled / $possible * 100);
    }

    /** One row per content item, across all types, for the data table. */
    private function inventory($content): array
    {
        $describe = [
            'Projects' => fn ($item) => [$item->name_en, Str::ucfirst($item->status).' · '.$item->area_name_en.' · '.$item->handover],
            'Hero slides' => fn ($item) => [Str::limit($item->alt_en, 60), 'Slide '.$item->sort_order],
            'Videos' => fn ($item) => [$item->title_en, 'YouTube '.$item->youtube_id],
            'Leaders' => fn ($item) => [$item->name, $item->role_en],
        ];

        return $content->flatMap(fn ($items, $label) => $items->map(function ($item) use ($describe, $label) {
            [$title, $detail] = $describe[$label]($item);

            return [
                'type' => $label,
                'title' => $title,
                'detail' => $detail,
                'bangla' => $this->banglaPercent(collect([$item]), self::BANGLA_FIELDS[$label]),
                'active' => $item->is_active,
                'edited' => $item->updated_at->format('Y-m-d H:i'),
            ];
        }))->values()->all();
    }
}
