<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('title_bn');
            $table->text('description_bn')->nullable()->after('description_en');
            $table->date('upload_date')->nullable()->after('description_bn');
            $table->unsignedInteger('duration_seconds')->nullable()->after('upload_date');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['description_en', 'description_bn', 'upload_date', 'duration_seconds']);
        });
    }
};
