<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('initials', 3);
            $table->string('role_en');
            $table->string('role_bn')->nullable();
            $table->text('quote_en');
            $table->text('quote_bn')->nullable();
            $table->json('photo')->nullable();
            $table->string('photo_alt_en')->nullable();
            $table->string('photo_alt_bn')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaders');
    }
};
