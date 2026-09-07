<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\PipelineStage;
use App\Models\Quotation;
use App\Models\User;
use App\Security\LeadAssignment;
use App\Support\CrmDatabaseGuard;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeReportController extends Controller
{

    public function __invoke(Request $request): View|JsonResponse
    {
        CrmDatabaseGuard::ensureConnected();
        $actor = $request->user();

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'stage_id' => ['nullable', 'integer', 'min:1'],
            'target_stage_id' => ['nullable', 'integer', 'min:1'],
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'period' => ['nullable', Rule::in(['all', 'today', 'week', 'month', 'year', 'custom'])],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $period = (string) ($validated['period'] ?? 'all');
        [$from, $to] = $this->resolvePeriod(
            $period,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        $assignableUsers = LeadAssignment::assignableUsers($actor);

        $selectedEmployeeId = isset($validated['employee_id'])
            ? (int) $validated['employee_id']
            : null;

        if ($selectedEmployeeId !== null && ! $assignableUsers->contains('id', $selectedEmployeeId)) {
            abort(404);
        }

        $selectedEmployee = $selectedEmployeeId !== null
            ? $assignableUsers->firstWhere('id', $selectedEmployeeId)
            : null;

        $selectedStageId = isset($validated['stage_id']) ? (int) $validated['stage_id'] : null;
        $selectedCampaignId = isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null;

        $stages = PipelineStage::query()->where('is_active', true)->orderBy('position')->get();
        $campaigns = Campaign::query()->visibleTo($actor)->orderByDesc('starts_at')->get();

        // 1. Lead Base Query (scoped to actor)
        $leadQuery = Lead::query()->accessibleTo($actor);

        if ($selectedEmployeeId !== null) {
            $leadQuery->where('assigned_user_id', $selectedEmployeeId);
        }

        if ($selectedStageId !== null) {
            $leadQuery->whereHas('status', static function (Builder $q) use ($selectedStageId): void {
                $q->where('pipeline_stage_id', $selectedStageId);
            });
        }

        if ($selectedCampaignId !== null) {
            $leadQuery->whereHas('campaigns', static function (Builder $q) use ($selectedCampaignId): void {
                $q->where('campaigns.id', $selectedCampaignId);
            });
        }

        if ($from !== null) {
            $leadQuery->where('leads.created_at', '>=', $from);
        }
        if ($to !== null) {
            $leadQuery->where('leads.created_at', '<=', $to);
        }

        $totalLeads = (clone $leadQuery)->count();
        // 2. Dynamic Target Stage & Conversion Rate (as in dashboard)
        $targetStageId = isset($validated['target_stage_id'])
            ? (int) $validated['target_stage_id']
            : (isset($validated['stage_id']) ? (int) $validated['stage_id'] : null);

        $defaultTargetStage = $stages->first(static fn (PipelineStage $s) => in_array($s->code, ['contract_closed', 'closing_execution', 'execution', 'donor', 'closed_won'], true))
            ?? $stages->last()
            ?? $stages->first();

        $targetStage = $targetStageId !== null
            ? ($stages->firstWhere('id', $targetStageId) ?? $defaultTargetStage)
            : $defaultTargetStage;

        $targetStageId = $targetStage?->id;

        $targetStatusIds = $targetStage ? $targetStage->statuses->pluck('id')->all() : [];
        $targetStageLeads = (! empty($targetStatusIds))
            ? (clone $leadQuery)->whereIn('leads.lead_status_id', $targetStatusIds)->count()
            : 0;

        $targetStageRate = $totalLeads > 0
            ? round(($targetStageLeads / $totalLeads) * 100, 1)
            : 0.0;

        // 3. Followups Conducted
        $followupQuery = LeadFollowup::query()
            ->whereHas('lead', static function (Builder $q) use ($actor): void {
                $q->accessibleTo($actor);
            });

        if ($selectedEmployeeId !== null) {
            $followupQuery->where('user_id', $selectedEmployeeId);
        }
        if ($from !== null) {
            $followupQuery->where('followed_up_at', '>=', $from);
        }
        if ($to !== null) {
            $followupQuery->where('followed_up_at', '<=', $to);
        }
        $totalFollowups = $followupQuery->count();

        // 4. Due & Overdue Followups (Replaces Quotation Value Card)
        $dueFollowups = (clone $leadQuery)
            ->whereNotNull('leads.next_follow_up_at')
            ->where('leads.next_follow_up_at', '<=', now()->endOfDay())
            ->count();

        $overdueFollowups = (clone $leadQuery)
            ->whereNotNull('leads.next_follow_up_at')
            ->where('leads.next_follow_up_at', '<', now()->startOfDay())
            ->count();
        // 5. Recent Leads (Table on one side)
        $recentLeads = (clone $leadQuery)
            ->with(['status.stage', 'assignedUser:id,name', 'campaigns:id,name'])
            ->orderByDesc('id')
            ->limit(7)
            ->get();

        // 6. Stage Funnel / Distribution (Chart & List)
        $stageCounts = (clone $leadQuery)
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(DISTINCT leads.id) as aggregate')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('aggregate', 'lead_statuses.pipeline_stage_id');

        $stageDistribution = $stages->map(static function (PipelineStage $stage) use ($stageCounts, $totalLeads): array {
            $count = (int) ($stageCounts[$stage->id] ?? 0);

            return [
                'id' => $stage->id,
                'name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3b82f6',
                'count' => $count,
                'percentage' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0.0,
            ];
        })->values();
        // 7. Active Pipeline Stages Strip (same as dashboard)
        $activePipelineStages = [];
        foreach ($stages as $stage) {
            $stageCount = (int) ($stageCounts[$stage->id] ?? 0);
            $icon = $stage->icon ? (str_starts_with($stage->icon, 'bi-') || str_starts_with($stage->icon, 'bi ') ? $stage->icon : 'bi-' . $stage->icon) : 'bi-diagram-3';
            if (! str_starts_with($icon, 'bi ') && ! str_starts_with($icon, 'bi-')) {
                $icon = 'bi bi-' . $icon;
            } elseif (str_starts_with($icon, 'bi-')) {
                $icon = 'bi ' . $icon;
            }

            $activePipelineStages[] = [
                'id' => $stage->id,
                'code' => $stage->code,
                'name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3b82f6',
                'icon' => $icon,
                'count' => $stageCount,
            ];
        }

        // 8. Communication Channels Distribution (replaces duplicate stage graph)
        $channelCounts = LeadFollowup::query()
            ->whereHas('lead', static fn (Builder $q) => $q->accessibleTo($actor))
            ->when($selectedEmployeeId !== null, static fn (Builder $q) => $q->where('user_id', $selectedEmployeeId))
            ->when($from !== null, static fn (Builder $q) => $q->where('followed_up_at', '>=', $from))
            ->when($to !== null, static fn (Builder $q) => $q->where('followed_up_at', '<=', $to))
            ->selectRaw('communication_type, count(*) as total')
            ->groupBy('communication_type')
            ->pluck('total', 'communication_type')
            ->all();
        $callCount = (int) (($channelCounts['call'] ?? 0) + ($channelCounts['phone'] ?? 0));
        $channelCounts['call'] = $callCount;
        unset($channelCounts['phone']);

        $channelMeta = [
            'call' => ['name' => 'اتصال هاتفي', 'color' => '#3b82f6', 'icon' => 'bi-telephone-fill'],
            'whatsapp' => ['name' => 'واتساب', 'color' => '#10b981', 'icon' => 'bi-whatsapp'],
            'meeting' => ['name' => 'مقابلة / اجتماع', 'color' => '#8b5cf6', 'icon' => 'bi-calendar-event-fill'],
            'email' => ['name' => 'بريد إلكتروني', 'color' => '#f59e0b', 'icon' => 'bi-envelope-fill'],
            'other' => ['name' => 'أخرى', 'color' => '#64748b', 'icon' => 'bi-chat-dots-fill'],
        ];

        $totalChannelsCount = array_sum($channelCounts);
        $channelsDistribution = [];
        foreach ($channelMeta as $key => $meta) {
            $cnt = (int) ($channelCounts[$key] ?? 0);
            $channelsDistribution[] = [
                'key' => $key,
                'name' => $meta['name'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'count' => $cnt,
                'percentage' => $totalChannelsCount > 0 ? round(($cnt / $totalChannelsCount) * 100, 1) : 0.0,
            ];
        }

        // 9. Lead Sources Distribution
        $sourceCounts = (clone $leadQuery)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->selectRaw('source, count(*) as total')
            ->groupBy('source')
            ->orderByDesc('total')
            ->pluck('total', 'source')
            ->all();

        $totalSourcesCount = array_sum($sourceCounts);
        $sourceColors = ['#3b82f6', '#10b981', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#64748b'];
        $sourcesDistribution = [];
        $srcIdx = 0;
        foreach ($sourceCounts as $src => $cnt) {
            $sourcesDistribution[] = [
                'name' => (string) $src,
                'count' => (int) $cnt,
                'color' => $sourceColors[$srcIdx % count($sourceColors)],
                'percentage' => $totalSourcesCount > 0 ? round(($cnt / $totalSourcesCount) * 100, 1) : 0.0,
            ];
            $srcIdx++;
        }

        // 10. Monthly Performance Timeline (6 Months)
        $monthlyTimeline = $this->buildMonthlyTimeline($actor, $selectedEmployeeId);

        // 11. Breakdown Table (Campaigns for selected employee, or Employees comparison)
        $breakdownRows = $this->buildBreakdownRows($actor, $assignableUsers, $selectedEmployeeId, $from, $to, $selectedStageId, $selectedCampaignId);
        if ($request->ajax() || $request->wantsJson() || $request->query('ajax')) {
            return response()->json([
                'success' => true,
                'target_stage_id' => $targetStageId,
                'target_stage_name' => $targetStage?->localizedName() ?? '—',
                'target_stage_color' => $targetStage?->color ?: '#10b981',
                'target_stage_icon' => $targetStage?->icon ?: 'bi-patch-check-fill',
                'target_stage_leads' => $targetStageLeads,
                'target_stage_leads_formatted' => number_format($targetStageLeads),
                'target_stage_rate' => $targetStageRate,
                'target_stage_rate_formatted' => number_format($targetStageRate, 1) . '%',
                'total_leads' => $totalLeads,
                'total_leads_formatted' => number_format($totalLeads),
                'subtitle_leads' => number_format($targetStageLeads) . ' ' . __('من إجمالي') . ' ' . number_format($totalLeads) . ' ' . __('عميل'),
                'subtitle_rate' => __('نسبة الإنجاز لمرحلة') . ' ' . ($targetStage?->localizedName() ?? '—'),
            ]);
        }

        $metrics = [
            'total_leads' => $totalLeads,
            'target_stage_leads' => $targetStageLeads,
            'target_stage_rate' => $targetStageRate,
            'target_stage_id' => $targetStageId,
            'target_stage_name' => $targetStage?->localizedName() ?? '—',
            'target_stage_color' => $targetStage?->color ?: '#10b981',
            'target_stage_icon' => $targetStage?->icon ?: 'bi-patch-check-fill',
            'total_followups' => $totalFollowups,
            'due_followups' => $dueFollowups,
            'overdue_followups' => $overdueFollowups,
            'converted_leads' => $targetStageLeads,
            'conversion_rate' => $targetStageRate,
        ];

        $filters = [
            'employee_id' => $selectedEmployeeId,
            'stage_id' => $selectedStageId,
            'target_stage_id' => $targetStageId,
            'campaign_id' => $selectedCampaignId,
            'period' => $period,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ];

        return view('reports.employees', [
            'assignableUsers' => $assignableUsers,
            'selectedEmployee' => $selectedEmployee,
            'stages' => $stages,
            'targetStage' => $targetStage,
            'activePipelineStages' => $activePipelineStages,
            'channelsDistribution' => $channelsDistribution,
            'sourcesDistribution' => $sourcesDistribution,
            'campaigns' => $campaigns,
            'metrics' => $metrics,
            'recentLeads' => $recentLeads,
            'stageDistribution' => $stageDistribution,
            'monthlyTimeline' => $monthlyTimeline,
            'breakdownRows' => $breakdownRows,
            'filters' => $filters,
        ]);
    }

    private function resolvePeriod(
        string $period,
        ?string $customFrom,
        ?string $customTo
    ): array {
        $now = CarbonImmutable::now();

        return match ($period) {
            'all' => [null, null],
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => [
                $customFrom ? CarbonImmutable::parse($customFrom)->startOfDay() : null,
                $customTo ? CarbonImmutable::parse($customTo)->endOfDay() : null,
            ],
            default => [null, null],
        };
    }

    private function buildMonthlyTimeline(User $actor, ?int $employeeId): array
    {
        $nowDate = CarbonImmutable::now();
        $monthRanges = [];
        $sixMonthsStart = null;
        $sixMonthsEnd = null;

        for ($i = 5; $i >= 0; $i--) {
            $month = $nowDate->subMonths($i);
            $startOfMonth = $month->startOfMonth();
            $endOfMonth = $month->endOfMonth();

            if ($sixMonthsStart === null) {
                $sixMonthsStart = $startOfMonth->toDateTimeString();
            }
            $sixMonthsEnd = $endOfMonth->toDateTimeString();

            $monthRanges[] = [
                'label' => $month->translatedFormat('M Y'),
                'start' => $startOfMonth->toDateTimeString(),
                'end' => $endOfMonth->toDateTimeString(),
            ];
        }

        $leadQuery = Lead::query()->accessibleTo($actor);
        if ($employeeId !== null) {
            $leadQuery->where('assigned_user_id', $employeeId);
        }

        $newSelectParts = [];
        $contractSelectParts = [];
        $followupSelectParts = [];

        foreach ($monthRanges as $idx => $r) {
            $newSelectParts[] = "count(case when created_at between '{$r['start']}' and '{$r['end']}' then 1 end) as m{$idx}";
            $contractSelectParts[] = "count(case when updated_at between '{$r['start']}' and '{$r['end']}' then 1 end) as m{$idx}";
            $followupSelectParts[] = "count(case when followed_up_at between '{$r['start']}' and '{$r['end']}' then 1 end) as m{$idx}";
        }

        $newRow = (clone $leadQuery)
            ->whereBetween('created_at', [$sixMonthsStart, $sixMonthsEnd])
            ->selectRaw(implode(', ', $newSelectParts))
            ->first();

        $contractRow = (clone $leadQuery)
            ->whereBetween('updated_at', [$sixMonthsStart, $sixMonthsEnd])
            ->whereHas('status.stage', static fn ($q) => $q->whereIn('code', ['contract_closed', 'closing_execution', 'execution', 'donor', 'closed_won']))
            ->selectRaw(implode(', ', $contractSelectParts))
            ->first();

        $followupQuery = LeadFollowup::query()->whereHas('lead', fn ($q) => $q->accessibleTo($actor));
        if ($employeeId !== null) {
            $followupQuery->where('user_id', $employeeId);
        }
        $followupRow = $followupQuery
            ->whereBetween('followed_up_at', [$sixMonthsStart, $sixMonthsEnd])
            ->selectRaw(implode(', ', $followupSelectParts))
            ->first();

        $labels = [];
        $newLeads = [];
        $converted = [];
        $followups = [];

        foreach ($monthRanges as $idx => $r) {
            $key = "m{$idx}";
            $labels[] = $r['label'];
            $newLeads[] = (int) ($newRow->{$key} ?? 0);
            $converted[] = (int) ($contractRow->{$key} ?? 0);
            $followups[] = (int) ($followupRow->{$key} ?? 0);
        }

        return [
            'labels' => $labels,
            'new_leads' => $newLeads,
            'converted' => $converted,
            'followups' => $followups,
        ];
    }

    private function buildBreakdownRows(
        User $actor,
        $assignableUsers,
        ?int $employeeId,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
        ?int $stageId,
        ?int $campaignId
    ): array {
        if ($employeeId !== null) {
            // Employee selected: breakdown by campaign
            $campaignQuery = Campaign::query()
                ->visibleTo($actor)
                ->whereHas('leads', static function (Builder $q) use ($employeeId): void {
                    $q->where('assigned_user_id', $employeeId);
                });

            if ($campaignId !== null) {
                $campaignQuery->whereKey($campaignId);
            }

            return $campaignQuery->get()->map(static function (Campaign $c) use ($employeeId, $from, $to, $stageId): array {
                $leadsQ = $c->leads()->where('assigned_user_id', $employeeId);
                if ($stageId !== null) {
                    $leadsQ->whereHas('status', fn ($q) => $q->where('pipeline_stage_id', $stageId));
                }
                if ($from !== null) {
                    $leadsQ->where('created_at', '>=', $from);
                }
                if ($to !== null) {
                    $leadsQ->where('created_at', '<=', $to);
                }

                $total = (clone $leadsQ)->count();
                $converted = (clone $leadsQ)->whereHas('status.stage', fn ($q) => $q->whereIn('code', ['contract_closed', 'closing_execution', 'execution', 'donor', 'closed_won']))->count();

                $badgeState = $c->ends_at->isPast()
                    ? 'ended'
                    : ($c->starts_at->isFuture() ? 'upcoming' : 'active');

                return [
                    'id' => $c->id,
                    'title' => $c->name,
                    'type' => 'campaign',
                    'subtitle' => $c->starts_at->format('Y-m-d') . ' - ' . $c->ends_at->format('Y-m-d'),
                    'leads_count' => $total,
                    'converted_count' => $converted,
                    'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
                    'badge' => $badgeState === 'active' ? 'نشطة' : ($badgeState === 'ended' ? 'منتهية' : 'قادمة'),
                    'badge_class' => $badgeState,
                ];
            })->all();
        }

        // All employees selected: breakdown comparing employees
        return $assignableUsers->map(static function (User $u) use ($actor, $from, $to, $stageId, $campaignId): array {
            $uLeadsQ = Lead::query()->accessibleTo($actor)->where('assigned_user_id', $u->id);

            if ($stageId !== null) {
                $uLeadsQ->whereHas('status', fn ($q) => $q->where('pipeline_stage_id', $stageId));
            }
            if ($campaignId !== null) {
                $uLeadsQ->whereHas('campaigns', fn ($q) => $q->where('campaigns.id', $campaignId));
            }
            if ($from !== null) {
                $uLeadsQ->where('created_at', '>=', $from);
            }
            if ($to !== null) {
                $uLeadsQ->where('created_at', '<=', $to);
            }

            $total = (clone $uLeadsQ)->count();
            $converted = (clone $uLeadsQ)->whereHas('status.stage', fn ($q) => $q->whereIn('code', ['contract_closed', 'closing_execution', 'execution', 'donor', 'closed_won']))->count();

            return [
                'id' => $u->id,
                'title' => $u->name,
                'type' => 'employee',
                'subtitle' => $u->username ?? '—',
                'leads_count' => $total,
                'converted_count' => $converted,
                'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0.0,
                'badge' => $u->is_active ? 'نشط' : 'معطل',
                'badge_class' => $u->is_active ? 'active' : 'ended',
            ];
        })->all();
    }
}
