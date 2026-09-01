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

        if (! Schema::hasTable('technical_support_tickets')) {
            Schema::create('technical_support_tickets', function (Blueprint $table): void {
                $table->id();
                $table->string('device_key', 120);
                $table->string('subject', 255);
                $table->text('description');
                $table->string('status', 20)->default('open');
                $table->text('resolution')->nullable();
                $table->foreignId('opened_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('closed_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('opened_at');
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['device_key', 'status']);
                $table->index(['device_key', 'opened_at']);
            });
        }
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        Schema::dropIfExists('technical_support_tickets');
    }
};
