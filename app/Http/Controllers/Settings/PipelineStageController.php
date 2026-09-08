<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PipelineStageController extends Controller
{
    public function index(): View
    {
        $this->assertCrmDatabase();

        // 1. Repair orphan stages on load so every stage has a default status
        PipelineStage::repairOrphanStages();

        $stages = PipelineStage::query()
            ->withCount([
                'leads',
                'fields' => static fn ($q) => $q->whereNull('deleted_at'),
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $totalStagesCount = $stages->count();
        $primaryStagesCount = $stages->where('is_primary', true)->count();
        $customStagesCount = $stages->where('is_primary', false)->count();

        return view('settings.stages.index', [
            'stages' => $stages,
            'totalStagesCount' => $totalStagesCount,
            'primaryStagesCount' => $primaryStagesCount,
            'customStagesCount' => $customStagesCount,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description_ar' => ['nullable', 'string', 'max:255'],
        ], [
            'name_ar.required' => 'اسم المرحلة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة مثل #3478f6.',
        ]);

        $nextPosition = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $color = $validated['color'] ?? '#7b61df';

        DB::transaction(function () use ($validated, $nextPosition, $color): void {
            $code = 'stage_' . Str::lower(Str::random(8));

            PipelineStage::query()->create([
                'code' => $code,
                'name_ar' => trim($validated['name_ar']),
                'description_ar' => $validated['description_ar'] ?? null,
                'position' => $nextPosition,
                'color' => $color,
                'icon' => ! empty($validated['icon']) ? trim($validated['icon']) : null,
                'is_primary' => false,
                'is_active' => true,
            ]);
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تمت إضافة المرحلة بنجاح.');
    }

    public function update(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'position' => ['required', 'integer', 'min:1', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name_ar.required' => 'اسم المرحلة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة.',
        ]);

        $isActive = $stage->isPrimary() ? true : ($request->has('is_active') ? $request->boolean('is_active') : false);

        // Safety: If deactivating an optional stage that contains leads
        if (! $isActive && $stage->hasLeads()) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'لا يمكن تعطيل هذه المرحلة لوجود عملاء مرتبطين بها حالياً. يرجى نقل العملاء إلى مرحلة أخرى أولاً.',
                ]);
        }

        DB::transaction(function () use ($stage, $validated, $isActive): void {
            $newPosition = (int) $validated['position'];
            $oldPosition = (int) $stage->position;

            if ($newPosition !== $oldPosition) {
                $existing = PipelineStage::query()
                    ->where('id', '!=', $stage->id)
                    ->where('position', $newPosition)
                    ->first();

                if ($existing !== null) {
                    $stage->update(['position' => 254]);

                    if ($newPosition < $oldPosition) {
                        PipelineStage::query()
                            ->where('id', '!=', $stage->id)
                            ->where('position', '>=', $newPosition)
                            ->where('position', '<', $oldPosition)
                            ->orderByDesc('position')
                            ->each(function (PipelineStage $s) {
                                $s->increment('position');
                            });
                    } else {
                        PipelineStage::query()
                            ->where('id', '!=', $stage->id)
                            ->where('position', '>', $oldPosition)
                            ->where('position', '<=', $newPosition)
                            ->orderBy('position')
                            ->each(function (PipelineStage $s) {
                                $s->decrement('position');
                            });
                    }
                }
            }

            $stage->update([
                'name_ar' => trim($validated['name_ar']),
                'color' => $validated['color'] ?? $stage->color,
                'icon' => array_key_exists('icon', $validated) ? (! empty($validated['icon']) ? trim($validated['icon']) : null) : $stage->icon,
                'position' => $newPosition,
                'description_ar' => $validated['description_ar'] ?? null,
                'is_active' => $isActive,
            ]);

            // Re-normalize all stages to contiguous positions
            $allStages = PipelineStage::query()->orderBy('position')->orderBy('id')->get();
            $pos = 1;
            foreach ($allStages as $st) {
                if ((int) $st->position !== $pos) {
                    $st->update(['position' => $pos]);
                }
                $pos++;
            }

            // Update matching default status color/name if applicable
            $defaultStatus = LeadStatus::query()
                ->where('pipeline_stage_id', $stage->id)
                ->first();

            if ($defaultStatus !== null && $stage->statuses()->count() === 1) {
                $defaultStatus->update([
                    'name_ar' => $stage->name_ar,
                    'color' => $stage->color,
                ]);
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم حفظ تعديلات المرحلة بنجاح.');
    }

    public function destroy(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        // 1. Pipeline integrity protection: Cannot delete the last remaining active stage
        $activeStagesCount = PipelineStage::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->count();

        if ($activeStagesCount <= 1) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'لا يمكن حذف المرحلة الأخيرة المتبقية في النظام. يجب أن يحتوي مسار العمل على مرحلة نشطة واحدة على الأقل.',
                ]);
        }

        // If deleting the default stage, designate a new active default stage
        if ($stage->is_default) {
            $newDefault = PipelineStage::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->where('id', '!=', $stage->id)
                ->orderBy('position')
                ->first();
            if ($newDefault !== null) {
                $newDefault->update(['is_default' => true]);
            }
        }
        $leadCount = (int) $stage->leads()->count();

        // 2. If stage has leads: admin must choose action: 'move' or 'trash'
        if ($leadCount > 0) {
            $action = (string) $request->input('lead_action');
            if (! in_array($action, ['move', 'trash'], true)) {
                return redirect()
                    ->route('v2.settings.stages.index')
                    ->withErrors([
                        'stage' => 'تحتوي هذه المرحلة على عملاء. يرجى تحديد الإجراء المطلوب (نقل العملاء أو إرسالهم إلى سلة المهملات).',
                    ]);
            }

            if ($action === 'move') {
                $destStageId = (int) $request->input('destination_stage_id');
                $destStage = PipelineStage::query()
                    ->whereNull('deleted_at')
                    ->where('is_active', true)
                    ->where('id', '!=', $stage->id)
                    ->find($destStageId);

                if ($destStage === null) {
                    return redirect()
                        ->route('v2.settings.stages.index')
                        ->withErrors([
                            'destination_stage_id' => 'المرحلة البديلة المختارة لنقل العملاء غير صالحة.',
                        ]);
                }

                $destStatus = $destStage->ensureDefaultStatus();

                DB::transaction(function () use ($stage, $destStage, $destStatus, $leadCount, $request): void {
                    $moved = 0;
                    $statusIds = $stage->statuses()->pluck('id');
                    $actorName = trim((string) ($request->user()?->name ?: 'Admin'));
                    $now = now();

                    Lead::query()
                        ->whereIn('lead_status_id', $statusIds)
                        ->chunkById(100, function ($chunk) use (&$moved, $destStatus, $actorName, $now): void {
                            foreach ($chunk as $lead) {
                                $fromStatusId = $lead->lead_status_id;
                                $lead->lead_status_id = $destStatus->id;
                                $lead->save();

                                DB::table('lead_status_histories')->insert([
                                    'lead_id' => $lead->id,
                                    'from_status_id' => $fromStatusId,
                                    'to_status_id' => $destStatus->id,
                                    'changed_by' => $actorName,
                                    'changed_by_user_id' => auth()->id(),
                                    'note' => 'نقل العميل بسبب حذف المرحلة السابقة بواسطة ' . $actorName,
                                    'changed_at' => $now,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);

                                $moved++;
                            }
                        });

                    // Strict accounting check: expected Lead count == moved Lead count
                    if ($moved !== $leadCount) {
                        throw new \RuntimeException("Mismatch in moved leads accounting: expected {$leadCount}, moved {$moved}. Rolling back.");
                    }

                    LeadStatus::query()->where('pipeline_stage_id', $stage->id)->delete();
                    $stage->delete();

                    $allStages = PipelineStage::query()->whereNull('deleted_at')->orderBy('position')->orderBy('id')->get();
                    $pos = 1;
                    foreach ($allStages as $st) {
                        if ((int) $st->position !== $pos) {
                            $st->update(['position' => $pos]);
                        }
                        $pos++;
                    }
                });

                PipelineStage::clearSidebarCache();

                return redirect()
                    ->route('v2.settings.stages.index')
                    ->with('success', "تم حذف المرحلة ونقل {$leadCount} عميل إلى مرحلة ({$destStage->name_ar}) بنجاح.");
            }

            if ($action === 'trash') {
                $trashService = app(\App\Services\LeadTrashService::class);
                $actor = $request->user();

                DB::transaction(function () use ($stage, $trashService, $actor, $leadCount): void {
                    $trashed = 0;
                    $statusIds = $stage->statuses()->pluck('id');

                    Lead::query()
                        ->whereIn('lead_status_id', $statusIds)
                        ->chunkById(100, function ($chunk) use (&$trashed, $trashService, $actor, $stage): void {
                            foreach ($chunk as $lead) {
                                $trashService->trashLead($lead, $actor, "حذف مرحلة: {$stage->name_ar}");
                                $trashed++;
                            }
                        });

                    // Strict accounting check: expected Lead count == trashed Lead count
                    if ($trashed !== $leadCount) {
                        throw new \RuntimeException("Mismatch in trashed leads accounting: expected {$leadCount}, trashed {$trashed}. Rolling back.");
                    }

                    LeadStatus::query()->where('pipeline_stage_id', $stage->id)->delete();
                    $stage->delete();

                    $allStages = PipelineStage::query()->whereNull('deleted_at')->orderBy('position')->orderBy('id')->get();
                    $pos = 1;
                    foreach ($allStages as $st) {
                        if ((int) $st->position !== $pos) {
                            $st->update(['position' => $pos]);
                        }
                        $pos++;
                    }
                });

                PipelineStage::clearSidebarCache();

                return redirect()
                    ->route('v2.settings.stages.index')
                    ->with('success', "تم حذف المرحلة ونقل {$leadCount} عميل إلى سلة المهملات بنجاح.");
            }
        }

        // 3. Stage has zero leads -> safe direct deletion
        DB::transaction(function () use ($stage): void {
            LeadStatus::query()
                ->where('pipeline_stage_id', $stage->id)
                ->delete();

            $stage->delete();

            $allStages = PipelineStage::query()->whereNull('deleted_at')->orderBy('position')->orderBy('id')->get();
            $pos = 1;
            foreach ($allStages as $st) {
                if ((int) $st->position !== $pos) {
                    $st->update(['position' => $pos]);
                }
                $pos++;
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم حذف المرحلة بنجاح.');
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
