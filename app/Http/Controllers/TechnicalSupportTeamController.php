<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\TechnicalSupportTask;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TechnicalSupportTeamController extends Controller
{
    private const ONLINE_THRESHOLD_MINUTES = 15;

    public function index(Request $request): View
    {
        CrmDatabaseGuard::ensureConnected();

        $currentUser = $request->user();
        abort_unless($currentUser !== null, 403);

        $onlineThreshold = now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES)->getTimestamp();

        // 1. Get latest session per user (contains IP address and last activity)
        $userSessions = collect();
        if (Schema::hasTable('sessions')) {
            try {
                $userSessions = DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->orderByDesc('last_activity')
                    ->get(['user_id', 'ip_address', 'last_activity'])
                    ->unique('user_id')
                    ->keyBy('user_id');
            } catch (\Throwable) {
                $userSessions = collect();
            }
        }

        // 2. Fetch active tasks count per user
        $userTasksCount = [];
        if (Schema::hasTable('technical_support_tasks')) {
            try {
                $userTasksCount = TechnicalSupportTask::query()
                    ->where('status', '!=', TechnicalSupportTask::STATUS_COMPLETED)
                    ->whereNotNull('assigned_to_user_id')
                    ->select('assigned_to_user_id', DB::raw('COUNT(*) as count'))
                    ->groupBy('assigned_to_user_id')
                    ->pluck('count', 'assigned_to_user_id')
                    ->map(static fn ($val) => (int) $val)
                    ->toArray();
            } catch (\Throwable) {
                $userTasksCount = [];
            }
        }

        // 3. Fetch all active users with their groups
        $usersQuery = User::query()
            ->where('is_active', true)
            ->with(['groups:id,name,code']);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $usersQuery->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('voip_extension', 'like', "%{$search}%")
                    ->orWhere('mobile_phone', 'like', "%{$search}%");
            });
        }

        $groupCode = trim((string) $request->query('group', ''));
        if ($groupCode !== '') {
            $usersQuery->whereHas('groups', static function ($q) use ($groupCode): void {
                $q->where('code', $groupCode);
            });
        }

        $users = $usersQuery->orderBy('name')->get();

        // 4. Fetch all currently open tickets
        $openTickets = TechnicalSupportTicket::query()
            ->where('status', TechnicalSupportTicket::STATUS_OPEN)
            ->whereNotNull('opened_by_user_id')
            ->with(['device:id,device_key,name,company_name'])
            ->latest('opened_at')
            ->get()
            ->groupBy('opened_by_user_id');

        // 5. Ticket metrics per user
        $ticketStats = [];
        try {
            $ticketStats = TechnicalSupportTicket::query()
                ->select(
                    'opened_by_user_id',
                    DB::raw('COUNT(*) as total_count'),
                    DB::raw('SUM(CASE WHEN status = "closed" THEN 1 ELSE 0 END) as closed_count'),
                    DB::raw('SUM(CASE WHEN status = "open" THEN 1 ELSE 0 END) as open_count'),
                    DB::raw('AVG(CASE WHEN status = "closed" AND opened_at IS NOT NULL AND closed_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, opened_at, closed_at) ELSE NULL END) as avg_seconds')
                )
                ->whereNotNull('opened_by_user_id')
                ->groupBy('opened_by_user_id')
                ->get()
                ->keyBy('opened_by_user_id');
        } catch (\Throwable) {
            $ticketStats = collect();
        }

        // 6. Build team cards collection
        $cards = $users->map(function (User $user) use ($currentUser, $request, $userSessions, $onlineThreshold, $openTickets, $ticketStats, $userTasksCount): array {
            $session = $userSessions->get($user->id);
            $lastActivity = $session?->last_activity ? (int) $session->last_activity : null;
            $localIp = $session?->ip_address;
            if ($user->id === $currentUser->id && ! $localIp) {
                $localIp = $request->ip();
            }

            $isOnline = ($user->id === $currentUser->id)
                || ($lastActivity !== null && $lastActivity >= $onlineThreshold);

            $userOpenTickets = $openTickets->get($user->id, collect());
            $activeTicket = $userOpenTickets->first();

            $activeTicketData = null;
            if ($activeTicket !== null) {
                $elapsedSeconds = $activeTicket->supportTimeSeconds() ?? 0;
                $timeBand = $activeTicket->supportTimeBand($elapsedSeconds);
                $formattedTime = CarbonInterval::seconds($elapsedSeconds)
                    ->cascade()
                    ->locale(app()->getLocale())
                    ->forHumans(['parts' => 2]);

                $activeTicketData = [
                    'id' => $activeTicket->id,
                    'subject' => $activeTicket->subject,
                    'device_key' => $activeTicket->device_key,
                    'device_name' => $activeTicket->device?->name ?? $activeTicket->device_key,
                    'company_name' => $activeTicket->device?->company_name,
                    'opened_at' => $activeTicket->opened_at,
                    'opened_at_iso' => $activeTicket->opened_at?->toIso8601String(),
                    'elapsed_seconds' => $elapsedSeconds,
                    'time_band' => $timeBand,
                    'formatted_time' => $formattedTime,
                    'open_count' => $userOpenTickets->count(),
                ];
            }

            $userStat = $ticketStats->get($user->id);
            $totalCount = (int) ($userStat->total_count ?? 0);
            $closedCount = (int) ($userStat->closed_count ?? 0);
            $openCount = (int) ($userStat->open_count ?? 0);
            $avgSeconds = $userStat && $userStat->avg_seconds !== null ? (int) round((float) $userStat->avg_seconds) : null;
            $avgFormatted = $avgSeconds !== null
                ? CarbonInterval::seconds($avgSeconds)->cascade()->locale(app()->getLocale())->forHumans(['parts' => 2])
                : null;

            $lastSeenText = null;
            if ($lastActivity !== null) {
                $lastSeenText = Carbon::createFromTimestamp($lastActivity)->locale(app()->getLocale())->diffForHumans();
            } elseif ($user->last_login_at !== null) {
                $lastSeenText = $user->last_login_at->locale(app()->getLocale())->diffForHumans();
            }

            $primaryGroup = $user->groups->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'voip_extension' => $user->voip_extension,
                'mobile_phone' => $user->mobile_phone,
                'local_ip' => $localIp,
                'role_name' => $primaryGroup?->name ?? __('crm.sales-agent'),
                'groups' => $user->groups->pluck('name')->toArray(),
                'is_online' => $isOnline,
                'last_activity_time' => $lastActivity,
                'last_seen_text' => $lastSeenText,
                'has_open_ticket' => $activeTicketData !== null,
                'active_ticket' => $activeTicketData,
                'total_tickets' => $totalCount,
                'closed_tickets' => $closedCount,
                'open_tickets_count' => $openCount,
                'active_tasks_count' => $userTasksCount[$user->id] ?? 0,
                'avg_support_time' => $avgFormatted,
            ];
        });

        // Metrics for summary bar
        $metrics = [
            'total' => $cards->count(),
            'online' => $cards->where('is_online', true)->count(),
            'busy' => $cards->where('has_open_ticket', true)->count(),
            'free' => $cards->where('has_open_ticket', false)->count(),
        ];

        // Status tab filter
        $status = trim((string) $request->query('status', 'all'));
        if ($status === 'online') {
            $cards = $cards->where('is_online', true);
        } elseif ($status === 'has_ticket') {
            $cards = $cards->where('has_open_ticket', true);
        } elseif ($status === 'available') {
            $cards = $cards->where('has_open_ticket', false);
        }

        // Sorting
        $sort = trim((string) $request->query('sort', 'workload'));
        $cards = (match ($sort) {
            'name' => $cards->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE),
            'tickets' => $cards->sortByDesc('total_tickets'),
            'online' => $cards->sortByDesc('is_online'),
            'recent' => $cards->sortByDesc('last_activity_time'),
            default => $cards->sort(static function (array $a, array $b): int {
                if ($a['has_open_ticket'] !== $b['has_open_ticket']) {
                    return $a['has_open_ticket'] ? -1 : 1;
                }
                if ($a['is_online'] !== $b['is_online']) {
                    return $a['is_online'] ? -1 : 1;
                }

                return strcasecmp($a['name'], $b['name']);
            }),
        })->values();

        $groups = Group::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('technical-support.team', compact(
            'cards',
            'metrics',
            'status',
            'search',
            'groupCode',
            'groups',
            'sort',
        ));
    }

    public function show(Request $request, User $user): JsonResponse
    {
        CrmDatabaseGuard::ensureConnected();

        $currentUser = $request->user();
        abort_unless($currentUser !== null, 403);

        $onlineThreshold = now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES)->getTimestamp();

        $session = null;
        if (Schema::hasTable('sessions')) {
            try {
                $session = DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->orderByDesc('last_activity')
                    ->first(['ip_address', 'last_activity']);
            } catch (\Throwable) {
                $session = null;
            }
        }

        $lastActivity = $session?->last_activity ? (int) $session->last_activity : null;
        $localIp = $session?->ip_address;
        if ($user->id === $currentUser->id && ! $localIp) {
            $localIp = $request->ip();
        }

        $isOnline = ($user->id === $currentUser->id)
            || ($lastActivity !== null && $lastActivity >= $onlineThreshold);

        $lastSeenText = null;
        if ($lastActivity !== null) {
            $lastSeenText = Carbon::createFromTimestamp($lastActivity)->locale(app()->getLocale())->diffForHumans();
        } elseif ($user->last_login_at !== null) {
            $lastSeenText = $user->last_login_at->locale(app()->getLocale())->diffForHumans();
        }

        // Load latest 15 tickets handled by this user
        $tickets = TechnicalSupportTicket::query()
            ->where(static function ($q) use ($user): void {
                $q->where('opened_by_user_id', $user->id)
                    ->orWhere('closed_by_user_id', $user->id);
            })
            ->with(['device:id,device_key,name,company_name', 'openedBy:id,name', 'closedBy:id,name'])
            ->latest('opened_at')
            ->take(15)
            ->get();

        $ticketsData = $tickets->map(function (TechnicalSupportTicket $ticket) use ($user): array {
            $elapsedSeconds = $ticket->supportTimeSeconds();
            $timeBand = $ticket->supportTimeBand($elapsedSeconds);
            $durationFormatted = $elapsedSeconds !== null
                ? CarbonInterval::seconds($elapsedSeconds)->cascade()->locale(app()->getLocale())->forHumans(['parts' => 2])
                : null;

            return [
                'id' => $ticket->id,
                'number' => '#' . str_pad((string) $ticket->id, 4, '0', STR_PAD_LEFT),
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'device_key' => $ticket->device_key,
                'device_name' => $ticket->device?->name ?? $ticket->device_key,
                'company_name' => $ticket->device?->company_name,
                'opened_at' => $ticket->opened_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A'),
                'opened_at_iso' => $ticket->opened_at?->toIso8601String(),
                'closed_at' => $ticket->closed_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A'),
                'closed_at_iso' => $ticket->closed_at?->toIso8601String(),
                'elapsed_seconds' => $elapsedSeconds,
                'time_band' => $timeBand,
                'duration_formatted' => $durationFormatted,
                'resolution' => $ticket->resolution,
                'is_opener' => $ticket->opened_by_user_id === $user->id,
                'is_closer' => $ticket->closed_by_user_id === $user->id,
                'view_url' => route('v2.technical-support.cards.show', $ticket->device_key),
            ];
        });

        // Summary stats
        $totalCount = TechnicalSupportTicket::query()
            ->where(static fn ($q) => $q->where('opened_by_user_id', $user->id)->orWhere('closed_by_user_id', $user->id))
            ->count();

        $openCount = TechnicalSupportTicket::query()
            ->where('opened_by_user_id', $user->id)
            ->where('status', TechnicalSupportTicket::STATUS_OPEN)
            ->count();

        $closedCount = TechnicalSupportTicket::query()
            ->where('closed_by_user_id', $user->id)
            ->where('status', TechnicalSupportTicket::STATUS_CLOSED)
            ->count();

        $avgSeconds = TechnicalSupportTicket::query()
            ->where('closed_by_user_id', $user->id)
            ->where('status', TechnicalSupportTicket::STATUS_CLOSED)
            ->whereNotNull('opened_at')
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, opened_at, closed_at)) as avg_sec')
            ->value('avg_sec');

        $avgFormatted = $avgSeconds !== null
            ? CarbonInterval::seconds((int) round((float) $avgSeconds))->cascade()->locale(app()->getLocale())->forHumans(['parts' => 2])
            : null;

        // Recent tasks
        $tasksData = [];
        if (Schema::hasTable('technical_support_tasks')) {
            try {
                $tasksData = TechnicalSupportTask::query()
                    ->where('assigned_to_user_id', $user->id)
                    ->latest('id')
                    ->take(5)
                    ->get(['id', 'title', 'status', 'priority', 'due_date', 'color'])
                    ->map(static fn (TechnicalSupportTask $task): array => [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'due_date' => $task->due_date?->format('Y-m-d'),
                        'color' => $task->color,
                    ])
                    ->toArray();
            } catch (\Throwable) {
                $tasksData = [];
            }
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'voip_extension' => $user->voip_extension,
                'mobile_phone' => $user->mobile_phone,
                'local_ip' => $localIp,
                'groups' => $user->groups->pluck('name')->toArray(),
                'is_online' => $isOnline,
                'last_seen_text' => $lastSeenText,
                'last_login_at' => $user->last_login_at?->locale(app()->getLocale())->translatedFormat('d M Y - h:i A'),
                'created_at' => $user->created_at?->locale(app()->getLocale())->translatedFormat('d M Y'),
            ],
            'stats' => [
                'total_tickets' => $totalCount,
                'open_tickets' => $openCount,
                'closed_tickets' => $closedCount,
                'active_tasks' => count($tasksData),
                'avg_support_time' => $avgFormatted,
            ],
            'tickets' => $ticketsData,
            'tasks' => $tasksData,
        ]);
    }
}
