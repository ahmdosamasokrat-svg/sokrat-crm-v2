<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\NotificationOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['nullable', 'string', 'in:all,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $user = $request->user();
        $query = $user->notifications()
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('notification_occurrences')
                    ->whereColumn('notification_occurrences.notification_id', 'notifications.id')
                    ->whereNotNull('notification_occurrences.dismissed_at');
            });

        if (($validated['filter'] ?? 'all') === 'unread') {
            $query->whereNull('read_at');
        }

        $paginator = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 20));
        $notificationIds = collect($paginator->items())->pluck('id');
        $occurrences = NotificationOccurrence::query()
            ->whereIn('notification_id', $notificationIds)
            ->get()
            ->keyBy('notification_id');

        $items = collect($paginator->items())->map(function (DatabaseNotification $notification) use ($occurrences): array {
            $data = $notification->data;
            $occurrence = $occurrences->get($notification->id);

            $rawUrl = (string) ($data['action_url'] ?? '');
            $actionUrl = $rawUrl;
            if ($rawUrl !== '') {
                $parsed = parse_url($rawUrl);
                if (! empty($parsed['path'])) {
                    $path = $parsed['path'];
                    $query = isset($parsed['query']) ? '?'.$parsed['query'] : '';
                    $fragment = isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
                    $actionUrl = $path.$query.$fragment;
                }
            }

            return [
                'id' => $notification->id,
                'title' => (string) ($data['title'] ?? ''),
                'body' => (string) ($data['body'] ?? ''),
                'priority' => (string) ($data['priority'] ?? 'normal'),
                'event_key' => (string) ($data['event_key'] ?? ''),
                'source_name' => (string) ($data['source_name'] ?? ''),
                'action_url' => $actionUrl,
                'created_at' => $notification->created_at?->toIso8601String(),
                'read_at' => $notification->read_at?->toIso8601String(),
                'can_snooze' => $occurrence !== null
                    && ! str_starts_with((string) $occurrence->event_key, 'system.'),
            ];
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()
            ->whereNotExists(function ($subQuery): void {
                $subQuery->selectRaw('1')
                    ->from('notification_occurrences')
                    ->whereColumn('notification_occurrences.notification_id', 'notifications.id')
                    ->whereNotNull('notification_occurrences.dismissed_at');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    public function dueFollowups(Request $request): JsonResponse
    {
        $user = $request->user();
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $tomorrowStart = $now->copy()->addDay()->startOfDay();
        $tomorrowEnd = $now->copy()->addDay()->endOfDay();

        $limit = max(1, min(100, (int) $request->query('limit', 100)));
        $filter = (string) $request->query('filter', '');
        $userId = (int) $user->getKey();

        $baseQuery = Lead::query()
            ->where('assigned_user_id', $userId)
            ->accessibleTo($user)
            ->whereNotNull('next_follow_up_at')
            ->where(static function (Builder $q): void {
                $q->whereNull('lead_status_id')
                    ->orWhereDoesntHave('status', static fn (Builder $sq): Builder => $sq->where('is_terminal', true));
            });

        $overdueCount = (clone $baseQuery)
            ->where('next_follow_up_at', '<', $now)
            ->count();
        $todayCount = (clone $baseQuery)
            ->whereBetween('next_follow_up_at', [$now, $todayEnd])
            ->count();
        $tomorrowCount = (clone $baseQuery)
            ->whereBetween('next_follow_up_at', [$tomorrowStart, $tomorrowEnd])
            ->count();
        $laterCount = (clone $baseQuery)
            ->where('next_follow_up_at', '>', $tomorrowEnd)
            ->count();
        $totalAttention = $overdueCount + $todayCount;
        $totalAll = $overdueCount + $todayCount + $tomorrowCount + $laterCount;

        // Query for specific requested filter or default
        $filterQuery = clone $baseQuery;
        if ($filter === 'overdue') {
            $filterQuery->where('next_follow_up_at', '<', $now);
            $totalForFilter = $overdueCount;
        } elseif ($filter === 'today') {
            $filterQuery->whereBetween('next_follow_up_at', [$now, $todayEnd]);
            $totalForFilter = $todayCount;
        } elseif ($filter === 'tomorrow') {
            $filterQuery->whereBetween('next_follow_up_at', [$tomorrowStart, $tomorrowEnd]);
            $totalForFilter = $tomorrowCount;
        } elseif ($filter === 'later') {
            $filterQuery->where('next_follow_up_at', '>', $tomorrowEnd);
            $totalForFilter = $laterCount;
        } else {
            $filterQuery->where('next_follow_up_at', '<=', $todayEnd);
            $totalForFilter = $totalAttention;
        }
        $dueQuery = (clone $baseQuery)->where('next_follow_up_at', '<=', $todayEnd);
        $leads = (clone $dueQuery)
            ->with([
                'status:id,pipeline_stage_id,code,name_ar,color',
                'status.stage:id,code,name_ar,color,position,is_active',
            ])
            ->orderBy('next_follow_up_at')
            ->orderBy('id')
            ->limit($limit)
            ->get([
                'id',
                'lead_status_id',
                'name',
                'company_name',
                'next_follow_up_at',
            ]);

        $attentionLeads = (clone $filterQuery)
            ->with([
                'status:id,pipeline_stage_id,code,name_ar,color',
                'status.stage:id,code,name_ar,color,position,is_active',
                'assignedUser:id,name',
            ])
            ->orderBy('next_follow_up_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $attentionItems = $attentionLeads->map(function (Lead $lead) use ($now, $todayEnd, $tomorrowEnd, $userId): array {
            $next = $lead->next_follow_up_at;
            $stage = $lead->status?->stage;
            $stageId = $stage?->getKey();
            $stageColor = (string) ($stage?->color ?: $lead->status?->color ?: '#64748b');
            if (! preg_match('/^#[0-9a-f]{6}$/i', $stageColor)) {
                $stageColor = '#64748b';
            }

            $bucket = 'later';
            $bucketLabel = __('crm.later') ?: 'قادمًا';
            $bucketClass = 'badge-later';
            if ($next < $now) {
                $bucket = 'overdue';
                $bucketLabel = __('crm.overdue_short') ?: 'متأخر';
                $bucketClass = 'badge-overdue';
            } elseif ($next <= $todayEnd) {
                $bucket = 'today';
                $bucketLabel = __('crm.today') ?: 'اليوم';
                $bucketClass = 'badge-today';
            } elseif ($next <= $tomorrowEnd) {
                $bucket = 'tomorrow';
                $bucketLabel = __('crm.tomorrow') ?: 'غدًا';
                $bucketClass = 'badge-tomorrow';
            }

            $routeParameters = [
                'scope' => $bucket,
                'employee_id' => $userId,
            ];
            if ($stageId !== null) {
                $routeParameters['stage_id'] = $stageId;
            }

            $timeString = $next->format('h:i A');
            $dateString = $next->format('d/m/Y');
            $dueDatetimeDisplay = $dateString . ' - ' . $timeString;

            return [
                'id' => $lead->getKey(),
                'name' => $lead->name,
                'company_name' => $lead->company_name,
                'status' => $lead->status?->localizedName() ?? ($lead->status?->name_ar ?: '—'),
                'status_color' => $lead->status?->color ?: '#64748b',
                'stage_name' => $stage?->localizedName() ?? ($stage?->name_ar ?: '—'),
                'stage_color' => $stageColor,
                'employee_name' => $lead->assignedUser?->name ?: ($lead->assigned_employee ?: '—'),
                'due_at' => $next->toIso8601String(),
                'due_formatted' => $dueDatetimeDisplay,
                'due_time' => $timeString,
                'due_date' => $dateString,
                'due_datetime' => $dueDatetimeDisplay,
                'bucket' => $bucket,
                'bucket_label' => $bucketLabel,
                'bucket_class' => $bucketClass,
                'action_url' => route('v2.tasks.daily', $routeParameters, false),
                'lead_url' => route('v2.leads.show', $lead->getKey()),
            ];
        })->values()->all();

        $groups = $leads
            ->groupBy(static fn (Lead $lead): string => (string) ($lead->status?->pipeline_stage_id ?? 'unassigned'))
            ->map(function ($stageLeads) use ($now, $todayStart, $userId): array {
                /** @var Lead $firstLead */
                $firstLead = $stageLeads->first();
                $stage = $firstLead->status?->stage;
                $stageId = $stage?->getKey();
                $stageColor = (string) ($stage?->color ?: $firstLead->status?->color ?: '#64748b');
                if (! preg_match('/^#[0-9a-f]{6}$/i', $stageColor)) {
                    $stageColor = '#64748b';
                }

                $items = $stageLeads->map(function (Lead $lead) use ($now, $todayStart, $stageId, $userId): array {
                    $bucket = $lead->next_follow_up_at->lt($now) ? 'overdue' : 'today';
                    $routeParameters = [
                        'scope' => $bucket,
                        'employee_id' => $userId,
                    ];
                    if ($stageId !== null) {
                        $routeParameters['stage_id'] = $stageId;
                    }

                    return [
                        'id' => $lead->getKey(),
                        'name' => $lead->name,
                        'company_name' => $lead->company_name,
                        'status' => $lead->status?->localizedName(),
                        'due_at' => $lead->next_follow_up_at->toIso8601String(),
                        'bucket' => $bucket,
                        'action_url' => route('v2.tasks.daily', $routeParameters, false),
                    ];
                })->values();

                $overdue = $items->where('bucket', 'overdue')->count();
                $today = $items->where('bucket', 'today')->count();

                return [
                    'stage' => [
                        'id' => $stageId,
                        'name' => $stage?->localizedName() ?? __('crm.stage_not_specified'),
                        'color' => $stageColor,
                        'position' => $stage?->position ?? PHP_INT_MAX,
                    ],
                    'counts' => [
                        'overdue' => $overdue,
                        'today' => $today,
                        'total' => $items->count(),
                    ],
                    'items' => $items,
                ];
            })
            ->sortBy(static fn (array $group): int => (int) $group['stage']['position'])
            ->values();

        return response()->json([
            'data' => $groups,
            'items' => $attentionItems,
            'meta' => [
                'overdue' => $overdueCount,
                'today' => $todayCount,
                'tomorrow' => $tomorrowCount,
                'later' => $laterCount,
                'total' => $totalAttention,
                'total_all' => $totalAll,
                'filter' => $filter ?: 'attention',
                'total_for_filter' => $totalForFilter,
                'limit' => $limit,
                'has_more' => $totalForFilter > count($attentionItems),
                'more_count' => max(0, $totalForFilter - count($attentionItems)),
                'view_all_url' => route('v2.tasks.daily', ['scope' => in_array($filter, ['overdue', 'today', 'tomorrow', 'later'], true) ? $filter : 'all', 'employee_id' => $userId]),
                'timezone' => (string) config('app.timezone'),
                'as_of' => $now->toIso8601String(),
                'truncated' => $totalAttention > $leads->count(),
            ],
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        $record->markAsRead();

        return response()->json(['success' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function snooze(Request $request, string $notification): JsonResponse
    {
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:5', 'max:10080'],
        ]);
        $snoozedUntil = now()
            ->addMinutes((int) $validated['minutes'])
            ->startOfMinute();

        DB::transaction(function () use ($request, $notification, $snoozedUntil): void {
            $record = $this->findNotification($request, $notification);
            $occurrence = NotificationOccurrence::query()
                ->where('notification_id', $record->id)
                ->where('recipient_user_id', $request->user()->getKey())
                ->firstOrFail();

            abort_if(str_starts_with($occurrence->event_key, 'system.'), 422);

            NotificationOccurrence::query()
                ->pending()
                ->where('notification_rule_id', $occurrence->notification_rule_id)
                ->where('source_kind', $occurrence->source_kind)
                ->where('source_id', $occurrence->source_id)
                ->where('recipient_user_id', $occurrence->recipient_user_id)
                ->where('trigger_at', '!=', $snoozedUntil)
                ->update([
                    'status' => NotificationOccurrence::STATUS_CANCELED,
                    'canceled_at' => now(),
                ]);

            NotificationOccurrence::query()->updateOrCreate([
                'notification_rule_id' => $occurrence->notification_rule_id,
                'source_kind' => $occurrence->source_kind,
                'source_id' => $occurrence->source_id,
                'recipient_user_id' => $occurrence->recipient_user_id,
                'trigger_at' => $snoozedUntil,
            ], [
                'event_key' => $occurrence->event_key,
                'source_due_at' => $occurrence->source_due_at,
                'priority' => $occurrence->priority,
                'status' => NotificationOccurrence::STATUS_PENDING,
                'payload' => $occurrence->payload,
                'canceled_at' => null,
                'dispatched_at' => null,
            ]);

            $occurrence->update(['snoozed_until' => $snoozedUntil]);
            $record->markAsRead();
        });

        return response()->json([
            'success' => true,
            'snoozed_until' => $snoozedUntil->toIso8601String(),
        ]);
    }

    public function destroy(Request $request, string $notification): JsonResponse
    {
        $record = $this->findNotification($request, $notification);
        $dismissed = NotificationOccurrence::query()
            ->where('notification_id', $record->id)
            ->where('recipient_user_id', $request->user()->getKey())
            ->update(['dismissed_at' => now()]);

        if ($dismissed === 0) {
            $record->delete();
        } else {
            $record->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    private function findNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}
