<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, array{name: string, groups: list<string>}>
     */
    private const PERMISSIONS = [
        'reports.employees.view' => [
            'name' => 'عرض تقارير الموظفين',
            'groups' => ['super-admin', 'sales-manager', 'read-only'],
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PERMISSIONS as $code => $definition) {
            DB::table('permissions')->insertOrIgnore([
                'code' => $code,
                'module' => 'reports',
                'name_ar' => $definition['name'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('permissions')
                ->where('code', $code)
                ->update([
                    'module' => 'reports',
                    'name_ar' => $definition['name'],
                    'updated_at' => $now,
                ]);

            $permissionId = DB::table('permissions')
                ->where('code', $code)
                ->value('id');

            if (! $permissionId) {
                continue;
            }

            $groupIds = DB::table('groups')
                ->whereIn('code', $definition['groups'])
                ->pluck('id');

            foreach ($groupIds as $groupId) {
                DB::table('group_permission')->insertOrIgnore([
                    'group_id' => $groupId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('code', array_keys(self::PERMISSIONS))
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('group_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();

            DB::table('permissions')
                ->whereIn('id', $permissionIds)
                ->delete();
        }
    }
};
