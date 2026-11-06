<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ContactDetailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HeroSlideController;
use App\Http\Controllers\LeaderController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::post('/hero-slides/reorder', [HeroSlideController::class, 'reorder'])->name('hero-slides.reorder');
    Route::patch('/hero-slides/{hero_slide}/toggle', [HeroSlideController::class, 'toggle'])->name('hero-slides.toggle');
    Route::resource('hero-slides', HeroSlideController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('/videos/reorder', [VideoController::class, 'reorder'])->name('videos.reorder');
    Route::patch('/videos/{video}/toggle', [VideoController::class, 'toggle'])->name('videos.toggle');
    Route::resource('videos', VideoController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('/leaders/reorder', [LeaderController::class, 'reorder'])->name('leaders.reorder');
    Route::patch('/leaders/{leader}/toggle', [LeaderController::class, 'toggle'])->name('leaders.toggle');
    Route::resource('leaders', LeaderController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::post('/projects/reorder', [ProjectController::class, 'reorder'])->name('projects.reorder');
    Route::patch('/projects/{project}/toggle', [ProjectController::class, 'toggle'])->name('projects.toggle');
    Route::resource('projects', ProjectController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/contact-details', [ContactDetailController::class, 'edit'])->name('contact-details.edit');
    Route::put('/contact-details', [ContactDetailController::class, 'update'])->name('contact-details.update');

    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-mail', [SettingController::class, 'testMail'])->middleware('throttle:5,1')->name('settings.test-mail');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
