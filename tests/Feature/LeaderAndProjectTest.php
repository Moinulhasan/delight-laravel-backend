<?php

namespace Tests\Feature;

use App\Http\Requests\LeaderRequest;
use App\Models\Leader;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeaderAndProjectTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->publicDir = sys_get_temp_dir().'/lp-test-'.uniqid();
        File::ensureDirectoryExists($this->publicDir);
        $this->app->usePublicPath($this->publicDir);
        $this->seed(ContentSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    private function projectForm(array $overrides = []): array
    {
        return $overrides + [
            'name_en' => 'Lake View',
            'tagline_en' => 'homes by the lake',
            'status' => 'upcoming',
            'type' => 'residential',
            'handover' => '2031',
            'area_name_en' => 'Gulshan Lake',
            'address_en' => '1 Lake Road, Gulshan',
            'cover_file' => UploadedFile::fake()->image('cover.jpg', 1500, 1000),
            'cover_alt_en' => 'Lake View (architectural rendering)',
        ];
    }

    /* ---------- Leaders ---------- */

    #[DataProvider('names')]
    public function test_initials_are_built_from_the_name(string $name, string $initials): void
    {
        $this->assertSame($initials, LeaderRequest::initialsFrom($name));
    }

    public static function names(): array
    {
        return [
            'two words' => ['Shimu Akter', 'SA'],
            'title left out' => ['Md. Helal Uddin', 'HU'],
            'four words' => ['Abdul Karim Sheikh Rahman', 'AKS'],
            'one word' => ['Rahim', 'R'],
        ];
    }

    public function test_a_leader_is_created_with_a_photo_and_generated_initials(): void
    {
        $this->postJson('/leaders', [
            'name' => 'Md. Karim Ali',
            'role_en' => 'Director',
            'quote_en' => 'We build with care.',
            'photo_image' => UploadedFile::fake()->image('p.jpg', 1000, 1250),
            'photo_alt_en' => 'Md. Karim Ali, Director',
        ])->assertOk();

        $leader = Leader::where('name', 'Md. Karim Ali')->sole();
        $this->assertSame('KA', $leader->initials);
        $this->assertSame(3, $leader->sort_order);
        $this->assertSame([800, 1000], [$leader->photo['w'], $leader->photo['h']]);
        $this->assertFileExists(public_path($leader->photo['src']));
    }

    public function test_typed_initials_are_kept(): void
    {
        $this->postJson('/leaders', ['name' => 'Karim Ali', 'initials' => 'KMA', 'role_en' => 'Director', 'quote_en' => 'Hello'])->assertOk();

        $this->assertSame('KMA', Leader::where('name', 'Karim Ali')->sole()->initials);
    }

    #[DataProvider('badLeaders')]
    public function test_bad_leaders_are_refused(callable $overrides, string $field): void
    {
        $this->postJson('/leaders', $overrides() + ['name' => 'Karim', 'role_en' => 'Director', 'quote_en' => 'Hello'])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function badLeaders(): array
    {
        return [
            'no name' => [fn () => ['name' => ''], 'name'],
            'no role' => [fn () => ['role_en' => ''], 'role_en'],
            'no message' => [fn () => ['quote_en' => ''], 'quote_en'],
            'long initials' => [fn () => ['initials' => 'ABCD'], 'initials'],
            'photo not 4:5' => [fn () => ['photo_image' => UploadedFile::fake()->image('p.jpg', 1000, 1000), 'photo_alt_en' => 'x'], 'photo_image'],
            'photo too small' => [fn () => ['photo_image' => UploadedFile::fake()->image('p.jpg', 400, 500), 'photo_alt_en' => 'x'], 'photo_image'],
            'photo with no description' => [fn () => ['photo_image' => UploadedFile::fake()->image('p.jpg', 800, 1000)], 'photo_alt_en'],
            'not an image' => [fn () => ['photo_image' => UploadedFile::fake()->create('p.pdf', 10, 'application/pdf'), 'photo_alt_en' => 'x'], 'photo_image'],
        ];
    }

    public function test_replacing_a_photo_keeps_files_tidy_and_old_site_images_are_only_removed_when_replaced(): void
    {
        $leader = Leader::first();

        $this->post("/leaders/{$leader->id}", [
            '_method' => 'PUT', 'name' => $leader->name, 'role_en' => $leader->role_en, 'quote_en' => $leader->quote_en,
            'photo_image' => UploadedFile::fake()->image('a.jpg', 800, 1000), 'photo_alt_en' => 'x',
        ], ['Accept' => 'application/json'])->assertOk();
        $first = $leader->fresh()->photo['src'];
        $this->assertFileExists(public_path($first));

        $this->post("/leaders/{$leader->id}", [
            '_method' => 'PUT', 'name' => $leader->name, 'role_en' => $leader->role_en, 'quote_en' => $leader->quote_en,
            'remove_photo' => '1',
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertNull($leader->fresh()->photo);
        $this->assertFileDoesNotExist(public_path($first));
    }

    public function test_the_last_active_leader_is_protected(): void
    {
        [$first, $second] = Leader::orderBy('sort_order')->get()->all();

        $this->patchJson("/leaders/{$second->id}/toggle")->assertOk();

        $this->patchJson("/leaders/{$first->id}/toggle")->assertUnprocessable();
        $this->deleteJson("/leaders/{$first->id}")->assertUnprocessable();
        $this->assertTrue($first->fresh()->is_active);

        $this->deleteJson("/leaders/{$second->id}")->assertOk();
        $this->assertModelMissing($second);
    }

    public function test_leaders_in_the_api_are_active_ordered_and_localized(): void
    {
        [$first, $second] = Leader::orderBy('sort_order')->get()->all();
        $this->postJson('/leaders/reorder', ['ids' => [$second->id, $first->id]])->assertOk();
        $first->update(['role_bn' => null]);

        $leaders = $this->getJson('/api/v1/content')->json('leaders');

        $this->assertSame(['Md. Helal Uddin', 'Shimu Akter'], array_column($leaders, 'name'));
        $this->assertSame(['en' => 'Chairman'], $leaders[1]['role']);
        $this->assertSame(url('/uploads/leaders/shimu-akter-chairman-delight-real-estate.webp'), $leaders[1]['photo']['src']);
        $this->assertSame('center 30%', $leaders[1]['photo']['focus']);
        $this->assertSame('Shimu Akter, Chairman of Delight Real Estate', $leaders[1]['photo']['alt']['en']);

        $this->patchJson("/leaders/{$first->id}/toggle")->assertOk();
        $this->assertSame(['Md. Helal Uddin'], array_column($this->getJson('/api/v1/content')->json('leaders'), 'name'));
    }

    public function test_a_leader_without_a_photo_has_no_photo_in_the_api(): void
    {
        Leader::query()->update(['photo' => null]);

        $this->assertArrayNotHasKey('photo', $this->getJson('/api/v1/content')->json('leaders.0'));
    }

    /* ---------- Projects ---------- */

    public function test_a_project_is_created_with_built_addresses_and_a_webp_cover(): void
    {
        $this->post('/projects', $this->projectForm([
            'name_bn' => 'লেক ভিউ',
            'is_featured' => '1',
            'stats' => [
                ['value_en' => '4', 'label_en' => 'Katha', 'value_bn' => '৪', 'label_bn' => 'কাঠা'],
                ['value_en' => '', 'label_en' => ''],
            ],
            'seo_title_en' => 'Lake View in Gulshan',
        ]), ['Accept' => 'application/json'])->assertOk();

        $project = Project::where('slug', 'lake-view')->sole();
        $this->assertSame('gulshan-lake', $project->area_slug);
        $this->assertTrue($project->is_featured);
        $this->assertSame(2, $project->sort_order);
        $this->assertSame([['value' => ['en' => '4', 'bn' => '৪'], 'label' => ['en' => 'Katha', 'bn' => 'কাঠা']]], $project->card_stats);
        $this->assertSame([1200, 800], [$project->cover_image['w'], $project->cover_image['h']]);
        $this->assertFileExists(public_path($project->cover_image['src']));
    }

    #[DataProvider('badProjects')]
    public function test_bad_projects_are_refused(callable $overrides, string $field): void
    {
        $this->post('/projects', $overrides() + $this->projectForm(), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertDatabaseCount('projects', 1);
    }

    public static function badProjects(): array
    {
        return [
            'no name' => [fn () => ['name_en' => ''], 'name_en'],
            'a name that cannot make an address' => [fn () => ['name_en' => '!!!', 'slug' => ''], 'slug'],
            'taken address' => [fn () => ['slug' => 'delwar-garden'], 'slug'],
            'bad address' => [fn () => ['slug' => 'Lake View!'], 'slug'],
            'bad status' => [fn () => ['status' => 'sold'], 'status'],
            'bad type' => [fn () => ['type' => 'farm'], 'type'],
            'bad year' => [fn () => ['handover' => '29'], 'handover'],
            'no cover' => [fn () => ['cover_file' => null], 'cover_file'],
            'small cover' => [fn () => ['cover_file' => UploadedFile::fake()->image('c.jpg', 800, 600)], 'cover_file'],
            'no cover description' => [fn () => ['cover_alt_en' => ''], 'cover_alt_en'],
            'stat with no label' => [fn () => ['stats' => [['value_en' => '4']]], 'stats.0.label_en'],
            'stat with no value' => [fn () => ['stats' => [['label_en' => 'Katha']]], 'stats.0.value_en'],
            'four stats' => [fn () => ['stats' => array_fill(0, 4, ['value_en' => '1', 'label_en' => 'x'])], 'stats'],
            'long page title' => [fn () => ['seo_title_en' => str_repeat('a', 61)], 'seo_title_en'],
        ];
    }

    public function test_editing_keeps_the_cover_and_its_own_address(): void
    {
        $project = Project::first();
        $cover = $project->cover_image;

        $this->putJson("/projects/{$project->id}", $this->projectForm(['slug' => $project->slug, 'name_en' => 'Renamed', 'cover_file' => null]))->assertOk();

        $this->assertSame('Renamed', $project->fresh()->name_en);
        $this->assertSame('delwar-garden', $project->fresh()->slug);
        $this->assertEquals($cover, $project->fresh()->cover_image);
        $this->assertSame([], $project->fresh()->card_stats);
    }

    public function test_every_project_can_be_hidden_or_deleted_and_files_go_with_it(): void
    {
        $this->post('/projects', $this->projectForm(), ['Accept' => 'application/json'])->assertOk();
        $project = Project::where('slug', 'lake-view')->sole();
        $file = public_path($project->cover_image['src']);

        $this->patchJson("/projects/{$project->id}/toggle")->assertOk();
        $this->assertSame(['delwar-garden'], array_column($this->getJson('/api/v1/content')->json('projects'), 'slug'));

        $this->deleteJson("/projects/{$project->id}")->assertOk();
        $this->assertFileDoesNotExist($file);

        $only = Project::sole();
        $this->patchJson("/projects/{$only->id}/toggle")->assertOk();
        $this->assertSame([], $this->getJson('/api/v1/content')->json('projects'));
    }

    public function test_projects_in_the_api_use_the_website_shape(): void
    {
        $project = $this->getJson('/api/v1/content')->json('projects.0');

        $this->assertSame('delwar-garden', $project['slug']);
        $this->assertSame('দেলোয়ার গার্ডেন', $project['name']['bn']);
        $this->assertSame('ongoing', $project['status']);
        $this->assertSame('2029', $project['handover']);
        $this->assertSame('bashundhara', $project['area']);
        $this->assertSame(['en' => 'Bashundhara R/A', 'bn' => 'বসুন্ধরা আবাসিক এলাকা'], $project['areaName']);
        $this->assertSame(url('/uploads/projects/delwar-garden-tower-rendering-bashundhara.webp'), $project['cover']['src']);
        $this->assertSame('center 30%', $project['cover']['focus']);
        $this->assertCount(3, $project['cardStats']);
        $this->assertTrue($project['featured']);
        $this->assertArrayNotHasKey('seo', $project);
    }

    public function test_a_not_featured_project_still_says_so_and_seo_reaches_the_api(): void
    {
        Project::query()->update(['is_featured' => false, 'seo_title_en' => 'Title', 'seo_description_en' => 'About', 'seo_description_bn' => 'সম্পর্কে']);

        $project = $this->getJson('/api/v1/content')->json('projects.0');

        $this->assertFalse($project['featured']);
        $this->assertSame(['title' => ['en' => 'Title'], 'description' => ['en' => 'About', 'bn' => 'সম্পর্কে']], $project['seo']);
    }

    public function test_projects_and_leaders_are_paginated(): void
    {
        foreach (range(1, 11) as $i) {
            Project::create(Project::first()->only(['tagline_en', 'status', 'type', 'handover', 'area_slug', 'area_name_en', 'address_en', 'cover_image', 'cover_alt_en']) + ['slug' => "extra-{$i}", 'name_en' => "Extra {$i}", 'sort_order' => 100 + $i]);
        }

        $this->get('/projects')->assertOk()->assertSee('Showing 1 to 10 of 12');
        $this->get('/projects?page=2')->assertOk()->assertSee('Extra 11');
        $this->get('/leaders')->assertOk()->assertSee('Shimu Akter');
        $this->get('/leaders?page=5')->assertRedirect();
    }

    public function test_guests_cannot_reach_either_screen(): void
    {
        auth()->logout();
        $leader = Leader::first();
        $project = Project::first();

        $this->get('/leaders')->assertRedirect('/');
        $this->get('/projects')->assertRedirect('/');
        $this->deleteJson("/leaders/{$leader->id}")->assertUnauthorized();
        $this->deleteJson("/projects/{$project->id}")->assertUnauthorized();
    }
}
