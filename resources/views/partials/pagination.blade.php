@if ($paginator->hasPages())
<nav class="crm-pagination-wrapper" role="navigation" aria-label="{{ __('crm.pagination_navigation') ?? 'Pagination Navigation' }}">
    {{-- Results Summary --}}
    <div class="crm-pagination-summary">
        @if (app()->getLocale() === 'ar')
            <span>{{ __('عرض') }} <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong> {{ __('من أصل') }} <strong>{{ number_format($paginator->total()) }}</strong> {{ __('عميل') }}</span>
        @else
            <span>{{ __('Showing') }} <strong>{{ $paginator->firstItem() }}</strong> {{ __('to') }} <strong>{{ $paginator->lastItem() }}</strong> {{ __('of') }} <strong>{{ number_format($paginator->total()) }}</strong> {{ __('results') }}</span>
        @endif
    </div>

    {{-- Pagination Controls --}}
    <ul class="crm-pagination-list">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <li class="crm-page-item disabled" aria-disabled="true" aria-label="{{ __('crm.previous') }}">
                <span class="crm-page-link prev-next">
                    <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                    <span>{{ __('crm.previous') }}</span>
                </span>
            </li>
        @else
            <li class="crm-page-item">
                <a class="crm-page-link prev-next" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('crm.previous') }}">
                    <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}" aria-hidden="true"></i>
                    <span>{{ __('crm.previous') }}</span>
                </a>
            </li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <li class="crm-page-item disabled" aria-disabled="true">
                    <span class="crm-page-link ellipsis">&hellip;</span>
                </li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="crm-page-item active" aria-current="page">
                            <span class="crm-page-link page-number">{{ $page }}</span>
                        </li>
                    @else
                        <li class="crm-page-item">
                            <a class="crm-page-link page-number" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <li class="crm-page-item">
                <a class="crm-page-link prev-next" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('crm.next') }}">
                    <span>{{ __('crm.next') }}</span>
                    <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
                </a>
            </li>
        @else
            <li class="crm-page-item disabled" aria-disabled="true" aria-label="{{ __('crm.next') }}">
                <span class="crm-page-link prev-next">
                    <span>{{ __('crm.next') }}</span>
                    <i class="bi bi-chevron-{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}" aria-hidden="true"></i>
                </span>
            </li>
        @endif
    </ul>
</nav>
@endif
