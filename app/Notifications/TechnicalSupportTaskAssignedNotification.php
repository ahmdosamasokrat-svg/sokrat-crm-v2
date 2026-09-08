<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\TechnicalSupportTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TechnicalSupportTaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TechnicalSupportTask $task,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        /** @var User $notifiable */
        $locale = in_array($notifiable->locale, ['ar', 'en'], true)
            ? $notifiable->locale
            : (string) config('app.locale', 'ar');
        $priority = match ($this->task->priority) {
            TechnicalSupportTask::PRIORITY_HIGH => 'urgent',
            TechnicalSupportTask::PRIORITY_NORMAL => 'important',
            default => 'normal',
        };

        return [
            'title' => trans('crm.support_task_assigned_notification', [], $locale),
            'body' => trans('crm.support_task_assigned_notification_body', [
                'title' => $this->task->title,
                'date' => $this->task->due_date?->format('Y-m-d')
                    ?? trans('crm.not_specified', [], $locale),
            ], $locale),
            'priority' => $priority,
            'event_key' => 'support_task.assigned',
            'source_kind' => 'technical_support_task',
            'source_id' => (int) $this->task->getKey(),
            'source_name' => $this->task->title,
            'action_url' => route('v2.technical-support.tasks.index', [
                'status' => 'all',
                'task_id' => $this->task->getKey(),
            ], false)
                .'#task-'.$this->task->getKey(),
            'color' => $this->task->color,
        ];
    }
}
