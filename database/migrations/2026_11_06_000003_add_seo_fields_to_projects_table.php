<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('seo_title_en')->nullable()->after('card_stats');
            $table->string('seo_title_bn')->nullable()->after('seo_title_en');
            $table->string('seo_description_en')->nullable()->after('seo_title_bn');
            $table->string('seo_description_bn')->nullable()->after('seo_description_en');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['seo_title_en', 'seo_title_bn', 'seo_description_en', 'seo_description_bn']);
        });
    }
};
