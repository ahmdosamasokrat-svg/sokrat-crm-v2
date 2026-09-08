@php
    $columnStageColor = $column['stage_color'] ?? ($column['status_color'] ?? '#3478f6');
    $columnStatusId = $column['status_id'] ?? $lead->lead_status_id;
    $columnStatusName = $column['name'] ?? ($column['status_name'] ?? '');
    $columnStageName = $column['stage_name'] ?? ($column['name'] ?? '');
    $cardScope = $scope ?? 'all';
    $canCreateFollowup = auth()->user()?->can('leads.followups.create');
@endphp
<article
 class="kanban-card"
 draggable="{{ $canCreateFollowup ? 'true' : 'false' }}"
 data-kanban-lead="{{ $lead->id }}"
 data-kanban-lead-name="{{ $lead->name }}"
 data-current-status-id="{{ $columnStatusId }}"
 data-current-status-name="{{ $columnStatusName }}"
 data-followup-url="{{ route('v2.leads.followups.index', $lead) }}"
 data-kanban-lead-scope="{{ $cardScope }}"
 style="--card-stage-color: {{ $columnStageColor }};"
>
 <div class="kanban-card-head">
  <a
   class="kanban-card-name"
   href="{{ route('v2.leads.show', $lead) }}"
  >
   {{ $lead->name }}
  </a>

  <span class="kanban-card-stage">
   {{ $columnStageName }}
  </span>
 </div>

 <div class="kanban-card-info">
  <div class="kanban-card-row">
   <span><i class="bi bi-telephone"></i> {{ __('crm.phone') }}</span>

   <strong>
    @if ($lead->phone)
     <a
      class="kanban-phone"
      href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $lead->phone) }}"
     >
      {{ $lead->phone }}
     </a>
    @else
     {{ __('crm.not_registered') }}
    @endif
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-building"></i> {{ __('crm.company_or_source') }}</span>

   <strong>
    {{ $lead->company_name ?: ($lead->source ?: __('crm.not_specified')) }}
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-person-badge"></i> {{ __('crm.employee') }}</span>

   <strong>
    {{ $lead->assignedUser?->name ?? ($lead->assigned_employee ?: __('crm.unassigned')) }}
   </strong>
  </div>

  <div class="kanban-card-row">
   <span><i class="bi bi-clock-history"></i> {{ __('crm.followup_date') }}</span>

   <strong>
    {{ $lead->next_follow_up_at?->format('d/m/Y H:i') ?? __('crm.no_date') }}
   </strong>
  </div>
 </div>

 <div class="kanban-card-actions">
  @if ($lead->phone)
   <a
    class="btn call"
    @if ($canCreateFollowup)
    data-kanban-call-dial-popup
    data-followup-url="{{ route('v2.leads.followups.index', $lead) }}"
    @endif
    data-tel-href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $lead->phone) }}"
    href="tel:{{ preg_replace('/[^0-9+]/', '', (string) $lead->phone) }}"
    draggable="false"
    title="{{ __('crm.call') }}"
   >
    <i class="bi bi-telephone-outbound-fill"></i> {{ __('crm.call') }}
   </a>
  @endif

  <a
   class="btn light"
   href="{{ route('v2.leads.show', $lead) }}"
   data-kanban-customer-popup
   draggable="false">
   <i class="bi bi-eye"></i> {{ __('crm.view_lead') }}
  </a>

  @if ($canCreateFollowup)
  <a
   class="btn"
   href="{{ route('v2.leads.followups.index', $lead) }}"
   data-kanban-followup-popup
   draggable="false">
   <i class="bi bi-plus-circle"></i> {{ __('crm.log_followup') }}
  </a>
  @endif
 </div>
</article>
