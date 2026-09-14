@extends('settings.layout')

@section('title', __('crm.users'))
@section('heading', __('crm.users'))
@section('subheading', __('crm.users_subtitle'))
@section('page-icon', 'bi-people')

@section('top-actions')
    @can('users.create')
        <a class="btn primary" href="{{ route('v2.settings.users.create') }}">
            <i class="bi bi-plus-lg"></i> {{ __('crm.new_user') }}
        </a>
    @endcan
@endsection

@section('content')
<section class="panel" data-ar-label="{{ __('crm.users_legacy', [], 'ar') }}">
    <div class="panel-head">
        <div>
            <h2>{{ __('crm.user_list') }}</h2>
            <p>{{ __('crm.inactive_user_notice') }}</p>
        </div>
        @can('users.create')
            <a class="btn primary" href="{{ route('v2.settings.users.create') }}">{{ __('crm.new_user') }}</a>
        @endcan
    </div>

    <form class="toolbar" method="GET" action="{{ route('v2.settings.users.index') }}">
        <div class="search" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="search" name="q" value="{{ $search }}" placeholder="{{ __('crm.user_search_placeholder') }}" style="min-height:44px; min-width:200px; flex:1;">
            <button class="btn soft" style="min-height:44px;">{{ __('crm.search') }}</button>
            @if ($search !== '')
                <a class="btn soft" href="{{ route('v2.settings.users.index') }}" style="min-height:44px;">{{ __('crm.cancel') }}</a>
            @endif
        </div>
    </form>

    <div class="table-wrap" style="margin-top:18px">
        <table>
            <thead>
                <tr>
                    <th>{{ __('crm.user') }}</th>
                    <th>{{ __('crm.username') }}</th>
                    @if(!empty($isVoipConnected))
                    <th>{{ __('crm.voip_extension') }}</th>
                    @endif
                    <th>{{ __('crm.groups') }}</th>
                    <th>{{ __('crm.status') }}</th>
                    <th>{{ __('crm.last_login') }}</th>
                    <th>{{ __('crm.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $managedUser)
                    <tr>
                        <td>
                            <strong>{{ $managedUser->name }}</strong>
                            <div class="hint">{{ $managedUser->email ?: __('crm.no_email') }}</div>
                        </td>
                        <td><code>{{ $managedUser->username }}</code></td>
                        @if(!empty($isVoipConnected))
                        <td>
                            @if ($managedUser->voip_extension)
                                <span class="badge active" style="font-family:monospace;display:inline-flex;align-items:center;gap:4px;">
                                    <svg width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.75 1.75 0 0 1-1.657-.459L5.482 8.062a1.75 1.75 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/></svg>
                                    {{ $managedUser->voip_extension }}
                                </span>
                            @else
                                <span class="hint">—</span>
                            @endif
                        </td>
                        @endif
                        <td>
                            @forelse ($managedUser->groups as $group)
                                <span class="badge {{ $group->isSuperAdmin() ? 'system' : '' }}">{{ $group->name }}</span>
                            @empty
                                <span class="hint">{{ __('crm.no_group') }}</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge {{ $managedUser->is_active ? 'active' : 'inactive' }}">
                                {{ $managedUser->is_active ? __('crm.active') : __('crm.inactive') }}
                            </span>
                        </td>
                        <td>{{ $managedUser->last_login_at?->format('Y-m-d H:i') ?? __('crm.never_logged_in') }}</td>
                        <td>
                            <div class="actions">
                                @if (!$managedUser->isSuperAdmin() || auth()->user()->isSuperAdmin())
                                @can('users.update')
                                    <a class="btn small soft" href="{{ route('v2.settings.users.edit', $managedUser) }}">{{ __('crm.edit') }}</a>
                                @endcan
                                @can('users.activate')
                                    @if (!auth()->user()->is($managedUser))
                                        <form method="POST" action="{{ route('v2.settings.users.status', $managedUser) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="{{ $managedUser->is_active ? 0 : 1 }}">
                                            <button class="btn small {{ $managedUser->is_active ? 'danger' : 'primary' }}">
                                                {{ $managedUser->is_active ? __('crm.deactivate') : __('crm.activate') }}
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ !empty($isVoipConnected) ? 7 : 6 }}" style="text-align:center;color:#697386;padding:30px">{{ __('crm.no_matching_users') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $users->links() }}</div>
</section>
@endsection
