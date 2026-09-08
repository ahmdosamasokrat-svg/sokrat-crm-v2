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

        // Migrate or ensure default canonical fields on primary stages if stages exist
        $this->seedStageCanonicalFields();
    }

    public function down(): void
    {
        // Non-destructive: do not delete field definitions or historical data on rollback
    }

    private function seedStageCanonicalFields(): void
    {
        // 1. Find interested stage and seed canonical company & contact fields if empty
        $interestedStage = PipelineStage::query()->where('code', 'interested')->first();
        if ($interestedStage !== null) {
            $hasFields = PipelineStageField::query()->where('pipeline_stage_id', $interestedStage->id)->exists();
            if (! $hasFields) {
                $now = now();
                $position = 1;
                $fieldsToSeed = [
                    [
                        'key' => 'company_name',
                        'label_ar' => 'اسم الشركة / المؤسسة',
                        'label_en' => 'Company Name',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'company_name',
                        'is_required' => false,
                        'position' => $position++,
                    ],
                    [
                        'key' => 'activity',
                        'label_ar' => 'النشاط التجاري',
                        'label_en' => 'Business Activity',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'activity',
                        'is_required' => false,
                        'position' => $position++,
                    ],
                    [
                        'key' => 'address',
                        'label_ar' => 'العنوان',
                        'label_en' => 'Address',
                        'type' => 'text',
                        'binding_type' => 'canonical',
                        'binding_target' => 'address',
                        'is_required' => false,
                        'position' => $position++,
                    ],
                ];

                foreach ($fieldsToSeed as $f) {
                    PipelineStageField::query()->create([
                        'pipeline_stage_id' => $interestedStage->id,
                        'key' => $f['key'],
                        'label_ar' => $f['label_ar'],
                        'label_en' => $f['label_en'],
                        'type' => $f['type'],
                        'binding_type' => $f['binding_type'],
                        'binding_target' => $f['binding_target'],
                        'is_required' => $f['is_required'],
                        'is_active' => true,
                        'position' => $f['position'],
                    ]);
                }

                StageFieldSchema::flushCache((int) $interestedStage->id);
            }
        }

        // 2. Find quotation stage and seed canonical quotation fields if empty
        $quotationStage = PipelineStage::query()->where('code', 'quotation')->first();
        if ($quotationStage !== null) {
            $hasFields = PipelineStageField::query()->where('pipeline_stage_id', $quotationStage->id)->exists();
            if (! $hasFields) {
                PipelineStageField::query()->create([
                    'pipeline_stage_id' => $quotationStage->id,
                    'key' => 'solution_type',
                    'label_ar' => 'نوع النظام / الحل المطلوب',
                    'label_en' => 'Solution Type',
                    'type' => 'select',
                    'binding_type' => 'canonical',
                    'binding_target' => 'solution_type',
                    'options' => [
                        ['value' => 'call_center', 'label_ar' => 'Call Center', 'label_en' => 'Call Center'],
                        ['value' => 'erp', 'label_ar' => 'ERP', 'label_en' => 'ERP'],
                    ],
                    'is_required' => false,
                    'is_active' => true,
                    'position' => 1,
                ]);

                StageFieldSchema::flushCache((int) $quotationStage->id);
            }
        }
    }
};
