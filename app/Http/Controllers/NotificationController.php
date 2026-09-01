<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\NotificationOccurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

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
        $limit = 100;
        $userId = (int) $user->getKey();

        $dueQuery = Lead::query()
            ->where('assigned_user_id', $userId)
            ->accessibleTo($user)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', $todayEnd);

        $overdueCount = (clone $dueQuery)
            ->where('next_follow_up_at', '<', $todayStart)
            ->count();
        $todayCount = (clone $dueQuery)
            ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
            ->count();
        $total = $overdueCount + $todayCount;

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

        $groups = $leads
            ->groupBy(static fn (Lead $lead): string => (string) ($lead->status?->pipeline_stage_id ?? 'unassigned'))
            ->map(function ($stageLeads) use ($todayStart, $userId): array {
                /** @var Lead $firstLead */
                $firstLead = $stageLeads->first();
                $stage = $firstLead->status?->stage;
                $stageId = $stage?->getKey();
                $stageColor = (string) ($stage?->color ?: $firstLead->status?->color ?: '#64748b');
                if (! preg_match('/^#[0-9a-f]{6}$/i', $stageColor)) {
                    $stageColor = '#64748b';
                }

                $items = $stageLeads->map(function (Lead $lead) use ($todayStart, $stageId, $userId): array {
                    $bucket = $lead->next_follow_up_at->lt($todayStart) ? 'overdue' : 'today';
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
            'meta' => [
                'overdue' => $overdueCount,
                'today' => $todayCount,
                'total' => $total,
                'timezone' => (string) config('app.timezone'),
                'as_of' => $now->toIso8601String(),
                'truncated' => $total > $leads->count(),
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
        NotificationOccurrence::query()
            ->where('notification_id', $record->id)
            ->where('recipient_user_id', $request->user()->getKey())
            ->update(['dismissed_at' => now()]);
        $record->markAsRead();

        return response()->json(['success' => true]);
    }

    private function findNotification(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}
