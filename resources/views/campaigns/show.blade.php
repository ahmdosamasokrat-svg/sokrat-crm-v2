@extends('leads.transfer-layout')

@section('title', $campaign->name)
@section('page-title', $campaign->name)
@section('page-description', __('crm.campaign_show_subtitle'))
@section('back-url', route('v2.campaigns.index'))
@section('back-title', __('crm.all_campaigns'))

@section('top-actions')
	@if ($canManage)
		@can('leads.create')
			<a class="btn primary" href="{{ route('v2.leads.create', ['campaign_id' => $campaign->id]) }}">
				<i class="bi bi-plus-lg"></i> {{ __('crm.add_lead') }}
			</a>
		@endcan
		@can('leads.import')
			<a class="btn soft" href="{{ route('v2.leads.import', ['campaign' => $campaign->id]) }}">
				<i class="bi bi-file-earmark-arrow-up"></i> {{ __('crm.import_leads') }}
			</a>
		@endcan
		<a class="btn soft" href="{{ route('v2.campaigns.edit', $campaign) }}">
			<i class="bi bi-pencil-square"></i> {{ __('crm.edit_campaign') }}
		</a>
	@endif
	@can('campaigns.reports')
		<a class="btn soft" href="{{ route('v2.campaigns.reports', ['campaign_id' => $campaign->id]) }}">
			<i class="bi bi-bar-chart-line"></i>
			{{ __('crm.campaign_reports') }}
		</a>
	@endcan
	<a class="btn soft" href="{{ route('v2.campaigns.index') }}">
		<i class="bi bi-megaphone"></i>
		{{ __('crm.all_campaigns') }}
	</a>
@endsection

