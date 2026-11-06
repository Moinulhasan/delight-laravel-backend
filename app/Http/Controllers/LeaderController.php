<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOrderedList;
use App\Http\Requests\LeaderRequest;
use App\Models\Leader;
use App\Services\WebpVariants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeaderController extends Controller
{
    use ManagesOrderedList;

    private const LAST_ACTIVE = 'The website needs at least one active leader. Activate another leader first.';

    public function __construct(private WebpVariants $images) {}

    public function index(Request $request): View|RedirectResponse
    {
        $leaders = Leader::orderBy('sort_order')->paginate(10);

        return $this->pageOverflow($leaders, $request) ?? view('leaders.index', ['leaders' => $leaders]);
    }

    public function store(LeaderRequest $request): JsonResponse
    {
        Leader::create($this->fields($request) + [
            'photo' => $request->hasFile('photo_image') ? $this->storePhoto($request) : null,
            'sort_order' => Leader::max('sort_order') + 1,
        ]);

        return $this->done('Leader added.');
    }

    public function update(LeaderRequest $request, Leader $leader): JsonResponse
    {
        $changes = $this->fields($request);
        $replaced = null;

        if ($request->hasFile('photo_image') || $request->boolean('remove_photo')) {
            $changes['photo'] = $request->hasFile('photo_image') ? $this->storePhoto($request) : null;
            $replaced = $leader->photo;
        }

        $leader->update($changes);
        $this->images->delete($replaced);

        return $this->done('Leader updated.');
    }

    public function toggle(Leader $leader): JsonResponse
    {
        if ($leader->is_active && $this->isLastActive()) {
            return response()->json(['message' => self::LAST_ACTIVE], 422);
        }

        $leader->update(['is_active' => ! $leader->is_active]);

        return $this->done($leader->is_active ? 'Leader activated.' : 'Leader deactivated.');
    }

    public function destroy(Leader $leader): JsonResponse
    {
        if ($leader->is_active && $this->isLastActive()) {
            return response()->json(['message' => self::LAST_ACTIVE], 422);
        }

        $leader->delete();
        $this->images->delete($leader->photo);

        return $this->done('Leader deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->saveOrder($request, Leader::class);
    }

    private function fields(LeaderRequest $request): array
    {
        return $request->safe()->only(['name', 'initials', 'role_en', 'role_bn', 'quote_en', 'quote_bn', 'photo_alt_en', 'photo_alt_bn']);
    }

    private function storePhoto(LeaderRequest $request): array
    {
        $base = (Str::slug(Str::limit($request->input('name'), 40, '')) ?: 'leader').'-'.Str::lower(Str::random(6));

        return $this->images->store($request->file('photo_image'), 'leaders', $base, [800], 800, 'photo_image');
    }

    private function isLastActive(): bool
    {
        return Leader::where('is_active', true)->count() <= 1;
    }
}
