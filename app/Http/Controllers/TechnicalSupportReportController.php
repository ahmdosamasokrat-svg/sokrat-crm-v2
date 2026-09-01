<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TechnicalSupportDevice;
use App\Models\TechnicalSupportTicket;
use App\Models\User;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TechnicalSupportReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        CrmDatabaseGuard::ensureConnected();
        $user = $request->user();

        $validated = $request->validate([
            'employee_id' => ['nullable', 'integer', 'min:1', 'exists:users,id'],
            'device_key' => ['nullable', 'string', 'max:120'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $filters = [
            'employee_id' => isset($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            'device_key' => trim((string) ($validated['device_key'] ?? '')) ?: null,
            'company_name' => trim((string) ($validated['company_name'] ?? '')) ?: null,
            'from_date' => $validated['from_date'] ?? null,
            'to_date' => $validated['to_date'] ?? null,
        ];

        if (! $user->isSuperAdmin()) {
            $filters['employee_id'] = (int) $user->getKey();
        }

        $employees = User::query()
            ->when(
                ! $user->isSuperAdmin(),
                static fn ($query) => $query->whereKey($user->getKey()),
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $devices = TechnicalSupportDevice::query()
            ->accessibleTo($user)
            ->where('is_hidden', false)
            ->orderBy('name')
            ->get(['id', 'device_key', 'name', 'company_name']);

        $companies = $devices
            ->pluck('company_name')
            ->filter(static fn ($company): bool => trim((string) $company) !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $reportQuery = TechnicalSupportTicket::query()
            ->accessibleTo($user)
            ->where('status', TechnicalSupportTicket::STATUS_CLOSED)
            ->whereNotNull('closed_at')
            ->with([
                'closedBy:id,name',
                'device:id,device_key,name,company_name',
            ]);

        if ($filters['employee_id'] !== null) {
            $reportQuery->where('closed_by_user_id', $filters['employee_id']);
        }

        if ($filters['device_key'] !== null) {
            $reportQuery->where('device_key', $filters['device_key']);
        }

        if ($filters['company_name'] !== null) {
            $reportQuery->whereHas('device', static fn ($query) => $query
                ->where('company_name', $filters['company_name']));
        }

        if ($filters['from_date'] !== null) {
            $reportQuery->whereDate('closed_at', '>=', $filters['from_date']);
        }

        if ($filters['to_date'] !== null) {
            $reportQuery->whereDate('closed_at', '<=', $filters['to_date']);
        }

        $allTickets = $reportQuery
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->get();

        $employeeSummaries = $allTickets
            ->groupBy(static fn (TechnicalSupportTicket $ticket): string => (string) ($ticket->closed_by_user_id ?? 'former'))
            ->map(static function ($employeeTickets): array {
                /** @var TechnicalSupportTicket $firstTicket */
                $firstTicket = $employeeTickets->first();
                $totalSeconds = (int) $employeeTickets->sum(
                    static fn (TechnicalSupportTicket $ticket): int => $ticket->supportTimeSeconds() ?? 0,
                );
                $servers = $employeeTickets
                    ->map(static fn (TechnicalSupportTicket $ticket): array => [
                        'device_key' => $ticket->device_key,
                        'name' => $ticket->device?->name ?? $ticket->device_key,
                        'company_name' => $ticket->device?->company_name,
                    ])
                    ->unique('device_key')
                    ->values();

                return [
                    'employee_id' => $firstTicket->closed_by_user_id,
                    'employee_name' => $firstTicket->closedBy?->name ?? __('crm.employee_unavailable'),
                    'ticket_count' => $employeeTickets->count(),
                    'server_count' => $servers->count(),
                    'servers' => $servers,
                    'tickets' => $employeeTickets->values(),
                    'total_seconds' => $totalSeconds,
                    'average_seconds' => $employeeTickets->isNotEmpty()
                        ? (int) round($totalSeconds / $employeeTickets->count())
                        : 0,
                ];
            })
            ->sortBy([
                ['ticket_count', 'desc'],
                ['employee_name', 'asc'],
            ])
            ->values();

        $totalSeconds = (int) $allTickets->sum(
            static fn (TechnicalSupportTicket $ticket): int => $ticket->supportTimeSeconds() ?? 0,
        );
        $metrics = [
            'tickets' => $allTickets->count(),
            'employees' => $employeeSummaries->count(),
            'servers' => $allTickets->pluck('device_key')->unique()->count(),
            'total_seconds' => $totalSeconds,
            'average_seconds' => $allTickets->isNotEmpty()
                ? (int) round($totalSeconds / $allTickets->count())
                : 0,
        ];

        $performanceCharts = [
            'tickets' => $employeeSummaries
                ->map(static fn (array $summary): array => [
                    'employee_name' => $summary['employee_name'],
                    'value' => $summary['ticket_count'],
                ])
                ->all(),
            'duration' => $employeeSummaries
                ->sortBy([
                    ['total_seconds', 'desc'],
                    ['employee_name', 'asc'],
                ])
                ->map(static fn (array $summary): array => [
                    'employee_name' => $summary['employee_name'],
                    'value' => $summary['total_seconds'],
                ])
                ->values()
                ->all(),
        ];

        $page = max(1, $request->integer('page', 1));
        $perPage = 25;
        $tickets = new LengthAwarePaginator(
            $allTickets->forPage($page, $perPage)->values(),
            $allTickets->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ],
        );
        $tickets->appends($request->except('page'));

        return view('technical-support.reports', [
            'companies' => $companies,
            'devices' => $devices,
            'employeeSummaries' => $employeeSummaries,
            'employees' => $employees,
            'filters' => $filters,
            'metrics' => $metrics,
            'performanceCharts' => $performanceCharts,
            'tickets' => $tickets,
        ]);
    }
}
