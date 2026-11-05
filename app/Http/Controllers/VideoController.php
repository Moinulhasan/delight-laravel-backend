<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOrderedList;
use App\Http\Requests\VideoRequest;
use App\Models\Video;
use App\Services\WebpVariants;
use App\Support\YouTube;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class VideoController extends Controller
{
    use ManagesOrderedList;

    public function __construct(private WebpVariants $images) {}

    public function index(Request $request): View|RedirectResponse
    {
        $videos = Video::orderBy('sort_order')->paginate(10);

        return $this->pageOverflow($videos, $request) ?? view('videos.index', ['videos' => $videos]);
    }

    public function store(VideoRequest $request): JsonResponse
    {
        Video::create($this->fields($request) + [
            'poster_image' => $request->hasFile('poster_image') ? $this->storePoster($request) : null,
            'sort_order' => Video::max('sort_order') + 1,
        ]);

        return $this->done('Video added.');
    }

    public function update(VideoRequest $request, Video $video): JsonResponse
    {
        $changes = $this->fields($request);
        $replaced = null;

        if ($request->hasFile('poster_image') || $request->boolean('remove_poster')) {
            $changes['poster_image'] = $request->hasFile('poster_image') ? $this->storePoster($request) : null;
            $replaced = $video->poster_image;
        }

        $video->update($changes);
        $this->images->delete($replaced);

        return $this->done('Video updated.');
    }

    public function toggle(Video $video): JsonResponse
    {
        $video->update(['is_active' => ! $video->is_active]);

        return $this->done($video->is_active ? 'Video activated.' : 'Video deactivated.');
    }

    public function destroy(Video $video): JsonResponse
    {
        $video->delete();
        $this->images->delete($video->poster_image);

        return $this->done('Video deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->saveOrder($request, Video::class);
    }

    private function fields(VideoRequest $request): array
    {
        return $request->safe()->only(['title_en', 'title_bn', 'description_en', 'description_bn', 'upload_date']) + [
            'youtube_id' => YouTube::idFrom($request->input('youtube_link')),
            'duration_seconds' => $request->durationSeconds(),
        ];
    }

    private function storePoster(VideoRequest $request): array
    {
        $base = (Str::slug(Str::limit($request->input('title_en'), 40, '')) ?: 'video').'-'.Str::lower(Str::random(6));

        return $this->images->store($request->file('poster_image'), 'videos', $base, [1280], 1280, 'poster_image');
    }
}
