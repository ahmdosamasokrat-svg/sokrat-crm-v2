<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
class CalendarEventRepository
{
    /**
     * Get accessible calendar events with filters.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getEventsForUser(
        User $user,
        ?string $start = null,
        ?string $end = null,
        array $filters = []
    ): Collection {
        $query = CalendarEvent::query()
            ->with([
                'user:id,name',
                'lead:id,name,company_name,phone,lead_status_id,assigned_user_id',
                'lead.status:id,pipeline_stage_id,code,name_ar,color',
                'lead.status.stage:id,code,name_ar,color',
            ])
            ->accessibleTo($user);

        if ($start && $end) {
            $query->between($start, $end);
        }

        if (! empty($filters['type'])) {
            $query->ofType((string) $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->withStatus((string) $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->forUser((int) $filters['user_id']);
        }

        if (! empty($filters['lead_id'])) {
            $query->forLead((int) $filters['lead_id']);
        }

        if (! empty($filters['stage_id'])) {
            $query->whereHas('lead.status', static function (Builder $q) use ($filters): void {
                $q->where('pipeline_stage_id', (int) $filters['stage_id']);
            });
        }

        return $query->orderBy('start_time', 'asc')->get();
    }

    /**
     * Get accessible leads with scheduled followups (calls) within date range.
     *
     * @return Collection<int, Lead>
     */
    public function getLeadFollowupsForUser(
        User $user,
        ?string $start = null,
        ?string $end = null,
        array $filters = []
    ): Collection {
        // If type filter is set and not 'call', lead followups are not included
        if (! empty($filters['type']) && $filters['type'] !== 'call') {
            return new Collection();
        }

        $query = Lead::query()
            ->with([
                'assignedUser:id,name',
                'status:id,pipeline_stage_id,code,name_ar,color',
                'status.stage:id,code,name_ar,color',
            ])
            ->accessibleTo($user)
            ->whereNotNull('next_follow_up_at');

        if ($start && $end) {
            try {
                $startDate = Carbon::parse($start);
                $endDate = Carbon::parse($end);
                $query->whereBetween('next_follow_up_at', [$startDate, $endDate]);
            } catch (\Throwable) {
                // Ignore parse errors and return empty if invalid dates
            }
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'scheduled') {
                $query->where('next_follow_up_at', '>=', now());
            } elseif ($filters['status'] === 'overdue') {
                $query->where('next_follow_up_at', '<', now());
            } elseif ($filters['status'] === 'completed' || $filters['status'] === 'canceled') {
                return new Collection();
            }
        }

        if (! empty($filters['stage_id'])) {
            $query->whereHas('status', static function (Builder $q) use ($filters): void {
                $q->where('pipeline_stage_id', (int) $filters['stage_id']);
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('assigned_user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['lead_id'])) {
            $query->where('id', (int) $filters['lead_id']);
        }

        return $query->orderBy('next_follow_up_at', 'asc')->get();
    }

    /**
     * Get upcoming reminders for user.
     *
     * @return Collection<int, CalendarEvent>
     */
    public function getUpcomingReminders(User $user, int $minutes = 30): Collection
    {
        return CalendarEvent::query()
            ->with(['user:id,name', 'lead:id,name,company_name,phone'])
            ->accessibleTo($user)
            ->upcomingReminders($minutes)
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Find event by ID if accessible by user.
     */
    public function findForUser(User $user, int $id): ?CalendarEvent
    {
        return CalendarEvent::query()
            ->with(['user:id,name', 'lead:id,name,company_name,phone'])
            ->accessibleTo($user)
            ->find($id);
    }

    /**
     * Create a new calendar event.
     */
    public function create(array $data): CalendarEvent
    {
        return CalendarEvent::query()->create([
            'user_id' => $data['user_id'],
            'lead_id' => $data['lead_id'] ?? null,
            'title' => trim((string) $data['title']),
            'description' => isset($data['description']) ? trim((string) $data['description']) : null,
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'type' => $data['type'] ?? 'meeting',
            'status' => $data['status'] ?? 'scheduled',
        ]);
    }

    /**
     * Update an existing calendar event.
     */
    public function update(CalendarEvent $event, array $data): CalendarEvent
    {
        $updateData = [];

        if (array_key_exists('title', $data)) {
            $updateData['title'] = trim((string) $data['title']);
        }
        if (array_key_exists('description', $data)) {
            $updateData['description'] = $data['description'] !== null ? trim((string) $data['description']) : null;
        }
        if (array_key_exists('start_time', $data)) {
            $updateData['start_time'] = $data['start_time'];
        }
        if (array_key_exists('end_time', $data)) {
            $updateData['end_time'] = $data['end_time'];
        }
        if (array_key_exists('type', $data)) {
            $updateData['type'] = $data['type'];
        }
        if (array_key_exists('status', $data)) {
            $updateData['status'] = $data['status'];
        }
        if (array_key_exists('lead_id', $data)) {
            $updateData['lead_id'] = $data['lead_id'];
        }
        if (array_key_exists('user_id', $data)) {
            $updateData['user_id'] = $data['user_id'];
        }

        $event->update($updateData);

        return $event->fresh(['user:id,name', 'lead:id,name,company_name,phone']);
    }

    /**
     * Delete a calendar event.
     */
    public function delete(CalendarEvent $event): bool
    {
        return (bool) $event->delete();
    }
}
