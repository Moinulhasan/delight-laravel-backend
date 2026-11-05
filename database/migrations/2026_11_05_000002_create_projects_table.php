<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_bn')->nullable();
            $table->string('tagline_en');
            $table->string('tagline_bn')->nullable();
            $table->string('status', 20);
            $table->string('type', 20);
            $table->string('handover', 4);
            $table->string('area_slug');
            $table->string('area_name_en');
            $table->string('area_name_bn')->nullable();
            $table->string('address_en');
            $table->string('address_bn')->nullable();
            $table->json('cover_image');
            $table->string('cover_alt_en');
            $table->string('cover_alt_bn')->nullable();
            $table->json('card_stats')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
