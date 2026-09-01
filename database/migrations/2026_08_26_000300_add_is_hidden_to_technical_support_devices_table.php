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

        if (Schema::hasTable('technical_support_devices') && ! Schema::hasColumn('technical_support_devices', 'is_hidden')) {
            Schema::table('technical_support_devices', function (Blueprint $table): void {
                $table->boolean('is_hidden')->default(false)->after('is_custom');
                $table->index(['is_hidden']);
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        if (Schema::hasTable('technical_support_devices') && Schema::hasColumn('technical_support_devices', 'is_hidden')) {
            Schema::table('technical_support_devices', function (Blueprint $table): void {
                $table->dropColumn('is_hidden');
            });
        }
    }
};
