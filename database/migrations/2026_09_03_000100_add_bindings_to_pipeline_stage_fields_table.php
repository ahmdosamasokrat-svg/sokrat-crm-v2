<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('pipeline_stage_fields')) {
            Schema::table('pipeline_stage_fields', function (Blueprint $table): void {
                if (! Schema::hasColumn('pipeline_stage_fields', 'binding_type')) {
                    $table->string('binding_type', 20)->default('custom')->after('type');
                }
                if (! Schema::hasColumn('pipeline_stage_fields', 'binding_target')) {
                    $table->string('binding_target', 100)->nullable()->after('binding_type');
                }
                if (! Schema::hasColumn('pipeline_stage_fields', 'default_value')) {
                    $table->text('default_value')->nullable()->after('position');
                }
            });

            Schema::table('pipeline_stage_fields', function (Blueprint $table): void {
                $table->index(['pipeline_stage_id', 'binding_type'], 'psf_stage_binding_type_idx');
            });
        }
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('pipeline_stage_fields')) {
            Schema::table('pipeline_stage_fields', function (Blueprint $table): void {
                $table->dropIndex('psf_stage_binding_type_idx');
                $table->dropColumn(['binding_type', 'binding_target', 'default_value']);
            });
        }
    }
};
