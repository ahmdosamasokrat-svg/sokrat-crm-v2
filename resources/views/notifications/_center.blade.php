@once('crm-notification-center')
<link rel="stylesheet" href="{{ asset('crm-notifications.css') }}?v={{ filemtime(public_path('crm-notifications.css')) }}">

<div
 id="crmNotificationCenter"
 class="crm-notification-center"
 data-csrf="{{ csrf_token() }}"
 data-locale="{{ app()->getLocale() }}"
 data-index-url="{{ route('v2.notifications.index') }}"
 data-count-url="{{ route('v2.notifications.unread-count') }}"
 @can('tasks.view') data-due-followups-url="{{ route('v2.notifications.due-followups') }}" @endcan
 data-read-all-url="{{ route('v2.notifications.read-all') }}"
 data-base-url="{{ url('/notifications') }}"
 data-preferences-url="{{ route('v2.notifications.preferences.edit') }}"
 data-poll-seconds="{{ max(15, (int) config('crm_notifications.poll_seconds', 60)) }}"
 data-label-loading="{{ __('crm.notification_loading') }}"
 data-label-empty="{{ __('crm.notification_empty') }}"
 data-label-error="{{ __('crm.notification_load_error') }}"
 data-label-open="{{ __('crm.notification_open') }}"
 data-label-dismiss="{{ __('crm.notification_dismiss') }}"
 data-label-snooze="{{ __('crm.notification_snooze') }}"
 data-label-snoozed="{{ __('crm.notification_snoozed') }}"
 data-label-load-more="{{ __('crm.load_more') }}"
 data-label-tasks-loading="{{ __('crm.notification_tasks_loading') }}"
 data-label-tasks-empty="{{ __('crm.notification_tasks_empty') }}"
 data-label-tasks-overdue="{{ __('crm.overdue_short') }}"
 data-label-tasks-today="{{ __('crm.today') }}"
 data-label-tasks-tomorrow="{{ __('crm.tomorrow') }}"
 data-label-tasks-later="{{ __('crm.later') }}"
 data-label-tasks-truncated="{{ __('crm.notification_tasks_truncated') }}"
 data-label-view-all="{{ __('crm.notification_view_all_tasks') }}"
 data-daily-tasks-url="{{ route('v2.tasks.daily', ['employee_id' => auth()->id()]) }}"
>
 <div class="crm-notification-backdrop" data-notification-close hidden></div>
 <section
  class="crm-notification-drawer"
  id="crmNotificationDrawer"
  role="dialog"
  aria-modal="true"
  aria-labelledby="crmNotificationTitle"
  aria-hidden="true"
 >
  <header class="crm-notification-head">
   <div class="crm-notification-title-group">
    <h2 id="crmNotificationTitle">
     <i class="bi bi-bell-fill" style="color: #ec4899;" aria-hidden="true"></i>
     {{ __('crm.notifications') }}
     <span class="crm-attention-total-badge" id="crmNotificationTasksTotal" hidden>0</span>
    </h2>
    <p>{{ __('crm.notification_center_subtitle') }}</p>
   </div>
   <button class="crm-notification-close" type="button" data-notification-close aria-label="{{ __('crm.close') }}">
    <i class="bi bi-x-lg" aria-hidden="true"></i>
   </button>
  </header>

  <div class="crm-attention-filters" role="tablist" aria-label="{{ __('crm.notification_filter') }}">
   <button class="crm-attention-pill is-overdue" type="button" data-attention-filter="overdue" role="tab" aria-selected="false">
    <span class="pill-dot dot-overdue"></span>
    <span class="pill-label">{{ __('crm.overdue_short') }}</span>
    <span class="pill-count" id="countOverdue">0</span>
   </button>
   <button class="crm-attention-pill is-today active" type="button" data-attention-filter="today" role="tab" aria-selected="true">
    <span class="pill-dot dot-today"></span>
    <span class="pill-label">{{ __('crm.today') }}</span>
    <span class="pill-count" id="countToday">0</span>
   </button>
   <button class="crm-attention-pill is-tomorrow" type="button" data-attention-filter="tomorrow" role="tab" aria-selected="false">
    <span class="pill-dot dot-tomorrow"></span>
    <span class="pill-label">{{ __('crm.tomorrow') }}</span>
    <span class="pill-count" id="countTomorrow">0</span>
   </button>
   <button class="crm-attention-pill is-later" type="button" data-attention-filter="later" role="tab" aria-selected="false">
    <span class="pill-dot dot-later"></span>
    <span class="pill-label">{{ __('crm.later') }}</span>
    <span class="pill-count" id="countLater">0</span>
   </button>
  </div>

  <section class="crm-attention-content" aria-labelledby="crmNotificationTitle">
   <div class="crm-notification-task-list crm-attention-list" id="crmNotificationTaskList" aria-live="polite"></div>
  </section>

  <footer class="crm-attention-footer" id="crmAttentionFooter">
   <span class="crm-attention-summary" id="crmAttentionSummary"></span>
   <a href="{{ route('v2.tasks.daily', ['employee_id' => auth()->id()]) }}" class="crm-attention-view-all" id="crmAttentionViewAll">
    <span>{{ __('crm.notification_view_all_tasks') }}</span>
    <i class="bi bi-arrow-{{ app()->getLocale() === 'en' ? 'right' : 'left' }}"></i>
   </a>
  </footer>
 </section>

 <div class="crm-notification-toast" id="crmNotificationToast" role="status" aria-live="polite" hidden>
  <strong id="crmNotificationToastTitle"></strong>
  <span id="crmNotificationToastBody"></span>
 </div>
</div>
<script>
(() => {
    const el = document.getElementById('crmNotificationCenter');
    if (el && el.parentElement && el.parentElement !== document.body) {
        document.body.appendChild(el);
    }
})();
</script>

<script src="{{ asset('crm-notifications.js') }}?v={{ filemtime(public_path('crm-notifications.js')) }}" defer></script>
@endonce
