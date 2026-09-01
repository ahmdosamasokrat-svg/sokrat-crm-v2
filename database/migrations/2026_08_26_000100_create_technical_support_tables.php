<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }

    public function up(): void
    {
        $this->verifyDatabase();

        if (! Schema::hasTable('technical_support_devices')) {
            Schema::create('technical_support_devices', function (Blueprint $table): void {
                $table->id();
                $table->string('device_key', 120)->unique();
                $table->string('name', 255);
                $table->string('dns_name', 255)->nullable();
                $table->string('os', 50)->default('linux');
                $table->boolean('is_online')->default(true);
                $table->text('notes')->nullable();
                $table->boolean('is_custom')->default(true);
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['is_custom', 'is_online']);
            });
        }

        if (! Schema::hasTable('technical_support_ips')) {
            Schema::create('technical_support_ips', function (Blueprint $table): void {
                $table->id();
                $table->string('device_key', 120);
                $table->string('ip_address', 120);
                $table->string('label', 100)->nullable();
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['device_key']);
                $table->index(['ip_address']);
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::dropIfExists('technical_support_ips');
        Schema::dropIfExists('technical_support_devices');
    }
};
