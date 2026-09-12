<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\CalendarEvent;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\VoipService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class DashboardController extends Controller
{
    public const KANBAN_COLUMN_PAGE_SIZE = 10;
    public const KANBAN_ALLOWED_PAGE_SIZES = [10, 20, 30, 40, 50];

    private const STATUS_UI = [
        'new' => [
            'slug' => 'new',
            'icon' => '＋',
            'class' => '',
            'kanban_class' => 'new',
        ],
        'no_answer' => [
            'slug' => 'no-answer',
            'icon' => '☎',
            'class' => 'orange',
            'kanban_class' => 'no-answer',
        ],
        'interested' => [
            'slug' => 'interested',
            'icon' => '♥',
            'class' => 'green',
            'kanban_class' => 'interested',
        ],
        'not_interested' => [
            'slug' => 'not-interested',
            'icon' => '×',
            'class' => 'red',
            'kanban_class' => 'not-interested',
        ],
        'meeting' => [
            'slug' => 'meeting',
            'icon' => '□',
            'class' => 'purple',
            'kanban_class' => 'meeting',
        ],
        'quotation' => [
            'slug' => 'quotation',
            'icon' => '▤',
            'class' => 'orange',
            'kanban_class' => 'quotation',
        ],
        'discussion' => [
            'slug' => 'discussion',
            'icon' => '◇',
            'class' => '',
            'kanban_class' => 'discussion',
        ],
        'contract_closed' => [
            'slug' => 'contract-closing',
            'icon' => '✓',
            'class' => 'green',
            'kanban_class' => 'contract',
        ],
        'execution' => [
            'slug' => 'execution',
            'icon' => '⚙',
            'class' => 'purple',
            'kanban_class' => 'execution',
        ],
    ];

    private const COMMUNICATION_LABELS = [
        'call' => 'اتصال',
        'whatsapp' => 'واتساب',
        'email' => 'بريد إلكتروني',
        'meeting' => 'مقابلة',
        'other' => 'أخرى',
    ];

    public function index(Request $request)
    {

        $this->assertCrmDatabase();
        $user = $request->user();

        $filters = $this->resolveFilters(
            $request
        );

        $statuses = LeadStatus::query()
            ->visibleTo($user)
            ->with('stage')
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get();
        $stages = PipelineStage::query()
            ->visibleTo($user)
            ->with('statuses')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $leadBase = Lead::query()
            ->accessibleTo($user);

        $this->applyLeadFilters(
            $leadBase,
            $filters
        );

        $totalLeads = (clone $leadBase)
            ->count();

        $rawStatusCounts = (clone $leadBase)
            ->whereNotNull('leads.lead_status_id')
            ->selectRaw('leads.lead_status_id, count(*) as total')
            ->groupBy('leads.lead_status_id')
            ->pluck('total', 'leads.lead_status_id')
            ->all();

        $statusCounts = [];
        foreach ($statuses as $status) {
            $statusCounts[$status->code] = (int) ($rawStatusCounts[$status->id] ?? 0);
        }

        $statusCards = [];

        foreach ($statuses as $status) {
            $ui = self::STATUS_UI[
                $status->code
            ] ?? [
                'slug' => $status->code,
                'icon' => '•',
                'class' => '',
                'kanban_class' => '',
            ];

            $statusCards[] = [
                'id' => $status->id,
                'code' => $status->code,
                'name' => $status->name_ar,
                'count' => (int) (
                    $statusCounts[
                        $status->code
                    ] ?? 0
                ),
                'color' => (
                    $status->color
                    ?: '#3478f6'
                ),
                'stage_color' => (
                    $status->stage?->color
                    ?: $status->color
                    ?: '#3478f6'
                ),
                'slug' => $ui['slug'],
                'icon' => $ui['icon'],
                'class' => $ui['class'],
                'filter_url' => route('v2.leads', array_filter(['status' => $status->code, 'employee' => $filters['employee']])),
            ];
        }

        $stageCards = [];

        foreach ($stages as $stage) {
            $stageStatuses = [];

            $stageTotal = 0;

            foreach ($stage->statuses as $status) {
                $count = (int) (
                    $statusCounts[
                        $status->code
                    ] ?? 0
                );

                $stageTotal += $count;

                $stageStatuses[] = [
                    'name' => $status->name_ar,
                    'count' => $count,
                ];
            }

            $stageCards[] = [
                'code' => $stage->code,
                'name' => $stage->name_ar,
                'description' => (
                    $stage->description_ar
                    ?: ''
                ),
                'position' => $stage->position,
                'color' => (
                    $stage->color
                    ?: '#3478f6'
                ),
                'total' => $stageTotal,
                'statuses' => $stageStatuses,
                'filter_url' => route('v2.leads', array_filter(['stage' => $stage->id, 'employee' => $filters['employee']])),
                'class' => match (
                    $stage->code
                ) {
                    'interest' => 'interest',
                    'negotiation' => 'negotiation',
                    'closing_execution' => 'closing',
                    default => 'start',
                },
            ];
        }

        $activePipelineStages = [];
        foreach ($stages as $stage) {
            $stageCount = 0;
            foreach ($stage->statuses as $status) {
                $stageCount += (int) ($statusCounts[$status->code] ?? 0);
            }
            $icon = $stage->icon ? (str_starts_with($stage->icon, 'bi-') || str_starts_with($stage->icon, 'bi ') ? $stage->icon : 'bi-' . $stage->icon) : 'bi-diagram-3';
            if (!str_starts_with($icon, 'bi ') && !str_starts_with($icon, 'bi-')) {
                $icon = 'bi bi-' . $icon;
            } elseif (str_starts_with($icon, 'bi-')) {
                $icon = 'bi ' . $icon;
            }

            $activePipelineStages[] = [
                'id' => $stage->id,
                'code' => $stage->code,
                'name' => $stage->localizedName(),
                'color' => $stage->color ?: '#3478f6',
                'icon' => $icon,
                'count' => $stageCount,
                'filter_url' => route('v2.leads', array_filter(['stage' => $stage->id, 'employee' => $filters['employee']])),
            ];
        }
        $activePipelineStages = collect($activePipelineStages);

        $firstActiveStage = $stages->first();
        $finalActiveStage = $stages->last();

        $finalConversionRate = null;
        $finalStageReachedCount = 0;
        $finalStageTitle = __('crm.conversion_rate');
        $finalStageColor = '#10b981';
        $finalStageIcon = 'bi-check-circle';

        if ($stages->isEmpty()) {
            $finalStageTitle = __('crm.conversion_rate');
        } elseif ($stages->count() === 1) {
            $finalStageName = $finalActiveStage->localizedName();
            $finalStageTitle = __('crm.stage_conversion_rate_title', ['stage' => $finalStageName]);
            $finalStageColor = $finalActiveStage->color ?: '#10b981';
            $finalStageIcon = $finalActiveStage->icon ?: 'bi-check-circle';
            $finalConversionRate = null;
            $finalStatusIds = $finalActiveStage->statuses->pluck('id')->all();
            $finalStageReachedCount = ! empty($finalStatusIds)
                ? (clone $leadBase)->whereIn('lead_status_id', $finalStatusIds)->distinct()->count('leads.id')
                : 0;
        } else {
            $finalStageName = $finalActiveStage->localizedName();
            $finalStageTitle = __('crm.stage_conversion_rate_title', ['stage' => $finalStageName]);
            $finalStageColor = $finalActiveStage->color ?: '#10b981';
            $finalStageIcon = $finalActiveStage->icon ?: 'bi-check-circle';

            $finalConversionMetrics = $this->calculateStageConversion($firstActiveStage, $finalActiveStage, $leadBase, $stages);
            $finalConversionRate = $finalConversionMetrics['rate'];
            $finalStageReachedCount = (int) ($finalConversionMetrics['numerator'] ?? 0);
        }

        $contractRate = $finalConversionRate ?? 0.0;
        $todayStart = now()
            ->startOfDay();

        $todayEnd = now()
            ->endOfDay();

        $datedLeadBase = clone $leadBase;

        $followupStats = (clone $datedLeadBase)
            ->selectRaw('
                count(case when next_follow_up_at between ? and ? then 1 end) as today_count,
                count(case when next_follow_up_at < ? then 1 end) as overdue_count,
                count(case when next_follow_up_at > ? then 1 end) as upcoming_count,
                count(case when next_follow_up_at is null then 1 end) as no_date_count
            ', [$todayStart, $todayEnd, $todayStart, $todayEnd])
            ->first();

        $followupCounts = [
            'today' => (int) ($followupStats->today_count ?? 0),
            'overdue' => (int) ($followupStats->overdue_count ?? 0),
            'upcoming' => (int) ($followupStats->upcoming_count ?? 0),
            'no_date' => (int) ($followupStats->no_date_count ?? 0),
        ];

        $meetingStatus = $statuses
            ->firstWhere(
                'code',
                'meeting'
            );

        $meetingBase = clone $leadBase;

        if ($meetingStatus) {
            $meetingBase->where(
                'lead_status_id',
                $meetingStatus->id
            );
        } else {
            $meetingBase->whereRaw(
                '1 = 0'
            );
        }

        $meetingStats = (clone $meetingBase)
            ->selectRaw('
                count(case when next_follow_up_at between ? and ? then 1 end) as today_count,
                count(case when next_follow_up_at < ? then 1 end) as overdue_count,
                count(case when next_follow_up_at > ? then 1 end) as upcoming_count
            ', [$todayStart, $todayEnd, $todayStart, $todayEnd])
            ->first();

        $meetingCounts = [
            'today' => (int) ($meetingStats->today_count ?? 0),
            'overdue' => (int) ($meetingStats->overdue_count ?? 0),
            'upcoming' => (int) ($meetingStats->upcoming_count ?? 0),
        ];
        // Dynamic Pipeline Stage Conversion Widget (FROM -> TO)
        $fromStageId = $request->query('from_stage_id');
        $toStageId = $request->query('to_stage_id') ?? $request->query('conversion_stage_id');

        $defaultFromStage = $stages->first();
        $defaultToStage = $stages->skip(1)->first() ?? $stages->first();

        if ($fromStageId === null && $toStageId !== null) {
            $toStageObj = $stages->firstWhere('id', (int) $toStageId);
            if ($toStageObj !== null) {
                $toIdx = $stages->search(static fn (PipelineStage $s) => $s->id === $toStageObj->id);
                if ($toIdx !== false && $toIdx > 0) {
                    $defaultFromStage = $stages->get($toIdx - 1);
                } elseif ($toIdx === 0) {
                    $defaultFromStage = null;
                }
            }
        }

        $fromStage = $fromStageId !== null
            ? ($stages->firstWhere('id', (int) $fromStageId) ?? $defaultFromStage)
            : $defaultFromStage;

        $toStage = $toStageId !== null
            ? ($stages->firstWhere('id', (int) $toStageId) ?? $defaultToStage)
            : $defaultToStage;
        $conversionMetrics = $this->calculateStageConversion($fromStage, $toStage, $leadBase, $stages);

        $kpi1StageId = $request->query('stage_kpi_1');
        $defaultKpi1Stage = $stages->first(static fn (PipelineStage $s) => in_array($s->code, ['contract_closed', 'closing_execution', 'execution', 'donor'], true))
            ?? $stages->last()
            ?? $stages->first();
        $kpi1Stage = $kpi1StageId !== null
            ? ($stages->firstWhere('id', (int) $kpi1StageId) ?? $defaultKpi1Stage)
            : $defaultKpi1Stage;
        $stageKpi1 = $this->calculateStageKpi($kpi1Stage, $leadBase, $totalLeads, $filters, $statusCounts);

        $kpi2StageId = $request->query('stage_kpi_2');
        $defaultKpi2Stage = $stages->first(static fn (PipelineStage $s) => in_array($s->code, ['meeting', 'discussion', 'quotation', 'negotiation'], true))
            ?? ($stages->count() > 2 ? $stages->get(2) : ($stages->skip(1)->first() ?? $stages->first()));
        $kpi2Stage = $kpi2StageId !== null
            ? ($stages->firstWhere('id', (int) $kpi2StageId) ?? $defaultKpi2Stage)
            : $defaultKpi2Stage;
        $stageKpi2 = $this->calculateStageKpi($kpi2Stage, $leadBase, $totalLeads, $filters, $statusCounts);

        $activityStageId = $request->query('activity_stage_id');
        $defaultActivityStage = $stages->first(static fn (PipelineStage $s) => $s->code === 'meeting')
            ?? ($stages->skip(1)->first() ?? $stages->first());
        $activityStage = $activityStageId !== null
            ? ($stages->firstWhere('id', (int) $activityStageId) ?? $defaultActivityStage)
            : $defaultActivityStage;
        $stageActivity = $this->calculateStageActivity($activityStage, $leadBase, $todayStart, $todayEnd);

        if ($request->ajax() || $request->wantsJson() || $request->query('ajax')) {
            $widget = $request->query('widget');
            if ($widget === 'conversion') {
                return response()->json([
                    'success' => true,
                    'widget' => 'conversion',
                    'from_stage_id' => $conversionMetrics['from_stage_id'],
                    'from_stage_name' => $conversionMetrics['from_stage_name'],
                    'to_stage_id' => $conversionMetrics['to_stage_id'],
                    'to_stage_name' => $conversionMetrics['to_stage_name'],
                    'stage_id' => $conversionMetrics['stage_id'],
                    'stage_name' => $conversionMetrics['stage_name'],
                    'has_previous_stage' => $conversionMetrics['has_previous_stage'],
                    'previous_stage_name' => $conversionMetrics['previous_stage_name'],
                    'rate' => $conversionMetrics['rate'],
                    'display_value' => $conversionMetrics['display_value'],
                    'subtitle' => $conversionMetrics['subtitle'],
                    'color' => $conversionMetrics['color'],
                    'icon' => $conversionMetrics['icon'],
                    'numerator' => $conversionMetrics['numerator'],
                    'denominator' => $conversionMetrics['denominator'],
                    'is_valid_direction' => $conversionMetrics['is_valid_direction'],
                ]);
            }
            if ($widget === 'stage_kpi_1') {
                return response()->json([
                    'success' => true,
                    'widget' => 'stage_kpi_1',
                    'stage_id' => $stageKpi1['stage_id'],
                    'stage_name' => $stageKpi1['stage_name'],
                    'count' => $stageKpi1['count'],
                    'formatted_count' => number_format($stageKpi1['count']),
                    'color' => $stageKpi1['color'],
                    'icon' => $stageKpi1['icon'],
                    'percentage' => $stageKpi1['percentage'],
                    'subtitle' => $stageKpi1['subtitle'],
                    'filter_url' => $stageKpi1['filter_url'],
                ]);
            }
            if ($widget === 'stage_kpi_2') {
                return response()->json([
                    'success' => true,
                    'widget' => 'stage_kpi_2',
                    'stage_id' => $stageKpi2['stage_id'],
                    'stage_name' => $stageKpi2['stage_name'],
                    'count' => $stageKpi2['count'],
                    'formatted_count' => number_format($stageKpi2['count']),
                    'color' => $stageKpi2['color'],
                    'icon' => $stageKpi2['icon'],
                    'percentage' => $stageKpi2['percentage'],
                    'subtitle' => $stageKpi2['subtitle'],
                    'filter_url' => $stageKpi2['filter_url'],
                ]);
            }
            if ($widget === 'stage_activity') {
                return response()->json([
                    'success' => true,
                    'widget' => 'stage_activity',
                    'stage_id' => $stageActivity['stage_id'],
                    'stage_name' => $stageActivity['stage_name'],
                    'today_count' => $stageActivity['today_count'],
                    'overdue_count' => $stageActivity['overdue_count'],
                    'upcoming_count' => $stageActivity['upcoming_count'],
                    'total_count' => $stageActivity['total_count'],
                    'leads' => $stageActivity['leads'],
                    'empty' => empty($stageActivity['leads']),
                ]);
            }
            if ($widget === 'stage_activity_leads') {
                $bucket = (string) $request->query('bucket', 'all');
                if (! in_array($bucket, ['all', 'today', 'overdue', 'upcoming'], true)) {
                    $bucket = 'all';
                }
                $page = max(1, (int) $request->query('page', 1));
                $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

                $activityLeadsData = $this->calculateStageActivityLeads(
                    $activityStage,
                    $leadBase,
                    $todayStart,
                    $todayEnd,
                    $bucket,
                    $page,
                    $perPage
                );

                return response()->json(array_merge([
                    'success' => true,
                    'widget' => 'stage_activity_leads',
                ], $activityLeadsData));
            }
            if ($widget === 'voip_status') {
                if (! $user->hasPermission('voip.view')) {
                    abort(403);
                }
                $status = ['status' => 'disconnected'];
                try {
                    $voip = app(VoipService::class);
                    if ($voip->isConfigured()) {
                        $status = $voip->health();
                    }
                } catch (\Throwable $e) {
                    $status = ['status' => 'error'];
                }
                return response()->json([
                    'success' => true,
                    'widget' => 'voip_status',
                    'voipStatus' => $status,
                ]);
            }
        }

        $latestFollowups =
            LeadFollowup::query()
                ->with([
                    'lead.status',
                    'toStatus',
                    'user:id,name',
                ])
                ->whereHas(
                    'lead',
                    function (
                        Builder $query
                    ) use ($filters, $user): void {
                        $query->accessibleTo($user);
                        $this->applyLeadFilters(
                            $query,
                            $filters
                        );
                    }
                )
                ->orderByDesc(
                    'followed_up_at'
                )
                ->orderByDesc('id')
                ->limit(8)
                ->get();

        $visibleAssignedUserIds = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');

        $employees = User::query()
            ->whereIn('id', $visibleAssignedUserIds)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Lead::query()
                    ->accessibleTo($user)
                    ->whereNull('assigned_user_id')
                    ->whereNotNull('assigned_employee')
                    ->where('assigned_employee', '<>', '')
                    ->distinct()
                    ->pluck('assigned_employee')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $distribution = [];

        foreach ($statusCards as $card) {
            $percentage = $totalLeads > 0
                ? round(
                    (
                        $card['count']
                        / $totalLeads
                    ) * 100,
                    1
                )
                : 0.0;

            $distribution[] =
                $card + [
                    'percentage' => $percentage,
                ];
        }

        $voipStatus = null;
        $miniCalendarEvents = [];
        if ($user->hasPermission(CrmPermission::CALENDAR_VIEW) || $user->hasPermission('calendar.view')) {
            $calendarEventsQuery = CalendarEvent::query()
                ->with(['user:id,name', 'lead:id,name,company_name,phone'])
                ->accessibleTo($user)
                ->orderBy('start_time', 'asc');

            if (! empty($filters['employee'])) {
                $calendarEventsQuery->where(function (Builder $q) use ($filters): void {
                    $q->whereHas('user', function (Builder $uq) use ($filters): void {
                        $uq->where('name', $filters['employee']);
                    })->orWhereHas('lead', function (Builder $lq) use ($filters): void {
                        $lq->whereHas('assignedUser', function (Builder $auq) use ($filters): void {
                            $auq->where('name', $filters['employee']);
                        })->orWhere('assigned_employee', $filters['employee']);
                    });
                });
            }

            $miniCalendarEvents = $calendarEventsQuery->get()
                ->map(static function (CalendarEvent $e): array {
                    $leadUrl = $e->lead_id ? route('v2.leads.show', $e->lead_id) : null;
                    $calendarUrl = route('v2.calendar.index');

                    return [
                        'id' => $e->id,
                        'title' => $e->title,
                        'description' => $e->description,
                        'date' => $e->start_time->format('Y-m-d'),
                        'time' => $e->start_time->format('h:i A'),
                        'start_time' => $e->start_time->toIso8601String(),
                        'end_time' => $e->end_time->toIso8601String(),
                        'type' => $e->type,
                        'status' => $e->status,
                        'user_id' => $e->user_id,
                        'user_name' => $e->user?->name,
                        'lead_id' => $e->lead_id,
                        'lead_name' => $e->lead?->name,
                        'lead_company' => $e->lead?->company_name,
                        'lead_phone' => $e->lead?->phone,
                        'lead_url' => $leadUrl,
                        'calendar_url' => $calendarUrl,
                        'action_url' => $leadUrl ?: $calendarUrl,
                    ];
                })
                ->values()
                ->all();
        }
        $chartStageParam = $request->query('chart_stages');
        $selectedChartStageIds = [];
        if ($chartStageParam !== null) {
            if (is_array($chartStageParam)) {
                $selectedChartStageIds = array_values(array_filter(array_map('intval', $chartStageParam)));
            } else {
                $selectedChartStageIds = array_values(array_filter(array_map('intval', explode(',', (string) $chartStageParam))));
            }
        }

        if (empty($selectedChartStageIds)) {
            $selectedChartStageIds = $stages->pluck('id')->all();
        }

        $selectedChartStages = $stages->whereIn('id', $selectedChartStageIds)->values();
        if ($selectedChartStages->isEmpty()) {
            $selectedChartStages = $stages->take(3)->values();
            $selectedChartStageIds = $selectedChartStages->pluck('id')->all();
        }

        $perfLabels = [];
        $perfTotal = [];
        $perfNew = [];
        $perfFollowups = [];
        $perfContracts = [];
        $stageMonthlyData = [];
        foreach ($selectedChartStages as $stg) {
            $stageMonthlyData[$stg->id] = [];
        }

        $timelineLeadBase = Lead::query()->accessibleTo($user);
        if ($filters['employee'] !== '') {
            $this->applyLeadFilters($timelineLeadBase, ['employee' => $filters['employee'], 'from_date' => null, 'to_date' => null]);
        }

        $nowDate = now();
        $startPeriod = (clone $nowDate)->subMonths(5)->startOfMonth();
        $endPeriod = (clone $nowDate)->endOfMonth();

        $monthKeys = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = (clone $nowDate)->subMonths($i);
            $ym = $month->format('Y-m');
            $monthKeys[$ym] = $month->translatedFormat('M Y');
        }
        $perfLabels = array_values($monthKeys);

        $monthlyNewLeads = (clone $timelineLeadBase)
            ->whereBetween('leads.created_at', [$startPeriod, $endPeriod])
            ->selectRaw("DATE_FORMAT(leads.created_at, '%Y-%m') as ym, count(*) as c")
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $perfNew = [];
        $perfTotal = [];
        foreach (array_keys($monthKeys) as $ym) {
            $count = (int) ($monthlyNewLeads[$ym] ?? 0);
            $perfNew[] = $count;
            $perfTotal[] = $count;
        }

        $monthlyFollowups = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$startPeriod, $endPeriod])
            ->whereHas('lead', static function (Builder $query) use ($user): void {
                $query->accessibleTo($user);
            })
            ->selectRaw("DATE_FORMAT(followed_up_at, '%Y-%m') as ym, count(*) as c")
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $perfFollowups = [];
        foreach (array_keys($monthKeys) as $ym) {
            $perfFollowups[] = (int) ($monthlyFollowups[$ym] ?? 0);
        }

        $monthlyContracts = (clone $timelineLeadBase)
            ->whereBetween('leads.updated_at', [$startPeriod, $endPeriod])
            ->whereHas('status', static fn ($q) => $q->where('code', 'contract_closed'))
            ->selectRaw("DATE_FORMAT(leads.updated_at, '%Y-%m') as ym, count(*) as c")
            ->groupBy('ym')
            ->pluck('c', 'ym')
            ->all();

        $perfContracts = [];
        foreach (array_keys($monthKeys) as $ym) {
            $perfContracts[] = (int) ($monthlyContracts[$ym] ?? 0);
        }

        $selectedStageIds = $selectedChartStages->pluck('id')->all();

        $q1 = (clone $timelineLeadBase)
            ->join('lead_statuses as ls1', 'ls1.id', '=', 'leads.lead_status_id')
            ->whereIn('ls1.pipeline_stage_id', $selectedStageIds)
            ->whereBetween('leads.created_at', [$startPeriod, $endPeriod])
            ->select('leads.id as lead_id', 'ls1.pipeline_stage_id as stage_id', DB::raw("DATE_FORMAT(leads.created_at, '%Y-%m') as ym"));

        $q2 = (clone $timelineLeadBase)
            ->join('lead_status_histories as lsh', 'lsh.lead_id', '=', 'leads.id')
            ->join('lead_statuses as ls2', 'ls2.id', '=', 'lsh.to_status_id')
            ->whereIn('ls2.pipeline_stage_id', $selectedStageIds)
            ->whereBetween('lsh.changed_at', [$startPeriod, $endPeriod])
            ->select('leads.id as lead_id', 'ls2.pipeline_stage_id as stage_id', DB::raw("DATE_FORMAT(lsh.changed_at, '%Y-%m') as ym"));

        $stageCountsRaw = DB::query()
            ->fromSub($q1->union($q2), 'u')
            ->select('ym', 'stage_id', DB::raw('count(distinct lead_id) as count'))
            ->groupBy('ym', 'stage_id')
            ->get();

        $stageCountLookup = [];
        foreach ($stageCountsRaw as $row) {
            $stageCountLookup[$row->stage_id][$row->ym] = (int) $row->count;
        }

        $stageMonthlyData = [];
        foreach ($selectedChartStages as $stage) {
            $stageMonthlyData[$stage->id] = [];
            foreach (array_keys($monthKeys) as $ym) {
                $stageMonthlyData[$stage->id][] = $stageCountLookup[$stage->id][$ym] ?? 0;
            }
        }

        $chartSeries = [];
        $totalMonthlyActivity = 0;
        $palette = ['#38bdf8', '#8b5cf6', '#10b981', '#f59e0b', '#ec4899', '#6366f1', '#14b8a6', '#f97316'];
        foreach ($selectedChartStages as $idx => $stage) {
            $chartSeries[] = [
                'id' => $stage->id,
                'name' => $stage->localizedName(),
                'code' => $stage->code,
                'color' => $stage->color ?: ($palette[$idx % count($palette)]),
                'data' => $stageMonthlyData[$stage->id] ?? [],
            ];
            $totalMonthlyActivity += array_sum($stageMonthlyData[$stage->id] ?? []);
        }

        $hasData = ($totalMonthlyActivity > 0) || ((array_sum($perfTotal) + array_sum($perfFollowups) + array_sum($perfContracts)) > 0);

        $performanceTimeline = [
            'labels' => $perfLabels,
            'series' => $chartSeries,
            'selected_stages' => $selectedChartStageIds,
            'total' => $perfTotal,
            'newLeads' => $perfNew,
            'followups' => $perfFollowups,
            'contracts' => $perfContracts,
            'hasData' => $hasData,
        ];

        if ($request->ajax() || $request->wantsJson() || $request->query('ajax')) {
            $widget = $request->query('widget');
            if ($widget === 'performance_chart') {
                return response()->json([
                    'success' => true,
                    'widget' => 'performance_chart',
                    'timeline' => $performanceTimeline,
                ]);
            }
        }
        $activeCampaigns = [];
        if ($user->hasPermission(CrmPermission::CAMPAIGNS_VIEW) || $user->hasPermission('campaigns.view')) {
            $campaignQuery = Campaign::query()
                ->active()
                ->visibleTo($user);

            if (! empty($filters['employee'])) {
                $campaignQuery->where(static function (Builder $q) use ($filters): void {
                    $q->whereHas('users', static function (Builder $uq) use ($filters): void {
                        $uq->where('name', $filters['employee']);
                    })->orWhereHas('creator', static function (Builder $cq) use ($filters): void {
                        $cq->where('name', $filters['employee']);
                    });
                });
            }

            $activeCampaigns = $campaignQuery
                ->withCount([
                    'leads as user_leads_count' => static function (Builder $q) use ($user, $filters): void {
                        $q->accessibleTo($user);
                        if (! empty($filters['employee'])) {
                            $q->where(static function (Builder $eq) use ($filters): void {
                                $eq->whereHas('assignedUser', static fn (Builder $uq) => $uq->where('name', $filters['employee']))
                                    ->orWhere('assigned_employee', $filters['employee']);
                            });
                        } else {
                            $q->where('leads.assigned_user_id', $user->id);
                        }
                        $q->select(DB::raw('count(distinct leads.id)'));
                    },
                    'leads as total_leads_count' => static function (Builder $q) use ($user, $filters): void {
                        $q->accessibleTo($user);
                        if (! empty($filters['employee'])) {
                            $q->where(static function (Builder $eq) use ($filters): void {
                                $eq->whereHas('assignedUser', static fn (Builder $uq) => $uq->where('name', $filters['employee']))
                                    ->orWhere('assigned_employee', $filters['employee']);
                            });
                        }
                        $q->select(DB::raw('count(distinct leads.id)'));
                    },
                ])
                ->orderByDesc('starts_at')
                ->orderBy('id')
                ->get()
                ->map(static function (Campaign $c): array {
                    $total = (int) ($c->user_leads_count ?? 0);

                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'cost' => $c->cost ? (float) $c->cost : null,
                        'total_leads' => $total,
                        'url' => route('v2.campaigns.show', $c->id),
                    ];
                })
                ->values()
                ->all();
        }



        return view(
            'dashboard',
            [
                'totalLeads' => $totalLeads,

                'statusCards' => $statusCards,
                'activePipelineStages' => $activePipelineStages,
                'stageCards' => $stageCards,
                'distribution' => $distribution,
                'miniCalendarEvents' => $miniCalendarEvents,
                'performanceTimeline' => $performanceTimeline,
                'firstActiveStage' => $firstActiveStage,
                'finalActiveStage' => $finalActiveStage,
                'finalStageTitle' => $finalStageTitle,
                'finalConversionRate' => $finalConversionRate,
                'finalStageReachedCount' => $finalStageReachedCount,
                'finalStageColor' => $finalStageColor,
                'finalStageIcon' => $finalStageIcon,
                'contractRate' => $contractRate,
                'followupCounts' => $followupCounts,

                'meetingCounts' => $meetingCounts,

                'latestFollowups' => $latestFollowups,

                'communicationLabels' => self::COMMUNICATION_LABELS,

                'employees' => $employees,

                'filters' => $filters,

                'voipStatus' => $voipStatus,
                'activeCampaigns' => $activeCampaigns,
                'conversionMetrics' => $conversionMetrics,
                'stageKpi1' => $stageKpi1,
                'stageKpi2' => $stageKpi2,
                'stageActivity' => $stageActivity,
            ]
        );
    }

    public function kanban(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();
        abort_unless($user && $user->hasPermission(CrmPermission::LEADS_VIEW), 403);
        $canFilterByEmployee = $user->hasPermission(
            CrmPermission::LEADS_SCOPE_ALL,
        );
        $employees = collect();
        $selectedEmployeeId = (int) $user->getKey();

        if ($canFilterByEmployee) {
            $employees = User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            $selectedEmployeeId = null;
            $requestedEmployeeId = $request->query('employee_id');

            if ($requestedEmployeeId !== null && $requestedEmployeeId !== '') {
                abort_unless(
                    ctype_digit((string) $requestedEmployeeId),
                    404,
                );

                $selectedEmployeeId = (int) $requestedEmployeeId;
                abort_unless(
                    $employees->contains('id', $selectedEmployeeId),
                    404,
                );
            }
        }

        $shouldFilterByEmployee = $selectedEmployeeId !== null
            && $request->filled('employee_id');
        $todayStart = now()
            ->startOfDay();

        $todayEnd = now()
            ->endOfDay();

        $statuses = LeadStatus::query()
            ->visibleTo($user)
            ->with('stage.activeFields')
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get();
        $kanbanColumns = [];
        $totalLeads = 0;
        $perPageRaw = $request->query('per_page', self::KANBAN_COLUMN_PAGE_SIZE);
        $perPage = self::KANBAN_COLUMN_PAGE_SIZE;
        if (is_numeric($perPageRaw)) {
            $parsedPerPage = (int) $perPageRaw;
            if (in_array($parsedPerPage, self::KANBAN_ALLOWED_PAGE_SIZES, true)) {
                $perPage = $parsedPerPage;
            }
        }
        $INITIAL_CARD_LIMIT = $perPage;
        $leadSelect = [
            'id',
            'name',
            'phone',
            'company_name',
            'source',
            'assigned_user_id',
            'assigned_employee',
            'next_follow_up_at',
            'lead_status_id',
            'updated_at',
        ];
        $activeUsers = User::query()->where('is_active', true)->get(['id', 'name'])->keyBy('id');
        $attachAssignedUser = static function ($leads) use ($activeUsers): void {
            foreach ($leads as $lead) {
                if ($lead->assigned_user_id) {
                    $u = $activeUsers->get($lead->assigned_user_id);
                    if ($u) {
                        $lead->setRelation('assignedUser', $u);
                    }
                }
            }
        };

        $statusIds = $statuses->pluck('id')->all();

        $scopeStatsRaw = Lead::query()
            ->accessibleTo($user)
            ->whereIn('lead_status_id', $statusIds)
            ->when(
                $shouldFilterByEmployee,
                static fn (Builder $query): Builder => $query
                    ->where('assigned_user_id', $selectedEmployeeId),
            )
            ->selectRaw("
                lead_status_id,
                count(*) as total_count,
                count(case when next_follow_up_at is null then 1 end) as no_date_count,
                count(case when next_follow_up_at between ? and ? then 1 end) as today_count,
                count(case when next_follow_up_at < ? then 1 end) as overdue_count,
                count(case when next_follow_up_at > ? then 1 end) as upcoming_count
            ", [$todayStart, $todayEnd, $todayStart, $todayEnd])
            ->groupBy('lead_status_id')
            ->get()
            ->keyBy('lead_status_id');

        foreach ($statuses as $status) {
            $ui = self::STATUS_UI[
                $status->code
            ] ?? [
                'slug' => $status->code,
                'icon' => '•',
                'class' => '',
                'kanban_class' => '',
            ];

            $stats = $scopeStatsRaw->get($status->id);
            $totalCount = (int) ($stats->total_count ?? 0);
            $totalLeads += $totalCount;
            $noDateCount = (int) ($stats->no_date_count ?? 0);
            $todayCount = (int) ($stats->today_count ?? 0);
            $overdueCount = (int) ($stats->overdue_count ?? 0);
            $upcomingCount = (int) ($stats->upcoming_count ?? 0);

            $scopeCounts = [
                'today' => $todayCount,
                'overdue' => $overdueCount,
                'upcoming' => $upcomingCount,
                'no_date' => $noDateCount,
            ];

            $baseQuery = Lead::query()
                ->accessibleTo($user)
                ->where('lead_status_id', $status->id)
                ->when(
                    $shouldFilterByEmployee,
                    static fn (Builder $query): Builder => $query
                        ->where('assigned_user_id', $selectedEmployeeId),
                );

            $datedBase = (clone $baseQuery)->whereNotNull('next_follow_up_at');

            $todayLeads = $todayCount > 0
                ? (clone $datedBase)
                    ->select($leadSelect)
                    ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->take($INITIAL_CARD_LIMIT)
                    ->get()
                : collect();
            $attachAssignedUser($todayLeads);

            $overdueLeads = $overdueCount > 0
                ? (clone $datedBase)
                    ->select($leadSelect)
                    ->where('next_follow_up_at', '<', $todayStart)
                    ->orderByDesc('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->take($INITIAL_CARD_LIMIT)
                    ->get()
                : collect();
            $attachAssignedUser($overdueLeads);

            $upcomingLeads = $upcomingCount > 0
                ? (clone $datedBase)
                    ->select($leadSelect)
                    ->where('next_follow_up_at', '>', $todayEnd)
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->take($INITIAL_CARD_LIMIT)
                    ->get()
                : collect();
            $attachAssignedUser($upcomingLeads);
            $noDateLeads = $noDateCount > 0
                ? (clone $baseQuery)
                    ->select($leadSelect)
                    ->whereNull('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->take($INITIAL_CARD_LIMIT)
                    ->get()
                : collect();
            $attachAssignedUser($noDateLeads);

            $allLeads = $totalCount > 0
                ? (clone $baseQuery)
                    ->select($leadSelect)
                    ->orderByRaw('next_follow_up_at IS NULL')
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->take($INITIAL_CARD_LIMIT)
                    ->get()
                : collect();
            $attachAssignedUser($allLeads);

            $scopeLeads = [
                'today' => $todayLeads,
                'overdue' => $overdueLeads,
                'upcoming' => $upcomingLeads,
                'no_date' => $noDateLeads,
                'all' => $allLeads,
            ];

            $kanbanColumns[] = [
                'id' => $status->id,
                'stage_id' => $status->pipeline_stage_id,
                'status_id' => $status->id,
                'destination_status_id' => $status->id,
                'code' => $status->code,
                'name' => $status->name_ar,
                'status_name' => $status->name_ar,
                'stage_name' => $status->stage?->localizedName() ?? ($status->stage?->name_ar ?? $status->name_ar),
                'slug' => $ui['slug'],
                'icon' => $ui['icon'],
                'class' => $ui['kanban_class'] ?: str_replace(['_', ' '], '-', (string) $status->code),
                'status_color' => $status->color ?: ($status->stage?->color ?: '#3478f6'),
                'stage_color' => $status->stage?->color ?: ($status->color ?: '#3478f6'),
                'total_count' => $totalCount,
                'no_date_count' => $noDateCount,
                'scope_counts' => $scopeCounts,
                'scope_leads' => $scopeLeads,
                'all_leads' => $allLeads,
                'has_questions' => $status->stage ? $status->stage->activeFields->isNotEmpty() : false,
                'fields' => $status->stage ? $status->stage->activeFields : collect(),
            ];
        }

        return view(
            'kanban',
            [
                'kanbanColumns' => $kanbanColumns,
                'totalLeads' => $totalLeads,
                'canFilterByEmployee' => $canFilterByEmployee,
                'employees' => $employees,
                'selectedEmployeeId' => $selectedEmployeeId,
                'perPage' => $perPage,
                'allowedPageSizes' => self::KANBAN_ALLOWED_PAGE_SIZES,
            ]
        );
    }

    public function kanbanColumn(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();
        abort_unless($user && $user->hasPermission(CrmPermission::LEADS_VIEW), 403);

        $statusId = $request->query('status_id') ?? $request->query('status');
        abort_unless(
            $statusId !== null && (is_int($statusId) || ctype_digit((string) $statusId)),
            404,
        );

        $status = LeadStatus::query()
            ->visibleTo($user)
            ->with('stage')
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->find((int) $statusId);

        abort_unless($status !== null, 404);

        $scope = (string) ($request->query('scope') ?: 'all');
        abort_unless(
            in_array($scope, ['today', 'overdue', 'upcoming', 'no_date', 'all'], true),
            400,
        );

        $pageRaw = $request->query('page', 1);
        abort_unless(
            is_int($pageRaw) || (is_string($pageRaw) && ctype_digit($pageRaw)),
            400,
        );
        $page = max(1, (int) $pageRaw);

        $canFilterByEmployee = $user->hasPermission(
            CrmPermission::LEADS_SCOPE_ALL,
        );
        $selectedEmployeeId = null;

        if ($canFilterByEmployee) {
            $requestedEmployeeId = $request->query('employee_id');
            if ($requestedEmployeeId !== null && $requestedEmployeeId !== '') {
                abort_unless(
                    ctype_digit((string) $requestedEmployeeId),
                    404,
                );
                $selectedEmployeeId = (int) $requestedEmployeeId;
                $employeeExists = User::query()
                    ->where('is_active', true)
                    ->whereKey($selectedEmployeeId)
                    ->exists();
                abort_unless($employeeExists, 404);
            }
        }

        $shouldFilterByEmployee = $selectedEmployeeId !== null;
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $baseQuery = Lead::query()
            ->accessibleTo($user)
            ->where('lead_status_id', $status->id)
            ->when(
                $shouldFilterByEmployee,
                static fn (Builder $query): Builder => $query
                    ->where('assigned_user_id', $selectedEmployeeId),
            );

        $datedBase = (clone $baseQuery)->whereNotNull('next_follow_up_at');

        $query = match ($scope) {
            'today' => (clone $datedBase)
                ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at'),
            'overdue' => (clone $datedBase)
                ->where('next_follow_up_at', '<', $todayStart)
                ->orderByDesc('next_follow_up_at')
                ->orderByDesc('updated_at'),
            'upcoming' => (clone $datedBase)
                ->where('next_follow_up_at', '>', $todayEnd)
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at'),
            'no_date' => (clone $baseQuery)
                ->whereNull('next_follow_up_at')
                ->orderByDesc('updated_at'),
            default => (clone $baseQuery)
                ->orderByRaw('next_follow_up_at IS NULL')
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at'),
        };

        $total = (clone $query)->count();
        $pageSizeRaw = $request->query('per_page') ?? $request->query('pageSize') ?? self::KANBAN_COLUMN_PAGE_SIZE;
        $pageSize = self::KANBAN_COLUMN_PAGE_SIZE;
        if (is_numeric($pageSizeRaw)) {
            $parsedPageSize = (int) $pageSizeRaw;
            if (in_array($parsedPageSize, self::KANBAN_ALLOWED_PAGE_SIZES, true)) {
                $pageSize = $parsedPageSize;
            }
        }

        $leadSelect = [
            'id',
            'name',
            'phone',
            'company_name',
            'source',
            'assigned_user_id',
            'assigned_employee',
            'next_follow_up_at',
            'lead_status_id',
            'updated_at',
        ];

        $leads = $query
            ->select($leadSelect)
            ->with('assignedUser:id,name')
            ->forPage($page, $pageSize)
            ->get();

        $ui = self::STATUS_UI[
            $status->code
        ] ?? [
            'slug' => $status->code,
            'icon' => '•',
            'class' => '',
            'kanban_class' => '',
        ];

        $column = [
            'id' => $status->id,
            'stage_id' => $status->pipeline_stage_id,
            'status_id' => $status->id,
            'destination_status_id' => $status->id,
            'code' => $status->code,
            'name' => $status->name_ar,
            'status_name' => $status->name_ar,
            'stage_name' => $status->stage?->localizedName() ?? ($status->stage?->name_ar ?? $status->name_ar),
            'slug' => $ui['slug'],
            'icon' => $ui['icon'],
            'class' => $ui['kanban_class'] ?: str_replace(['_', ' '], '-', (string) $status->code),
            'status_color' => $status->color ?: ($status->stage?->color ?: '#3478f6'),
            'stage_color' => $status->stage?->color ?: ($status->color ?: '#3478f6'),
            'total_count' => $total,
        ];

        $html = view('leads.partials.kanban-column-cards', [
            'leads' => $leads,
            'column' => $column,
            'scope' => $scope,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
        ])->render();

        $from = $total === 0 ? 0 : (($page - 1) * $pageSize + 1);
        $to = min($total, $page * $pageSize);
        $hasMore = ($page * $pageSize) < $total;
        $hasPrevious = $page > 1;

        return response()->json([
            'success' => true,
            'html' => $html,
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $total,
            'from' => $from,
            'to' => $to,
            'hasMore' => $hasMore,
            'hasPrevious' => $hasPrevious,
            'count' => $leads->count(),
        ]);
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }

    private function resolveFilters(
        Request $request
    ): array {
        $employee = trim(
            (string) $request->query(
                'employee',
                ''
            )
        );

        $period = (string)
            $request->query(
                'period',
                'all'
            );

        if (
            ! in_array(
                $period,
                [
                    'all',
                    'today',
                    'week',
                    'month',
                ],
                true
            )
        ) {
            $period = 'all';
        }

        $fromInput = trim(
            (string) $request->query(
                'from',
                ''
            )
        );

        $toInput = trim(
            (string) $request->query(
                'to',
                ''
            )
        );

        $fromDate = $this->parseDate(
            $fromInput,
            false
        );

        $toDate = $this->parseDate(
            $toInput,
            true
        );

        if (
            ! $fromDate
            && ! $toDate
        ) {
            $now = now();

            if ($period === 'today') {
                $fromDate = $now
                    ->copy()
                    ->startOfDay();

                $toDate = $now
                    ->copy()
                    ->endOfDay();
            }

            if ($period === 'week') {
                $fromDate = $now
                    ->copy()
                    ->startOfWeek();

                $toDate = $now
                    ->copy()
                    ->endOfWeek();
            }

            if ($period === 'month') {
                $fromDate = $now
                    ->copy()
                    ->startOfMonth();

                $toDate = $now
                    ->copy()
                    ->endOfMonth();
            }
        }

        return [
            'employee' => $employee,
            'period' => $period,
            'from' => (
                $fromDate
                && $fromInput !== ''
                    ? $fromInput
                    : ''
            ),
            'to' => (
                $toDate
                && $toInput !== ''
                    ? $toInput
                    : ''
            ),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    private function parseDate(
        string $value,
        bool $endOfDay
    ): ?Carbon {
        if (
            $value === ''
            || ! preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $value
            )
        ) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                $value,
                config('app.timezone')
            );

            return $endOfDay
                ? $date->endOfDay()
                : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyLeadFilters(
        Builder $query,
        array $filters
    ): void {
        if (
            $filters['employee'] !== ''
        ) {
            $query->where(
                function (Builder $employeeQuery) use ($filters): void {
                    $employeeQuery
                        ->whereHas(
                            'assignedUser',
                            function (Builder $userQuery) use ($filters): void {
                                $userQuery->where(
                                    'name',
                                    $filters['employee']
                                );
                            }
                        )
                        ->orWhere(
                            function (Builder $legacyQuery) use ($filters): void {
                                $legacyQuery
                                    ->whereNull('assigned_user_id')
                                    ->where(
                                        'assigned_employee',
                                        $filters['employee']
                                    );
                            }
                        );
                }
            );
        }

        if (
            $filters['from_date']
            instanceof Carbon
        ) {
            $query->where(
                'leads.created_at',
                '>=',
                $filters['from_date']
            );
        }

        if (
            $filters['to_date']
            instanceof Carbon
        ) {
            $query->where(
                'leads.created_at',
                '<=',
                $filters['to_date']
            );
        }
    }

    private array $stageConversionMemo = [];

    private function calculateStageConversion(?PipelineStage $fromStage, ?PipelineStage $toStage, Builder $leadBase, ?iterable $allStages = null): array
    {
        $memoKey = ($fromStage?->id ?? 'null') . '_' . ($toStage?->id ?? 'null');
        if (isset($this->stageConversionMemo[$memoKey])) {
            return $this->stageConversionMemo[$memoKey];
        }

        if ($fromStage === null || $toStage === null) {
            $targetStage = $toStage ?? $fromStage;
            return $this->stageConversionMemo[$memoKey] = [
                'from_stage_id' => $fromStage?->id,
                'from_stage_name' => $fromStage?->localizedName() ?? '—',
                'to_stage_id' => $toStage?->id,
                'to_stage_name' => $toStage?->localizedName() ?? '—',
                'stage_id' => $targetStage?->id,
                'stage_name' => $targetStage?->localizedName() ?? '—',
                'color' => $targetStage?->color ?: '#8b5cf6',
                'icon' => $this->formatStageIcon($targetStage?->icon ?: 'bi-funnel'),
                'has_previous_stage' => false,
                'previous_stage_name' => null,
                'rate' => null,
                'display_value' => __('crm.not_available'),
                'subtitle' => __('crm.first_stage_no_previous'),
                'numerator' => 0,
                'denominator' => 0,
                'is_valid_direction' => false,
            ];
        }

        $color = $toStage->color ?: '#8b5cf6';
        $icon = $this->formatStageIcon($toStage->icon ?: 'bi-funnel');

        $fromIndex = null;
        $toIndex = null;
        if ($allStages !== null) {
            $allStagesCollection = $allStages instanceof \Illuminate\Support\Collection ? $allStages : collect($allStages);
            $fromIndex = $allStagesCollection->search(static fn (PipelineStage $s) => $s->id === $fromStage->id);
            $toIndex = $allStagesCollection->search(static fn (PipelineStage $s) => $s->id === $toStage->id);
        }

        $isReverse = ($fromIndex !== false && $toIndex !== false && $fromIndex !== null && $toIndex !== null)
            ? ($toIndex < $fromIndex)
            : ($toStage->position < $fromStage->position);

        $isSame = ($fromStage->id === $toStage->id);

        if ($isReverse) {
            return $this->stageConversionMemo[$memoKey] = [
                'from_stage_id' => $fromStage->id,
                'from_stage_name' => $fromStage->localizedName(),
                'to_stage_id' => $toStage->id,
                'to_stage_name' => $toStage->localizedName(),
                'stage_id' => $toStage->id,
                'stage_name' => $toStage->localizedName(),
                'color' => $color,
                'icon' => $icon,
                'has_previous_stage' => true,
                'previous_stage_name' => $fromStage->localizedName(),
                'rate' => null,
                'display_value' => '—',
                'subtitle' => __('crm.invalid_stage_direction'),
                'numerator' => 0,
                'denominator' => 0,
                'is_valid_direction' => false,
            ];
        }

        $fromStatusIds = $fromStage->statuses->pluck('id')->all();
        $toStatusIds = $toStage->statuses->pluck('id')->all();

        if ($isSame) {
            $denominator = (clone $leadBase)
                ->where(static function (Builder $q) use ($fromStatusIds): void {
                    $q->whereIn('leads.lead_status_id', $fromStatusIds);
                    if (! empty($fromStatusIds)) {
                        $q->orWhereHas('statusHistory', static function (Builder $hq) use ($fromStatusIds): void {
                            $hq->whereIn('to_status_id', $fromStatusIds)
                                ->orWhereIn('from_status_id', $fromStatusIds);
                        });
                    }
                })
                ->distinct()
                ->count('leads.id');

            return $this->stageConversionMemo[$memoKey] = [
                'from_stage_id' => $fromStage->id,
                'from_stage_name' => $fromStage->localizedName(),
                'to_stage_id' => $toStage->id,
                'to_stage_name' => $toStage->localizedName(),
                'stage_id' => $toStage->id,
                'stage_name' => $toStage->localizedName(),
                'color' => $color,
                'icon' => $icon,
                'has_previous_stage' => true,
                'previous_stage_name' => $fromStage->localizedName(),
                'rate' => 100.0,
                'display_value' => '100%',
                'subtitle' => __('crm.same_stage_selected'),
                'numerator' => $denominator,
                'denominator' => $denominator,
                'is_valid_direction' => true,
            ];
        }

        // Forward Progression
        $denominator = (clone $leadBase)
            ->where(static function (Builder $q) use ($fromStatusIds, $toStatusIds): void {
                $q->whereIn('leads.lead_status_id', $fromStatusIds);
                if (! empty($toStatusIds)) {
                    $q->orWhereIn('leads.lead_status_id', $toStatusIds);
                }
                if (! empty($fromStatusIds)) {
                    $q->orWhereHas('statusHistory', static function (Builder $hq) use ($fromStatusIds, $toStatusIds): void {
                        $hq->whereIn('to_status_id', $fromStatusIds)
                            ->orWhereIn('from_status_id', $fromStatusIds);
                        if (! empty($toStatusIds)) {
                            $hq->orWhereIn('to_status_id', $toStatusIds);
                        }
                    });
                }
            })
            ->distinct()
            ->count('leads.id');

        $numerator = (clone $leadBase)
            ->where(static function (Builder $q) use ($fromStatusIds, $toStatusIds): void {
                $q->where(static function (Builder $sub) use ($fromStatusIds, $toStatusIds): void {
                    $sub->whereIn('leads.lead_status_id', $fromStatusIds);
                    if (! empty($toStatusIds)) {
                        $sub->orWhereIn('leads.lead_status_id', $toStatusIds);
                    }
                    if (! empty($fromStatusIds)) {
                        $sub->orWhereHas('statusHistory', static function (Builder $hq) use ($fromStatusIds, $toStatusIds): void {
                            $hq->whereIn('to_status_id', $fromStatusIds)
                                ->orWhereIn('from_status_id', $fromStatusIds);
                            if (! empty($toStatusIds)) {
                                $hq->orWhereIn('to_status_id', $toStatusIds);
                            }
                        });
                    }
                });
            })
            ->where(static function (Builder $q) use ($toStatusIds): void {
                $q->whereIn('leads.lead_status_id', $toStatusIds);
                if (! empty($toStatusIds)) {
                    $q->orWhereHas('statusHistory', static function (Builder $hq) use ($toStatusIds): void {
                        $hq->whereIn('to_status_id', $toStatusIds);
                    });
                }
            })
            ->distinct()
            ->count('leads.id');

        $rate = $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : 0.0;
        $subtitle = __('crm.from_stage_prefix') . ' ' . $fromStage->localizedName() . ' ' . __('crm.to_stage_prefix') . ' ' . $toStage->localizedName();

        return $this->stageConversionMemo[$memoKey] = [
            'from_stage_id' => $fromStage->id,
            'from_stage_name' => $fromStage->localizedName(),
            'to_stage_id' => $toStage->id,
            'to_stage_name' => $toStage->localizedName(),
            'stage_id' => $toStage->id,
            'stage_name' => $toStage->localizedName(),
            'color' => $color,
            'icon' => $icon,
            'has_previous_stage' => true,
            'previous_stage_name' => $fromStage->localizedName(),
            'rate' => $rate,
            'display_value' => $rate . '%',
            'subtitle' => $subtitle,
            'numerator' => $numerator,
            'denominator' => $denominator,
            'is_valid_direction' => true,
        ];
    }

    private function calculateStageKpi(?PipelineStage $stage, Builder $leadBase, int $totalLeads, array $filters, array $statusCounts = []): array
    {
        if ($stage === null) {
            return [
                'stage_id' => null,
                'stage_name' => '—',
                'count' => 0,
                'color' => '#3b82f6',
                'icon' => 'bi bi-diagram-3',
                'percentage' => 0.0,
                'subtitle' => '0% ' . __('crm.of_total_customers'),
                'filter_url' => route('v2.leads'),
            ];
        }

        if (! empty($statusCounts)) {
            $count = 0;
            foreach ($stage->statuses as $status) {
                $count += (int) ($statusCounts[$status->code] ?? 0);
            }
        } else {
            $statusIds = $stage->statuses->pluck('id')->all();
            $count = ! empty($statusIds)
                ? (clone $leadBase)->whereIn('lead_status_id', $statusIds)->count()
                : 0;
        }
        $percentage = $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0.0;
        $color = $stage->color ?: '#3b82f6';
        $icon = $this->formatStageIcon($stage->icon ?: 'bi-diagram-3');

        return [
            'stage_id' => $stage->id,
            'stage_name' => $stage->localizedName(),
            'count' => $count,
            'color' => $color,
            'icon' => $icon,
            'percentage' => $percentage,
            'subtitle' => $percentage . '% ' . __('crm.of_total_customers'),
            'filter_url' => route('v2.leads', array_filter(['stage' => $stage->id, 'employee' => $filters['employee'] ?? null])),
        ];
    }

    private function calculateStageActivity(?PipelineStage $stage, Builder $leadBase, Carbon $todayStart, Carbon $todayEnd): array
    {
        if ($stage === null) {
            return [
                'stage_id' => null,
                'stage_name' => '—',
                'today_count' => 0,
                'overdue_count' => 0,
                'upcoming_count' => 0,
                'total_count' => 0,
                'leads' => [],
            ];
        }

        $statusIds = $stage->statuses->pluck('id')->all();
        $stageLeadBase = clone $leadBase;

        if (! empty($statusIds)) {
            $stageLeadBase->whereIn('lead_status_id', $statusIds);
        } else {
            $stageLeadBase->whereRaw('1 = 0');
        }

        $todayCount = (clone $stageLeadBase)
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
            ->count();

        $overdueCount = (clone $stageLeadBase)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', $todayStart)
            ->count();

        $upcomingCount = (clone $stageLeadBase)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>', $todayEnd)
            ->count();

        $detailLeads = (clone $stageLeadBase)
            ->whereNotNull('next_follow_up_at')
            ->with(['status', 'assignedUser:id,name'])
            ->orderBy('next_follow_up_at', 'asc')
            ->limit(5)
            ->get()
            ->map(static function (Lead $lead) use ($todayStart, $todayEnd): array {
                $next = $lead->next_follow_up_at;
                $timing = 'upcoming';
                $timingLabel = __('crm.upcoming_short');
                $timingClass = 'badge-upcoming';
                if ($next < $todayStart) {
                    $timing = 'overdue';
                    $timingLabel = __('crm.overdue_short');
                    $timingClass = 'badge-overdue';
                } elseif ($next <= $todayEnd) {
                    $timing = 'today';
                    $timingLabel = __('crm.today');
                    $timingClass = 'badge-today';
                }

                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'url' => route('v2.leads.show', $lead->id),
                    'scheduled_at' => $next ? $next->format('Y-m-d H:i') : '—',
                    'scheduled_date' => $next ? $next->format('d/m/Y') : '—',
                    'scheduled_time' => $next ? $next->format('h:i A') : '—',
                    'timing' => $timing,
                    'timing_label' => $timingLabel,
                    'timing_class' => $timingClass,
                    'employee_name' => $lead->assignedUser?->name ?: ($lead->assigned_employee ?: '—'),
                    'status_name' => $lead->status?->localizedName() ?? ($lead->status?->name_ar ?: '—'),
                ];
            })
            ->values()
            ->all();

        return [
            'stage_id' => $stage->id,
            'stage_name' => $stage->localizedName(),
            'today_count' => $todayCount,
            'overdue_count' => $overdueCount,
            'upcoming_count' => $upcomingCount,
            'total_count' => $todayCount + $overdueCount + $upcomingCount,
            'leads' => $detailLeads,
        ];
    }

    private function calculateStageActivityLeads(
        ?PipelineStage $stage,
        Builder $leadBase,
        Carbon $todayStart,
        Carbon $todayEnd,
        string $bucket = 'all',
        int $page = 1,
        int $perPage = 10
    ): array {
        if ($stage === null) {
            return [
                'stage_id' => null,
                'stage_name' => '—',
                'bucket' => $bucket,
                'bucket_label' => '—',
                'total' => 0,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'from' => 0,
                'to' => 0,
                'leads' => [],
                'empty' => true,
            ];
        }

        $statusIds = $stage->statuses->pluck('id')->all();
        $stageLeadBase = clone $leadBase;

        if (! empty($statusIds)) {
            $stageLeadBase->whereIn('lead_status_id', $statusIds);
        } else {
            $stageLeadBase->whereRaw('1 = 0');
        }

        $query = (clone $stageLeadBase)->whereNotNull('next_follow_up_at');

        if ($bucket === 'today') {
            $query->whereBetween('next_follow_up_at', [$todayStart, $todayEnd]);
        } elseif ($bucket === 'overdue') {
            $query->where('next_follow_up_at', '<', $todayStart);
        } elseif ($bucket === 'upcoming') {
            $query->where('next_follow_up_at', '>', $todayEnd);
        }

        $paginator = $query
            ->with(['status:id,name_ar,code,color', 'assignedUser:id,name'])
            ->orderBy('next_follow_up_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(static function (Lead $lead) use ($todayStart, $todayEnd, $stage): array {
            $next = $lead->next_follow_up_at;
            $timing = 'upcoming';
            $timingLabel = __('crm.upcoming_short');
            $timingClass = 'badge-upcoming';
            if ($next < $todayStart) {
                $timing = 'overdue';
                $timingLabel = __('crm.overdue_short');
                $timingClass = 'badge-overdue';
            } elseif ($next <= $todayEnd) {
                $timing = 'today';
                $timingLabel = __('crm.today');
                $timingClass = 'badge-today';
            }

            return [
                'id' => $lead->id,
                'name' => $lead->name,
                'url' => route('v2.leads.show', $lead->id),
                'phone' => $lead->phone,
                'company_name' => $lead->company_name,
                'stage_name' => $stage->localizedName(),
                'scheduled_at' => $next ? $next->format('Y-m-d H:i') : '—',
                'scheduled_date' => $next ? $next->format('d/m/Y') : '—',
                'scheduled_time' => $next ? $next->format('h:i A') : '—',
                'timing' => $timing,
                'timing_label' => $timingLabel,
                'timing_class' => $timingClass,
                'employee_name' => $lead->assignedUser?->name ?: ($lead->assigned_employee ?: '—'),
                'status_name' => $lead->status?->localizedName() ?? ($lead->status?->name_ar ?: '—'),
                'status_color' => $lead->status?->color ?: '#64748b',
            ];
        })->values()->all();

        $bucketLabels = [
            'all' => app()->getLocale() === 'ar' ? 'جميع المتابعات' : 'All Activity',
            'today' => app()->getLocale() === 'ar' ? 'متابعات اليوم' : 'Today\'s Activity',
            'overdue' => app()->getLocale() === 'ar' ? 'المتابعات المتأخرة' : 'Overdue Activity',
            'upcoming' => app()->getLocale() === 'ar' ? 'المتابعات القادمة' : 'Upcoming Activity',
        ];

        return [
            'stage_id' => $stage->id,
            'stage_name' => $stage->localizedName(),
            'bucket' => $bucket,
            'bucket_label' => $bucketLabels[$bucket] ?? $bucketLabels['all'],
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
            'leads' => $items,
            'empty' => empty($items),
        ];
    }

    private function formatStageIcon(?string $icon): string
    {
        if (empty($icon)) {
            return 'bi bi-diagram-3';
        }

        $icon = trim($icon);
        if (str_starts_with($icon, 'bi bi-')) {
            return $icon;
        }
        if (str_starts_with($icon, 'bi-')) {
            return 'bi ' . $icon;
        }
        if (str_starts_with($icon, 'bi ')) {
            return $icon;
        }

        return 'bi bi-' . $icon;
    }
}