@push('styles')
<style>

	/* Hero Card */
	.campaign-hero-card {
		background: var(--card);
		border: 1px solid var(--line);
		border-radius: var(--radius);
		box-shadow: var(--shadow);
		margin-bottom: 20px;
		overflow: hidden;
	}
	.campaign-hero {
		display: flex;
		align-items: center;
		justify-content: space-between;
		gap: 20px;
		padding: 22px 26px;
		background: color-mix(in srgb, var(--red) 4%, var(--card));
		border-bottom: 1px solid var(--line);
		flex-wrap: wrap;
	}
	.campaign-hero-copy {
		min-width: 0;
		display: flex;
		align-items: center;
		gap: 16px;
		flex: 1;
	}
	.campaign-hero-image {
		width: 68px;
		height: 68px;
		display: block;
		flex: 0 0 68px;
		object-fit: cover;
		border: 2px solid rgba(255, 255, 255, 0.7);
		border-radius: 16px;
		background: #fff;
		box-shadow: 0 8px 24px rgba(0,0,0,0.06);
	}
	.campaign-hero-badge {
		display: inline-flex;
		align-items: center;
		gap: 4px;
		padding: 2px 10px;
		border-radius: 20px;
		background: rgba(220, 38, 55, 0.1);
		color: var(--red);
		font-size: 11px;
		font-weight: 800;
		margin-bottom: 5px;
	}
	.campaign-hero h2 {
		margin: 0;
		font-size: 20px;
		font-weight: 900;
		color: var(--dark);
	}
	.campaign-hero p {
		margin: 3px 0 0;
		color: var(--muted);
		font-size: 12px;
		line-height: 1.5;
	}
	.campaign-hero-count {
		min-width: 130px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;
		padding: 12px 18px;
		border: 1px solid var(--line);
		border-radius: 14px;
		background: var(--card);
		text-align: center;
		box-shadow: 0 4px 12px rgba(0,0,0,0.02);
	}
	.campaign-hero-count strong {
		font-size: 28px;
		font-weight: 900;
		color: var(--dark);
		line-height: 1.1;
	}
	.campaign-hero-count span {
		margin-top: 3px;
		color: var(--muted);
		font-size: 11px;
		font-weight: 800;
	}
	.campaign-summary {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: 12px;
		padding: 16px 24px;
	}
	.campaign-stat {
		padding: 10px 14px;
		border: 1px solid var(--line);
		border-radius: 12px;
		background: var(--bg);
	}
	.campaign-stat span {
		display: block;
		color: var(--muted);
		font-size: 11px;
		font-weight: 700;
	}
	.campaign-stat strong {
		display: block;
		margin-top: 4px;
		color: var(--dark);
		font-size: 13px;
		font-weight: 900;
	}
	.campaign-team {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 8px;
		padding: 0 24px 16px;
	}
	.campaign-team-label {
		font-size: 11px;
		font-weight: 800;
		color: var(--muted);
		margin-inline-end: 4px;
	}
	.campaign-team span {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 4px 10px;
		border-radius: 20px;
		background: var(--bg);
		border: 1px solid var(--line);
		color: var(--dark);
		font-size: 11px;
		font-weight: 800;
	}

	/* Hero Pipeline Banner (Exact Match with leads/index.blade.php) */
	.hero-pipeline {
		display: flex;
		align-items: center;
		gap: 8px;
		overflow-x: auto;
		padding: 12px 16px;
		background: var(--card);
		border: 1px solid var(--line);
		border-radius: var(--radius);
		margin-bottom: 20px;
		box-shadow: var(--shadow);
		scrollbar-width: thin;
	}
	.hero-stage {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 14px;
		border-radius: 12px;
		border: 1px solid var(--line);
		background: var(--bg);
		text-decoration: none;
		color: var(--dark);
		font-weight: 800;
		font-size: 13px;
		white-space: nowrap;
		transition: all 0.15s ease;
	}
	.hero-stage:hover {
		border-color: var(--stage-color, var(--red));
		background: color-mix(in srgb, var(--stage-color, var(--red)) 10%, var(--card));
	}
	.hero-stage.is-selected {
		border-color: var(--stage-color, var(--red));
		background: var(--stage-color, var(--red));
		color: #fff;
	}
	.hero-stage .stage-dot {
		width: 8px;
		height: 8px;
		border-radius: 50%;
		background: var(--stage-color, var(--red));
		flex-shrink: 0;
	}
	.hero-stage.is-selected .stage-dot {
		background: #fff;
	}
	.hero-stage .stage-count-badge {
		display: inline-block;
		padding: 1px 7px;
		border-radius: 10px;
		background: rgba(0,0,0,0.06);
		font-size: 11px;
		font-weight: 800;
	}
	.hero-stage.is-selected .stage-count-badge {
		background: rgba(255,255,255,0.25);
		color: #fff;
	}

	/* Filter Panel (Exact Match with leads/index.blade.php) */
	.filter-panel {
		background: var(--card);
		border: 1px solid var(--line);
		border-radius: 14px;
		padding: 14px 18px;
		box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
		margin-bottom: 20px;
	}
	.filter-form-grid {
		display: grid;
		grid-template-columns: minmax(240px, 1.5fr) minmax(220px, 1fr) auto;
		gap: 10px 14px;
		align-items: end;
	}
	.filter-field {
		display: flex;
		flex-direction: column;
		gap: 4px;
		min-width: 0;
	}
	.filter-field label {
		display: flex;
		align-items: center;
		gap: 4px;
		font-size: 11px;
		font-weight: 800;
		color: var(--muted);
		white-space: nowrap;
	}
	.filter-input-wrap {
		position: relative;
		display: flex;
		align-items: center;
		width: 100%;
	}
	.filter-input-icon {
		position: absolute;
		inset-inline-start: 12px;
		color: var(--muted);
		pointer-events: none;
		font-size: 13px;
	}
	.filter-control {
		width: 100%;
		height: 44px;
		min-height: 44px;
		border: 1px solid var(--line);
		border-radius: 10px;
		padding: 0 12px;
		padding-inline-start: 34px;
		background: var(--card);
		color: var(--dark);
		font-size: 13px;
		font-weight: 600;
		outline: none;
		transition: border-color 0.15s ease, box-shadow 0.15s ease;
	}
	.filter-control:focus {
		border-color: var(--red);
		box-shadow: 0 0 0 2px rgba(220, 38, 55, 0.12);
	}
	html.dark-mode .filter-control {
		background: rgba(30, 41, 59, 0.7);
		border-color: var(--line);
		color: var(--dark);
	}
	.filter-actions-col {
		display: flex;
		align-items: center;
		gap: 8px;
		height: 44px;
	}

	/* Bulk Toolbar (Exact Match with leads/index.blade.php) */
	.bulk-toolbar {
		display: none;
		margin-bottom: 16px;
		padding: 12px 18px;
		background: var(--card);
		border: 1px solid var(--line);
		border-radius: 12px;
		box-shadow: var(--shadow);
		align-items: center;
		justify-content: space-between;
		gap: 14px;
		flex-wrap: wrap;
	}
	.bulk-toolbar-left {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	.bulk-toolbar-tools {
		display: flex;
		align-items: center;
		gap: 10px;
		flex-wrap: wrap;
	}

	/* Leads Table (Exact Match with leads/index.blade.php) */
	.table-card {
		background: var(--card);
		border: 1px solid var(--line);
		border-radius: var(--radius);
		box-shadow: var(--shadow);
		overflow: hidden;
		margin-bottom: 24px;
	}
	.table-wrap {
		overflow-x: auto;
		-webkit-overflow-scrolling: touch;
		width: 100%;
	}
	.table-card table {
		width: 100%;
		border-collapse: collapse;
		min-width: 980px;
	}
	.table-card th, .table-card td {
		text-align: start;
		padding: 12px 16px;
		border-bottom: 1px solid var(--line);
		vertical-align: middle;
	}
	.table-card th {
		background: var(--bg);
		color: var(--muted);
		font-size: 12px;
		font-weight: 800;
		white-space: nowrap;
	}
	html.dark-mode .table-card th {
		background: rgba(255, 255, 255, 0.03);
	}
	.table-card tr:last-child td { border-bottom: none; }
	.table-card tr:hover td { background: var(--bg); }
	html.dark-mode .table-card tr:hover td { background: rgba(255, 255, 255, 0.02); }

	/* Customer Cell */
	.customer-name-cell {
		display: flex;
		align-items: center;
		gap: 10px;
	}
	.customer-avatar {
		width: 38px;
		height: 38px;
		border-radius: 12px;
		background: #fef2f2;
		color: var(--red);
		display: grid;
		place-items: center;
		font-weight: 900;
		font-size: 14px;
		flex-shrink: 0;
	}
	html.dark-mode .customer-avatar {
		background: rgba(220, 38, 55, 0.15);
	}
	.customer-info strong {
		display: block;
		font-size: 13px;
		font-weight: 900;
		color: var(--dark);
	}
	.customer-info small {
		display: block;
		color: var(--muted);
		font-size: 11px;
	}

	/* Status Badge */
	.status-badge {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		padding: 4px 10px;
		border-radius: 20px;
		font-size: 11px;
		font-weight: 800;
		background: color-mix(in srgb, var(--status-color, #64748b) 12%, transparent);
		color: var(--status-color, #64748b);
		border: 1px solid color-mix(in srgb, var(--status-color, #64748b) 25%, transparent);
		white-space: nowrap;
	}
	.status-dot {
		width: 7px;
		height: 7px;
		border-radius: 50%;
		background: var(--status-color, #64748b);
	}
	.stage-name {
		display: block;
		font-size: 11px;
		color: var(--muted);
		margin-top: 3px;
	}

	/* Actions Cell */
	.actions-cell {
		display: flex;
		align-items: center;
		gap: 6px;
		justify-content: center;
	}
	.btn-action {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 38px;
		height: 38px;
		min-width: 38px;
		min-height: 38px;
		border-radius: 9px;
		background: #f1f5f9;
		border: 1px solid var(--line);
		color: var(--dark);
		font-size: 14px;
		cursor: pointer;
		transition: all 0.15s ease;
		text-decoration: none;
	}
	.btn-action:hover {
		background: #e2e8f0;
		border-color: #cbd5e1;
		transform: translateY(-1px);
	}
	html.dark-mode .btn-action {
		background: rgba(255, 255, 255, 0.06);
		border-color: var(--line);
		color: var(--dark);
	}
	.btn-action.call { color: #2563eb; }
	.btn-action.call:hover { background: #eff6ff; border-color: #bfdbfe; }
	.btn-action.whatsapp { color: #15803d; }
	.btn-action.whatsapp:hover { background: #dcfce7; border-color: #bbf7d0; }

	/* Pagination Container */
	.pagination-wrap {
		padding: 16px 20px;
		border-top: 1px solid var(--line);
		display: flex;
		justify-content: space-between;
		align-items: center;
		gap: 16px;
		flex-wrap: wrap;
		background: var(--card);
	}
	.pagination-info {
		font-size: 13px;
		font-weight: 700;
		color: var(--muted);
	}

	/* Empty State */
	.campaign-empty {
		min-height: 240px;
		display: flex;
		align-items: center;
		justify-content: center;
		flex-direction: column;
		padding: 30px;
		color: var(--muted);
		text-align: center;
	}
	.campaign-empty i {
		font-size: 38px;
		color: #cbd5e1;
		margin-bottom: 10px;
	}
</style>
@endpush

@section('content')
@php
	$assigneeLabel = $showAllAssignees
		? __('crm.all_campaign_leads')
		: ($showUnassigned
			? __('crm.unassigned_leads')
			: ($selectedAssignee?->name ?? __('crm.responsible_user')));
@endphp

<!-- CAMPAIGN HERO & METRICS CARD -->
<section class="campaign-hero-card">
	<div class="campaign-hero">
		<div class="campaign-hero-copy">
			@if ($campaign->image_path)
				<img class="campaign-hero-image" src="{{ asset('storage/'.$campaign->image_path) }}" alt="{{ $campaign->name }}">
			@else
				<div class="campaign-hero-image" style="display:grid;place-items:center;background:color-mix(in srgb, var(--red) 10%, var(--bg));color:var(--red);font-size:26px;">
					<i class="bi bi-megaphone-fill"></i>
				</div>
			@endif
			<div>
				<span class="campaign-hero-badge">
					<i class="bi bi-shield-check"></i> {{ __('crm.manage_campaign_leads') }}
				</span>
				<h2>{{ $campaign->name }}</h2>
				<p>
					{{ $canManage
						? __('crm.campaign_all_users_view_hint')
						: __('crm.campaign_own_leads_hint') }}
				</p>
			</div>
		</div>

		<div class="campaign-hero-count">
			<strong>{{ number_format($operationalCampaignLeads ?? $leads->total()) }}</strong>
			<span>{{ $showAllAssignees ? __('crm.all_campaign_leads') : ($showUnassigned ? __('crm.unassigned_leads') : __('crm.assigned_lead_to_count_label')) }}</span>
		</div>
	</div>

	<div class="campaign-summary">
		<div class="campaign-stat">
			<span><i class="bi bi-calendar-event"></i> {{ __('crm.campaign_start') }}</span>
			<strong>{{ $campaign->starts_at->format('Y-m-d H:i') }}</strong>
		</div>
		<div class="campaign-stat">
			<span><i class="bi bi-calendar-check"></i> {{ __('crm.campaign_end') }}</span>
			<strong>{{ $campaign->ends_at->format('Y-m-d H:i') }}</strong>
		</div>
		<div class="campaign-stat">
			<span><i class="bi bi-cash-stack"></i> {{ __('crm.campaign_cost') }}</span>
			<strong>{{ number_format((float) $campaign->cost, 2) }} {{ __('crm.pound') }}</strong>
		</div>
		<div class="campaign-stat">
			<span><i class="bi bi-person"></i> {{ __('crm.created_by') }}</span>
			<strong>{{ $campaign->creator?->name ?: '—' }}</strong>
		</div>
	</div>

	@if ($campaign->users->isNotEmpty())
		<div class="campaign-team">
			<span class="campaign-team-label"><i class="bi bi-people"></i> {{ __('فريق الحملة:') }}</span>
			@foreach ($campaign->users as $campaignUser)
				<span><i class="bi bi-person-fill" style="color:var(--muted)"></i> {{ $campaignUser->name }}</span>
			@endforeach
		</div>
	@endif
</section>

<!-- PIPELINE STAGES BANNER (Exact Style of leads/index.blade.php) -->
<div class="hero-pipeline">
	@php
		$isAllStagesSelected = ($selectedStage === null && $selectedStatus === null);
	@endphp
	<a
		class="hero-stage {{ $isAllStagesSelected ? 'is-selected' : '' }}"
		href="{{ route('v2.campaigns.show', array_filter([
			'campaign' => $campaign,
			'assigned_user_id' => $canManage ? ($showAllAssignees ? 'all' : ($showUnassigned ? 'unassigned' : $selectedAssignee?->id)) : null,
			'q' => request('q'),
		])) }}"
		style="--stage-color: var(--red);"
	>
		<i class="bi bi-grid-fill" style="font-size:12px;"></i>
		<span>{{ __('crm.all_stages') }}</span>
		<span class="stage-count-badge">{{ number_format($totalCampaignLeads) }}</span>
	</a>

	@foreach ($pipelineStages as $stage)
		@php
			$stageColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $stage->color) ? $stage->color : '#64748b';
			$isStageSelected = ($selectedStage?->id === $stage->id) || ($selectedStatus && $selectedStatus->pipeline_stage_id === $stage->id);
		@endphp
		<a
			class="hero-stage {{ $isStageSelected ? 'is-selected' : '' }}"
			href="{{ route('v2.campaigns.show', array_filter([
				'campaign' => $campaign,
				'assigned_user_id' => $canManage ? ($showAllAssignees ? 'all' : ($showUnassigned ? 'unassigned' : $selectedAssignee?->id)) : null,
				'stage' => $stage->id,
				'q' => request('q'),
			])) }}"
			style="--stage-color: {{ $stageColor }};"
		>
			<span class="stage-dot"></span>
			<span>{{ (app()->getLocale() === 'en' && !empty($stage->name_en)) ? $stage->name_en : $stage->name_ar }}</span>
			<span class="stage-count-badge">{{ number_format($stage->campaign_leads_count ?? 0) }}</span>
		</a>
	@endforeach
