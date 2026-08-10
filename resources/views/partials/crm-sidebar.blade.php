@php
    $crmSidebarDashboardActive = request()->routeIs(
        'dashboard'
    );

    $crmSidebarLeadsActive = request()->routeIs(
        'v2.leads',
        'v2.leads.*'
    );

    $crmSidebarTasksActive = request()->routeIs(
        'v2.followups',
        'v2.tasks.*'
    );

    // CRM CAMPAIGNS SIDEBAR V2 START
    $crmSidebarCampaignsActive =
        request()->routeIs(
            'v2.campaigns.*'
        );

    // CRM QUOTATIONS SIDEBAR V2 START
    $crmSidebarQuotationsActive = request()->routeIs(
        'v2.quotations.*'
    );

    $crmSidebarSettingsActive = request()->routeIs(
        'v2.settings'
    );

    $crmSidebarLeadCount = isset($totalLeads)
        ? (int) $totalLeads
        : 0;
@endphp

{{-- CRM SHARED SIDEBAR ASSET V1 START --}}
@once
<link
 rel="stylesheet"
 href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-shared-v1"
>
@endonce
{{-- CRM SHARED SIDEBAR ASSET V1 END --}}

<aside class="crm-side side" id="crmSidebar">
 <a
  class="crm-side-brand brand"
  href="{{ route('dashboard') }}"
 >
  <img
   class="logo"
   src="{{ asset('images/sokrat-pro-tech.png') }}"
   alt="Sokrat PRO"
  >

  <span>
   <strong>SokratCRM</strong>
   <small>إدارة علاقات العملاء</small>
  </span>
 </a>

 <p class="crm-side-caption caption">
  القائمة الرئيسية
 </p>

 <nav class="crm-side-nav nav">
  <a
   class="crm-link link {{ $crmSidebarDashboardActive ? 'active' : '' }}"
   href="{{ route('dashboard') }}"
  >
   <span class="crm-ico ico">⌂</span>
   <span class="crm-label label">لوحة التحكم</span>
  </a>

  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarLeadsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmLeadsMenu"
    data-menu="crmLeadsMenu"
    aria-expanded="{{ $crmSidebarLeadsActive ? 'true' : 'false' }}"
    aria-controls="crmLeadsMenu"
   >
    <span class="crm-ico ico">♙</span>

    <span class="crm-label label">
     العملاء المحتملين
    </span>

    <span class="crm-count count">
     {{ number_format($crmSidebarLeadCount) }}
    </span>

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarLeadsActive ? 'open' : '' }}"
    id="crmLeadsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.leads') ? 'active' : '' }}"
       href="{{ route('v2.leads') }}"
      >
       عرض العملاء
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.create') ? 'active' : '' }}"
       href="{{ route('v2.leads.create') }}"
      >
       إضافة عميل جديد
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.import') ? 'active' : '' }}"
       href="{{ route('v2.leads.import') }}"
      >
       استيراد العملاء
      </a>

      <a
       class="{{ request()->routeIs('v2.leads.export') ? 'active' : '' }}"
       href="{{ route('v2.leads.export') }}"
      >
       تصدير العملاء
      </a>
     </nav>
    </div>
   </div>
  </div>

  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarTasksActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmTasksMenu"
    data-menu="crmTasksMenu"
    aria-expanded="{{ $crmSidebarTasksActive ? 'true' : 'false' }}"
    aria-controls="crmTasksMenu"
   >
    <span class="crm-ico ico">✓</span>

    <span class="crm-label label">
     المهام والمتابعات
    </span>

    <span class="crm-count count">0</span>
    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarTasksActive ? 'open' : '' }}"
    id="crmTasksMenu"
   >
    <div class="crm-sub-inner">
     <nav>


      <a href="{{ route('v2.tasks.daily') }}">
       المهام اليومية
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'new' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'new']) }}"
      >
       جديد
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'no-answer' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'no-answer']) }}"
      >
       لم يرد
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'interested' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'interested']) }}"
      >
       مهتم
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'not-interested' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'not-interested']) }}"
      >
       غير مهتم
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'meeting' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'meeting']) }}"
      >
       مقابلة
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'quotation' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'quotation']) }}"
      >
       عرض سعر
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'discussion' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'discussion']) }}"
      >
       مناقشة
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'contract-closing' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'contract-closing']) }}"
      >
       تقفيل عقد
      </a>

      <a
       class="crm-task-status-link {{ request()->routeIs('v2.tasks.status') && (string) request()->route('status') === 'execution' ? 'active' : '' }}"
       href="{{ route('v2.tasks.status', ['status' => 'execution']) }}"
      >
       تنفيذ
      </a>
     </nav>
    </div>
   </div>
  </div>

  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarCampaignsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmCampaignsMenu"
    data-menu="crmCampaignsMenu"
    aria-expanded="{{ $crmSidebarCampaignsActive ? 'true' : 'false' }}"
    aria-controls="crmCampaignsMenu"
   >
    <span class="crm-ico ico">◎</span>

    <span class="crm-label label">
     الحملات
    </span>

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarCampaignsActive ? 'open' : '' }}"
    id="crmCampaignsMenu"
   >
    <div class="crm-sub-inner">
     <nav>
      <a
       class="{{ request()->routeIs('v2.campaigns.index') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.index') }}"
      >
       عرض الحملات
      </a>

      <a
       class="{{ request()->routeIs('v2.campaigns.create') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.create') }}"
      >
       إضافة حملة
      </a>

      <a
       class="{{ request()->routeIs('v2.campaigns.reports') ? 'active' : '' }}"
       href="{{ route('v2.campaigns.reports') }}"
      >
       تقارير الحملة
      </a>
     </nav>
    </div>
   </div>
  </div>

  <!-- CRM CAMPAIGNS SIDEBAR V2 END -->

  <div>
   <button
    class="crm-toggle toggle {{ $crmSidebarQuotationsActive ? 'active' : '' }}"
    type="button"
    data-crm-menu="crmQuotationsMenu"
    data-menu="crmQuotationsMenu"
    aria-expanded="{{ $crmSidebarQuotationsActive ? 'true' : 'false' }}"
    aria-controls="crmQuotationsMenu"
   >
    <span class="crm-ico ico">▤</span>

    <span class="crm-label label">
     عرض السعر
    </span>

    <span class="crm-arrow arrow">⌄</span>
   </button>

   <div
    class="crm-sub sub {{ $crmSidebarQuotationsActive ? 'open' : '' }}"
    id="crmQuotationsMenu"
   >
    <div class="crm-sub-inner">
     <nav>

      <a
       class="{{ request()->routeIs('v2.quotations.create') ? 'active' : '' }}"
       href="{{ route('v2.quotations.create') }}"
      >
       إنشاء عرض سعر
      </a>

      <a
       class="{{
        request()->routeIs(
         'v2.quotations.index',
         'v2.quotations.show'
        )
         ? 'active'
         : ''
       }}"
       href="{{ route('v2.quotations.index') }}"
      >
       عروض الأسعار
      </a>

     </nav>
    </div>
   </div>
  </div>

  <!-- CRM QUOTATIONS SIDEBAR V2 END -->

  <a
   class="crm-link link {{ $crmSidebarSettingsActive ? 'active' : '' }}"
   href="{{ route('v2.settings') }}"
  >
   <span class="crm-ico ico">⚙</span>
   <span class="crm-label label">الإعدادات</span>
  </a>
 </nav>
</aside>

<!-- CRM TASK SIDEBAR ACTIVE STATUS START -->
<style>
 .crm-task-status-link.active{
  background:#dc2637!important;
  color:#fff!important;
  border-color:#dc2637!important;
  font-weight:900!important;
  box-shadow:
   0 7px 18px #dc26372b!important
 }

 .crm-task-status-link.active:hover{
  background:#dc2637!important;
  color:#fff!important
 }
</style>
<!-- CRM TASK SIDEBAR ACTIVE STATUS END -->

