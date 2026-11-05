<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HeroSlideTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        // Uploads go to a throwaway folder, never the real public/uploads.
        $this->publicDir = sys_get_temp_dir().'/hero-test-'.uniqid();
        File::ensureDirectoryExists($this->publicDir);
        $this->app->usePublicPath($this->publicDir);

        $this->actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    private function slide(array $attributes = []): HeroSlide
    {
        return HeroSlide::create($attributes + [
            'desktop_images' => ['src' => '/uploads/hero/a-1920.webp', 'srcset' => '/uploads/hero/a-1280.webp 1280w, /uploads/hero/a-1920.webp 1920w'],
            'alt_en' => 'Tower (architectural rendering)',
            'sort_order' => 1,
        ]);
    }

    private function uploadedFiles(): array
    {
        return array_map(fn ($file) => $file->getFilename(), File::files($this->publicDir.'/uploads/hero'));
    }

    public function test_guests_cannot_reach_the_screen_or_its_actions(): void
    {
        auth()->logout();
        $slide = $this->slide();

        $this->get('/hero-slides')->assertRedirect('/');
        $this->deleteJson("/hero-slides/{$slide->id}")->assertUnauthorized();
        $this->assertModelExists($slide);
    }

    public function test_index_lists_slides(): void
    {
        $this->slide();

        $this->get('/hero-slides')->assertOk()->assertSee('Tower (architectural rendering)');
    }

    public function test_create_makes_every_webp_width_and_never_upscales(): void
    {
        $this->postJson('/hero-slides', [
            'desktop_image' => UploadedFile::fake()->image('tower.jpg', 2000, 1000),
            'alt_en' => 'A tower',
            'alt_bn' => 'টাওয়ার',
        ])->assertOk();

        $slide = HeroSlide::sole();
        $this->assertStringEndsWith('-1920.webp', $slide->desktop_images['src']);
        $this->assertStringContainsString(' 1280w', $slide->desktop_images['srcset']);
        $this->assertStringNotContainsString('2560', $slide->desktop_images['srcset']);
        $this->assertNull($slide->mobile_images);
        $this->assertTrue($slide->is_active);
        $this->assertCount(2, $this->uploadedFiles());
        $this->assertFileExists(public_path($slide->desktop_images['src']));
    }

    public function test_create_stores_the_mobile_widths(): void
    {
        $this->postJson('/hero-slides', [
            'desktop_image' => UploadedFile::fake()->image('tower.jpg', 1920, 1080),
            'mobile_image' => UploadedFile::fake()->image('tower-m.jpg', 1080, 1920),
            'alt_en' => 'A tower',
        ])->assertOk();

        $this->assertStringEndsWith('-m-1080.webp', HeroSlide::sole()->mobile_images['src']);
    }

    public function test_new_slides_go_last(): void
    {
        $this->slide(['sort_order' => 7]);

        $this->postJson('/hero-slides', [
            'desktop_image' => UploadedFile::fake()->image('tower.jpg', 1920, 1080),
            'alt_en' => 'New',
        ])->assertOk();

        $this->assertSame(8, HeroSlide::where('alt_en', 'New')->sole()->sort_order);
    }

    /** @dataProvider badDesktopImages */
    public function test_create_rejects_a_bad_desktop_image(callable $file): void
    {
        $this->postJson('/hero-slides', ['desktop_image' => $file(), 'alt_en' => 'A tower'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('desktop_image');

        $this->assertDatabaseCount('hero_slides', 0);
    }

    public static function badDesktopImages(): array
    {
        return [
            'too narrow' => [fn () => UploadedFile::fake()->image('a.jpg', 1200, 600)],
            'too wide' => [fn () => UploadedFile::fake()->image('a.jpg', 6001, 100)],
            'portrait' => [fn () => UploadedFile::fake()->image('a.jpg', 2000, 3000)],
            'not an image' => [fn () => UploadedFile::fake()->create('a.pdf', 100, 'application/pdf')],
            'a php file' => [fn () => UploadedFile::fake()->createWithContent('a.php', '<?php echo 1;')],
            'over 10 MB' => [fn () => UploadedFile::fake()->image('a.jpg', 2000, 1000)->size(10241)],
        ];
    }

    public function test_create_requires_english_alt_text_and_a_landscape_mobile_is_refused(): void
    {
        $this->postJson('/hero-slides', [
            'desktop_image' => UploadedFile::fake()->image('a.jpg', 1920, 1080),
            'mobile_image' => UploadedFile::fake()->image('b.jpg', 1080, 720),
        ])->assertUnprocessable()->assertJsonValidationErrors(['alt_en', 'mobile_image']);
    }

    public function test_update_can_change_text_only_and_keeps_the_images(): void
    {
        $slide = $this->slide();
        $images = $slide->desktop_images;

        $this->putJson("/hero-slides/{$slide->id}", ['alt_en' => 'Changed'])->assertOk();

        $this->assertSame('Changed', $slide->fresh()->alt_en);
        $this->assertSame($images, $slide->fresh()->desktop_images);
    }

    public function test_replacing_an_image_removes_the_old_files(): void
    {
        File::ensureDirectoryExists($this->publicDir.'/uploads/hero');
        File::put($this->publicDir.'/uploads/hero/a-1920.webp', 'old');
        $slide = $this->slide();

        $this->putJson("/hero-slides/{$slide->id}", [
            'desktop_image' => UploadedFile::fake()->image('new.jpg', 1920, 1080),
            'alt_en' => 'Tower',
        ])->assertOk();

        $this->assertNotContains('a-1920.webp', $this->uploadedFiles());
        $this->assertFileExists(public_path($slide->fresh()->desktop_images['src']));
    }

    public function test_the_mobile_image_can_be_removed(): void
    {
        $slide = $this->slide(['mobile_images' => ['src' => '/uploads/hero/m.webp', 'srcset' => '/uploads/hero/m.webp 720w']]);

        $this->putJson("/hero-slides/{$slide->id}", ['alt_en' => 'Tower', 'remove_mobile' => true])->assertOk();

        $this->assertNull($slide->fresh()->mobile_images);
    }

    public function test_toggle_flips_a_slide_when_another_stays_active(): void
    {
        $this->slide();
        $slide = $this->slide();

        $this->patchJson("/hero-slides/{$slide->id}/toggle")->assertOk()->assertJson(['message' => 'Slide deactivated.']);
        $this->assertFalse($slide->fresh()->is_active);

        $this->patchJson("/hero-slides/{$slide->id}/toggle")->assertOk()->assertJson(['message' => 'Slide activated.']);
        $this->assertTrue($slide->fresh()->is_active);
    }

    public function test_the_last_active_slide_cannot_be_deactivated_or_deleted(): void
    {
        $slide = $this->slide();
        $this->slide(['is_active' => false]);

        $this->patchJson("/hero-slides/{$slide->id}/toggle")->assertUnprocessable();
        $this->deleteJson("/hero-slides/{$slide->id}")->assertUnprocessable();

        $this->assertTrue($slide->fresh()->is_active);
    }

    public function test_a_hidden_slide_can_be_deleted_and_its_files_go_with_it(): void
    {
        File::ensureDirectoryExists($this->publicDir.'/uploads/hero');
        File::put($this->publicDir.'/uploads/hero/a-1920.webp', 'x');
        $this->slide();
        $hidden = $this->slide(['is_active' => false]);

        $this->deleteJson("/hero-slides/{$hidden->id}")->assertOk();

        $this->assertModelMissing($hidden);
        $this->assertFileDoesNotExist($this->publicDir.'/uploads/hero/a-1920.webp');
    }

    public function test_reorder_saves_the_new_order(): void
    {
        $first = $this->slide(['sort_order' => 1]);
        $second = $this->slide(['sort_order' => 2]);

        $this->postJson('/hero-slides/reorder', ['ids' => [$second->id, $first->id]])->assertOk();

        $this->assertSame(1, $second->fresh()->sort_order);
        $this->assertSame(2, $first->fresh()->sort_order);
    }

    public function test_reorder_rejects_unknown_ids(): void
    {
        $this->postJson('/hero-slides/reorder', ['ids' => [999]])->assertUnprocessable();
    }

    public function test_api_returns_active_slides_in_order_with_absolute_urls(): void
    {
        $this->slide(['alt_en' => 'Second', 'sort_order' => 2, 'alt_bn' => 'দ্বিতীয়']);
        $this->slide(['alt_en' => 'First', 'sort_order' => 1]);
        $this->slide(['alt_en' => 'Hidden', 'sort_order' => 0, 'is_active' => false]);

        $json = $this->getJson('/api/v1/content')->assertOk()->json();

        $this->assertSame(['en' => 'First'], $json['heroSlides'][0]['alt']);
        $this->assertSame(['en' => 'Second', 'bn' => 'দ্বিতীয়'], $json['heroSlides'][1]['alt']);
        $this->assertCount(2, $json['heroSlides']);
        $this->assertSame(url('/uploads/hero/a-1920.webp'), $json['heroSlides'][0]['desktop']['src']);
        $this->assertSame(
            url('/uploads/hero/a-1280.webp').' 1280w, '.url('/uploads/hero/a-1920.webp').' 1920w',
            $json['heroSlides'][0]['desktop']['srcset'],
        );
    }

    public function test_api_falls_back_to_the_desktop_image_on_mobile(): void
    {
        $this->slide();

        $slide = $this->getJson('/api/v1/content')->json('heroSlides.0');

        $this->assertSame($slide['desktop'], $slide['mobile']);
    }

    public function test_api_is_public_cacheable_and_open_to_the_website_only(): void
    {
        auth()->logout();
        $this->slide();

        $response = $this->getJson('/api/v1/content', ['Origin' => 'https://delightre.com'])->assertOk();
        $this->assertStringContainsString('max-age=60', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $response->assertHeader('Access-Control-Allow-Origin', 'https://delightre.com');

        $this->getJson('/api/v1/content', ['Origin' => 'https://evil.example'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_api_version_changes_on_every_kind_of_edit(): void
    {
        $this->slide();
        $hidden = $this->slide(['alt_en' => 'B', 'sort_order' => 2]);

        $version = fn () => $this->getJson('/api/v1/content')->json('version');
        $original = $version();

        $this->patchJson("/hero-slides/{$hidden->id}/toggle");
        $afterHide = $version();
        $this->assertNotSame($original, $afterHide);

        $this->patchJson("/hero-slides/{$hidden->id}/toggle");
        $this->assertSame($original, $version(), 'The same content keeps the same version.');

        $hidden->update(['alt_en' => 'C']);
        $afterEdit = $version();
        $this->assertNotSame($original, $afterEdit);

        $hidden->delete();
        $this->assertNotSame($afterEdit, $version());
    }
}
