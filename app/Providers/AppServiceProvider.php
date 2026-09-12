<?php

namespace App\Providers;

use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\PipelineStage;
use App\Models\Quotation;
use App\Models\TechnicalSupportTask;
use App\Models\User;
use App\Observers\CalendarEventNotificationObserver;
use App\Observers\LeadNotificationObserver;
use App\Policies\CalendarEventPolicy;
use App\Policies\LeadFollowupPolicy;
use App\Policies\LeadPolicy;
use App\Policies\QuotationPolicy;
use App\Policies\UserPolicy;
use App\Security\CrmPermission;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(LeadFollowup::class, LeadFollowupPolicy::class);
        Gate::policy(Quotation::class, QuotationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Group::class, GroupPolicy::class);
        Gate::policy(CalendarEvent::class, CalendarEventPolicy::class);

        Lead::observe(LeadNotificationObserver::class);
        CalendarEvent::observe(CalendarEventNotificationObserver::class);

        foreach (CrmPermission::cases() as $permission) {
            Gate::define(
                $permission->value,
                static fn (User $user): bool => $user->hasPermission($permission),
            );
        }

        View::composer(
            'partials.crm-sidebar',
            static function ($view): void {
                $user = auth()->user();
                $totalLeads = $user !== null
                    && Gate::allows(CrmPermission::LEADS_VIEW->value)
                        ? Lead::query()->accessibleTo($user)->count()
                        : 0;

                $totalTasks = $user !== null
                    && Gate::allows(CrmPermission::TASKS_VIEW->value)
                        ? Lead::query()->accessibleTo($user)->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->endOfDay())->count()
                        : 0;

                $sidebarPipelineStages = PipelineStage::getActiveStagesForSidebar($user);
                $crmSidebarHasSupportTasks = $user !== null
                    && Schema::hasTable('technical_support_tasks')
                    && TechnicalSupportTask::query()->accessibleTo($user)->exists();

                $view->with([
                    'totalLeads' => $totalLeads,
                    'totalTasks' => $totalTasks,
                    'sidebarPipelineStages' => $sidebarPipelineStages,
                    'crmSidebarHasSupportTasks' => $crmSidebarHasSupportTasks,
                ]);
            },
        );
    }
}
