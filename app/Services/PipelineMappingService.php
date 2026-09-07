<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class PipelineMappingService
{
    public const SIDEBAR_CACHE_KEY = PipelineStage::SIDEBAR_CACHE_KEY;
    public const DASHBOARD_CACHE_KEY = PipelineStage::DASHBOARD_CACHE_KEY;
    public const DASHBOARD_LEGACY_KEY = 'crm.dashboard.pipeline_stages';

    /**
     * Return canonical active pipeline stages ordered by position and ID.
     *
     * @param bool $requireStatuses When true, only stages with at least one status are returned.
     * @return Collection<int, PipelineStage>
     */
    public static function getActiveStages(bool $requireStatuses = true): Collection
    {
        $query = PipelineStage::query()
            ->where('is_active', true)
            ->with(['statuses' => static fn ($q) => $q->orderBy('position')->orderBy('id')])
            ->orderBy('position')
            ->orderBy('id');

        if ($requireStatuses) {
            $query->whereHas('statuses');
        }

        return $query->get();
    }

    /**
     * Return canonical active lead statuses ordered by position and ID.
     * Only statuses linked to an active pipeline stage are returned.
     *
     * @return Collection<int, LeadStatus>
     */
    public static function getActiveStatuses(): Collection
    {
        return LeadStatus::query()
            ->with(['stage.activeFields'])
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Get active pipeline stages for sidebar navigation.
     * Uses cache with validation to prevent stale/orphan entries from ever appearing.
     *
     * @return Collection<int, PipelineStage>
     */
    public static function getActiveStagesForSidebar(): Collection
    {
        $cached = Cache::get(self::SIDEBAR_CACHE_KEY);

        if (is_array($cached) && ! empty($cached)) {
            // Self-healing validation: verify all cached IDs actually exist as active stages in DB
            $cachedIds = array_column($cached, 'id');
            $activeDbCount = PipelineStage::query()
                ->whereIn('id', $cachedIds)
                ->where('is_active', true)
                ->whereHas('statuses')
                ->count();

            if ($activeDbCount === count($cachedIds)) {
                return PipelineStage::hydrate($cached);
            }

            // Cache was stale or contained orphan/deleted IDs - invalidate immediately
            self::clearCache();
        }

        $stages = self::getActiveStages(requireStatuses: true);

        Cache::put(
            self::SIDEBAR_CACHE_KEY,
            $stages->map(static fn (PipelineStage $stage) => [
                'id' => $stage->id,
                'code' => $stage->code,
                'name_ar' => $stage->name_ar,
                'color' => $stage->color,
                'icon' => $stage->icon,
                'position' => $stage->position,
                'is_primary' => (bool) $stage->is_primary,
                'is_active' => (bool) $stage->is_active,
            ])->toArray(),
            now()->addHours(24)
        );

        return $stages;
    }

    /**
     * Clear all cached pipeline stage navigation entries.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::SIDEBAR_CACHE_KEY);
        Cache::forget(self::DASHBOARD_CACHE_KEY);
        Cache::forget(self::DASHBOARD_LEGACY_KEY);
    }

    /**
     * Re-normalize pipeline stage positions to be contiguous 1..N.
     */
    public static function normalizeStagePositions(): void
    {
        $stages = PipelineStage::query()->orderBy('position')->orderBy('id')->get();
        $pos = 1;
        foreach ($stages as $stage) {
            if ((int) $stage->position !== $pos) {
                $stage->update(['position' => $pos]);
            }
            $pos++;
        }
        self::clearCache();
    }
}
