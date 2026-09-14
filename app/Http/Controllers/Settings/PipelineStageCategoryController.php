<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use App\Models\PipelineStageCategory;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PipelineStageCategoryController extends Controller
{
    public function index(): View
    {
        $this->assertCrmDatabase();

        $categories = PipelineStageCategory::query()
            ->with([
                'stages' => static fn ($q) => $q->orderBy('position')->orderBy('id'),
            ])
            ->withCount('stages')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $allStages = PipelineStage::query()
            ->whereNull('deleted_at')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $totalCategoriesCount = $categories->count();
        $activeCategoriesCount = $categories->where('is_active', true)->count();
        $assignedStagesCount = PipelineStage::query()
            ->whereNotNull('pipeline_stage_category_id')
            ->count();
        $unassignedStagesCount = $allStages->count() - $assignedStagesCount;

        return view('settings.stage-categories.index', [
            'categories' => $categories,
            'allStages' => $allStages,
            'totalCategoriesCount' => $totalCategoriesCount,
            'activeCategoriesCount' => $activeCategoriesCount,
            'assignedStagesCount' => $assignedStagesCount,
            'unassignedStagesCount' => max(0, $unassignedStagesCount),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'stage_ids' => ['nullable', 'array'],
            'stage_ids.*' => ['integer', 'exists:pipeline_stages,id'],
        ], [
            'name_ar.required' => 'اسم الفئة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة مثل #3478f6.',
        ]);

        $nextPosition = (int) (PipelineStageCategory::query()->max('position') ?? 0) + 1;
        $color = $validated['color'] ?? '#3478f6';
        $icon = ! empty($validated['icon']) ? trim($validated['icon']) : 'bi-collection';

        DB::transaction(function () use ($validated, $nextPosition, $color, $icon): void {
            $category = PipelineStageCategory::query()->create([
                'name_ar' => trim($validated['name_ar']),
                'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
                'description_ar' => ! empty($validated['description_ar']) ? trim($validated['description_ar']) : null,
                'position' => $nextPosition,
                'color' => $color,
                'icon' => $icon,
                'is_active' => true,
            ]);

            $stageIds = $validated['stage_ids'] ?? [];
            if (! empty($stageIds)) {
                PipelineStage::query()
                    ->whereIn('id', $stageIds)
                    ->update(['pipeline_stage_category_id' => $category->id]);
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stage_categories.index')
            ->with('success', 'تمت إضافة فئة المراحل بنجاح.');
    }

    public function update(Request $request, PipelineStageCategory $category): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'position' => ['required', 'integer', 'min:1', 'max:255'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'stage_ids' => ['nullable', 'array'],
            'stage_ids.*' => ['integer', 'exists:pipeline_stages,id'],
        ], [
            'name_ar.required' => 'اسم الفئة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة.',
        ]);

        $isActive = $request->has('is_active') ? $request->boolean('is_active') : false;
        $color = $validated['color'] ?? $category->color;
        $icon = ! empty($validated['icon']) ? trim($validated['icon']) : ($category->icon ?: 'bi-collection');

        DB::transaction(function () use ($category, $validated, $isActive, $color, $icon): void {
            $newPosition = (int) $validated['position'];
            $oldPosition = (int) $category->position;

            if ($newPosition !== $oldPosition) {
                $existing = PipelineStageCategory::query()
                    ->where('id', '!=', $category->id)
                    ->where('position', $newPosition)
                    ->first();

                if ($existing !== null) {
                    $category->update(['position' => 254]);

                    if ($newPosition < $oldPosition) {
                        PipelineStageCategory::query()
                            ->where('id', '!=', $category->id)
                            ->where('position', '>=', $newPosition)
                            ->where('position', '<', $oldPosition)
                            ->orderByDesc('position')
                            ->each(function (PipelineStageCategory $c): void {
                                $c->increment('position');
                            });
                    } else {
                        PipelineStageCategory::query()
                            ->where('id', '!=', $category->id)
                            ->where('position', '>', $oldPosition)
                            ->where('position', '<=', $newPosition)
                            ->orderBy('position')
                            ->each(function (PipelineStageCategory $c): void {
                                $c->decrement('position');
                            });
                    }
                }
            }

            $category->update([
                'name_ar' => trim($validated['name_ar']),
                'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
                'description_ar' => ! empty($validated['description_ar']) ? trim($validated['description_ar']) : null,
                'position' => $newPosition,
                'color' => $color,
                'icon' => $icon,
                'is_active' => $isActive,
            ]);

            $stageIds = array_map('intval', $validated['stage_ids'] ?? []);

            // Unlink stages that were previously in this category but unselected
            PipelineStage::query()
                ->where('pipeline_stage_category_id', $category->id)
                ->whereNotIn('id', $stageIds)
                ->update(['pipeline_stage_category_id' => null]);

            // Link selected stages to this category
            if (! empty($stageIds)) {
                PipelineStage::query()
                    ->whereIn('id', $stageIds)
                    ->update(['pipeline_stage_category_id' => $category->id]);
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stage_categories.index')
            ->with('success', 'تم تحديث فئة المراحل بنجاح.');
    }

    public function destroy(PipelineStageCategory $category): RedirectResponse
    {
        $this->assertCrmDatabase();

        DB::transaction(function () use ($category): void {
            // Unlink stages from this category before deleting
            PipelineStage::query()
                ->where('pipeline_stage_category_id', $category->id)
                ->update(['pipeline_stage_category_id' => null]);

            $category->delete();

            // Re-sequence remaining categories
            $all = PipelineStageCategory::query()
                ->whereNull('deleted_at')
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            $pos = 1;
            foreach ($all as $c) {
                if ((int) $c->position !== $pos) {
                    $c->update(['position' => $pos]);
                }
                $pos++;
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stage_categories.index')
            ->with('success', 'تم حذف فئة المراحل بنجاح.');
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
