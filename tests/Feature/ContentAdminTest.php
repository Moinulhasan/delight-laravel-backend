<?php

namespace Tests\Feature;

use App\Mail\LeadReceived;
use App\Mail\SettingsTestMail;
use App\Models\ContactDetail;
use App\Models\HeroSlide;
use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use App\Support\YouTube;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->publicDir = sys_get_temp_dir().'/admin-test-'.uniqid();
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

    private function contactForm(array $overrides = []): array
    {
        return $overrides + [
            'hotline_display' => '+880 1700-000000',
            'hotline_e164' => '+8801700000000',
            'whatsapp' => '+8801700000001',
            'email' => 'hello@example.com',
            'street' => '1 New Road',
            'locality' => 'Gulshan',
            'city' => 'Dhaka',
            'postal_code' => '1212',
            'show_floor' => '0',
            'show_hours' => '0',
        ];
    }

    private function settingsForm(array $overrides = []): array
    {
        return $overrides + ['company_name' => 'Delight', 'legal_name' => 'Delight Ltd.', 'smtp_encryption' => 'ssl'];
    }

    private function lead(array $overrides = []): array
    {
        return $overrides + ['kind' => 'general', 'name' => 'Rahim', 'phone' => '01712345678', 'phoneE164' => '+8801712345678', 'interest' => 'general', 'consent' => true];
    }

    private function configureMail(): void
    {
        Setting::current()->update(['lead_recipient' => 'sales@example.com', 'mail_from_address' => 'site@example.com', 'smtp_host' => 'mail.example.com', 'smtp_port' => 465]);
    }

    /* ---------- YouTube links ---------- */

    #[DataProvider('youtubeLinks')]
    public function test_youtube_links_become_ids(string $input, ?string $expected): void
    {
        $this->assertSame($expected, YouTube::idFrom($input));
    }

    public static function youtubeLinks(): array
    {
        return [
            'share link' => ['https://youtu.be/mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'share link with tracking' => ['https://youtu.be/mJVuZiK9a6I?si=AbCdEfG123', 'mJVuZiK9a6I'],
            'browser link' => ['https://www.youtube.com/watch?v=mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'browser link with extras' => ['https://www.youtube.com/watch?list=PL1&v=mJVuZiK9a6I&t=30s', 'mJVuZiK9a6I'],
            'phone link' => ['https://m.youtube.com/watch?v=mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'embed' => ['https://www.youtube.com/embed/mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'private embed' => ['https://www.youtube-nocookie.com/embed/mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'shorts' => ['https://www.youtube.com/shorts/mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'live' => ['https://www.youtube.com/live/mJVuZiK9a6I?feature=share', 'mJVuZiK9a6I'],
            'no scheme' => ['youtu.be/mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'spaces around it' => ["  https://youtu.be/mJVuZiK9a6I\n", 'mJVuZiK9a6I'],
            'bare id' => ['mJVuZiK9a6I', 'mJVuZiK9a6I'],
            'another site' => ['https://evil.example/watch?v=mJVuZiK9a6I', null],
            'lookalike host' => ['https://youtube.com.evil.example/watch?v=mJVuZiK9a6I', null],
            'channel page' => ['https://www.youtube.com/@delight', null],
            'id too short' => ['https://youtu.be/abc', null],
            'playlist only' => ['https://www.youtube.com/playlist?list=PL123', null],
            'blank' => ['', null],
        ];
    }

    /* ---------- Videos ---------- */

    public function test_a_video_is_created_from_a_pasted_share_link(): void
    {
        $this->postJson('/videos', [
            'youtube_link' => 'https://youtu.be/AbCdEfGhIjK?si=xyz',
            'title_en' => 'Site tour',
            'description_en' => 'A walk around the site.',
            'upload_date' => '2026-10-01',
            'duration' => '2:30',
        ])->assertOk();

        $video = Video::where('title_en', 'Site tour')->sole();
        $this->assertSame('AbCdEfGhIjK', $video->youtube_id);
        $this->assertSame(150, $video->duration_seconds);
        $this->assertSame('PT2M30S', $video->isoDuration());
        $this->assertSame('2:30', $video->clockDuration());
        $this->assertNull($video->poster_image);
    }

    public function test_a_link_from_another_site_is_refused(): void
    {
        $this->postJson('/videos', ['youtube_link' => 'https://vimeo.com/12345678901', 'title_en' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('youtube_link');
    }

    public function test_a_poster_must_be_16_by_9(): void
    {
        $this->postJson('/videos', ['youtube_link' => 'mJVuZiK9a6I', 'title_en' => 'x', 'poster_image' => UploadedFile::fake()->image('a.jpg', 1280, 1000)])
            ->assertJsonValidationErrors('poster_image');

        $this->postJson('/videos', ['youtube_link' => 'mJVuZiK9a6I', 'title_en' => 'New', 'poster_image' => UploadedFile::fake()->image('a.jpg', 1920, 1080)])
            ->assertOk();

        $poster = Video::where('title_en', 'New')->sole()->poster_image;
        $this->assertSame([1280, 720], [$poster['w'], $poster['h']]);
        $this->assertFileExists(public_path($poster['src']));
    }

    public function test_videos_can_be_edited_toggled_and_deleted_with_no_active_minimum(): void
    {
        Video::where('youtube_id', '!=', 'mJVuZiK9a6I')->delete();
        $video = Video::sole();

        $this->putJson("/videos/{$video->id}", ['youtube_link' => 'https://www.youtube.com/watch?v=AbCdEfGhIjK', 'title_en' => 'Changed'])->assertOk();
        $this->assertSame('AbCdEfGhIjK', $video->fresh()->youtube_id);

        $this->patchJson("/videos/{$video->id}/toggle")->assertOk();
        $this->assertFalse($video->fresh()->is_active);
        $this->assertSame([], $this->getJson('/api/v1/content')->json('videos'));

        $this->deleteJson("/videos/{$video->id}")->assertOk();
        $this->assertModelMissing($video);
    }

    /* ---------- Pagination ---------- */

    public function test_lists_are_paginated(): void
    {
        foreach (range(1, 12) as $i) {
            Video::create(['youtube_id' => 'AbCdEfGhIjK', 'title_en' => "Extra {$i}", 'sort_order' => 100 + $i]);
        }

        $this->get('/videos')->assertOk()->assertSee('Showing 1 to 10 of 14')->assertSee('rel="next"', false);
        $this->get('/videos?page=2')->assertOk()->assertSee('Extra 12')->assertDontSee('Cinematic property film');
    }

    public function test_a_page_past_the_end_goes_to_the_last_page(): void
    {
        $this->get('/videos?page=9')->assertRedirect();
        $this->get('/hero-slides?page=9')->assertRedirect();
    }

    public function test_the_success_message_survives_the_redirect_after_emptying_a_page(): void
    {
        $this->withSession(['status' => 'Video deleted.', '_flash.new' => ['status']])
            ->followingRedirects()
            ->get('/videos?page=9')
            ->assertOk()
            ->assertSee('data-flash="Video deleted."', false);
    }

    public function test_reordering_one_page_leaves_other_pages_alone(): void
    {
        foreach (range(1, 12) as $i) {
            Video::create(['youtube_id' => 'AbCdEfGhIjK', 'title_en' => "Extra {$i}", 'sort_order' => 100 + $i]);
        }

        $page = Video::orderBy('sort_order')->skip(10)->take(4)->get();
        $firstPageBefore = Video::orderBy('sort_order')->take(10)->pluck('id')->all();

        $this->postJson('/videos/reorder', ['ids' => $page->pluck('id')->reverse()->values()->all()])->assertOk();

        $this->assertSame($firstPageBefore, Video::orderBy('sort_order')->take(10)->pluck('id')->all());
        $this->assertSame($page->pluck('id')->reverse()->values()->all(), Video::orderBy('sort_order')->skip(10)->take(4)->pluck('id')->all());
    }

    /* ---------- Contact details ---------- */

    public function test_contact_details_save_and_reach_the_api(): void
    {
        $this->get('/contact-details')->assertOk()->assertSee('+880 1713-903025');

        $this->putJson('/contact-details', $this->contactForm())->assertOk();

        $contacts = $this->getJson('/api/v1/content')->json('contacts');
        $this->assertSame('+880 1700-000000', $contacts['hotline']['display']);
        $this->assertSame('+8801700000001', $contacts['whatsapp']);
        $this->assertSame('Gulshan', $contacts['office']['locality']);
        $this->assertDatabaseCount('contact_details', 1);
    }

    public function test_unconfirmed_floor_and_hours_never_leave_the_server(): void
    {
        $response = $this->getJson('/api/v1/content');

        $this->assertNull($response->json('contacts.office.floor'));
        $this->assertNull($response->json('contacts.hours'));
        $this->assertStringNotContainsString('5th Floor', $response->getContent());
        $this->assertStringNotContainsString('10:00', $response->getContent());
    }

    public function test_floor_and_hours_show_once_switched_on_with_the_week_in_order(): void
    {
        $this->putJson('/contact-details', $this->contactForm([
            'floor' => '5th Floor', 'show_floor' => '1', 'show_hours' => '1',
            'opening_days' => ['Monday', 'Saturday'], 'opens' => '10:00', 'closes' => '19:00',
        ]))->assertOk();

        $contacts = $this->getJson('/api/v1/content')->json('contacts');
        $this->assertSame('5th Floor', $contacts['office']['floor']);
        $this->assertSame(['days' => ['Saturday', 'Monday'], 'opens' => '10:00', 'closes' => '19:00'], $contacts['hours']);
    }

    #[DataProvider('badContactForms')]
    public function test_bad_contact_details_are_refused(array $overrides, string $field): void
    {
        $this->putJson('/contact-details', $this->contactForm($overrides))->assertUnprocessable()->assertJsonValidationErrors($field);
    }

    public static function badContactForms(): array
    {
        return [
            'hotline with spaces' => [['hotline_e164' => '+880 1700'], 'hotline_e164'],
            'whatsapp without plus' => [['whatsapp' => '8801700000001'], 'whatsapp'],
            'bad email' => [['email' => 'nope'], 'email'],
            'no street' => [['street' => ''], 'street'],
            'floor shown but blank' => [['show_floor' => '1'], 'floor'],
            'hours shown but blank' => [['show_hours' => '1'], 'opens'],
            'closes before opens' => [['show_hours' => '1', 'opening_days' => ['Monday'], 'opens' => '19:00', 'closes' => '10:00'], 'closes'],
            'unknown day' => [['opening_days' => ['Funday']], 'opening_days.0'],
        ];
    }

    /* ---------- Settings ---------- */

    public function test_smtp_credentials_are_encrypted_at_rest_and_never_sent_out(): void
    {
        $this->putJson('/settings', $this->settingsForm(['smtp_username' => 'mailbox-user', 'smtp_password' => 'S3cret-value', 'smtp_host' => 'mail.secret-host.example', 'lead_recipient' => 'private-inbox@example.com']))->assertOk();

        $raw = DB::table('settings')->first();
        $this->assertStringNotContainsString('S3cret-value', $raw->smtp_password);
        $this->assertStringNotContainsString('mailbox-user', $raw->smtp_username);

        $settings = Setting::first();
        $this->assertSame('S3cret-value', $settings->smtp_password);
        $this->assertArrayNotHasKey('smtp_password', $settings->toArray());

        $this->get('/settings')->assertOk()->assertDontSee('S3cret-value');

        $api = $this->getJson('/api/v1/content')->getContent();
        foreach (['S3cret-value', 'mailbox-user', 'secret-host', 'private-inbox', 'smtp'] as $private) {
            $this->assertStringNotContainsString($private, $api);
        }
    }

    public function test_a_blank_password_keeps_the_saved_one(): void
    {
        $this->putJson('/settings', $this->settingsForm(['smtp_password' => 'first']))->assertOk();
        $this->putJson('/settings', $this->settingsForm(['smtp_password' => '']))->assertOk();

        $this->assertSame('first', Setting::first()->smtp_password);
    }

    public function test_a_wrong_app_key_does_not_break_the_settings_page(): void
    {
        DB::table('settings')->update(['smtp_username' => 'not-valid-ciphertext', 'smtp_password' => 'not-valid-ciphertext']);

        $this->get('/settings')->assertOk();
    }

    public function test_logos_upload_replace_and_reach_the_api(): void
    {
        $this->post('/settings', ['_method' => 'PUT'] + $this->settingsForm(['logo_header' => UploadedFile::fake()->image('logo.png', 600, 140)]), ['Accept' => 'application/json'])->assertOk();

        $first = Setting::first()->logo_header;
        $this->assertFileExists(public_path($first));
        $this->assertSame(url($first), $this->getJson('/api/v1/content')->json('site.logos.header'));

        $this->post('/settings', ['_method' => 'PUT'] + $this->settingsForm(['logo_header' => UploadedFile::fake()->image('logo2.png', 600, 140)]), ['Accept' => 'application/json'])->assertOk();
        $this->assertFileDoesNotExist(public_path($first));

        $second = Setting::first()->logo_header;
        $this->post('/settings', ['_method' => 'PUT'] + $this->settingsForm(['remove' => ['logo_header']]), ['Accept' => 'application/json'])->assertOk();
        $this->assertNull(Setting::first()->logo_header);
        $this->assertFileDoesNotExist(public_path($second));
    }

    public function test_svg_and_other_files_are_refused_as_logos(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->post('/settings', ['_method' => 'PUT'] + $this->settingsForm(['logo_header' => $svg]), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('logo_header');
    }

    public function test_company_name_and_seo_reach_the_api_and_the_admin(): void
    {
        $this->putJson('/settings', $this->settingsForm(['company_name' => 'New Name', 'seo_title_en' => 'Homes in Dhaka', 'seo_description_en' => 'Modern apartments.', 'seo_description_bn' => 'আধুনিক']))->assertOk();

        $site = $this->getJson('/api/v1/content')->json('site');
        $this->assertSame('New Name', $site['companyName']);
        $this->assertSame(['en' => 'Homes in Dhaka'], $site['seo']['title']);
        $this->assertSame(['en' => 'Modern apartments.', 'bn' => 'আধুনিক'], $site['seo']['description']);
    }

    public function test_seo_limits_are_enforced(): void
    {
        $this->putJson('/settings', $this->settingsForm(['seo_title_en' => str_repeat('a', 61), 'seo_description_en' => str_repeat('a', 161)]))
            ->assertUnprocessable()->assertJsonValidationErrors(['seo_title_en', 'seo_description_en']);
    }

    public function test_guests_cannot_reach_settings(): void
    {
        auth()->logout();

        $this->get('/settings')->assertRedirect('/');
        $this->putJson('/settings', $this->settingsForm())->assertUnauthorized();
        $this->postJson('/settings/test-mail')->assertUnauthorized();
    }

    public function test_the_test_email_goes_to_the_saved_recipient(): void
    {
        Mail::fake();
        $this->configureMail();

        $this->postJson('/settings/test-mail')->assertOk()->assertJsonFragment(['message' => 'Test email sent to sales@example.com.']);

        Mail::assertSent(SettingsTestMail::class, fn ($mail) => $mail->hasTo('sales@example.com') && $mail->hasFrom('site@example.com'));
        $this->assertSame('mail.example.com', config('mail.mailers.settings.host'));
    }

    public function test_the_test_email_explains_missing_settings(): void
    {
        Setting::current()->update(['smtp_host' => null]);

        $this->postJson('/settings/test-mail')->assertUnprocessable()->assertJsonFragment(['message' => 'The test email was not sent: Email is not set up yet: fill in the recipient, sender and SMTP server in Settings.']);
    }

    /* ---------- Leads ---------- */

    public function test_a_lead_is_emailed_to_the_saved_recipient(): void
    {
        Mail::fake();
        $this->configureMail();

        $this->postJson('/api/v1/leads', $this->lead(['message' => '<script>x</script>', 'project' => 'delwar-garden', 'pageUrl' => 'https://delightre.com/en/contact']))
            ->assertOk()
            ->assertExactJson(['ok' => true, 'delivered' => 'email', 'name' => 'Rahim', 'phone' => '+8801712345678']);

        Mail::assertSent(LeadReceived::class, function (LeadReceived $mail) {
            $html = $mail->render();

            return $mail->hasTo('sales@example.com')
                && $mail->mailSubject === 'New enquiry: Rahim · Delwar Garden'
                && str_contains($html, 'https://delightre.com/en/contact')
                && str_contains($html, '&lt;script&gt;')
                && ! str_contains($html, '<script>');
        });
    }

    public function test_a_page_link_from_another_site_is_dropped(): void
    {
        Mail::fake();
        $this->configureMail();

        $this->postJson('/api/v1/leads', $this->lead(['pageUrl' => 'https://evil.example/phish']))->assertOk();

        Mail::assertSent(LeadReceived::class, fn (LeadReceived $mail) => ! isset($mail->source['Page']));
    }

    public function test_the_honeypot_gets_a_normal_answer_and_sends_nothing(): void
    {
        Mail::fake();
        $this->configureMail();

        $this->postJson('/api/v1/leads', $this->lead(['website' => 'http://spam.example']))->assertOk()->assertJson(['ok' => true]);

        Mail::assertNothingSent();
    }

    public function test_invalid_leads_use_the_form_error_keys(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/leads', $this->lead(['name' => 'R', 'phone' => '', 'consent' => false, 'kind' => 'landowner']))
            ->assertUnprocessable()
            ->assertJson(['ok' => false, 'reason' => 'invalid', 'fields' => ['name' => 'name', 'phone' => 'phone', 'consent' => 'consent', 'landLocation' => 'required']]);

        Mail::assertNothingSent();
    }

    public function test_the_form_is_told_honestly_when_email_is_not_set_up(): void
    {
        Setting::current()->update(['lead_recipient' => null]);

        $this->postJson('/api/v1/leads', $this->lead())->assertStatus(502)->assertExactJson(['ok' => false, 'reason' => 'failed']);
    }

    public function test_leads_are_rate_limited(): void
    {
        Mail::fake();
        $this->configureMail();
        RateLimiter::clear('leads');

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/leads', $this->lead())->assertOk();
        }

        $this->postJson('/api/v1/leads', $this->lead())->assertStatus(429)->assertExactJson(['ok' => false, 'reason' => 'rate_limited']);
    }

    public function test_the_website_may_post_leads_from_its_own_origin_only(): void
    {
        Mail::fake();
        $this->configureMail();

        $this->postJson('/api/v1/leads', $this->lead(), ['Origin' => 'https://delightre.com'])->assertHeader('Access-Control-Allow-Origin', 'https://delightre.com');
    }

    /* ---------- API shape ---------- */

    public function test_videos_in_the_api_use_the_website_shape(): void
    {
        Video::where('youtube_id', 'mJVuZiK9a6I')->update(['upload_date' => '2026-10-01', 'duration_seconds' => 3725, 'description_en' => 'A film']);

        $video = $this->getJson('/api/v1/content')->json('videos.0');

        $this->assertSame('mJVuZiK9a6I', $video['youtubeId']);
        $this->assertSame('সিনেমাটিক প্রপার্টি ফিল্ম', $video['title']['bn']);
        $this->assertSame(['en' => 'A film'], $video['description']);
        $this->assertSame('2026-10-01', $video['uploadDate']);
        $this->assertSame('PT1H2M5S', $video['duration']);
        $this->assertSame(url('/uploads/videos/cinematic-property-film.webp'), $video['poster']['src']);
    }

    public function test_seeding_again_keeps_edits(): void
    {
        Setting::current()->update(['lead_recipient' => 'edited@example.com']);
        HeroSlide::first()->update(['alt_bn' => 'সম্পাদিত']);

        $this->seed(ContentSeeder::class);

        $this->assertSame('edited@example.com', Setting::first()->lead_recipient);
        $this->assertDatabaseHas('hero_slides', ['alt_bn' => 'সম্পাদিত']);
        $this->assertDatabaseCount('contact_details', 1);
        $this->assertTrue(ContactDetail::first()->exists);
    }
}
