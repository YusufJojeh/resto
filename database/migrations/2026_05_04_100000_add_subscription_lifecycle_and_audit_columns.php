<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->timestamp('current_period_ends_at')->nullable()->after('subscription_ends_at');
            $table->string('provider_name', 64)->nullable()->after('current_period_ends_at');
            $table->string('provider_customer_id', 120)->nullable()->after('provider_name');
            $table->string('provider_subscription_id', 120)->nullable()->after('provider_customer_id');
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->string('provider_price_id', 120)->nullable()->after('billing_interval');
        });

        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->timestamp('old_current_period_ends_at')->nullable()->after('new_subscription_ends_at');
            $table->timestamp('new_current_period_ends_at')->nullable()->after('old_current_period_ends_at');
            $table->boolean('old_access_allowed')->nullable()->after('new_current_period_ends_at');
            $table->boolean('new_access_allowed')->nullable()->after('old_access_allowed');
            $table->string('old_access_reason', 48)->nullable()->after('new_access_allowed');
            $table->string('new_access_reason', 48)->nullable()->after('old_access_reason');
        });
    }

    public function down(): void
    {
        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->dropColumn([
                'old_current_period_ends_at',
                'new_current_period_ends_at',
                'old_access_allowed',
                'new_access_allowed',
                'old_access_reason',
                'new_access_reason',
            ]);
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('provider_price_id');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'current_period_ends_at',
                'provider_name',
                'provider_customer_id',
                'provider_subscription_id',
            ]);
        });
    }
};
