<?php

declare(strict_types=1);

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

        // 1. Add SoftDeletes and trash tracking metadata to leads table
        Schema::table('leads', function (Blueprint $table): void {
            if (! Schema::hasColumn('leads', 'deleted_at')) {
                $table->softDeletes()->index();
            }
            if (! Schema::hasColumn('leads', 'deleted_by_user_id')) {
                $table->unsignedBigInteger('deleted_by_user_id')->nullable()->after('deleted_at')->index();
            }
            if (! Schema::hasColumn('leads', 'deleted_from_stage_id')) {
                $table->unsignedBigInteger('deleted_from_stage_id')->nullable()->after('deleted_by_user_id')->index();
            }
            if (! Schema::hasColumn('leads', 'deleted_reason')) {
                $table->string('deleted_reason', 255)->nullable()->after('deleted_from_stage_id');
            }
        });

        // 2. Add SoftDeletes, is_system, and is_default to pipeline_stages table
        Schema::table('pipeline_stages', function (Blueprint $table): void {
            if (! Schema::hasColumn('pipeline_stages', 'deleted_at')) {
                $table->softDeletes()->index();
            }
            if (! Schema::hasColumn('pipeline_stages', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_primary');
            }
            if (! Schema::hasColumn('pipeline_stages', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_system')->index();
            }
        });

        // Ensure default stage is designated (first active stage by position)
        $defaultStageId = DB::table('pipeline_stages')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->orderBy('position')
            ->orderBy('id')
            ->value('id');

        if ($defaultStageId !== null) {
            DB::table('pipeline_stages')
                ->where('id', $defaultStageId)
                ->update(['is_default' => true]);
        }

        // 3. Add SoftDeletes to lead_statuses table
        Schema::table('lead_statuses', function (Blueprint $table): void {
            if (! Schema::hasColumn('lead_statuses', 'deleted_at')) {
                $table->softDeletes()->index();
            }
        });

        // 4. Register new canonical permissions
        $now = now();
        $permissions = [
            [
                'code' => 'pipeline_stages.delete',
                'module' => 'pipeline_stages',
                'name_ar' => 'حذف مراحل العملاء',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'leads.trash.view',
                'module' => 'leads',
                'name_ar' => 'عرض سلة مهملات العملاء',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'leads.trash.restore',
                'module' => 'leads',
                'name_ar' => 'استعادة العملاء من سلة المهملات',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'code' => 'leads.trash.force_delete',
                'module' => 'leads',
                'name_ar' => 'الحذف النهائي للعملاء من سلة المهملات',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $perm['code']],
                $perm
            );
        }

        // 5. Grant permissions to super-admin and sales-manager
        $permMap = DB::table('permissions')
            ->whereIn('code', [
                'pipeline_stages.delete',
                'leads.trash.view',
                'leads.trash.restore',
                'leads.trash.force_delete',
            ])
            ->pluck('id', 'code');

        $superAdminGroup = DB::table('groups')->where('id', 5)->first();
        if ($superAdminGroup && $permMap->isNotEmpty()) {
            foreach ($permMap as $permId) {
                DB::table('group_permission')->updateOrInsert([
                    'group_id' => $superAdminGroup->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        $salesManagerGroup = DB::table('groups')->where('id', 6)->first();
        if ($salesManagerGroup) {
            $managerPermCodes = ['leads.trash.view', 'leads.trash.restore'];
            foreach ($managerPermCodes as $code) {
                if (isset($permMap[$code])) {
                    DB::table('group_permission')->updateOrInsert([
                        'group_id' => $salesManagerGroup->id,
                        'permission_id' => $permMap[$code],
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        $codes = [
            'pipeline_stages.delete',
            'leads.trash.view',
            'leads.trash.restore',
            'leads.trash.force_delete',
        ];

        $permIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('group_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();

        Schema::table('lead_statuses', function (Blueprint $table): void {
            if (Schema::hasColumn('lead_statuses', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('pipeline_stages', function (Blueprint $table): void {
            if (Schema::hasColumn('pipeline_stages', 'is_default')) {
                $table->dropColumn('is_default');
            }
            if (Schema::hasColumn('pipeline_stages', 'is_system')) {
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('pipeline_stages', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'deleted_reason')) {
                $table->dropColumn('deleted_reason');
            }
            if (Schema::hasColumn('leads', 'deleted_from_stage_id')) {
                $table->dropColumn('deleted_from_stage_id');
            }
            if (Schema::hasColumn('leads', 'deleted_by_user_id')) {
                $table->dropColumn('deleted_by_user_id');
            }
            if (Schema::hasColumn('leads', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
