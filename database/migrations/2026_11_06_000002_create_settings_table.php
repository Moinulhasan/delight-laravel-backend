<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row.
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('legal_name');

            // Paths under public/uploads/brand; null means the site's built-in logo.
            $table->string('logo_header')->nullable();
            $table->string('logo_header_light')->nullable();
            $table->string('logo_footer')->nullable();
            $table->string('logo_footer_light')->nullable();
            $table->string('favicon')->nullable();

            $table->string('lead_recipient')->nullable();
            $table->string('mail_from_address')->nullable();
            $table->string('mail_from_name')->nullable();
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption', 3)->default('ssl');
            // Encrypted with APP_KEY (text: ciphertext is much longer than the value).
            $table->text('smtp_username')->nullable();
            $table->text('smtp_password')->nullable();

            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_bn')->nullable();
            $table->string('seo_description_en')->nullable();
            $table->string('seo_description_bn')->nullable();
            $table->json('seo_image')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
