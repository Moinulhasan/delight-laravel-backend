<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_details', function (Blueprint $table) {
            $table->id();
            $table->string('hotline_display');
            $table->string('hotline_e164', 16);
            $table->string('whatsapp', 16);
            $table->string('email');
            $table->string('street');
            $table->string('floor')->nullable();
            $table->boolean('show_floor')->default(false);
            $table->string('locality');
            $table->string('city');
            $table->string('postal_code', 10);
            $table->string('country', 2)->default('BD');
            $table->json('opening_days')->nullable();
            $table->string('opens', 5)->nullable();
            $table->string('closes', 5)->nullable();
            $table->boolean('show_hours')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_details');
    }
};
