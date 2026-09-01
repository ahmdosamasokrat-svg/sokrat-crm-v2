<?php

namespace Database\Seeders;

use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmV2PipelineSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $stages = [
                [
                    'code' => 'new',
                    'name_ar' => 'جديد',
                    'description_ar' => 'العملاء الجدد المسجلين في النظام',
                    'position' => 1,
                    'color' => '#3478f6',
                    'icon' => 'bi-person-plus-fill',
                ],
                [
                    'code' => 'no_answer',
                    'name_ar' => 'لم يرد',
                    'description_ar' => 'عملاء لم يتم الرد على الاتصال',
                    'position' => 2,
                    'color' => '#e59b16',
                    'icon' => 'bi-telephone-x-fill',
                ],
                [
                    'code' => 'interested',
                    'name_ar' => 'مهتم',
                    'description_ar' => 'عملاء أبدوا اهتماماً بالخدمة',
                    'position' => 3,
                    'color' => '#169a64',
                    'icon' => 'bi-hand-thumbs-up-fill',
                ],
                [
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'description_ar' => 'عملاء غير مهتمين بالخدمة',
                    'position' => 4,
                    'color' => '#dc2637',
                    'icon' => 'bi-hand-thumbs-down-fill',
                ],
                [
                    'code' => 'meeting',
                    'name_ar' => 'مقابلة',
                    'description_ar' => 'تحديد وتنسيق موعد مقابلة أو عرض توضيحي',
                    'position' => 5,
                    'color' => '#7b61df',
                    'icon' => 'bi-calendar-event-fill',
                ],
                [
                    'code' => 'quotation',
                    'name_ar' => 'عرض سعر',
                    'description_ar' => 'تجهيز وإرسال عرض السعر للعميل',
                    'position' => 6,
                    'color' => '#e59b16',
                    'icon' => 'bi-file-earmark-text-fill',
                ],
                [
                    'code' => 'discussion',
                    'name_ar' => 'مناقشة',
                    'description_ar' => 'مناقشة بنود وتفاصيل العرض مع العميل',
                    'position' => 7,
                    'color' => '#5865f2',
                    'icon' => 'bi-chat-dots-fill',
                ],
                [
                    'code' => 'contract_closed',
                    'name_ar' => 'تقفيل عقد',
                    'description_ar' => 'توقيع العقد وإتمام الاتفاق',
                    'position' => 8,
                    'color' => '#15803d',
                    'icon' => 'bi-check-circle-fill',
                ],
                [
                    'code' => 'execution',
                    'name_ar' => 'تنفيذ',
                    'description_ar' => 'بدء تنفيذ وتسليم النظام للعميل',
                    'position' => 9,
                    'color' => '#16a34a',
                    'icon' => 'bi-gear-fill',
                ],
            ];

            $stageModels = [];

            foreach ($stages as $stage) {
                $stageModels[$stage['code']] =
                    PipelineStage::query()->updateOrCreate(
                        ['code' => $stage['code']],
                        [
                            'name_ar' => $stage['name_ar'],
                            'description_ar' => $stage['description_ar'],
                            'position' => $stage['position'],
                            'color' => $stage['color'],
                            'icon' => $stage['icon'],
                            'is_primary' => true,
                            'is_active' => true,
                        ]
                    );
            }

            $statuses = [
                [
                    'stage' => 'new',
                    'code' => 'new',
                    'name_ar' => 'جديد',
                    'position' => 1,
                    'color' => '#3478f6',
                    'terminal' => false,
                ],
                [
                    'stage' => 'no_answer',
                    'code' => 'no_answer',
                    'name_ar' => 'لم يرد',
                    'position' => 2,
                    'color' => '#e59b16',
                    'terminal' => false,
                ],
                [
                    'stage' => 'interested',
                    'code' => 'interested',
                    'name_ar' => 'مهتم',
                    'position' => 3,
                    'color' => '#169a64',
                    'terminal' => false,
                ],
                [
                    'stage' => 'not_interested',
                    'code' => 'not_interested',
                    'name_ar' => 'غير مهتم',
                    'position' => 4,
                    'color' => '#dc2637',
                    'terminal' => true,
                ],
                [
                    'stage' => 'meeting',
                    'code' => 'meeting',
                    'name_ar' => 'مقابلة',
                    'position' => 5,
                    'color' => '#7b61df',
                    'terminal' => false,
                ],
                [
                    'stage' => 'quotation',
                    'code' => 'quotation',
                    'name_ar' => 'عرض سعر',
                    'position' => 6,
                    'color' => '#e59b16',
                    'terminal' => false,
                ],
                [
                    'stage' => 'discussion',
                    'code' => 'discussion',
                    'name_ar' => 'مناقشة',
                    'position' => 7,
                    'color' => '#5865f2',
                    'terminal' => false,
                ],
                [
                    'stage' => 'contract_closed',
                    'code' => 'contract_closed',
                    'name_ar' => 'تقفيل عقد',
                    'position' => 8,
                    'color' => '#15803d',
                    'terminal' => false,
                ],
                [
                    'stage' => 'execution',
                    'code' => 'execution',
                    'name_ar' => 'تنفيذ',
                    'position' => 9,
                    'color' => '#16a34a',
                    'terminal' => true,
                ],
            ];

            foreach ($statuses as $status) {
                $stageModel = $stageModels[$status['stage']] ?? null;

                if (! $stageModel) {
                    continue;
                }

                LeadStatus::query()->updateOrCreate(
                    ['code' => $status['code']],
                    [
                        'pipeline_stage_id' => $stageModel->id,
                        'name_ar' => $status['name_ar'],
                        'position' => $status['position'],
                        'color' => $status['color'],
                        'is_terminal' => $status['terminal'],
                    ]
                );
            }
        });
    }
}
