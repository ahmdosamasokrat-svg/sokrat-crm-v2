@extends('settings.layout')

@section('title', __('crm.settings'))
@section('heading', __('crm.system_settings'))
@section('subheading', __('crm.manage_access_from_one_place'))
@section('page-icon', 'bi-sliders')

@section('content')
<!-- STATS SUMMARY CARDS -->
<section class="grid stats-grid">
    <article class="stat-card">
        <span><i class="bi bi-people-fill" style="color:#3b82f6"></i> {{ __('crm.total_users') }}</span>
        <b>{{ number_format($usersCount) }}</b>
    </article>
    <article class="stat-card">
        <span><i class="bi bi-person-check-fill" style="color:#16a34a"></i> {{ __('crm.active_users') }}</span>
        <b>{{ number_format($activeUsersCount) }}</b>
    </article>
    <article class="stat-card">
        <span><i class="bi bi-diagram-2-fill" style="color:#f59e0b"></i> {{ __('crm.groups') }}</span>
        <b>{{ number_format($groupsCount) }}</b>
    </article>
    <article class="stat-card">
        <span><i class="bi bi-shield-lock-fill" style="color:#8b5cf6"></i> {{ __('crm.defined_permissions') }}</span>
        <b>{{ number_format($permissionsCount) }}</b>
    </article>
</section>

<!-- QUICK MANAGEMENT ACCESS CARDS -->
<section class="panel" style="margin-top:20px">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-grid"></i> {{ __('crm.access_management') }}</h2>
            <p>{{ __('crm.permissions_inherited_notice') }}</p>
        </div>
    </div>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <a class="stat-card action-card" href="{{ route('v2.settings.stages.index') }}">
            <span><i class="bi bi-diagram-3" style="color:#dc2637"></i> {{ __('crm.stages_and_statuses') }}</span>
            <b style="font-size:17px;margin-top:8px">{{ __('crm.stages_settings_heading') }}</b>
        </a>
        @can('users.view')
            <a class="stat-card action-card" href="{{ route('v2.settings.users.index') }}">
                <span><i class="bi bi-people" style="color:#2563eb"></i> {{ __('crm.users') }}</span>
                <b style="font-size:17px;margin-top:8px">{{ __('crm.manage_accounts') }}</b>
            </a>
        @endcan
        @can('groups.view')
            <a class="stat-card action-card" href="{{ route('v2.settings.groups.index') }}">
                <span><i class="bi bi-diagram-2" style="color:#059669"></i> {{ __('crm.groups') }}</span>
                <b style="font-size:17px;margin-top:8px">{{ __('crm.manage_groups') }}</b>
            </a>
            <a class="stat-card action-card" href="{{ route('v2.settings.permissions.index') }}">
                <span><i class="bi bi-shield-lock" style="color:#7c3aed"></i> {{ __('crm.permissions_matrix') }}</span>
                <b style="font-size:17px;margin-top:8px">{{ __('crm.access_distribution') }}</b>
            </a>
        @endcan
        @can('notifications.manage')
            <a class="stat-card action-card" href="{{ route('v2.settings.notifications.index') }}">
                <span><i class="bi bi-bell" style="color:#ea580c"></i> {{ __('crm.notifications') }}</span>
                <b style="font-size:17px;margin-top:8px">{{ __('crm.manage_notification_rules') }}</b>
            </a>
        @endcan
        @can('voip.settings')
            <a class="stat-card action-card" href="{{ route('v2.settings.voip') }}">
                <span><i class="bi bi-telephone" style="color:#0284c7"></i> {{ __('crm.voip_link') }}</span>
                <b style="font-size:17px;margin-top:8px">{{ __('crm.voip_server_settings') }}</b>
            </a>
        @endcan
    </div>
</section>

<!-- SYSTEM LANGUAGE SECTION -->
<section class="panel" style="margin-top:20px">
    <div class="panel-head">
        <div>
            <h2><i class="bi bi-translate"></i> {{ __('crm.system_language') }}</h2>
            <p>{{ __('crm.language_settings_desc') }}</p>
        </div>
    </div>
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
        <a
            class="stat-card action-card"
            href="{{ route('v2.lang.switch', 'ar') }}"
            style="border-radius: 14px; border: 2px solid {{ app()->getLocale() === 'ar' ? 'var(--red)' : 'var(--line)' }}; background: {{ app()->getLocale() === 'ar' ? (app()->getLocale() === 'ar' ? 'color-mix(in srgb, var(--red) 5%, var(--card))' : 'var(--card)') : 'var(--card)' }}; text-decoration: none; padding: 18px;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 24px; color: var(--red);"><i class="bi bi-translate"></i></span>
                @if (app()->getLocale() === 'ar')
                    <span class="badge active">{{ __('crm.active_now') }}</span>
                @endif
            </div>
            <strong style="display: block; font-size: 16px; color: var(--dark);">{{ __('crm.arabic') }}</strong>
            <small style="display: block; margin-top: 4px; color: var(--muted);">{{ __('crm.arabic_desc') }}</small>
        </a>

        <a
            class="stat-card action-card"
            href="{{ route('v2.lang.switch', 'en') }}"
            style="border-radius: 14px; border: 2px solid {{ app()->getLocale() === 'en' ? 'var(--red)' : 'var(--line)' }}; background: {{ app()->getLocale() === 'en' ? 'color-mix(in srgb, var(--red) 5%, var(--card))' : 'var(--card)' }}; text-decoration: none; padding: 18px;"
        >
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <span style="font-size: 24px; color: #0284c7;"><i class="bi bi-globe"></i></span>
                @if (app()->getLocale() === 'en')
                    <span class="badge active">{{ __('crm.active_now') }}</span>
                @endif
            </div>
            <strong style="display: block; font-size: 16px; color: var(--dark);">{{ __('crm.english') }}</strong>
            <small style="display: block; margin-top: 4px; color: var(--muted);">{{ __('crm.english_desc') }}</small>
        </a>
    </div>
</section>
@endsection
