@extends('leads.transfer-layout')

@section('title', __('crm.campaigns'))
@section('page-title', __('crm.campaigns'))
@section('page-description', __('إدارة ومتابعة الحملات التسويقية والعملاء المرتبطين بها.'))
@section('back-url', route('dashboard'))
@section('back-title', __('crm.dashboard'))

@section('top-actions')
	@can('campaigns.reports')
		<a class="btn soft" href="{{ route('v2.campaigns.reports') }}">
			<i class="bi bi-bar-chart-line"></i> {{ __('crm.campaign_reports') }}
		</a>
	@endcan
	@can('campaigns.create')
		<a class="btn primary" href="{{ route('v2.campaigns.create') }}">
			<i class="bi bi-plus-lg"></i> {{ __('crm.new_campaign') }}
		</a>
	@endcan
@endsection

@push('styles')
<style>
	.campaign-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
		gap: 20px;
	}
	.campaign-filters {
		display: grid;
		grid-template-columns: minmax(220px, 1.5fr) repeat(2, minmax(170px, 1fr));
		gap: 12px;
		margin-bottom: 24px;
		padding: 16px 18px;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		background: var(--card);
		box-shadow: var(--shadow);
	}
	.campaign-filter-field label {
		display: block;
		margin: 0 3px 6px;
		color: var(--muted);
		font-size: 11px;
		font-weight: 800;
	}
	.campaign-filter-field input,
	.campaign-filter-field select {
		width: 100%;
		height: 44px;
		min-height: 44px;
		padding: 0 12px;
		border: 1px solid var(--line);
		border-radius: 10px;
		background: var(--bg);
		color: var(--dark);
		font-size: 13px;
		font-weight: 600;
		outline: none;
		transition: border-color .15s, box-shadow .15s;
	}
	.campaign-filter-field input:focus,
	.campaign-filter-field select:focus {
		border-color: var(--red);
		background: var(--card);
		box-shadow: 0 0 0 2px rgba(220, 38, 55, .12);
	}

	.campaign-card {
		position: relative;
		display: flex;
		flex-direction: column;
		padding: 22px;
		border: 1px solid var(--line);
		border-radius: var(--radius);
		background: var(--card);
		color: inherit;
		box-shadow: 0 4px 18px rgba(15, 23, 42, .03);
		transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
	}
	.campaign-card:hover {
		transform: translateY(-3px);
		border-color: rgba(220, 38, 55, .35);
		box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
	}

	.campaign-card-header {
		display: flex;
		align-items: flex-start;
		justify-content: space-between;
		gap: 12px;
		margin-bottom: 14px;
	}
	.campaign-card-brand {
		display: flex;
		align-items: center;
		gap: 12px;
		min-width: 0;
		flex: 1;
	}
	.campaign-card-thumb,
	.campaign-card-icon {
		width: 48px;
		height: 48px;
		border-radius: 14px;
		flex-shrink: 0;
		display: grid;
		place-items: center;
	}
	.campaign-card-thumb {
		object-fit: cover;
		border: 1px solid var(--line);
		background: var(--bg);
	}
	.campaign-card-icon {
		background: rgba(220, 38, 55, .08);
		border: 1px solid rgba(220, 38, 55, .18);
		color: var(--red);
		font-size: 20px;
	}

	.campaign-card-tags {
		display: flex;
		flex-direction: column;
		gap: 4px;
		min-width: 0;
	}
	.campaign-status-pill {
		display: inline-flex;
		align-items: center;
		gap: 5px;
		padding: 3px 9px;
		border-radius: 999px;
		font-size: 11px;
		font-weight: 800;
		width: fit-content;
	}
	.campaign-status-pill.is-active {
		background: #eaf9ef;
		color: #15803d;
		border: 1px solid #bbf7d0;
	}
	.campaign-status-pill.is-upcoming {
		background: #fffbeb;
		color: #b45309;
		border: 1px solid #fde68a;
	}
	.campaign-status-pill.is-ended {
		background: #f1f5f9;
		color: #64748b;
		border: 1px solid #e2e8f0;
	}
	.status-pulse-dot {
		width: 6px;
		height: 6px;
		border-radius: 50%;
		background: #16a34a;
		display: inline-block;
		box-shadow: 0 0 0 2px rgba(22, 163, 74, .2);
	}

	.campaign-card-menu {
		position: relative;
		z-index: 15;
		margin: 0;
	}
	.campaign-card-menu summary {
		width: 44px;
		height: 44px;
		min-width: 44px;
		min-height: 44px;
		display: grid;
		place-items: center;
		border: 1px solid var(--line);
		border-radius: 10px;
		background: var(--card);
		color: var(--muted);
		list-style: none;
		cursor: pointer;
		font-size: 18px;
		font-weight: 900;
		line-height: 1;
		transition: all .15s ease;
	}
	.campaign-card-menu summary::-webkit-details-marker { display: none; }
	.campaign-card-menu summary:hover,
	.campaign-card-menu[open] summary {
		border-color: var(--red);
		background: rgba(220, 38, 55, .08);
		color: var(--red);
	}
	.campaign-card-dropdown {
		position: absolute;
		top: calc(100% + 4px);
		inset-inline-end: 0;
		inset-inline-start: auto;
		width: 165px;
		padding: 6px;
		border: 1px solid var(--line);
		border-radius: 12px;
		background: var(--card);
		box-shadow: 0 14px 34px rgba(15, 23, 42, .15);
		z-index: 25;
	}
	.campaign-card-dropdown a,
	.campaign-card-dropdown button {
		width: 100%;
		min-height: 40px;
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		border: 0;
		border-radius: 8px;
		background: transparent;
		color: var(--dark);
		text-decoration: none;
		text-align: start;
		font-size: 13px;
		font-weight: 800;
		cursor: pointer;
		transition: background .12s ease;
	}
	.campaign-card-dropdown a:hover,
	.campaign-card-dropdown button:hover {
		background: var(--bg);
	}
	.campaign-card-dropdown form {
		margin: 4px 0 0;
		padding-top: 4px;
		border-top: 1px solid var(--line);
	}
	.campaign-card-dropdown .delete-action {
		color: #dc2626;
	}
	.campaign-card-dropdown .delete-action:hover {
		background: rgba(220, 38, 55, .08);
	}

	.campaign-card-link {
		display: flex;
		flex: 1;
		flex-direction: column;
		text-decoration: none;
		color: inherit;
	}
	.campaign-card-main {
		flex: 1;
		display: flex;
		flex-direction: column;
	}
	.campaign-card h2 {
		margin: 0 0 6px;
		font-size: 17px;
		font-weight: 900;
		line-height: 1.4;
		color: var(--dark);
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		text-overflow: ellipsis;
		word-break: break-word;
	}
	.campaign-card-meta {
		display: flex;
		align-items: center;
		gap: 6px;
		color: var(--muted);
		font-size: 11px;
		font-weight: 700;
		margin-bottom: 14px;
		font-variant-numeric: tabular-nums;
		flex-wrap: wrap;
	}

	.campaign-card-stats {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 8px;
		margin-bottom: 14px;
	}
	.campaign-card .campaign-stat {
		padding: 10px 12px;
		border-radius: 12px;
		border: 1px solid var(--line);
		background: var(--bg);
		display: flex;
		flex-direction: column;
		gap: 3px;
	}
	.campaign-card .campaign-stat span {
		display: flex;
		align-items: center;
		gap: 5px;
		color: var(--muted);
		font-size: 10px;
		font-weight: 800;
	}
	.campaign-card .campaign-stat strong {
		display: block;
		color: var(--dark);
		font-size: 14px;
		font-weight: 900;
		font-variant-numeric: tabular-nums;
	}

	.campaign-card-users {
		display: flex;
		flex-wrap: wrap;
		gap: 5px;
		margin-top: auto;
		padding-bottom: 12px;
	}
	.campaign-user-chip {
		display: inline-flex;
		align-items: center;
		padding: 4px 9px;
		border-radius: 999px;
		background: var(--bg);
		border: 1px solid var(--line);
		color: var(--dark);
		font-size: 10px;
		font-weight: 800;
		max-width: 160px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.campaign-user-chip.is-more {
		background: rgba(220, 38, 55, .08);
		border-color: rgba(220, 38, 55, .2);
		color: var(--red);
	}

	.campaign-card-open {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding-top: 12px;
		border-top: 1px solid var(--line);
		color: var(--red);
		font-size: 12px;
		font-weight: 800;
		margin-top: auto;
	}
	.campaign-card-open .open-icon {
		font-size: 14px;
		transition: transform .18s ease;
	}
	.campaign-card:hover .campaign-card-open .open-icon {
		transform: translateX(-4px);
	}
	html[dir="ltr"] .campaign-card:hover .campaign-card-open .open-icon {
		transform: translateX(4px);
	}

	.campaign-empty {
		padding: 60px 18px;
		border: 1px dashed var(--line);
		border-radius: var(--radius);
		background: var(--card);
		color: var(--muted);
		text-align: center;
		font-weight: 800;
	}
	.campaign-mobile-create { display: none; margin-bottom: 14px; }
	.campaign-pager { margin-top: 20px; }

	@media(max-width: 1100px) { .campaign-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
	@media(max-width: 800px) { .campaign-filters { grid-template-columns: 1fr; } }
	@media(max-width: 700px) { .campaign-grid { grid-template-columns: 1fr; } .campaign-mobile-create { display: inline-flex; } }

	/* Dark Mode */
	html.dark-mode .campaign-filters {
		background: rgba(30, 41, 59, 0.6) !important;
		border-color: var(--line) !important;
	}
	html.dark-mode .campaign-filter-field input,
	html.dark-mode .campaign-filter-field select {
		background: rgba(30, 41, 59, 0.7) !important;
		border-color: var(--line) !important;
		color: var(--dark) !important;
	}
	html.dark-mode .campaign-card {
		background: #1e293b !important;
		border-color: var(--line) !important;
	}
	html.dark-mode .campaign-card .campaign-stat {
		background: rgba(255, 255, 255, 0.03) !important;
		border-color: var(--line) !important;
	}
	html.dark-mode .campaign-card-menu summary {
		background: #1e293b !important;
		border-color: var(--line) !important;
		color: var(--muted) !important;
	}
	html.dark-mode .campaign-card-dropdown {
		background: #1e293b !important;
		border-color: var(--line) !important;
	}
	html.dark-mode .campaign-card-dropdown a,
	html.dark-mode .campaign-card-dropdown button {
		color: var(--dark) !important;
	}
	html.dark-mode .campaign-card-dropdown a:hover,
	html.dark-mode .campaign-card-dropdown button:hover {
		background: rgba(255, 255, 255, 0.08) !important;
	}
	html.dark-mode .campaign-user-chip {
		background: rgba(255, 255, 255, 0.05) !important;
		border-color: var(--line) !important;
		color: var(--dark) !important;
	}
</style>
@endpush

@section('content')
	@can('campaigns.create')
		<a class="btn primary campaign-mobile-create" href="{{ route('v2.campaigns.create') }}">
			<i class="bi bi-plus-lg"></i> {{ __('crm.new_campaign') }}
		</a>
	@endcan

	<!-- FILTERS BAR -->
	<form class="campaign-filters" id="campaignFilters" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}" method="GET" action="{{ route('v2.campaigns.index') }}">
		<div class="campaign-filter-field">
			<label for="campaignSearch"><i class="bi bi-search"></i> {{ __('crm.campaign_name_search') }}</label>
			<input id="campaignSearch" type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('crm.campaign_name_placeholder') }}">
		</div>
		<div class="campaign-filter-field">
			<label for="campaignState"><i class="bi bi-tag"></i> {{ __('crm.campaign_status') }}</label>
			<select class="campaign-live-filter" id="campaignState" name="state">
				<option value="">{{ __('crm.all_states') }}</option>
				<option value="active" @selected($filters['state'] === 'active')>{{ __('crm.active_now') }}</option>
				<option value="upcoming" @selected($filters['state'] === 'upcoming')>{{ __('crm.upcoming') }}</option>
				<option value="ended" @selected($filters['state'] === 'ended')>{{ __('crm.ended') }}</option>
			</select>
		</div>
		<div class="campaign-filter-field">
			<label for="campaignUser"><i class="bi bi-person-check"></i> {{ __('crm.campaign_user') }}</label>
			<select class="campaign-live-filter" id="campaignUser" name="user_id">
				<option value="">{{ __('crm.all_users') }}</option>
				@foreach ($filterUsers as $filterUser)
					<option value="{{ $filterUser->id }}" @selected($filters['user_id'] === $filterUser->id)>{{ $filterUser->name }}</option>
				@endforeach
			</select>
		</div>
	</form>

	<!-- CAMPAIGNS CARDS GRID -->
	@if ($campaigns->isNotEmpty())
		<section class="campaign-grid" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
			@foreach ($campaigns as $campaign)
				@php
					$isActive = $campaign->isActive();
					$isUpcoming = $campaign->isUpcoming();
					$isEnded = $campaign->isEnded();
					$statusClass = $isActive ? 'is-active' : ($isUpcoming ? 'is-upcoming' : 'is-ended');
					$statusLabel = $isActive ? __('crm.active_now') : ($isUpcoming ? __('crm.upcoming') : __('crm.ended'));
				@endphp
				<article class="campaign-card">
					<div class="campaign-card-header">
						<div class="campaign-card-brand">
							@if ($campaign->image_path)
								<img class="campaign-card-thumb" src="{{ Storage::disk('local')->url($campaign->image_path) }}" alt="{{ $campaign->name }}">
							@else
								<div class="campaign-card-icon">
									<i class="bi bi-megaphone"></i>
								</div>
							@endif
							<div class="campaign-card-tags">
								<span class="campaign-status-pill {{ $statusClass }}">
									@if ($isActive) <span class="status-pulse-dot"></span> @endif
									{{ $statusLabel }}
								</span>
								<span class="campaign-leads-pill">
									<i class="bi bi-people"></i> {{ $campaign->leads_count }} {{ __('عميل') }}
								</span>
							</div>
						</div>

						@if ($campaign->can_manage || auth()->user()->can('campaigns.reports'))
							<details class="campaign-card-menu">
								<summary aria-label="خيارات الحملة">⋮</summary>
								<div class="campaign-card-dropdown">
									<a href="{{ route('v2.campaigns.show', $campaign) }}">
										<i class="bi bi-eye"></i> {{ __('crm.view_details') }}
									</a>
									@can('campaigns.reports')
										<a href="{{ route('v2.campaigns.reports', ['campaign_id' => $campaign->id]) }}">
											<i class="bi bi-bar-chart-line"></i> {{ __('crm.reports') }}
										</a>
									@endcan
									@if ($campaign->can_manage)
										<a href="{{ route('v2.campaigns.edit', $campaign) }}">
											<i class="bi bi-pencil-square"></i> {{ __('crm.edit_campaign') }}
										</a>
										<form method="POST" action="{{ route('v2.campaigns.destroy', $campaign) }}" onsubmit="return confirm('{{ __('crm.confirm_delete_campaign_warning') }}')">
											@csrf
											@method('DELETE')
											<button type="submit" class="delete-action">
												<i class="bi bi-trash"></i> {{ __('crm.delete_campaign') }}
											</button>
										</form>
									@endif
								</div>
							</details>
						@endif
					</div>

					<a class="campaign-card-link" href="{{ route('v2.campaigns.show', $campaign) }}">
						<div class="campaign-card-main">
							<h2>{{ $campaign->name }}</h2>

							<div class="campaign-card-meta">
								<i class="bi bi-calendar3"></i>
								<span>{{ $campaign->starts_at?->format('d/m/Y') ?? '—' }}</span>
								<span class="meta-arrow">←</span>
								<span>{{ $campaign->ends_at?->format('d/m/Y') ?? '—' }}</span>
							</div>

							<div class="campaign-card-stats">
								<div class="campaign-stat">
									<span><i class="bi bi-people-fill"></i> {{ __('crm.campaign_leads') }}</span>
									<strong>{{ number_format($campaign->leads_count) }}</strong>
								</div>
								<div class="campaign-stat">
									<span><i class="bi bi-cash-stack"></i> {{ __('crm.campaign_cost') }}</span>
									<strong>{{ number_format((float) ($campaign->cost ?? 0), 2) }} <small>ج.م</small></strong>
								</div>
							</div>

							@if ($campaign->users->isNotEmpty())
								<div class="campaign-card-users">
									@foreach ($campaign->users->take(4) as $assignedUser)
										<span class="campaign-user-chip">{{ $assignedUser->name }}</span>
									@endforeach
									@if ($campaign->users->count() > 4)
										<span class="campaign-user-chip is-more">+{{ $campaign->users->count() - 4 }}</span>
									@endif
								</div>
							@endif
						</div>

						<div class="campaign-card-open">
							<span>{{ __('فتح تفاصيل الحملة') }}</span>
							<i class="bi bi-arrow-left rtl:rotate-0 ltr:rotate-180 open-icon"></i>
						</div>
					</a>
				</article>
			@endforeach
		</section>

		@if ($campaigns->hasPages())
			<div class="campaign-pager">
				{{ $campaigns->links() }}
			</div>
		@endif
	@else
		<div class="campaign-empty" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
			<i class="bi bi-megaphone" style="font-size:36px;display:block;margin-bottom:12px;color:var(--muted)"></i>
			<p style="margin:0 0 16px;font-size:15px">{{ __('crm.no_campaigns_found') }}</p>
			@can('campaigns.create')
				<a class="btn primary" href="{{ route('v2.campaigns.create') }}">
					<i class="bi bi-plus-lg"></i> {{ __('crm.new_campaign') }}
				</a>
			@endcan
		</div>
	@endif
@endsection

@push('scripts')
<script>
(() => {
	const filtersForm = document.getElementById('campaignFilters');
	const liveSelects = document.querySelectorAll('.campaign-live-filter');
	liveSelects.forEach(select => {
		select.addEventListener('change', () => {
			filtersForm?.submit();
		});
	});

	// Close card dropdowns on outside click
	document.addEventListener('click', (e) => {
		document.querySelectorAll('.campaign-card-menu[open]').forEach(menu => {
			if (!menu.contains(e.target)) {
				menu.removeAttribute('open');
			}
		});
	});
})();
</script>
@endpush
