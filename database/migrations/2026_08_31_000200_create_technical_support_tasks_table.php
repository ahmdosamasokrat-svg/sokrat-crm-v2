<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('technical_support_tasks')) {
            return;
        }

        Schema::create('technical_support_tasks', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('priority', 20)->default('normal');
            $table->date('due_date')->nullable();
            $table->foreignId('assigned_to_user_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('completed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['assigned_to_user_id', 'status', 'due_date'], 'support_tasks_assignee_status_due_idx');
            $table->index(['created_by_user_id', 'status'], 'support_tasks_creator_status_idx');
        });
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        Schema::dropIfExists('technical_support_tasks');
    }
};
