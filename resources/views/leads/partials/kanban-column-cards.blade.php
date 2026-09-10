@php
    $scopeLabel = match($scope ?? 'all') {
        'today' => __('crm.today'),
        'overdue' => __('crm.overdue'),
        'upcoming' => __('crm.upcoming'),
        default => __('crm.all_leads_in_stage'),
    };
    $canCreateFollowup = auth()->user()?->can('leads.followups.create');
@endphp
@forelse ($leads as $lead)
 @include('leads.partials.kanban-card', [
     'lead' => $lead,
     'column' => $column,
     'scope' => $scope ?? 'all',
     'canCreateFollowup' => $canCreateFollowup,
 ])
@empty
 <div class="kanban-scope-empty">
  <i><i class="bi bi-inbox-fill"></i></i>
  <strong>{{ __('crm.no_leads_found') }}</strong>
  <p>
   {{ __('crm.no_leads_in_column') }}
   {{ $column['name'] ?? '' }}
   @if (!empty($scopeLabel) && ($scope ?? 'all') !== 'all')
    {{ __('crm.in_scope') }}
    {{ $scopeLabel }}.
   @else
    .
   @endif
  </p>
 </div>
@endforelse
