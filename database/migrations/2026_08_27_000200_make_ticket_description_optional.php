<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('technical_support_tickets')) {
            Schema::table('technical_support_tickets', function (Blueprint $table): void {
                $table->text('description')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('technical_support_tickets')) {
            DB::table('technical_support_tickets')
                ->whereNull('description')
                ->update(['description' => '']);

            Schema::table('technical_support_tickets', function (Blueprint $table): void {
                $table->text('description')->nullable(false)->change();
            });
        }
    }
};