</div>

<!-- CAMPAIGN FILTER PANEL (Exact Style of leads/index.blade.php) -->
@if ($canManage)
<section class="filter-panel">
	<form class="filter-form-grid" id="campaignFilters" method="GET" action="{{ route('v2.campaigns.show', $campaign) }}">
		<!-- Search Query Input -->
		<div class="filter-field">
			<label for="campaignSearch"><i class="bi bi-search"></i> {{ __('البحث بالعميل') }}</label>
			<div class="filter-input-wrap">
				<i class="bi bi-search filter-input-icon"></i>
				<input type="text"
					   id="campaignSearch"
					   name="q"
					   class="filter-control"
					   value="{{ request('q') }}"
					   placeholder="{{ __('بحث بالاسم، الشركة، الهاتف، البريد...') }}">
			</div>
		</div>

		<!-- Dynamic Employee Filter with Accurate Context Counts -->
		<div class="filter-field">
			<label for="assigned_user_id"><i class="bi bi-person-check"></i> {{ __('crm.responsible_user') }}</label>
			<div class="filter-input-wrap">
				<i class="bi bi-person filter-input-icon"></i>
				<select class="filter-control campaign-live-filter" id="assigned_user_id" name="assigned_user_id">
					<option value="all" @selected($showAllAssignees)>
						{{ __('كل الموظفين') }} ({{ number_format($totalFilteredLeads) }})
					</option>
					@foreach ($filterUsers as $filterUser)
						@php
							$userLeadCount = (int) ($assignmentCounts->get((string) $filterUser->id, 0));
						@endphp
						<option value="{{ $filterUser->id }}" @selected(! $showAllAssignees && ! $showUnassigned && $selectedAssignee && $selectedAssignee->is($filterUser))>
							{{ $filterUser->name }}{{ $filterUser->is(auth()->user()) ? ' ' . __('crm.my_account') : '' }}
							({{ number_format($userLeadCount) }})
						</option>
					@endforeach
					<option value="unassigned" @selected($showUnassigned)>
						{{ __('crm.unassigned') }} ({{ number_format($assignmentCounts->get('unassigned', 0)) }})
					</option>
				</select>
			</div>
		</div>

		<div class="filter-actions-col">
			<button class="btn primary small" type="submit">
				<i class="bi bi-search"></i> {{ __('تصفية') }}
			</button>
			@if (request()->hasAny(['q', 'assigned_user_id', 'stage', 'status']))
				<a class="btn soft small" href="{{ route('v2.campaigns.show', $campaign) }}">
					<i class="bi bi-arrow-counterclockwise"></i> {{ __('إعادة ضبط') }}
				</a>
			@endif
		</div>

		@if ($selectedStage)
			<input type="hidden" name="stage" value="{{ $selectedStage->id }}">
		@elseif ($selectedStatus)
			<input type="hidden" name="status" value="{{ $selectedStatus->code }}">
		@endif
	</form>
