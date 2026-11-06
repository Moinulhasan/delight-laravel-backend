<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManagesOrderedList;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Services\WebpVariants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use ManagesOrderedList;

    public function __construct(private WebpVariants $images) {}

    public function index(Request $request): View|RedirectResponse
    {
        $projects = Project::orderBy('sort_order')->paginate(10);

        return $this->pageOverflow($projects, $request) ?? view('projects.index', ['projects' => $projects]);
    }

    public function store(ProjectRequest $request): JsonResponse
    {
        Project::create($this->fields($request) + [
            'cover_image' => $this->storeCover($request),
            'sort_order' => Project::max('sort_order') + 1,
        ]);

        return $this->done('Project added.');
    }

    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $changes = $this->fields($request);
        $replaced = null;

        if ($request->hasFile('cover_file')) {
            $changes['cover_image'] = $this->storeCover($request);
            $replaced = $project->cover_image;
        }

        $project->update($changes);
        $this->images->delete($replaced);

        return $this->done('Project updated.');
    }

    public function toggle(Project $project): JsonResponse
    {
        $project->update(['is_active' => ! $project->is_active]);

        return $this->done($project->is_active ? 'Project activated.' : 'Project deactivated.');
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();
        $this->images->delete($project->cover_image);

        return $this->done('Project deleted.');
    }

    public function reorder(Request $request): JsonResponse
    {
        return $this->saveOrder($request, Project::class);
    }

    private function fields(ProjectRequest $request): array
    {
        return $request->safe()->only([
            'name_en', 'name_bn', 'slug', 'tagline_en', 'tagline_bn', 'status', 'type', 'handover',
            'area_slug', 'area_name_en', 'area_name_bn', 'address_en', 'address_bn', 'cover_alt_en', 'cover_alt_bn',
            'seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn',
        ]) + [
            'card_stats' => $request->cardStats(),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }

    private function storeCover(ProjectRequest $request): array
    {
        $base = $request->input('slug').'-'.Str::lower(Str::random(6));

        return $this->images->store($request->file('cover_file'), 'projects', $base, [1200], 1200, 'cover_file');
    }
}
