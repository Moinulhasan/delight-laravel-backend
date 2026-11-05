<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(5)
            ->by(Str::lower($request->string('email')).'|'.$request->ip())
            ->response(fn () => back()
                ->withErrors(['email' => 'Too many login attempts. Please wait a minute and try again.'])
                ->onlyInput('email')));

        // Same limit as api/lead.php, and the same answer the website's form expects.
        RateLimiter::for('leads', fn (Request $request) => Limit::perMinutes(10, 5)
            ->by($request->ip())
            ->response(fn () => response()->json(['ok' => false, 'reason' => 'rate_limited'], 429)));

        Paginator::defaultView('partials.pagination');

        View::composer(['partials.head', 'auth.login', 'layouts.admin'], fn ($view) => $view->with('settings', Setting::current()));
    }
}
