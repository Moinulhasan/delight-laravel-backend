<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_sees_the_login_page_at_the_root(): void
    {
        $this->get('/')->assertOk()->assertSee('Sign in');
    }

    public function test_guest_cannot_open_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/');
    }

    public function test_admin_can_log_in_and_is_sent_to_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->from('/')->post('/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->from('/')->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // Even the correct password is refused once the limit is reached.
        $this->from('/')->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logged_in_admin_skips_the_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect('/dashboard');
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_dashboard_reports_real_content_counts(): void
    {
        $this->seed(ContentSeeder::class);
        Project::query()->update(['is_active' => false]);

        $response = $this->actingAs(User::factory()->create())->get('/dashboard');

        $response->assertOk()->assertSee('Delwar Garden')->assertSee('dashboard-data', false);

        $types = $response->viewData('types');
        $this->assertSame(['total' => 1, 'live' => 0, 'hidden' => 1], array_intersect_key($types['Projects'], array_flip(['total', 'live', 'hidden'])));
        $this->assertSame(4, $types['Hero slides']['live']);
        $this->assertSame(100, $types['Leaders']['bangla']);
    }

    public function test_bangla_coverage_counts_blank_fields(): void
    {
        $this->seed(ContentSeeder::class);
        Project::query()->update(['name_bn' => null]);

        $types = $this->actingAs(User::factory()->create())->get('/dashboard')->viewData('types');

        $this->assertSame(80, $types['Projects']['bangla']);
    }
}