</section>
@endif

<!-- BULK ASSIGNMENT TOOLBAR (Appears smoothly only when leads are checked) -->
@if ($canManage && $assignableUsers->isNotEmpty())
	<div class="bulk-toolbar" id="leadsBulkToolbar">
		<div class="bulk-toolbar-left">
			<span class="badge" style="background:#eef2ff; color:#4f46e5; font-size:12px; padding:5px 12px; font-weight:800;">
				<i class="bi bi-check2-square"></i>
				<span id="selectedLeadsCounter">0</span> {{ __('عملاء محددين') }}
			</span>
			<button type="button" class="btn small soft" onclick="clearLeadsSelection()" style="font-size:12px;">
				{{ __('إلغاء التحديد') }}
			</button>
		</div>

		<form id="campaignAssignmentForm" method="POST" action="{{ route('v2.campaigns.leads.assign', $campaign) }}" style="margin:0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
			@csrf
			@method('PATCH')
			<div style="display:flex; align-items:center; gap:8px;">
				<label for="target_user_id" style="font-size:12px; font-weight:800; color:var(--dark); white-space:nowrap;">
					<i class="bi bi-person-fill" style="color:var(--red);"></i> {{ __('إسناد إلى:') }}
				</label>
				<select class="filter-control" id="target_user_id" name="target_user_id" required style="height:38px; min-height:38px; min-width:220px; font-size:12px;">
					<option value="">{{ __('اختر الموظف المسؤول...') }}</option>
					@foreach ($assignableUsers as $assignUser)
						@php
							$roleTitle = $assignUser->groups->pluck('name')->first() ?? ($assignUser->is_super_admin ? 'مدير عام' : 'موظف مبيعات');
						@endphp
						<option value="{{ $assignUser->id }}">{{ $assignUser->name }} ({{ $roleTitle }})</option>
					@endforeach
				</select>
			</div>
			<div id="bulkAssignmentInputs"></div>
			<button class="btn primary small" type="submit" style="height:38px; min-height:38px; padding:0 18px;">
				<i class="bi bi-person-check-fill"></i> {{ __('crm.assign_selected') }}
			</button>
		</form>
	</div>
