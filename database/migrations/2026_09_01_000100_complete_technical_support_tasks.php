<?php

declare(strict_types=1);

use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasTable('technical_support_tasks')
            && ! Schema::hasColumn('technical_support_tasks', 'color')) {
            Schema::table('technical_support_tasks', function (Blueprint $table): void {
                $table->string('color', 7)->default('#dc2637')->after('priority');
            });
        }

        $now = now();
        DB::table('permissions')->insertOrIgnore([
            'code' => 'technical_support.tasks.manage',
            'module' => 'technical_support',
            'name_ar' => 'إدارة مهام الدعم الفني',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')
            ->where('code', 'technical_support.tasks.manage')
            ->value('id');
        $groupIds = DB::table('groups')
            ->whereIn('code', ['super-admin', 'sales-manager'])
            ->pluck('id');

        if (! $permissionId) {
            return;
        }

        foreach ($groupIds as $groupId) {
            DB::table('group_permission')->insertOrIgnore([
                'group_id' => $groupId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        $permissionId = DB::table('permissions')
            ->where('code', 'technical_support.tasks.manage')
            ->value('id');

        if ($permissionId) {
            DB::table('group_permission')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        if (Schema::hasTable('technical_support_tasks')
            && Schema::hasColumn('technical_support_tasks', 'color')) {
            Schema::table('technical_support_tasks', function (Blueprint $table): void {
                $table->dropColumn('color');
            });
        }
    }
};
