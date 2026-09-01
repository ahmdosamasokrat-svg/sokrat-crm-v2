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

        if (Schema::hasTable('technical_support_devices') && ! Schema::hasColumn('technical_support_devices', 'image_path')) {
            Schema::table('technical_support_devices', function (Blueprint $table): void {
                $table->string('image_path', 500)->nullable()->after('company_name');
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        if (Schema::hasTable('technical_support_devices') && Schema::hasColumn('technical_support_devices', 'image_path')) {
            Schema::table('technical_support_devices', function (Blueprint $table): void {
                $table->dropColumn('image_path');
            });
        }
    }
};
