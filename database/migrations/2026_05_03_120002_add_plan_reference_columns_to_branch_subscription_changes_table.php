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
            $table->foreignId('old_plan_id')->nullable()->after('actor_id')->constrained('plans')->nullOnDelete();
            $table->foreignId('new_plan_id')->nullable()->after('old_plan_id')->constrained('plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branch_subscription_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('new_plan_id');
            $table->dropConstrainedForeignId('old_plan_id');
        });
    }
};
