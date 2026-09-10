(() => {
 'use strict';

 const centerInstances = Array.from(document.querySelectorAll('#crmNotificationCenter'));
 if (!centerInstances.length) return;
 const center = centerInstances[0];
 for (let i = 1; i < centerInstances.length; i++) {
  centerInstances[i].remove();
 }
 if (center.parentElement && center.parentElement !== document.body) {
  document.body.appendChild(center);
 }

 const triggers = () => Array.from(document.querySelectorAll('.crm-notification-trigger, #crmNotificationTrigger'));
 const badges = () => Array.from(document.querySelectorAll('.crm-notification-badge, #crmNotificationBadge'));

 const getDrawer = () => document.getElementById('crmNotificationDrawer') || center.querySelector('#crmNotificationDrawer');
 const getTaskList = () => document.getElementById('crmNotificationTaskList') || center.querySelector('#crmNotificationTaskList');
 const getTotalBadge = () => document.getElementById('crmNotificationTasksTotal') || center.querySelector('#crmNotificationTasksTotal');
 const getToast = () => document.getElementById('crmNotificationToast') || center.querySelector('#crmNotificationToast');
 const getToastTitle = () => document.getElementById('crmNotificationToastTitle') || center.querySelector('#crmNotificationToastTitle');
 const getToastBody = () => document.getElementById('crmNotificationToastBody') || center.querySelector('#crmNotificationToastBody');
 const getAttentionSummary = () => document.getElementById('crmAttentionSummary') || center.querySelector('#crmAttentionSummary');
 const getAttentionViewAll = () => document.getElementById('crmAttentionViewAll') || center.querySelector('#crmAttentionViewAll');

 const getCountOverdue = () => document.getElementById('countOverdue') || center.querySelector('#countOverdue');
 const getCountToday = () => document.getElementById('countToday') || center.querySelector('#countToday');
 const getCountTomorrow = () => document.getElementById('countTomorrow') || center.querySelector('#countTomorrow');
 const getCountLater = () => document.getElementById('countLater') || center.querySelector('#countLater');

 const config = center.dataset;
 const locale = config.locale === 'en' ? 'en' : 'ar';
 const pollMs = Math.max(15, Number(config.pollSeconds || 60)) * 1000;

 let activeFilter = 'today';
 let unreadCount = null;
 let tasksLoading = false;
 let toastTimer = null;

 const request = async (url, options = {}) => {
  const response = await fetch(url, {
   credentials: 'same-origin',
   ...options,
   headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.method && options.method !== 'GET' ? {
     'Content-Type': 'application/json',
     'X-CSRF-TOKEN': config.csrf,
    } : {}),
    ...(options.headers || {}),
   },
  });

  if (!response.ok) {
   const payload = await response.json().catch(() => ({}));
   throw new Error(payload.message || config.labelError || 'Error loading data');
  }

  return response.status === 204 ? {} : response.json();
 };

 const setBadge = count => {
  const safeCount = Math.max(0, Number(count || 0));
  badges().forEach(badgeNode => {
   badgeNode.textContent = safeCount > 99 ? '99+' : String(safeCount);
   badgeNode.hidden = safeCount === 0;
  });
  triggers().forEach(triggerNode => {
   triggerNode.setAttribute('aria-label', `${triggerNode.dataset.label || 'Notifications'} (${safeCount})`);
  });
 };

 const showToast = item => {
  if (!item || center.classList.contains('is-open')) return;
  const toastTitle = getToastTitle();
  const toastBody = getToastBody();
  const toast = getToast();
  if (toastTitle) toastTitle.textContent = item.title;
  if (toastBody) toastBody.textContent = item.body;
  if (toast) {
   toast.hidden = false;
   clearTimeout(toastTimer);
   toastTimer = setTimeout(() => { toast.hidden = true; }, 5500);
  }
 };

 const resolveActionUrl = rawUrl => {
  if (!rawUrl || typeof rawUrl !== 'string') return '';
  try {
   const parsed = new URL(rawUrl, window.location.origin);
   if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1' || parsed.origin === window.location.origin) {
    return `${parsed.pathname}${parsed.search}${parsed.hash}`;
   }
   return parsed.href;
  } catch (_) {
   return rawUrl;
  }
 };

 const stateNode = (icon, title, body = '') => {
  const state = document.createElement('div');
  state.className = 'crm-notification-state is-compact';
  const iconNode = document.createElement('i');
  iconNode.className = `bi ${icon}`;
  iconNode.setAttribute('aria-hidden', 'true');
  const strong = document.createElement('strong');
  strong.textContent = title;
  state.append(iconNode, strong);
  if (body) {
   const span = document.createElement('span');
   span.textContent = body;
   state.append(span);
  }
  return state;
 };

 const createAttentionItem = item => {
  const a = document.createElement('a');
  a.className = 'crm-attention-item';
  a.href = resolveActionUrl(item.action_url) || resolveActionUrl(item.lead_url) || '#';

  const top = document.createElement('div');
  top.className = 'crm-attention-item-top';
  const name = document.createElement('strong');
  name.className = 'crm-attention-item-name';
  name.textContent = item.name || '';
  const badge = document.createElement('span');
  badge.className = `crm-attention-item-badge ${item.bucket_class || 'badge-' + item.bucket}`;
  badge.textContent = item.bucket_label || item.status || '';
  top.append(name, badge);

  const bottom = document.createElement('div');
  bottom.className = 'crm-attention-item-bottom';
  const meta = document.createElement('div');
  meta.className = 'crm-attention-item-meta';

  if (item.stage_name) {
   const stage = document.createElement('span');
   stage.className = 'crm-attention-item-stage';
   stage.style.setProperty('--stage-color', item.stage_color || '#64748b');
   stage.textContent = item.stage_name;
   meta.append(stage);
  }
  if (item.employee_name && item.employee_name !== '—') {
   const emp = document.createElement('span');
   emp.className = 'crm-attention-item-emp';
   emp.textContent = item.employee_name;
   meta.append(emp);
  }

  const time = document.createElement('time');
  time.className = 'crm-attention-item-time';
  time.setAttribute('dir', 'ltr');

  let dueDisplay = '';
  if (item.due_date && item.due_time) {
   dueDisplay = `${item.due_date} - ${item.due_time}`;
  } else if (item.due_datetime) {
   dueDisplay = item.due_datetime;
  } else if (item.due_formatted) {
   dueDisplay = item.due_formatted;
  } else {
   dueDisplay = item.due_time || item.due_date || '';
  }

  time.textContent = dueDisplay;
  bottom.append(meta, time);

  a.append(top, bottom);
  return a;
 };

 const loadAttentionTasks = async (filterToLoad = activeFilter) => {
  const taskList = getTaskList();
  if (!config.dueFollowupsUrl || !taskList || tasksLoading) return;
  tasksLoading = true;
  activeFilter = filterToLoad;

  // Update active pill UI
  center.querySelectorAll('[data-attention-filter]').forEach(button => {
   const isActive = button.dataset.attentionFilter === activeFilter;
   button.classList.toggle('active', isActive);
   button.setAttribute('aria-selected', isActive ? 'true' : 'false');
  });

  taskList.replaceChildren(stateNode('bi-arrow-repeat', config.labelTasksLoading || (locale === 'ar' ? 'جاري التحميل...' : 'Loading...')));

  try {
   const payload = await request(`${config.dueFollowupsUrl}?filter=${encodeURIComponent(activeFilter)}&limit=5`);
   const meta = payload.meta || {};

   const countOverdue = getCountOverdue();
   const countToday = getCountToday();
   const countTomorrow = getCountTomorrow();
   const countLater = getCountLater();
   const totalBadge = getTotalBadge();
   const attentionSummary = getAttentionSummary();
   const attentionViewAll = getAttentionViewAll();

   // Update pill counts
   if (countOverdue) countOverdue.textContent = String(meta.overdue ?? 0);
   if (countToday) countToday.textContent = String(meta.today ?? 0);
   if (countTomorrow) countTomorrow.textContent = String(meta.tomorrow ?? 0);
   if (countLater) countLater.textContent = String(meta.later ?? 0);

   // Update top total attention count (overdue + today)
   const totalAttention = Number(meta.total ?? 0);
   if (totalBadge) {
    totalBadge.textContent = totalAttention > 99 ? '99+' : String(totalAttention);
    totalBadge.hidden = totalAttention === 0;
   }
   setBadge(totalAttention);

   const items = payload.items || [];
   const totalForFilter = Number(meta.total_for_filter ?? items.length);

   if (!items.length) {
    taskList.replaceChildren(stateNode('bi-check2-circle', config.labelTasksEmpty || (locale === 'ar' ? 'لا توجد مهام في هذه الفترة' : 'No tasks in this period')));
    if (attentionSummary) {
     attentionSummary.textContent = locale === 'ar' ? 'لا توجد مهام في هذه الفترة' : 'No tasks in this period';
    }
    if (attentionViewAll) {
     attentionViewAll.href = meta.view_all_url || (config.dailyTasksUrl ? `${config.dailyTasksUrl}?scope=${activeFilter}` : '#');
    }
    return;
   }

   const fragment = document.createDocumentFragment();
   items.slice(0, 5).forEach(item => fragment.append(createAttentionItem(item)));
   taskList.replaceChildren(fragment);

   // Update footer summary and View All link
   if (attentionSummary) {
    attentionSummary.textContent = locale === 'ar'
     ? `عرض ${Math.min(5, items.length)} من أصل ${totalForFilter}`
     : `Showing ${Math.min(5, items.length)} of ${totalForFilter}`;
   }

   if (attentionViewAll) {
    attentionViewAll.href = meta.view_all_url || (config.dailyTasksUrl ? `${config.dailyTasksUrl}?scope=${activeFilter}` : '#');
   }
  } catch (error) {
   taskList.replaceChildren(stateNode('bi-exclamation-circle', config.labelError || 'Error', error.message));
  } finally {
   tasksLoading = false;
  }
 };

 const refreshCount = async () => {
  if (document.hidden) return;
  try {
   if (config.countUrl) {
    const payload = await request(config.countUrl);
    const nextCount = Number(payload.count || 0);
    unreadCount = nextCount;
   }
   if (center.classList.contains('is-open')) {
    loadAttentionTasks(activeFilter);
   } else if (config.dueFollowupsUrl) {
    const payload = await request(`${config.dueFollowupsUrl}?limit=1`);
    const totalAttention = Number(payload.meta?.total ?? 0);
    setBadge(totalAttention);
   }
  } catch (_) {}
 };

 const openCenter = () => {
  center.classList.add('is-open');
  document.body.classList.add('crm-notification-open');
  const drawer = getDrawer();
  if (drawer) drawer.setAttribute('aria-hidden', 'false');
  const backdrop = center.querySelector('[data-notification-close]');
  if (backdrop) backdrop.hidden = false;
  
  loadAttentionTasks(activeFilter);
  setTimeout(() => {
   const closeBtn = drawer?.querySelector('.crm-notification-close');
   if (closeBtn) closeBtn.focus();
  }, 0);
 };

 let lastActiveTrigger = null;
 const closeCenter = () => {
  center.classList.remove('is-open');
  document.body.classList.remove('crm-notification-open');
  const drawer = getDrawer();
  if (drawer) drawer.setAttribute('aria-hidden', 'true');
  const backdrop = center.querySelector('.crm-notification-backdrop');
  if (backdrop) backdrop.hidden = true;
  if (lastActiveTrigger && typeof lastActiveTrigger.focus === 'function') {
   lastActiveTrigger.focus();
  } else {
   const firstTrigger = triggers()[0];
   if (firstTrigger) firstTrigger.focus();
  }
 };

 document.addEventListener('click', event => {
  const target = event.target.closest('.crm-notification-trigger, #crmNotificationTrigger');
  if (target) {
   event.preventDefault();
   lastActiveTrigger = target;
   openCenter();
  }
 });

 center.querySelectorAll('[data-notification-close]').forEach(node => node.addEventListener('click', closeCenter));

 center.querySelectorAll('[data-attention-filter]').forEach(button => {
  button.addEventListener('click', () => {
   const selectedFilter = button.dataset.attentionFilter;
   if (selectedFilter) {
    loadAttentionTasks(selectedFilter);
   }
  });
 });

 document.addEventListener('keydown', event => {
  if (!center.classList.contains('is-open')) return;
  if (event.key === 'Escape') {
   closeCenter();
   return;
  }
  if (event.key !== 'Tab') return;
  const drawer = getDrawer();
  const focusable = [...drawer.querySelectorAll('button:not([disabled]),a[href],select:not([disabled])')]
   .filter(node => !node.hidden && node.offsetParent !== null);
  if (!focusable.length) return;
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) {
   event.preventDefault();
   last.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
   event.preventDefault();
   first.focus();
  }
 });

 document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshCount(); });

 refreshCount();
 setInterval(refreshCount, pollMs);
})();
