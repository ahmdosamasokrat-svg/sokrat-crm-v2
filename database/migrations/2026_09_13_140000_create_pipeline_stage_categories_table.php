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

        if (! Schema::hasTable('pipeline_stage_categories')) {
            Schema::create('pipeline_stage_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->string('description_ar', 255)->nullable();
                $table->string('color', 20)->default('#3478f6');
                $table->string('icon', 50)->nullable()->default('bi-collection');
                $table->unsignedSmallInteger('position')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasColumn('pipeline_stages', 'pipeline_stage_category_id')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->foreignId('pipeline_stage_category_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('pipeline_stage_categories')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        if (Schema::hasColumn('pipeline_stages', 'pipeline_stage_category_id')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->dropForeign(['pipeline_stage_category_id']);
                $table->dropColumn('pipeline_stage_category_id');
            });
        }

        Schema::dropIfExists('pipeline_stage_categories');
    }
};
