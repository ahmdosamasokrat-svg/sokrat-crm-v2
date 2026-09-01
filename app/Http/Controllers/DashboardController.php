<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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

class DashboardController extends Controller
{
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
            ->with('stage')
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get();
        $stages = PipelineStage::query()
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

        $statusCounts = [];

        foreach ($statuses as $status) {
            $statusCounts[$status->code] =
                (clone $leadBase)
                    ->where(
                        'lead_status_id',
                        $status->id
                    )
                    ->count();
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

        $contractClosed = (int) (
            $statusCounts[
                'contract_closed'
            ] ?? 0
        );

        $contractRate = $totalLeads > 0
            ? round(
                (
                    $contractClosed
                    / $totalLeads
                ) * 100,
                1
            )
            : 0.0;

        $todayStart = now()
            ->startOfDay();

        $todayEnd = now()
            ->endOfDay();

        $datedLeadBase = clone $leadBase;

        $followupCounts = [
            'today' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->whereBetween(
                    'next_follow_up_at',
                    [
                        $todayStart,
                        $todayEnd,
                    ]
                )
                ->count(),

            'overdue' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '<',
                    $todayStart
                )
                ->count(),

            'upcoming' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '>',
                    $todayEnd
                )
                ->count(),

            'no_date' => (
                clone $datedLeadBase
            )
                ->whereNull(
                    'next_follow_up_at'
                )
                ->count(),
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

        $meetingCounts = [
            'today' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->whereBetween(
                    'next_follow_up_at',
                    [
                        $todayStart,
                        $todayEnd,
                    ]
                )
                ->count(),

            'overdue' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '<',
                    $todayStart
                )
                ->count(),

