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
 data-label-tasks-overdue="{{ __('crm.notification_tasks_overdue') }}"
 data-label-tasks-today="{{ __('crm.notification_tasks_today') }}"
 data-label-tasks-truncated="{{ __('crm.notification_tasks_truncated') }}"
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
   <div>
    <h2 id="crmNotificationTitle">{{ __('crm.notifications') }}</h2>
    <p>{{ __('crm.notification_center_subtitle') }}</p>
   </div>
   <button class="crm-notification-close" type="button" data-notification-close aria-label="{{ __('crm.close') }}">
    <i class="bi bi-x-lg" aria-hidden="true"></i>
   </button>
  </header>

  @can('tasks.view')
   <section class="crm-notification-tasks" aria-labelledby="crmNotificationTasksTitle">
    <header class="crm-notification-tasks-head">
     <div>
      <h3 id="crmNotificationTasksTitle"><i class="bi bi-list-check" aria-hidden="true"></i>{{ __('crm.notification_my_tasks') }} <span id="crmNotificationTasksTotal" hidden>0</span></h3>
      <p>{{ __('crm.notification_my_tasks_desc') }}</p>
     </div>
     <a href="{{ route('v2.tasks.daily', ['employee_id' => auth()->id()]) }}">{{ __('crm.notification_view_all_tasks') }}</a>
    </header>
    <div class="crm-notification-task-list" id="crmNotificationTaskList" aria-live="polite"></div>
   </section>
  @endcan

  <div class="crm-notification-controls">
   <div class="crm-notification-tabs" role="tablist" aria-label="{{ __('crm.notification_filter') }}">
    <button class="active" type="button" data-notification-filter="unread" role="tab" aria-selected="true">{{ __('crm.unread') }}</button>
    <button type="button" data-notification-filter="all" role="tab" aria-selected="false">{{ __('crm.all') }}</button>
   </div>
   <button class="crm-notification-read-all" type="button" id="crmNotificationReadAll">{{ __('crm.mark_all_read') }}</button>
  </div>

  <div class="crm-notification-list" id="crmNotificationList" aria-live="polite"></div>

  <footer class="crm-notification-footer">
   <button type="button" id="crmNotificationLoadMore" hidden>{{ __('crm.load_more') }}</button>
   <a href="{{ route('v2.notifications.preferences.edit') }}">
    <i class="bi bi-sliders" aria-hidden="true"></i>
    {{ __('crm.notification_preferences') }}
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
