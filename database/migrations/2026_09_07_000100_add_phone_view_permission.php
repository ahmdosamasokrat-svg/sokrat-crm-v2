<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'production' => 'sokrat_crm',
            'testing' => 'sokrat_crm_test',
            default => null,
        };
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();

        if (
            $connection->getDriverName() !== 'mysql'
            || $expectedDatabase === null
            || $database !== $expectedDatabase
        ) {
            throw new RuntimeException("Unexpected database: {$database}");
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['code' => 'leads.phone.view'],
            [
                'code' => 'leads.phone.view',
                'name_ar' => 'عرض أرقام هواتف العملاء',
                'module' => 'leads',
                'description' => 'السماح بعرض أرقام الهواتف الكاملة للعملاء. بدون هذه الصلاحية تظهر الأرقام مخفية.',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Auto-grant to Super Admin group
        $superAdminGroup = DB::table('groups')->where('code', 'super_admin')->first();
        if ($superAdminGroup) {
            $permId = DB::table('permissions')->where('code', 'leads.phone.view')->value('id');
            if ($permId) {
                DB::table('group_permission')->updateOrInsert([
                    'group_id' => $superAdminGroup->id,
                    'permission_id' => $permId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();
        $permId = DB::table('permissions')->where('code', 'leads.phone.view')->value('id');
        if ($permId) {
            DB::table('group_permission')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }
};
