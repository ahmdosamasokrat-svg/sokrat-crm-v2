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

        if (Schema::hasTable('technical_support_devices')) {
            $existing = Schema::getColumnListing('technical_support_devices');
            Schema::table('technical_support_devices', function (Blueprint $table) use ($existing): void {
                if (! in_array('company_name', $existing, true)) {
                    $table->string('company_name', 255)->nullable()->after('name');
                }
                if (! in_array('employees_count', $existing, true)) {
                    $table->unsignedInteger('employees_count')->default(0)->after('os');
                }
                if (! in_array('lines_count', $existing, true)) {
                    $table->unsignedInteger('lines_count')->default(0)->after('employees_count');
                }
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::table('technical_support_devices', function (Blueprint $table): void {
            $columnsToDrop = [];
            if (Schema::hasColumn('technical_support_devices', 'company_name')) {
                $columnsToDrop[] = 'company_name';
            }
            if (Schema::hasColumn('technical_support_devices', 'employees_count')) {
                $columnsToDrop[] = 'employees_count';
            }
            if (Schema::hasColumn('technical_support_devices', 'lines_count')) {
                $columnsToDrop[] = 'lines_count';
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