@endif

<!-- LEADS TABLE CARD (Exact Structure & Classes of leads/index.blade.php) -->
<section class="table-card">
	@if (session('success'))
		<div class="notice success" style="margin: 16px 20px 0;">
			<i class="bi bi-check-circle-fill"></i>
			<div>{{ session('success') }}</div>
		</div>
	@endif

	<div class="table-wrap">
		<table>
				<thead>
					<tr>
						@if ($canManage && $assignableUsers->isNotEmpty())
							<th style="width:40px; text-align:center; padding:0 8px;">
								<input type="checkbox" id="selectAllLeads" title="تحديد الكل في هذه الصفحة" style="width:17px; height:17px; cursor:pointer;">
							</th>
						@endif
						<th style="min-width:200px">{{ __('crm.client') }}</th>
						<th style="min-width:140px">{{ __('crm.contact_data') }}</th>
						<th style="min-width:140px">{{ __('crm.company_source') }}</th>
						<th style="min-width:140px">{{ __('crm.current_status') }}</th>
						<th style="min-width:130px">{{ __('crm.responsible_employee') }}</th>
						<th style="min-width:140px">{{ __('crm.next_followup') }}</th>
						<th style="min-width:110px">{{ __('crm.created_date') }}</th>
						<th style="min-width:140px; text-align:center;">{{ __('crm.actions') }}</th>
					</tr>
				</thead>
				<tbody>
					@forelse ($leads as $lead)
						@php
							$leadStatusColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $lead->status?->color) ? $lead->status->color : '#64748b';
							$leadPhoneRaw = trim((string) $lead->phone);
							$leadPhoneDigits = preg_replace('/\D+/', '', $leadPhoneRaw) ?? '';
							$callPhone = preg_match('/^[0-9]{2,20}$/', $leadPhoneDigits) === 1 ? $leadPhoneDigits : null;
							$whatsappPhone = null;

							if (str_starts_with($leadPhoneDigits, '0020')) {
								$whatsappPhone = substr($leadPhoneDigits, 2);
							} elseif (preg_match('/^01[0125][0-9]{8}$/', $leadPhoneDigits) === 1) {
								$whatsappPhone = '20'.substr($leadPhoneDigits, 1);
							} elseif (preg_match('/^20[0-9]{10}$/', $leadPhoneDigits) === 1 || preg_match('/^[1-9][0-9]{7,14}$/', $leadPhoneDigits) === 1) {
								$whatsappPhone = $leadPhoneDigits;
							}
						@endphp
						<tr class="lead-row">
							@if ($canManage && $assignableUsers->isNotEmpty())
								<td style="width:40px; text-align:center; padding:0 8px;">
									<input type="checkbox" class="lead-select-checkbox campaign-lead-check" value="{{ $lead->id }}" name="lead_ids[]" style="width:17px; height:17px; cursor:pointer;" onchange="handleRowSelectionChange()">
								</td>
							@endif

							<td>
								<div class="customer-name-cell">
									<div class="customer-avatar">
										{{ mb_substr((string) $lead->name, 0, 1) }}
									</div>
									<div class="customer-info">
										@can('leads.view')
											<a href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}">
												<strong>{{ $lead->name }}</strong>
											</a>
										@else
											<strong>{{ $lead->name }}</strong>
										@endcan
										<small>{{ $lead->email ?: __('crm.no_email') }}</small>
									</div>
								</div>
							</td>

							<td>
								@if ($lead->phone)
									@can('leads.followups.view')
										@if ($callPhone)
											<a
												class="js-call-followup"
												href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
												data-call-href="tel:{{ $callPhone }}"
												title="{{ __('crm.open_microsip_followup') }}"
												style="font-weight:700; color:inherit"
												dir="ltr"
											>
												<i class="bi bi-telephone" style="color:var(--muted); font-size:11px;"></i> {{ $lead->phone }}
											</a>
										@else
											<a href="tel:{{ $lead->phone }}" style="font-weight:700; color:inherit" dir="ltr">
												<i class="bi bi-telephone" style="color:var(--muted); font-size:11px;"></i> {{ $lead->phone }}
											</a>
										@endif
									@else
										<a href="tel:{{ $lead->phone }}" style="font-weight:700; color:inherit" dir="ltr">
											<i class="bi bi-telephone" style="color:var(--muted); font-size:11px;"></i> {{ $lead->phone }}
										</a>
									@endcan
								@else
									<span style="color:var(--muted)">—</span>
								@endif
							</td>

							<td>
								<strong>{{ $lead->company_name ?: __('crm.no_company') }}</strong>
								<span class="stage-name">{{ $lead->source ? __($lead->source) : __('غير محدد') }}</span>
							</td>

							<td>
								<span class="status-badge" style="--status-color:{{ $leadStatusColor }}">
									<i class="status-dot"></i>
									{{ (app()->getLocale() === 'en' && !empty($lead->status?->name_en)) ? $lead->status?->name_en : ($lead->status?->name_ar ?? __('crm.without_status')) }}
								</span>
								<span class="stage-name">
									{{ (app()->getLocale() === 'en' && !empty($lead->status?->stage?->name_en)) ? $lead->status?->stage?->name_en : ($lead->status?->stage?->name_ar ?? __('crm.without_stage')) }}
								</span>
							</td>

							<td>
								<span style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
									<i class="bi bi-person" style="color:var(--muted)"></i>
									{{ $lead->assignedUser?->name ?? $lead->assigned_employee ?: __('crm.unassigned') }}
								</span>
							</td>

							<td>
								@if ($lead->next_follow_up_at)
									<span class="badge {{ $lead->next_follow_up_at->isPast() ? 'overdue' : ($lead->next_follow_up_at->isToday() ? 'today' : '') }}">
										<i class="bi bi-clock"></i> {{ $lead->next_follow_up_at->format('d/m/Y - h:i A') }}
									</span>
								@else
									<span style="color:var(--muted)">—</span>
								@endif
							</td>

							<td>
								{{ $lead->created_at?->format('d/m/Y') ?? '—' }}
							</td>

							<td>
								<div class="actions-cell">
									@can('leads.view')
										<a
											class="btn-action"
											href="{{ route('v2.leads.show', array_merge(request()->query(), ['lead' => $lead->id])) }}"
											title="{{ __('crm.view_lead') }}"
										>
											<i class="bi bi-eye"></i>
										</a>
									@endcan

									@if ($callPhone)
										@can('leads.followups.view')
											<a
												class="btn-action call js-call-followup"
												href="{{ route('v2.leads.followups.index', ['lead' => $lead, 'channel' => 'call']) }}"
												data-call-href="tel:{{ $callPhone }}"
												title="{{ __('crm.call_action') }}"
											>
												<i class="bi bi-telephone"></i>
											</a>
										@else
											<a class="btn-action call" href="tel:{{ $callPhone }}" title="{{ __('crm.call_action') }}">
												<i class="bi bi-telephone"></i>
											</a>
										@endcan
									@endif

									@can('leads.followups.view')
										<a
											class="btn-action"
											href="{{ route('v2.leads.followups.index', $lead) }}"
											title="{{ __('crm.log_new_lead_followup_title') }}"
										>
											<i class="bi bi-clock-history"></i>
										</a>
									@endcan

									@if ($whatsappPhone)
										<a
											class="btn-action whatsapp"
											href="https://wa.me/{{ $whatsappPhone }}"
											target="_blank"
											rel="noopener noreferrer"
											title="{{ __('crm.phone_type_whatsapp') }}"
										>
											<i class="bi bi-whatsapp"></i>
										</a>
									@endif

									@can('leads.update')
										<a class="btn-action" href="{{ route('v2.leads.edit', $lead) }}" title="{{ __('crm.edit') }}">
											<i class="bi bi-pencil"></i>
										</a>
									@endcan

									@can('leads.delete')
										<form class="js-delete-lead-form" method="POST" action="{{ route('v2.leads.destroy', $lead) }}" data-lead-name="{{ $lead->name }}" style="margin:0; display:inline;">
											@csrf
											@method('DELETE')
											<button class="btn-action" type="submit" style="color:var(--red);" title="{{ __('crm.delete') }}">
												<i class="bi bi-trash"></i>
											</button>
										</form>
									@endcan
								</div>
							</td>
						</tr>
				@empty
					<tr>
						<td colspan="20" style="text-align:center; padding:48px 20px;">
							<div class="campaign-empty" style="min-height:auto; padding:0;">
								<i class="bi bi-inbox" style="font-size:38px; color:#cbd5e1; margin-bottom:10px; display:inline-block;"></i>
								<h3 style="margin:0 0 6px; font-size:16px; font-weight:800; color:var(--dark);">{{ __('crm.no_matching_results') }}</h3>
								<p style="margin:0; font-size:12px; color:var(--muted);">{{ __('crm.try_other_filter') }}</p>
							</div>
						</td>
					</tr>
				@endforelse
			</tbody>
		</table>
	</div>

	@if ($leads->hasPages())
		<div class="pagination-wrap">
			<div class="pagination-info">
				{{ __('عرض') }} {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} {{ __('من إجمالي') }} {{ $leads->total() }} {{ __('عميل') }}
			</div>
			<div>
				{{ $leads->links() }}
			</div>
		</div>
	@endif
