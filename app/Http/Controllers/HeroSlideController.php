<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOrderedList;
use App\Http\Requests\HeroSlideRequest;
use App\Models\HeroSlide;
use App\Services\WebpVariants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HeroSlideController extends Controller
{
    use ManagesOrderedList;

    private const DESKTOP_WIDTHS = [1280, 1920, 2560];

    private const MOBILE_WIDTHS = [720, 1080];

    private const LAST_ACTIVE = 'The website needs at least one active slide. Activate another slide first.';

    public function __construct(private WebpVariants $images) {}

    public function index(Request $request): View|RedirectResponse
    {
        $slides = HeroSlide::orderBy('sort_order')->paginate(10);

        return $this->pageOverflow($slides, $request) ?? view('hero-slides.index', ['slides' => $slides]);
    }

    public function store(HeroSlideRequest $request): JsonResponse
    {
        $base = $this->baseName($request->string('alt_en'));

        HeroSlide::create([
            'alt_en' => $request->input('alt_en'),
            'alt_bn' => $request->input('alt_bn'),
            'desktop_images' => $this->storeDesktop($request, $base),
            'mobile_images' => $request->hasFile('mobile_image') ? $this->storeMobile($request, $base) : null,
            'sort_order' => HeroSlide::max('sort_order') + 1,
        ]);

        return $this->done('Slide added.');
    }

    public function update(HeroSlideRequest $request, HeroSlide $heroSlide): JsonResponse
    {
        $base = $this->baseName($request->string('alt_en'));
        $replaced = [];
        $changes = ['alt_en' => $request->input('alt_en'), 'alt_bn' => $request->input('alt_bn')];

        if ($request->hasFile('desktop_image')) {
            $changes['desktop_images'] = $this->storeDesktop($request, $base);
            $replaced[] = $heroSlide->desktop_images;
        }

        if ($request->hasFile('mobile_image') || $request->boolean('remove_mobile')) {
            $changes['mobile_images'] = $request->hasFile('mobile_image') ? $this->storeMobile($request, $base) : null;
            $replaced[] = $heroSlide->mobile_images;
        }

        $heroSlide->update($changes);

        // Old files go only after the new record is saved, so a failure never leaves a broken slide.
        array_walk($replaced, fn ($image) => $this->images->delete($image));

        return $this->done('Slide updated.');
    }

    public function toggle(HeroSlide $heroSlide): JsonResponse
    {
        if ($heroSlide->is_active && $this->isLastActive()) {
            return response()->json(['message' => self::LAST_ACTIVE], 422);
        }

        $heroSlide->update(['is_active' => ! $heroSlide->is_active]);

        return $this->done($heroSlide->is_active ? 'Slide activated.' : 'Slide deactivated.');
    }

    public function destroy(HeroSlide $heroSlide): JsonResponse
    {
        if ($heroSlide->is_active && $this->isLastActive()) {
            return response()->json(['message' => self::LAST_ACTIVE], 422);
        }

        $heroSlide->delete();
        $this->images->delete($heroSlide->desktop_images);
        $this->images->delete($heroSlide->mobile_images);

        return $this->done('Slide deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->saveOrder($request, HeroSlide::class);
    }

    private function storeDesktop(HeroSlideRequest $request, string $base): array
    {
        return $this->images->store($request->file('desktop_image'), 'hero', $base, self::DESKTOP_WIDTHS, 1920, 'desktop_image');
    }

    private function storeMobile(HeroSlideRequest $request, string $base): array
    {
        return $this->images->store($request->file('mobile_image'), 'hero', "{$base}-m", self::MOBILE_WIDTHS, 1080, 'mobile_image');
    }

    /** Readable file names from the alt text; the random part keeps old cached URLs from clashing. */
    private function baseName(string $alt): string
    {
        return (Str::slug(Str::limit($alt, 40, '')) ?: 'slide').'-'.Str::lower(Str::random(6));
    }

    private function isLastActive(): bool
    {
        return HeroSlide::where('is_active', true)->count() <= 1;
    }
}
