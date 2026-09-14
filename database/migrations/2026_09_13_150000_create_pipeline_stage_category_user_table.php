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

        if (! Schema::hasTable('pipeline_stage_category_user')) {
            Schema::create('pipeline_stage_category_user', function (Blueprint $table): void {
                $table->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();
                $table->foreignId('pipeline_stage_category_id')
                    ->constrained('pipeline_stage_categories')
                    ->cascadeOnDelete();
                $table->primary(['user_id', 'pipeline_stage_category_id'], 'stage_cat_user_primary');
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        Schema::dropIfExists('pipeline_stage_category_user');
    }
};
