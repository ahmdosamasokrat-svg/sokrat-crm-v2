<?php

declare(strict_types=1);

use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Support\CrmDatabaseGuard;
use App\Support\StageFieldSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (! Schema::hasTable('pipeline_stage_fields') || ! Schema::hasTable('pipeline_stages')) {
            return;
        }

        $newStage = PipelineStage::query()->where('code', 'new')->first();
        $noAnswerStage = PipelineStage::query()->where('code', 'no_answer')->first();
        $notInterestedStage = PipelineStage::query()->where('code', 'not_interested')->first();

        if ($newStage && $noAnswerStage && $notInterestedStage) {
            // Move callback_at and attempt notes to no_answer stage
            DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $newStage->id)
                ->whereIn('key', ['callback_at'])
                ->update(['pipeline_stage_id' => $noAnswerStage->id]);

            DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $newStage->id)
                ->where('key', 'notes')
                ->where('label_ar', 'ملاحظات المحاولة')
                ->update(['pipeline_stage_id' => $noAnswerStage->id]);

            // Move reason and details notes to not_interested stage
            DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $newStage->id)
                ->whereIn('key', ['reason'])
                ->update(['pipeline_stage_id' => $notInterestedStage->id]);

            DB::table('pipeline_stage_fields')
                ->where('pipeline_stage_id', $newStage->id)
                ->where('key', 'notes')
                ->where('label_ar', 'ملاحظات وتفاصيل')
                ->update(['pipeline_stage_id' => $notInterestedStage->id]);

            // Synchronize lead_stage_field_values for consistency
            if (Schema::hasTable('lead_stage_field_values')) {
                DB::table('lead_stage_field_values')
                    ->where('field_key', 'callback_at')
                    ->where('pipeline_stage_id', $newStage->id)
                    ->update(['pipeline_stage_id' => $noAnswerStage->id]);

                DB::table('lead_stage_field_values')
                    ->where('field_key', 'reason')
                    ->where('pipeline_stage_id', $newStage->id)
                    ->update(['pipeline_stage_id' => $notInterestedStage->id]);
            }

            StageFieldSchema::flushCache((int) $newStage->id);
            StageFieldSchema::flushCache((int) $noAnswerStage->id);
            StageFieldSchema::flushCache((int) $notInterestedStage->id);
        }
    }

    public function down(): void
    {
        // Safe no-op on rollback to preserve data consistency
    }
};