</section>
@endsection

@push('scripts')
<script>
	const getSelectedLeadIds = () => {
		return Array.from(document.querySelectorAll('.lead-select-checkbox:checked')).map(cb => cb.value);
	};

	const updateBulkToolbar = () => {
		const selectedIds = getSelectedLeadIds();
		const toolbar = document.getElementById('leadsBulkToolbar');
		const counter = document.getElementById('selectedLeadsCounter');
		const selectAll = document.getElementById('selectAllLeads');
		const checkboxes = Array.from(document.querySelectorAll('.lead-select-checkbox'));
		const total = checkboxes.length;

		if (selectAll) {
			selectAll.checked = total > 0 && selectedIds.length === total;
			selectAll.indeterminate = selectedIds.length > 0 && selectedIds.length < total;
		}

		if (counter) counter.textContent = String(selectedIds.length);
		if (toolbar) {
			toolbar.style.display = selectedIds.length > 0 ? 'flex' : 'none';
		}
	};

	window.handleRowSelectionChange = () => {
		updateBulkToolbar();
	};

	window.clearLeadsSelection = () => {
		document.querySelectorAll('.lead-select-checkbox').forEach(cb => { cb.checked = false; });
		const selectAll = document.getElementById('selectAllLeads');
		if (selectAll) {
			selectAll.checked = false;
			selectAll.indeterminate = false;
		}
		updateBulkToolbar();
	};

	document.addEventListener('change', (e) => {
		if (e.target && e.target.id === 'selectAllLeads') {
			document.querySelectorAll('.lead-select-checkbox').forEach(cb => {
				cb.checked = e.target.checked;
			});
			updateBulkToolbar();
		} else if (e.target && e.target.classList.contains('lead-select-checkbox')) {
			updateBulkToolbar();
		}
	});

	// Populate hidden inputs into campaignAssignmentForm on submit so lead_ids[] is always sent
	document.getElementById('campaignAssignmentForm')?.addEventListener('submit', (e) => {
		const selectedIds = getSelectedLeadIds();
		if (selectedIds.length === 0) {
			e.preventDefault();
			alert('اختر عميلًا واحدًا على الأقل.');
			return;
		}
		const container = document.getElementById('bulkAssignmentInputs');
		if (container) {
			container.innerHTML = '';
			selectedIds.forEach(id => {
				const inp = document.createElement('input');
				inp.type = 'hidden';
				inp.name = 'lead_ids[]';
				inp.value = id;
				container.appendChild(inp);
			});
		}
	});

	(() => {
		const filters = document.getElementById('campaignFilters');

		document.querySelectorAll('.campaign-live-filter').forEach((filter) => {
			filter.addEventListener('change', () => {
				if (filter.id === 'assigned_user_id') {
					const stageInput = filters?.querySelector('input[name="stage"]');
					const statusInput = filters?.querySelector('input[name="status"]');
					if (stageInput) stageInput.remove();
					if (statusInput) statusInput.remove();
				}
				filters?.requestSubmit();
			});
		});

		document.querySelectorAll('.js-delete-lead-form').forEach((form) => {
			form.addEventListener('submit', (event) => {
				if (!window.confirm(@json(__('crm.confirm_delete_lead_warning')).replace(':name', form.dataset.leadName || @json(__('crm.client')))) {
					event.preventDefault();
				}
			});
		});

		document.querySelectorAll('.js-call-followup').forEach((link) => {
			link.addEventListener('click', () => {
				if (link.dataset.callHref && link.target === '_blank') {
					window.setTimeout(() => { window.location.href = link.dataset.callHref; }, 120);
				}
			});
		});
	})();
</script>
@endpush
