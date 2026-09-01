<?php

declare(strict_types=1);

use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

        // 1. Extend pipeline_stages table
        if (! Schema::hasColumn('pipeline_stages', 'is_primary')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->boolean('is_primary')->default(false)->after('position');
            });
        }
        if (! Schema::hasColumn('pipeline_stages', 'icon')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->string('icon', 50)->nullable()->after('color');
            });
        }

        // 2. Safely drop unique constraint on position on pipeline_stages
        try {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->dropUnique(['position']);
            });
        } catch (\Throwable) {
            try {
                DB::statement('ALTER TABLE pipeline_stages DROP INDEX pipeline_stages_position_unique');
            } catch (\Throwable) {
            }
        }

        // 3. Safely drop unique constraint on position on lead_statuses
        try {
            Schema::table('lead_statuses', function (Blueprint $table): void {
                $table->dropUnique(['position']);
            });
        } catch (\Throwable) {
            try {
                DB::statement('ALTER TABLE lead_statuses DROP INDEX lead_statuses_position_unique');
            } catch (\Throwable) {
            }
        }

        // 3. Mark existing primary stages as is_primary
        $primaryCodes = ['start', 'interest', 'negotiation', 'closing_execution', 'new', 'no_answer', 'not_interested', 'donor'];
        DB::table('pipeline_stages')
            ->whereIn('code', $primaryCodes)
            ->update(['is_primary' => true]);

        // 4. Ensure every existing pipeline stage has at least one LeadStatus
        $stages = PipelineStage::query()->with('statuses')->get();
        foreach ($stages as $stage) {
            if ($stage->statuses->isEmpty()) {
                $maxPos = (int) (LeadStatus::query()->max('position') ?? 0);
                LeadStatus::query()->create([
                    'pipeline_stage_id' => $stage->id,
                    'code' => $stage->code,
                    'name_ar' => $stage->name_ar,
                    'position' => $maxPos + 1,
                    'color' => $stage->color ?: '#3478f6',
                    'is_terminal' => false,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::table('pipeline_stages', function (Blueprint $table): void {
            if (Schema::hasColumn('pipeline_stages', 'icon')) {
                $table->dropColumn('icon');
            }
            if (Schema::hasColumn('pipeline_stages', 'is_primary')) {
                $table->dropColumn('is_primary');
            }
        });
    }
};
