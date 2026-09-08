<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\LeadDocument;
use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }

    public function up(): void
    {
        $this->verifyDatabase();

        if (! Schema::hasTable('lead_documents')) {
            Schema::create('lead_documents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')
                    ->constrained('leads')
                    ->cascadeOnDelete();
                $table->foreignId('pipeline_stage_id')
                    ->nullable()
                    ->constrained('pipeline_stages')
                    ->nullOnDelete();
                $table->foreignId('pipeline_stage_field_id')
                    ->nullable()
                    ->constrained('pipeline_stage_fields')
                    ->nullOnDelete();
                $table->foreignId('lead_status_history_id')
                    ->nullable()
                    ->constrained('lead_status_histories')
                    ->nullOnDelete();
                $table->string('category', 50)->default('attachment')->index();
                $table->string('original_name', 255);
                $table->string('stored_name', 255);
                $table->string('disk', 50)->default('local');
                $table->string('path', 500);
                $table->string('mime_type', 100)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps();

                $table->index(['lead_id', 'category', 'created_at'], 'lead_doc_lead_cat_date_idx');
                $table->index(['lead_id', 'created_at'], 'lead_doc_lead_date_idx');
            });
        }

        // Backfill legacy quotation files into lead_documents for continuous historical visibility
        $this->backfillLegacyQuotationFiles();
    }

    public function down(): void
    {
        $this->verifyDatabase();
        Schema::dropIfExists('lead_documents');
    }

    private function backfillLegacyQuotationFiles(): void
    {
        try {
            $leadsWithQuotations = DB::table('leads')
                ->whereNotNull('quotation_file_path')
                ->where('quotation_file_path', '!=', '')
                ->get(['id', 'quotation_file_path', 'created_by_user_id', 'lead_status_id', 'created_at', 'updated_at']);

            $disk = Storage::disk('local');

            foreach ($leadsWithQuotations as $leadRow) {
                $path = trim((string) $leadRow->quotation_file_path);
                if ($path === '') {
                    continue;
                }

                $existsAlready = DB::table('lead_documents')
                    ->where('lead_id', $leadRow->id)
                    ->where('path', $path)
                    ->exists();

                if ($existsAlready) {
                    continue;
                }

                $filename = basename($path);
                $size = 0;
                $mime = 'application/pdf';

                if ($disk->exists($path)) {
                    try {
                        $size = (int) $disk->size($path);
                        $mime = (string) ($disk->mimeType($path) ?: 'application/octet-stream');
                    } catch (\Throwable) {
                        // Keep safe fallback
                    }
                }

                // Resolve pipeline stage if status exists
                $stageId = null;
                if ($leadRow->lead_status_id) {
                    $status = DB::table('lead_statuses')->where('id', $leadRow->lead_status_id)->first();
                    $stageId = $status?->pipeline_stage_id;
                }

                DB::table('lead_documents')->insert([
                    'lead_id' => $leadRow->id,
                    'pipeline_stage_id' => $stageId,
                    'pipeline_stage_field_id' => null,
                    'lead_status_history_id' => null,
                    'category' => 'quotation',
                    'original_name' => $filename ?: 'عرض سعر.pdf',
                    'stored_name' => $filename,
                    'disk' => 'local',
                    'path' => $path,
                    'mime_type' => $mime,
                    'size' => $size,
                    'created_by_user_id' => $leadRow->created_by_user_id,
                    'created_at' => $leadRow->created_at ?? now(),
                    'updated_at' => $leadRow->updated_at ?? now(),
                ]);
            }
        } catch (\Throwable) {
            // Non-blocking backfill
        }
    }
};
