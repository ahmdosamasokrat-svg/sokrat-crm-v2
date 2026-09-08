<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'lsfv_field_lead_latest_idx';

    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('lead_stage_field_values')) {
            Schema::table('lead_stage_field_values', function (Blueprint $table): void {
                $table->index(
                    ['pipeline_stage_field_id', 'lead_id', 'id'],
                    self::INDEX,
                );
            });
        }
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('lead_stage_field_values')) {
            Schema::table('lead_stage_field_values', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX);
            });
        }
    }
};