            'upcoming' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '>',
                    $todayEnd
                )
                ->count(),
        ];

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
        if ($user->hasPermission('voip.view')) {
            try {
                $voip = app(VoipService::class);
                if ($voip->isConfigured()) {
                    $voipStatus = $voip->health();
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }
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
        $perfLabels = [];
        $perfTotal = [];
        $perfNew = [];
        $perfFollowups = [];
        $perfContracts = [];

        $nowDate = now();
        for ($i = 5; $i >= 0; $i--) {
            $month = (clone $nowDate)->subMonths($i);
            $perfLabels[] = $month->translatedFormat('M Y');
            $startOfMonth = (clone $month)->startOfMonth();
            $endOfMonth = (clone $month)->endOfMonth();

            $newCount = (clone $leadBase)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();
            $perfNew[] = $newCount;
            $perfTotal[] = $newCount;
            $perfFollowups[] = LeadFollowup::query()
                ->whereBetween('followed_up_at', [$startOfMonth, $endOfMonth])
                ->whereHas('lead', function (Builder $query) use ($user): void {
                    $query->accessibleTo($user);
                })
                ->count();
            $perfContracts[] = (clone $leadBase)
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->whereHas('status', static fn ($q) => $q->where('code', 'contract_closed'))
                ->count();
        }

        $hasData = (array_sum($perfTotal) + array_sum($perfFollowups) + array_sum($perfContracts)) > 0;

        $performanceTimeline = [
            'labels' => $perfLabels,
            'total' => $perfTotal,
            'newLeads' => $perfNew,
            'followups' => $perfFollowups,
            'contracts' => $perfContracts,
            'hasData' => $hasData,
        ];

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
                    'leads as total_leads_count' => static function (Builder $q) use ($user): void {
                        $q->accessibleTo($user);
                    },
                    'leads as converted_leads_count' => static function (Builder $q) use ($user): void {
                        $q->accessibleTo($user)->whereHas('status', static function (Builder $sq): void {
                            $sq->where('code', 'contract_closed')
                                ->orWhereHas('stage', static fn (Builder $stq) => $stq->whereIn('code', ['closing_execution', 'execution', 'donor']));
                        });
                    },
                ])
                ->orderByDesc('starts_at')
                ->orderBy('id')
                ->get()
                ->map(static function (Campaign $c): array {
                    $total = (int) ($c->total_leads_count ?? 0);
                    $converted = (int) ($c->converted_leads_count ?? 0);
                    $rate = $total > 0 ? round(($converted / $total) * 100, 1) : 0.0;

                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'cost' => $c->cost ? (float) $c->cost : null,
                        'total_leads' => $total,
                        'converted_leads' => $converted,
                        'progress' => $rate,
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
                'contractRate' => $contractRate,

                'followupCounts' => $followupCounts,

                'meetingCounts' => $meetingCounts,

                'latestFollowups' => $latestFollowups,

                'communicationLabels' => self::COMMUNICATION_LABELS,

                'employees' => $employees,

                'filters' => $filters,

                'voipStatus' => $voipStatus,
                'activeCampaigns' => $activeCampaigns,
            ]
        );
    }

    public function kanban(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();
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
            ->with('stage')
            ->whereHas('stage', static fn ($query) => $query->where('is_active', true))
            ->orderBy('position')
            ->get();

        $kanbanColumns = [];
        $totalLeads = 0;
        $INITIAL_CARD_LIMIT = 15;
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

        foreach ($statuses as $status) {
            $ui = self::STATUS_UI[
                $status->code
            ] ?? [
                'slug' => $status->code,
                'icon' => '•',
                'class' => '',
                'kanban_class' => '',
            ];

            $baseQuery = Lead::query()
                ->accessibleTo($user)
                ->where('lead_status_id', $status->id)
                ->when(
                    $shouldFilterByEmployee,
                    static fn (Builder $query): Builder => $query
                        ->where('assigned_user_id', $selectedEmployeeId),
                );

            $totalCount = (clone $baseQuery)->count();
            $totalLeads += $totalCount;

            $datedBase = (clone $baseQuery)->whereNotNull('next_follow_up_at');

            $todayCount = (clone $datedBase)
                ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                ->count();

            $overdueCount = (clone $datedBase)
                ->where('next_follow_up_at', '<', $todayStart)
                ->count();

            $upcomingCount = (clone $datedBase)
                ->where('next_follow_up_at', '>', $todayEnd)
                ->count();

            $noDateCount = (clone $baseQuery)
                ->whereNull('next_follow_up_at')
                ->count();

            $scopeCounts = [
                'today' => $todayCount,
                'overdue' => $overdueCount,
                'upcoming' => $upcomingCount,
            ];

            $todayLeads = (clone $datedBase)
                ->select($leadSelect)
                ->with('assignedUser:id,name')
                ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at')
                ->take($INITIAL_CARD_LIMIT)
                ->get();

            $overdueLeads = (clone $datedBase)
                ->select($leadSelect)
                ->with('assignedUser:id,name')
                ->where('next_follow_up_at', '<', $todayStart)
                ->orderByDesc('next_follow_up_at')
                ->orderByDesc('updated_at')
                ->take($INITIAL_CARD_LIMIT)
                ->get();

            $upcomingLeads = (clone $datedBase)
                ->select($leadSelect)
                ->with('assignedUser:id,name')
                ->where('next_follow_up_at', '>', $todayEnd)
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at')
                ->take($INITIAL_CARD_LIMIT)
                ->get();

            $allLeads = (clone $baseQuery)
                ->select($leadSelect)
                ->with('assignedUser:id,name')
                ->orderByRaw('next_follow_up_at IS NULL')
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at')
                ->take($INITIAL_CARD_LIMIT)
                ->get();

            $scopeLeads = [
                'today' => $todayLeads,
                'overdue' => $overdueLeads,
                'upcoming' => $upcomingLeads,
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
            ]
        );
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
                'created_at',
                '>=',
                $filters['from_date']
            );
        }

        if (
            $filters['to_date']
            instanceof Carbon
        ) {
            $query->where(
                'created_at',
                '<=',
                $filters['to_date']
            );
        }
    }
}
