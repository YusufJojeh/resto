<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->string('source', 32)->default('manual')->after('actor_id');
            $table->string('provider', 64)->nullable()->after('source');
            $table->string('provider_event_id', 255)->nullable()->after('provider');
        });

        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->dropColumn(['source', 'provider', 'provider_event_id']);
        });

        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->unsignedBigInteger('actor_id')->nullable(false)->change();
        });
    }
};
