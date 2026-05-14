<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('public_slug', 80)->nullable()->unique()->after('is_active');
            $table->boolean('is_public')->default(false)->after('public_slug');
            $table->string('business_name', 150)->nullable()->after('is_public');
            $table->string('tagline', 200)->nullable()->after('business_name');
            $table->text('story')->nullable()->after('tagline');
            $table->string('logo_path', 255)->nullable()->after('story');
            $table->string('cover_path', 255)->nullable()->after('logo_path');
            $table->string('primary_color', 7)->nullable()->after('cover_path');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
            $table->string('accent_color', 7)->nullable()->after('secondary_color');
            $table->string('whatsapp', 30)->nullable()->after('accent_color');
            $table->string('instagram_url', 255)->nullable()->after('whatsapp');
            $table->string('facebook_url', 255)->nullable()->after('instagram_url');
            $table->string('tiktok_url', 255)->nullable()->after('facebook_url');
            $table->string('google_maps_url', 255)->nullable()->after('tiktok_url');
            $table->json('opening_hours')->nullable()->after('google_maps_url');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'public_slug', 'is_public', 'business_name', 'tagline', 'story',
                'logo_path', 'cover_path', 'primary_color', 'secondary_color', 'accent_color',
                'whatsapp', 'instagram_url', 'facebook_url', 'tiktok_url', 'google_maps_url',
                'opening_hours',
            ]);
        });
    }
};
