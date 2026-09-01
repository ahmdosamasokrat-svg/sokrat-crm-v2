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

        if (! Schema::hasTable('followup_customer_fields')) {
            Schema::create('followup_customer_fields', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 100)->unique();
                $table->string('lead_attribute', 100)->nullable()->unique();
                $table->string('label_ar', 255);
                $table->string('label_en', 255)->nullable();
                $table->string('type', 50)->default('text');
                $table->string('placeholder_ar', 255)->nullable();
                $table->string('placeholder_en', 255)->nullable();
                $table->text('help_text_ar')->nullable();
                $table->text('help_text_en')->nullable();
                $table->boolean('is_required')->default(false);
                $table->json('options')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(1);
                $table->softDeletes();
                $table->timestamps();

                $table->index(['is_active', 'position'], 'fcf_active_position_idx');
            });
        }

        if (! Schema::hasColumn('leads', 'custom_fields')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->json('custom_fields')->nullable()->after('notes');
            });
        }

        $this->seedSystemFields();
    }

    public function down(): void
    {
        CrmDatabaseGuard::ensureConnected();

        if (Schema::hasColumn('leads', 'custom_fields')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->dropColumn('custom_fields');
            });
        }

        Schema::dropIfExists('followup_customer_fields');
    }

    private function seedSystemFields(): void
    {
        $now = now();
        $fields = [
            ['company_name', 'اسم الشركة', 'Company Name', 'text', true],
            ['activity', 'النشاط', 'Activity', 'text', true],
            ['governorate', 'المحافظة', 'Governorate', 'text', true],
            ['address', 'العنوان', 'Address', 'text', true],
            ['users_count', 'عدد المستخدمين', 'Users Count', 'number', false],
            ['branches_count', 'عدد الفروع', 'Branches Count', 'number', false],
            ['job_title', 'المنصب', 'Job Title', 'text', false],
        ];

        foreach ($fields as $position => [$attribute, $labelAr, $labelEn, $type, $active]) {
            DB::table('followup_customer_fields')->updateOrInsert(
                ['key' => $attribute],
                [
                    'lead_attribute' => $attribute,
                    'label_ar' => $labelAr,
                    'label_en' => $labelEn,
                    'type' => $type,
                    'is_required' => false,
                    'is_system' => true,
                    'is_active' => $active,
                    'position' => $position + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }
};
