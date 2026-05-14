<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_subscription_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20)->nullable();
            $table->timestamp('old_trial_ends_at')->nullable();
            $table->timestamp('new_trial_ends_at')->nullable();
            $table->timestamp('old_subscription_ends_at')->nullable();
            $table->timestamp('new_subscription_ends_at')->nullable();
            $table->timestamps();

            $table->index('branch_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_subscription_changes');
    }
};