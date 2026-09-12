<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EmployeeReportService
{
    /**
     * Resolve date range from presets or custom inputs.
     *
     * @param array<string, mixed> $filters
     * @return array{from: CarbonImmutable, to: CarbonImmutable, preset: string, previous_from: CarbonImmutable, previous_to: CarbonImmutable}
     */
    public function resolveDateRange(array $filters): array
    {
        $now = CarbonImmutable::now();
        $preset = (string) ($filters['preset'] ?? 'this_month');

        switch ($preset) {
            case 'today':
                $from = $now->startOfDay();
                $to = $now->endOfDay();
                break;
            case 'yesterday':
                $from = $now->subDay()->startOfDay();
                $to = $now->subDay()->endOfDay();
                break;
            case 'this_week':
                $from = $now->startOfWeek();
                $to = $now->endOfWeek();
                break;
            case 'last_month':
                $from = $now->subMonth()->startOfMonth();
                $to = $now->subMonth()->endOfMonth();
                break;
            case 'custom':
                if (!empty($filters['from']) && !empty($filters['to'])) {
                    $from = CarbonImmutable::parse((string) $filters['from'])->startOfDay();
                    $to = CarbonImmutable::parse((string) $filters['to'])->endOfDay();
                    if ($from->gt($to)) {
                        $temp = $from;
                        $from = $to->startOfDay();
                        $to = $temp->endOfDay();
                    }
                } else {
                    $from = $now->startOfMonth();
                    $to = $now->endOfDay();
                    $preset = 'this_month';
                }
                break;
            case 'this_month':
            default:
                $preset = 'this_month';
                $from = $now->startOfMonth();
                $to = $now->endOfDay();
                break;
        }

        $durationInDays = max(1, $from->diffInDays($to) + 1);
        $previousTo = $from->subSecond();
        $previousFrom = $previousTo->subDays($durationInDays - 1)->startOfDay();

        return [
            'from' => $from,
            'to' => $to,
            'preset' => $preset,
            'previous_from' => $previousFrom,
            'previous_to' => $previousTo,
        ];
    }

    /**
     * Get authorized employee query based on viewer's permission scope.
     */
    public function getAuthorizedEmployeesQuery(User $viewer): Builder
    {
        $query = User::query()
            ->where('is_active', true)
            ->with(['groups:id,name,code']);

        if ($viewer->isSuperAdmin() || $viewer->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            return $query;
        }

        if ($viewer->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $viewer->loadMissing('groups');
            $groupIds = $viewer->groups->modelKeys();

            return $query->whereHas('groups', static function (Builder $gq) use ($groupIds): void {
                $gq->whereIn('groups.id', $groupIds);
            });
        }

        return $query->where('users.id', $viewer->id);
    }

    /**
     * Get list of authorized employees for select filter.
     *
     * @return Collection<int, User>
     */
    public function getAuthorizedEmployees(User $viewer): Collection
    {
        return $this->getAuthorizedEmployeesQuery($viewer)
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'email']);
    }

    /**
     * Get authorized groups for select filter.
     *
     * @return Collection<int, Group>
     */
    public function getAuthorizedGroups(User $viewer): Collection
    {
        $query = Group::query()->orderBy('name');

        if ($viewer->isSuperAdmin() || $viewer->hasPermission(CrmPermission::LEADS_SCOPE_ALL)) {
            return $query->get(['id', 'name', 'code']);
        }

        if ($viewer->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $viewer->loadMissing('groups');
            $groupIds = $viewer->groups->modelKeys();
            return $query->whereIn('id', $groupIds)->get(['id', 'name', 'code']);
        }

        $viewer->loadMissing('groups');
        return $viewer->groups;
    }

    /**
     * Get visible active campaigns.
     *
     * @return Collection<int, Campaign>
     */
    public function getVisibleCampaigns(User $viewer): Collection
    {
        return Campaign::query()
            ->orderBy('name')
            ->get(['id', 'name', 'starts_at', 'ends_at']);
    }

    /**
     * Get active pipeline stages ordered by position.
     *
     * @return Collection<int, PipelineStage>
     */
    public function getActivePipelineStages(?User $viewer = null): Collection
    {
        return PipelineStage::query()
            ->when(
                $viewer !== null,
                static fn (Builder $query): Builder => $query->visibleTo($viewer),
            )
            ->where('is_active', true)
            ->with(['statuses' => static fn ($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    /**
     * Determine conversion stages / terminal success status IDs.
     *
     * @param Collection<int, PipelineStage> $stages
     * @return array{stage_ids: list<int>, status_ids: list<int>}
     */
    public function resolveConversionTargets(Collection $stages): array
    {
        $conversionCodes = ['contract_closed', 'closing_execution', 'execution', 'donor'];
        $conversionStages = $stages->filter(static fn (PipelineStage $s) => in_array($s->code, $conversionCodes, true));

        if ($conversionStages->isEmpty()) {
            $lastActiveStage = $stages->sortByDesc('position')->first();
            if ($lastActiveStage !== null) {
                $conversionStages = collect([$lastActiveStage]);
            }
        }

        $stageIds = $conversionStages->pluck('id')->all();
        $statusIds = [];

        foreach ($conversionStages as $stage) {
            foreach ($stage->statuses as $status) {
                $statusIds[] = (int) $status->id;
            }
        }

        if (empty($statusIds)) {
            $statusIds = LeadStatus::query()
                ->where('is_terminal', true)
                ->whereNotIn('code', ['not_interested', 'lost', 'cancelled'])
                ->pluck('id')
                ->all();
        }

        return [
            'stage_ids' => array_values(array_unique($stageIds)),
            'status_ids' => array_values(array_unique($statusIds)),
        ];
    }

    /**
     * Build base Lead query with authorization scope and applied filters.
     *
     * @param array<string, mixed> $filters
     */
    public function buildScopedLeadQuery(User $viewer, array $filters, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Builder
    {
        $query = Lead::query()->accessibleTo($viewer);

        if (!empty($filters['user_id'])) {
            $query->where('leads.assigned_user_id', (int) $filters['user_id']);
        } elseif (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $query->whereHas('assignedUser.groups', static function (Builder $gq) use ($groupId): void {
                $gq->where('groups.id', $groupId);
            });
        }

        if (!empty($filters['campaign_id'])) {
            $campaignId = (int) $filters['campaign_id'];
            $query->whereHas('campaigns', static function (Builder $cq) use ($campaignId): void {
                $cq->where('campaigns.id', $campaignId);
            });
        }

        if (!empty($filters['stage_id'])) {
            $stageId = (int) $filters['stage_id'];
            $query->whereHas('status', static function (Builder $sq) use ($stageId): void {
                $sq->where('lead_statuses.pipeline_stage_id', $stageId);
            });
        }

        if ($from !== null && $to !== null) {
            $query->whereBetween('leads.created_at', [$from, $to]);
        }

        return $query;
    }

    /**
     * Calculate global Overview KPIs for the filtered dataset.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable, previous_from: CarbonImmutable, previous_to: CarbonImmutable} $dateRange
     * @param array{stage_ids: list<int>, status_ids: list<int>} $conversionTargets
     * @return array<string, mixed>
     */
    public function calculateOverviewKpis(
        User $viewer,
        array $filters,
        array $dateRange,
        array $conversionTargets
    ): array {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // 1. Current Scoped Leads (Current ownership)
        $currentLeadsQuery = $this->buildScopedLeadQuery($viewer, $filters);
        $totalLeads = (clone $currentLeadsQuery)->count();

        // 2. Active Leads (non-terminal statuses)
        $terminalStatusIds = LeadStatus::query()
            ->where('is_terminal', true)
            ->pluck('id')
            ->all();

        $activeLeads = (clone $currentLeadsQuery)
            ->whereNotIn('leads.lead_status_id', $terminalStatusIds)
            ->count();

        // 3. New Leads Created During Selected Period
        $newLeads = (clone $currentLeadsQuery)
            ->whereBetween('leads.created_at', [$from, $to])
            ->count();

        // 4. Follow-up Performance during period (Attributed to performer, scoped to accessible leads)
        $followupBase = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            });

        if (!empty($filters['user_id'])) {
            $followupBase->where('lead_followups.user_id', (int) $filters['user_id']);
        } elseif (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $followupBase->whereHas('user.groups', fn ($gq) => $gq->where('groups.id', $groupId));
        }

        $totalFollowups = (clone $followupBase)->count();
        $completedFollowups = (clone $followupBase)
            ->whereNotNull('followed_up_at')
            ->count();

        // 5. Overdue Follow-ups (current state of non-terminal leads where next_follow_up_at < NOW())
        $overdueFollowups = (clone $currentLeadsQuery)
            ->whereNotIn('leads.lead_status_id', $terminalStatusIds)
            ->whereNotNull('leads.next_follow_up_at')
            ->where('leads.next_follow_up_at', '<', now())
            ->count();

        // 6. Leads Without Follow-up (current state: non-terminal leads with zero followups)
        $leadsWithoutFollowup = (clone $currentLeadsQuery)
            ->whereNotIn('leads.lead_status_id', $terminalStatusIds)
            ->whereDoesntHave('followups')
            ->count();

        // 7. Converted Leads and Conversion Rate
        $conversionStatusIds = $conversionTargets['status_ids'];
        $convertedLeads = 0;
        if (!empty($conversionStatusIds)) {
            $convertedLeads = (clone $currentLeadsQuery)
                ->whereIn('leads.lead_status_id', $conversionStatusIds)
                ->count();
        }

        $conversionRate = $totalLeads > 0
            ? round(($convertedLeads / $totalLeads) * 100, 1)
            : 0.0;

        // 8. Average Follow-ups per Lead
        $avgFollowupsPerLead = $totalLeads > 0
            ? round($totalFollowups / $totalLeads, 1)
            : 0.0;

        // 9. Average First Response Time (hours between lead creation and first follow-up)
        $avgResponseHours = $this->calculateAvgFirstResponseHours($viewer, $filters, $from, $to);

        // 10. Average Stage Duration across current leads (days since last stage change or creation)
        $avgStageDurationDays = $this->calculateAvgStageDurationDays($viewer, $filters);

        $kpis = [
            'total_leads' => $totalLeads,
            'active_leads' => $activeLeads,
            'new_leads' => $newLeads,
            'total_followups' => $totalFollowups,
            'completed_followups' => $completedFollowups,
            'overdue_followups' => $overdueFollowups,
            'leads_without_followup' => $leadsWithoutFollowup,
            'converted_leads' => $convertedLeads,
            'conversion_rate' => $conversionRate,
            'avg_followups_per_lead' => $avgFollowupsPerLead,
            'avg_response_hours' => $avgResponseHours,
            'avg_stage_duration_days' => $avgStageDurationDays,
        ];

        // Previous Period Comparison if requested
        if (!empty($filters['compare'])) {
            $prevFrom = $dateRange['previous_from'];
            $prevTo = $dateRange['previous_to'];

            $prevFollowupBase = LeadFollowup::query()
                ->whereBetween('followed_up_at', [$prevFrom, $prevTo])
                ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                    $lq->accessibleTo($viewer);
                    if (!empty($filters['campaign_id'])) {
                        $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                    }
                });

            if (!empty($filters['user_id'])) {
                $prevFollowupBase->where('lead_followups.user_id', (int) $filters['user_id']);
            } elseif (!empty($filters['group_id'])) {
                $groupId = (int) $filters['group_id'];
                $prevFollowupBase->whereHas('user.groups', fn ($gq) => $gq->where('groups.id', $groupId));
            }

            $prevFollowups = (clone $prevFollowupBase)->count();
            $prevNewLeads = (clone $currentLeadsQuery)
                ->whereBetween('leads.created_at', [$prevFrom, $prevTo])
                ->count();

            $kpis['comparison'] = [
                'new_leads_diff' => $this->calculateDelta($newLeads, $prevNewLeads),
                'total_followups_diff' => $this->calculateDelta($totalFollowups, $prevFollowups),
            ];
        }

        return $kpis;
    }

    /**
     * Calculate delta percentage.
     */
    private function calculateDelta(float|int $current, float|int $previous): array
    {
        if ($previous == 0) {
            return [
                'diff' => $current > 0 ? 100.0 : 0.0,
                'direction' => $current > 0 ? 'up' : 'flat',
                'label' => $current > 0 ? '+100%' : '0%',
            ];
        }

        $delta = round((($current - $previous) / $previous) * 100, 1);
        $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
        $label = ($delta > 0 ? '+' : '') . $delta . '%';

        return [
            'diff' => $delta,
            'direction' => $direction,
            'label' => $label,
        ];
    }

    /**
     * Calculate average first response time in hours.
     */
    private function calculateAvgFirstResponseHours(User $viewer, array $filters, CarbonImmutable $from, CarbonImmutable $to): ?float
    {
        $scopedLeadIds = $this->buildScopedLeadQuery($viewer, $filters, $from, $to)
            ->pluck('leads.id')
            ->all();

        if (empty($scopedLeadIds)) {
            return null;
        }

        // Subquery first follow-up per lead
        $result = DB::table('leads')
            ->join('lead_followups', 'leads.id', '=', 'lead_followups.lead_id')
            ->whereIn('leads.id', $scopedLeadIds)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, leads.created_at, lead_followups.followed_up_at)) as avg_hours')
            ->whereRaw('lead_followups.followed_up_at >= leads.created_at')
            ->first();

        return $result && $result->avg_hours !== null ? round((float) $result->avg_hours, 1) : null;
    }

    /**
     * Calculate average stage duration in days.
     */
    private function calculateAvgStageDurationDays(User $viewer, array $filters): ?float
    {
        $scopedLeadIds = $this->buildScopedLeadQuery($viewer, $filters)
            ->limit(500)
            ->pluck('leads.id')
            ->all();

        if (empty($scopedLeadIds)) {
            return null;
        }

        $result = DB::table('leads')
            ->whereIn('id', $scopedLeadIds)
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, COALESCE(updated_at, created_at), NOW())) as avg_days')
            ->first();

        return $result && $result->avg_days !== null ? round((float) $result->avg_days, 1) : null;
    }

    /**
     * Paginate employees and compute consolidated, grouped factual metrics.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @param Collection<int, PipelineStage> $stages
     * @param array{stage_ids: list<int>, status_ids: list<int>} $conversionTargets
     * @return LengthAwarePaginator
     */
    public function getPaginatedEmployeePerformance(
        User $viewer,
        array $filters,
        array $dateRange,
        Collection $stages,
        array $conversionTargets,
        int $perPage = 15
    ): LengthAwarePaginator {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // 1. Fetch authorized employees paginated
        $employeesQuery = $this->getAuthorizedEmployeesQuery($viewer);

        if (!empty($filters['user_id'])) {
            $employeesQuery->where('users.id', (int) $filters['user_id']);
        }

        if (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $employeesQuery->whereHas('groups', fn ($gq) => $gq->where('groups.id', $groupId));
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $employeesQuery->where(static function (Builder $sq) use ($search): void {
                $sq->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.username', 'like', "%{$search}%");
            });
        }

        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = strtolower((string) ($filters['direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        if (in_array($sort, ['name', 'username'], true)) {
            $employeesQuery->orderBy("users.{$sort}", $direction);
        } else {
            $employeesQuery->orderBy('users.name', 'asc');
        }

        $paginatedEmployees = $employeesQuery->paginate($perPage)->withQueryString();
        $employeeIds = $paginatedEmployees->getCollection()->pluck('id')->all();

        if (empty($employeeIds)) {
            return $paginatedEmployees;
        }

        // 2. Grouped Query: Leads by assigned_user_id & lead_status_id (Single indexed aggregate query)
        $leadCountsQuery = Lead::query()
            ->accessibleTo($viewer)
            ->whereIn('assigned_user_id', $employeeIds);

        if (!empty($filters['campaign_id'])) {
            $campaignId = (int) $filters['campaign_id'];
            $leadCountsQuery->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', $campaignId));
        }

        $leadCountsByStatus = (clone $leadCountsQuery)
            ->selectRaw('assigned_user_id, lead_status_id, COUNT(*) as lead_count')
            ->groupBy('assigned_user_id', 'lead_status_id')
            ->get();

        // 3. Grouped Query: New Leads created during period
        $newLeadCounts = (clone $leadCountsQuery)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('assigned_user_id, COUNT(*) as new_lead_count')
            ->groupBy('assigned_user_id')
            ->pluck('new_lead_count', 'assigned_user_id')
            ->all();

        // 4. Grouped Query: Follow-up metrics by user_id (the actor who performed the follow-up)
        $followupCounts = LeadFollowup::query()
            ->whereIn('lead_followups.user_id', $employeeIds)
            ->whereBetween('lead_followups.followed_up_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            })
            ->selectRaw('lead_followups.user_id, COUNT(*) as total_followups, SUM(CASE WHEN followed_up_at IS NOT NULL THEN 1 ELSE 0 END) as completed_followups')
            ->groupBy('lead_followups.user_id')
            ->get()
            ->keyBy('user_id');

        // 5. Grouped Query: Overdue followups & leads without followup
        $terminalStatusIds = LeadStatus::query()->where('is_terminal', true)->pluck('id')->all();
        $operationalStats = (clone $leadCountsQuery)
            ->whereNotIn('lead_status_id', $terminalStatusIds)
            ->selectRaw('
                assigned_user_id,
                SUM(CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < NOW() THEN 1 ELSE 0 END) as overdue_count,
                SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM lead_followups lf WHERE lf.lead_id = leads.id) THEN 1 ELSE 0 END) as without_followup_count
            ')
            ->groupBy('assigned_user_id')
            ->get()
            ->keyBy('assigned_user_id');

        // Map status IDs to stages
        $statusToStageMap = [];
        foreach ($stages as $stage) {
            foreach ($stage->statuses as $status) {
                $statusToStageMap[$status->id] = $stage->id;
            }
        }

        $conversionStatusIds = array_flip($conversionTargets['status_ids']);

        // Build enriched items
        $enrichedItems = $paginatedEmployees->getCollection()->map(function (User $employee) use (
            $leadCountsByStatus,
            $newLeadCounts,
            $followupCounts,
            $operationalStats,
            $statusToStageMap,
            $conversionStatusIds,
            $stages
        ): array {
            $empId = $employee->id;

            // Compute stage counts & total leads from grouped status data
            $stageCounts = [];
            foreach ($stages as $st) {
                $stageCounts[$st->id] = 0;
            }

            $totalLeads = 0;
            $convertedLeads = 0;

            $empStatusRows = $leadCountsByStatus->where('assigned_user_id', $empId);
            foreach ($empStatusRows as $row) {
                $count = (int) $row->lead_count;
                $statusId = (int) $row->lead_status_id;
                $totalLeads += $count;

                if (isset($conversionStatusIds[$statusId])) {
                    $convertedLeads += $count;
                }

                if (isset($statusToStageMap[$statusId])) {
                    $stageId = $statusToStageMap[$statusId];
                    $stageCounts[$stageId] = ($stageCounts[$stageId] ?? 0) + $count;
                }
            }

            $newLeads = (int) ($newLeadCounts[$empId] ?? 0);
            $followupRow = $followupCounts->get($empId);
            $totalFollowups = $followupRow ? (int) $followupRow->total_followups : 0;
            $completedFollowups = $followupRow ? (int) $followupRow->completed_followups : 0;

            $opRow = $operationalStats->get($empId);
            $overdueCount = $opRow ? (int) $opRow->overdue_count : 0;
            $withoutFollowupCount = $opRow ? (int) $opRow->without_followup_count : 0;

            $conversionRate = $totalLeads > 0
                ? round(($convertedLeads / $totalLeads) * 100, 1)
                : 0.0;

            return [
                'id' => $empId,
                'name' => $employee->name,
                'username' => $employee->username,
                'email' => $employee->email,
                'groups' => $employee->groups->pluck('name')->all(),
                'assigned_leads' => $totalLeads,
                'new_leads' => $newLeads,
                'total_followups' => $totalFollowups,
                'completed_followups' => $completedFollowups,
                'overdue_followups' => $overdueCount,
                'without_followup' => $withoutFollowupCount,
                'converted_leads' => $convertedLeads,
                'conversion_rate' => $conversionRate,
                'stage_counts' => $stageCounts,
            ];
        });

        // Client sorting on in-memory collection if metric column sorted
        if (!in_array($sort, ['name', 'username'], true)) {
            $enrichedItems = $enrichedItems->sortBy(
                fn ($item) => $item[$sort] ?? ($item['stage_counts'][(int) str_replace('stage_', '', $sort)] ?? 0),
                SORT_REGULAR,
                $direction === 'desc'
            )->values();
        }

        $paginatedEmployees->setCollection($enrichedItems);

        return $paginatedEmployees;
    }

    /**
     * Get dynamic Pipeline performance analytics.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @param Collection<int, PipelineStage> $stages
     * @return array<int, array<string, mixed>>
     */
    public function getPipelineStagePerformance(
        User $viewer,
        array $filters,
        array $dateRange,
        Collection $stages
    ): array {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // 1. Grouped Current Counts by pipeline_stage_id (1 single query)
        $currentCounts = $this->buildScopedLeadQuery($viewer, $filters)
            ->join('lead_statuses', 'leads.lead_status_id', '=', 'lead_statuses.id')
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(*) as current_count')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('current_count', 'pipeline_stage_id')
            ->all();

        // 2. Grouped Entered Counts during period (1 single query)
        $enteredCounts = LeadStatusHistory::query()
            ->join('lead_statuses', 'lead_status_histories.to_status_id', '=', 'lead_statuses.id')
            ->whereBetween('lead_status_histories.changed_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['user_id'])) {
                    $lq->where('leads.assigned_user_id', (int) $filters['user_id']);
                }
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            })
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(*) as entered_count')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('entered_count', 'pipeline_stage_id')
            ->all();

        // 3. Grouped Exited Counts during period (1 single query)
        $exitedCounts = LeadStatusHistory::query()
            ->join('lead_statuses', 'lead_status_histories.from_status_id', '=', 'lead_statuses.id')
            ->whereBetween('lead_status_histories.changed_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['user_id'])) {
                    $lq->where('leads.assigned_user_id', (int) $filters['user_id']);
                }
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            })
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(*) as exited_count')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('exited_count', 'pipeline_stage_id')
            ->all();

        // 4. Oldest lead per stage (1 single query)
        $oldestLeads = $this->buildScopedLeadQuery($viewer, $filters)
            ->join('lead_statuses', 'leads.lead_status_id', '=', 'lead_statuses.id')
            ->select('leads.id', 'leads.name', 'leads.company_name', 'leads.created_at', 'leads.assigned_user_id', 'lead_statuses.pipeline_stage_id')
            ->orderBy('leads.created_at', 'asc')
            ->get()
            ->unique('pipeline_stage_id')
            ->keyBy('pipeline_stage_id');

        $performance = [];

        foreach ($stages as $stage) {
            $stageId = $stage->id;
            $oldestLead = $oldestLeads->get($stageId);

            $performance[] = [
                'stage_id' => $stageId,
                'stage_name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3478f6',
                'icon' => $stage->icon ?: 'bi-funnel',
                'position' => $stage->position,
                'current_count' => (int) ($currentCounts[$stageId] ?? 0),
                'entered_count' => (int) ($enteredCounts[$stageId] ?? 0),
                'exited_count' => (int) ($exitedCounts[$stageId] ?? 0),
                'oldest_lead' => $oldestLead ? [
                    'id' => $oldestLead->id,
                    'name' => $oldestLead->name,
                    'company' => $oldestLead->company_name,
                    'days_in_stage' => (int) $oldestLead->created_at?->diffInDays(now()),
                ] : null,
            ];
        }

        return $performance;
    }

    /**
     * Get Follow-up Performance breakdown.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @return array<string, mixed>
     */
    public function getFollowupPerformance(User $viewer, array $filters, array $dateRange): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        $base = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            });

        if (!empty($filters['user_id'])) {
            $base->where('lead_followups.user_id', (int) $filters['user_id']);
        } elseif (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $base->whereHas('user.groups', fn ($gq) => $gq->where('groups.id', $groupId));
        }

        // Communication types breakdown
        $commBreakdown = (clone $base)
            ->selectRaw('communication_type, COUNT(*) as count')
            ->groupBy('communication_type')
            ->pluck('count', 'communication_type')
            ->all();

        // Standardized channel mapping
        $channels = [
            'call' => (int) (($commBreakdown['call'] ?? 0) + ($commBreakdown['phone'] ?? 0)),
            'whatsapp' => (int) ($commBreakdown['whatsapp'] ?? 0),
            'meeting' => (int) ($commBreakdown['meeting'] ?? 0),
            'email' => (int) ($commBreakdown['email'] ?? 0),
            'other' => (int) ($commBreakdown['other'] ?? 0),
        ];

        // Upcoming follow-ups today
        $upcomingTodayQuery = Lead::query()
            ->accessibleTo($viewer)
            ->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()]);

        $upcomingTotalQuery = Lead::query()
            ->accessibleTo($viewer)
            ->where('next_follow_up_at', '>', now());

        if (!empty($filters['user_id'])) {
            $upcomingTodayQuery->where('assigned_user_id', (int) $filters['user_id']);
            $upcomingTotalQuery->where('assigned_user_id', (int) $filters['user_id']);
        }

        $upcomingToday = $upcomingTodayQuery->count();
        $upcomingTotal = $upcomingTotalQuery->count();

        return [
            'channels' => $channels,
            'upcoming_today' => $upcomingToday,
            'upcoming_total' => $upcomingTotal,
            'total_followups' => array_sum($channels),
        ];
    }
    /**
     * Get Attention / Stuck Leads and Aging Buckets.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function getAttentionAndAging(User $viewer, array $filters, int $stuckThresholdDays = 7): array
    {
        $terminalStatusIds = LeadStatus::query()->where('is_terminal', true)->pluck('id')->all();

        $scopedLeads = $this->buildScopedLeadQuery($viewer, $filters)
            ->whereNotIn('leads.lead_status_id', $terminalStatusIds);

        // Stuck leads in same stage for >= threshold days
        $thresholdDate = now()->subDays($stuckThresholdDays);
        $stuckCount = (clone $scopedLeads)
            ->where('leads.updated_at', '<=', $thresholdDate)
            ->count();

        // Aging buckets (days since lead updated/created)
        $agingBuckets = [
            '0_1' => 0,
            '2_3' => 0,
            '4_7' => 0,
            '8_14' => 0,
            '15_30' => 0,
            '30_plus' => 0,
        ];

        $agingCounts = (clone $scopedLeads)
            ->selectRaw('
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) <= 1 THEN 1 ELSE 0 END) as b_0_1,
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) BETWEEN 2 AND 3 THEN 1 ELSE 0 END) as b_2_3,
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) BETWEEN 4 AND 7 THEN 1 ELSE 0 END) as b_4_7,
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) BETWEEN 8 AND 14 THEN 1 ELSE 0 END) as b_8_14,
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) BETWEEN 15 AND 30 THEN 1 ELSE 0 END) as b_15_30,
                SUM(CASE WHEN DATEDIFF(NOW(), updated_at) > 30 THEN 1 ELSE 0 END) as b_30_plus
            ')
            ->first();

        if ($agingCounts) {
            $agingBuckets['0_1'] = (int) $agingCounts->b_0_1;
            $agingBuckets['2_3'] = (int) $agingCounts->b_2_3;
            $agingBuckets['4_7'] = (int) $agingCounts->b_4_7;
            $agingBuckets['8_14'] = (int) $agingCounts->b_8_14;
            $agingBuckets['15_30'] = (int) $agingCounts->b_15_30;
            $agingBuckets['30_plus'] = (int) $agingCounts->b_30_plus;
        }

        // Top 5 critical stuck leads needing attention
        $criticalLeads = (clone $scopedLeads)
            ->where('leads.updated_at', '<=', $thresholdDate)
            ->with(['assignedUser:id,name', 'status:id,name_ar,pipeline_stage_id', 'status.stage:id,name_ar,code'])
            ->orderBy('leads.updated_at', 'asc')
            ->limit(6)
            ->get(['id', 'name', 'company_name', 'phone', 'assigned_user_id', 'lead_status_id', 'next_follow_up_at', 'updated_at']);

        return [
            'stuck_count' => $stuckCount,
            'stuck_threshold_days' => $stuckThresholdDays,
            'aging_buckets' => $agingBuckets,
            'critical_leads' => $criticalLeads,
        ];
    }

    /**
     * Get Campaign performance breakdown.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @return array<int, array<string, mixed>>
     */
    public function getCampaignPerformance(User $viewer, array $filters, array $dateRange): array
    {
        $campaigns = $this->getVisibleCampaigns($viewer);
        if ($campaigns->isEmpty()) {
            return [];
        }

        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // Grouped Query: Campaign Leads in 1 query
        $leadQuery = Lead::query()->accessibleTo($viewer);
        if (!empty($filters['user_id'])) {
            $leadQuery->where('leads.assigned_user_id', (int) $filters['user_id']);
        }

        $leadCounts = (clone $leadQuery)
            ->join('campaign_lead', 'leads.id', '=', 'campaign_lead.lead_id')
            ->selectRaw('
                campaign_lead.campaign_id,
                COUNT(*) as total_leads,
                SUM(CASE WHEN leads.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_leads
            ', [$from, $to])
            ->groupBy('campaign_lead.campaign_id')
            ->get()
            ->keyBy('campaign_id');

        // Grouped Query: Followups by Campaign in 1 query
        $followupCounts = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$from, $to])
            ->join('campaign_lead', 'lead_followups.lead_id', '=', 'campaign_lead.lead_id')
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['user_id'])) {
                    $lq->where('leads.assigned_user_id', (int) $filters['user_id']);
                }
            })
            ->selectRaw('campaign_lead.campaign_id, COUNT(*) as followups_count')
            ->groupBy('campaign_lead.campaign_id')
            ->get()
            ->keyBy('campaign_id');

        $campaignData = [];

        foreach ($campaigns as $campaign) {
            $row = $leadCounts->get($campaign->id);
            $totalLeads = $row ? (int) $row->total_leads : 0;
            if ($totalLeads === 0) {
                continue;
            }

            $newLeads = $row ? (int) $row->new_leads : 0;
            $fRow = $followupCounts->get($campaign->id);
            $followups = $fRow ? (int) $fRow->followups_count : 0;

            $campaignData[] = [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'total_leads' => $totalLeads,
                'new_leads' => $newLeads,
                'followups_count' => $followups,
            ];
        }

        return $campaignData;
    }

    /**
     * Get periodic Performance Trend data (Line Chart).
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @return array{labels: list<string>, new_leads: list<int>, followups: list<int>}
     */
    public function getPerformanceTrend(User $viewer, array $filters, array $dateRange, ?Collection $stages = null): array
    {
        $from = $dateRange['from'];
        $to = $dateRange['to'];
        $diffDays = $from->diffInDays($to);

        if ($stages === null) {
            $stages = $this->getActivePipelineStages($viewer);
        }

        $leadQuery = $this->buildScopedLeadQuery($viewer, $filters, $from, $to);
        $leadsByDate = (clone $leadQuery)
            ->selectRaw("DATE_FORMAT(leads.created_at, '%Y-%m-%d') as d_key, COUNT(*) as count")
            ->groupBy('d_key')
            ->pluck('count', 'd_key')
            ->all();

        $followupBase = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            });

        if (!empty($filters['user_id'])) {
            $followupBase->where('lead_followups.user_id', (int) $filters['user_id']);
        } elseif (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $followupBase->whereHas('user.groups', fn ($gq) => $gq->where('groups.id', $groupId));
        }

        $followupsByDate = (clone $followupBase)
            ->selectRaw("DATE_FORMAT(lead_followups.followed_up_at, '%Y-%m-%d') as d_key, COUNT(*) as count")
            ->groupBy('d_key')
            ->pluck('count', 'd_key')
            ->all();

        // Grouped status history by date and stage
        $historyCounts = LeadStatusHistory::query()
            ->join('lead_statuses', 'lead_status_histories.to_status_id', '=', 'lead_statuses.id')
            ->whereBetween('lead_status_histories.changed_at', [$from, $to])
            ->whereHas('lead', function (Builder $lq) use ($viewer, $filters): void {
                $lq->accessibleTo($viewer);
                if (!empty($filters['user_id'])) {
                    $lq->where('leads.assigned_user_id', (int) $filters['user_id']);
                }
                if (!empty($filters['campaign_id'])) {
                    $lq->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
                }
            })
            ->selectRaw("DATE_FORMAT(lead_status_histories.changed_at, '%Y-%m-%d') as d_key, lead_statuses.pipeline_stage_id, COUNT(*) as count")
            ->groupBy('d_key', 'lead_statuses.pipeline_stage_id')
            ->get();

        $indexedHistory = [];
        foreach ($historyCounts as $hRow) {
            $indexedHistory[$hRow->pipeline_stage_id][$hRow->d_key] = (int) $hRow->count;
        }

        $initialStage = $stages->first();
        $labels = [];
        $dateKeys = [];
        $newLeadsData = [];
        $followupsData = [];

        $current = $from->copy();
        $stepDays = $diffDays <= 31 ? 1 : (int) ceil($diffDays / 15);

        while ($current->lte($to)) {
            $dKey = $current->format('Y-m-d');
            $dateKeys[] = $dKey;
            $labels[] = $current->format('d M');
            $newLeadsData[] = (int) ($leadsByDate[$dKey] ?? 0);
            $followupsData[] = (int) ($followupsByDate[$dKey] ?? 0);

            $current = $current->addDays(max(1, $stepDays));
        }

        // Build stage series
        $stageSeries = [];
        foreach ($stages as $stage) {
            $sData = [];
            $isInitial = ($initialStage && $stage->id === $initialStage->id);
            $totalCount = 0;

            foreach ($dateKeys as $dKey) {
                $cnt = (int) ($indexedHistory[$stage->id][$dKey] ?? 0);
                if ($isInitial) {
                    $cnt += (int) ($leadsByDate[$dKey] ?? 0);
                }
                $sData[] = $cnt;
                $totalCount += $cnt;
            }

            $stageSeries[] = [
                'id' => $stage->id,
                'name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3b82f6',
                'data' => $sData,
                'total' => $totalCount,
            ];
        }

        return [
            'labels' => $labels,
            'new_leads' => $newLeadsData,
            'followups' => $followupsData,
            'series' => $stageSeries,
        ];
    }

    /**
     * Get Upcoming Follow-ups list with customer and status details.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Lead>
     */
    public function getUpcomingFollowups(User $viewer, array $filters, int $limit = 6): Collection
    {
        $query = Lead::query()
            ->accessibleTo($viewer)
            ->whereNotNull('leads.next_follow_up_at');

        if (!empty($filters['user_id'])) {
            $query->where('leads.assigned_user_id', (int) $filters['user_id']);
        } elseif (!empty($filters['group_id'])) {
            $groupId = (int) $filters['group_id'];
            $query->whereHas('assignedUser.groups', fn ($gq) => $gq->where('groups.id', $groupId));
        }

        if (!empty($filters['campaign_id'])) {
            $query->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
        }

        return $query
            ->with(['assignedUser:id,name', 'status:id,name_ar,pipeline_stage_id', 'status.stage:id,name_ar,color'])
            ->orderBy('leads.next_follow_up_at', 'asc')
            ->limit($limit)
            ->get(['id', 'name', 'company_name', 'phone', 'assigned_user_id', 'lead_status_id', 'next_follow_up_at']);
    }

    /**
     * Get detailed follow-ups list by category type (overdue, today, upcoming) for popups.
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Lead>
     */
    public function getFollowupsListByType(User $viewer, array $filters, string $type = 'overdue', int $limit = 60): Collection
    {
        $terminalStatusIds = LeadStatus::query()->where('is_terminal', true)->pluck('id')->all();

        $query = Lead::query()
            ->accessibleTo($viewer)
            ->whereNotIn('leads.lead_status_id', $terminalStatusIds)
            ->whereNotNull('leads.next_follow_up_at');

        if (!empty($filters['user_id'])) {
            $query->where('leads.assigned_user_id', (int) $filters['user_id']);
        }
        if (!empty($filters['campaign_id'])) {
            $query->whereHas('campaigns', fn ($cq) => $cq->where('campaigns.id', (int) $filters['campaign_id']));
        }
        if (!empty($filters['stage_id'])) {
            $query->whereHas('status', fn ($sq) => $sq->where('pipeline_stage_id', (int) $filters['stage_id']));
        }

        if ($type === 'overdue') {
            $query->where('leads.next_follow_up_at', '<', now())
                ->orderBy('leads.next_follow_up_at', 'asc');
        } elseif ($type === 'today') {
            $query->whereBetween('leads.next_follow_up_at', [now()->startOfDay(), now()->endOfDay()])
                ->orderBy('leads.next_follow_up_at', 'asc');
        } else {
            // upcoming
            $query->where('leads.next_follow_up_at', '>', now())
                ->orderBy('leads.next_follow_up_at', 'asc');
        }

        return $query
            ->with(['assignedUser:id,name', 'status:id,name_ar,pipeline_stage_id', 'status.stage:id,name_ar,color'])
            ->limit($limit)
            ->get(['id', 'name', 'company_name', 'phone', 'assigned_user_id', 'lead_status_id', 'next_follow_up_at']);
    }

    /**
     * Get customer distribution by campaign/source/stage (Donut Chart).
     *
     * @param array<string, mixed> $filters
     * @return array{type: string, labels: list<string>, data: list<int>, colors: list<string>, total: int}
     */
    public function getCustomerDistribution(User $viewer, array $filters): array
    {
        $baseQuery = $this->buildScopedLeadQuery($viewer, $filters);
        $total = (clone $baseQuery)->count();

        $campaignLeads = (clone $baseQuery)
            ->join('campaign_lead', 'leads.id', '=', 'campaign_lead.lead_id')
            ->join('campaigns', 'campaign_lead.campaign_id', '=', 'campaigns.id')
            ->selectRaw('campaigns.name, COUNT(*) as count')
            ->groupBy('campaigns.id', 'campaigns.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        if ($campaignLeads->isNotEmpty()) {
            $labels = [];
            $data = [];
            $palette = ['#dc2637', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'];
            $sum = 0;
            foreach ($campaignLeads as $row) {
                $labels[] = $row->name;
                $data[] = (int) $row->count;
                $sum += (int) $row->count;
            }
            if ($total > $sum) {
                $labels[] = __('crm.other') ?? 'أخرى';
                $data[] = $total - $sum;
            }
            return [
                'type' => 'campaign',
                'labels' => $labels,
                'data' => $data,
                'colors' => array_slice($palette, 0, count($labels)),
                'total' => $total,
            ];
        }

        $stageLeads = (clone $baseQuery)
            ->join('lead_statuses', 'leads.lead_status_id', '=', 'lead_statuses.id')
            ->join('pipeline_stages', 'lead_statuses.pipeline_stage_id', '=', 'pipeline_stages.id')
            ->selectRaw('pipeline_stages.name_ar, pipeline_stages.color, COUNT(*) as count')
            ->groupBy('pipeline_stages.id', 'pipeline_stages.name_ar', 'pipeline_stages.color')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $labels = [];
        $data = [];
        $colors = [];
        foreach ($stageLeads as $row) {
            $labels[] = $row->name_ar;
            $data[] = (int) $row->count;
            $colors[] = $row->color ?: '#3b82f6';
        }

        return [
            'type' => 'stage',
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
            'total' => $total,
        ];
    }

    /**
     * Get Detailed Drilldown Data for a Specific Employee.
     *
     * @param array<string, mixed> $filters
     * @param array{from: CarbonImmutable, to: CarbonImmutable} $dateRange
     * @param Collection<int, PipelineStage> $stages
     * @param array{stage_ids: list<int>, status_ids: list<int>} $conversionTargets
     * @return array<string, mixed>
     */
    public function getEmployeeDrilldown(
        User $employee,
        User $viewer,
        array $filters,
        array $dateRange,
        Collection $stages,
        array $conversionTargets
    ): array {
        $from = $dateRange['from'];
        $to = $dateRange['to'];

        // Employee Leads
        $leadsQuery = Lead::query()
            ->accessibleTo($viewer)
            ->where('assigned_user_id', $employee->id)
            ->with(['status:id,name_ar,pipeline_stage_id', 'status.stage:id,name_ar,color'])
            ->orderByDesc('updated_at');

        $totalLeads = (clone $leadsQuery)->count();
        $recentLeads = (clone $leadsQuery)->limit(15)->get();

        // Employee Follow-ups (Attributed to this employee)
        $followupsQuery = LeadFollowup::query()
            ->where('user_id', $employee->id)
            ->whereHas('lead', fn ($lq) => $lq->accessibleTo($viewer))
            ->with(['lead:id,name,company_name,phone', 'toStatus:id,name_ar'])
            ->orderByDesc('followed_up_at');

        $totalFollowups = (clone $followupsQuery)->count();
        $recentFollowups = (clone $followupsQuery)->limit(15)->get();

        // Stage Distribution for this Employee
        $stageDistribution = [];
        foreach ($stages as $stage) {
            $statusIds = $stage->statuses->pluck('id')->all();
            $count = (clone $leadsQuery)->whereIn('lead_status_id', $statusIds)->count();
            $stageDistribution[] = [
                'stage_id' => $stage->id,
                'stage_name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3478f6',
                'count' => $count,
            ];
        }

        // Overdue Follow-ups for this employee
        $terminalStatusIds = LeadStatus::query()->where('is_terminal', true)->pluck('id')->all();
        $overdueCount = (clone $leadsQuery)
            ->whereNotIn('lead_status_id', $terminalStatusIds)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->count();

        // Conversion
        $conversionStatusIds = $conversionTargets['status_ids'];
        $convertedCount = !empty($conversionStatusIds)
            ? (clone $leadsQuery)->whereIn('lead_status_id', $conversionStatusIds)->count()
            : 0;

        $conversionRate = $totalLeads > 0
            ? round(($convertedCount / $totalLeads) * 100, 1)
            : 0.0;

        // Recent Activity (Status transitions conducted by this employee)
        $recentActivity = LeadStatusHistory::query()
            ->where('changed_by_user_id', $employee->id)
            ->whereHas('lead', fn ($lq) => $lq->accessibleTo($viewer))
            ->with(['lead:id,name,company_name', 'fromStatus:id,name_ar', 'toStatus:id,name_ar'])
            ->orderByDesc('changed_at')
            ->limit(15)
            ->get();

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'username' => $employee->username,
                'email' => $employee->email,
                'groups' => $employee->groups->pluck('name')->all(),
            ],
            'metrics' => [
                'total_leads' => $totalLeads,
                'total_followups' => $totalFollowups,
                'overdue_count' => $overdueCount,
                'converted_count' => $convertedCount,
                'conversion_rate' => $conversionRate,
            ],
            'recent_leads' => $recentLeads,
            'recent_followups' => $recentFollowups,
            'stage_distribution' => $stageDistribution,
            'recent_activity' => $recentActivity,
        ];
    }
}
