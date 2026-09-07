<!-- HERO PIPELINE -->
<section class="hero-pipeline" id="heroPipelineSection">
    @foreach ($pipelineStages as $index => $hStage)
        @php
            $hColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $hStage->color) ? $hStage->color : '#3478f6';
            $hIcon = !empty($hStage->icon) ? $hStage->icon : match ($hStage->code) {
                'start', 'new' => 'bi-person-plus-fill',
                'no_answer', 'no-answer' => 'bi-telephone-x-fill',
                'interest', 'interested' => 'bi-hand-thumbs-up-fill',
                'not_interested', 'not-interested' => 'bi-hand-thumbs-down-fill',
                'negotiation', 'meeting' => 'bi-calendar-event-fill',
                'quotation' => 'bi-file-earmark-text-fill',
                'discussion' => 'bi-chat-dots-fill',
                'closing_execution', 'contract_closed', 'contract' => 'bi-check-circle-fill',
                'execution' => 'bi-gear-fill',
                'donor' => 'bi-heart-fill',
                default => 'bi-diagram-3-fill',
            };
            $isHeroSelected = ((string) ($filters['stage'] ?? '') === (string) $hStage->id)
                || (isset($selectedStage) && $selectedStage?->id === $hStage->id);
            $heroStageQuery = $isHeroSelected
                ? $queryWithoutStatus
                : array_merge($queryWithoutStatus, ['stage' => $hStage->id]);
        @endphp

        @if ($index > 0)
            <span class="hero-pipe-arrow" aria-hidden="true"><i class="bi bi-chevron-right rtl-flip"></i></span>
        @endif

        <a
            class="hero-stage {{ $isHeroSelected ? 'is-selected' : '' }}"
            href="{{ route('v2.leads', $heroStageQuery) }}"
            style="--stage-color:{{ $hColor }};"
            data-stage-id="{{ $hStage->id }}"
            title="{{ $hStage->localizedName() }}"
        >
            <span class="stage-icon"><i class="bi {{ $hIcon }}"></i></span>
            <span class="stage-label">{{ $hStage->localizedName() }}</span>
        </a>
    @endforeach
</section>

<!-- STAGES STATS CARDS -->
<section class="stats-grid" id="statsGridSection">
    <a href="{{ route('v2.leads', $queryWithoutStatus) }}" class="stat-card {{ empty($filters['stage']) && empty($filters['status']) ? 'selected' : '' }}">
        <span><i class="bi bi-people-fill"></i> {{ __('crm.total_leads') }}</span>
        <b>{{ number_format($totalLeads) }}</b>
    </a>
    @foreach ($pipelineStages as $stg)
        @php
            $stgColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $stg->color) ? $stg->color : '#3478f6';
            $isStgSelected = ((string) ($filters['stage'] ?? '') === (string) $stg->id)
                || (isset($selectedStage) && $selectedStage?->id === $stg->id)
                || (!empty($filters['status']) && in_array($filters['status'], $stg->statuses->pluck('code')->all(), true));
            $stgQuery = $isStgSelected
                ? $queryWithoutStatus
                : array_merge($queryWithoutStatus, ['stage' => $stg->id]);
        @endphp
        <a href="{{ route('v2.leads', $stgQuery) }}" class="stat-card {{ $isStgSelected ? 'selected' : '' }}" style="--status-color:{{ $stgColor }}">
            <span>
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:{{ $stgColor }};flex-shrink:0;"></span>
                {{ $stg->localizedName() }}
            </span>
            <b>{{ number_format($stg->leads_count ?? ($stg->scoped_leads_count ?? 0)) }}</b>
            <small>{{ $stg->isPrimary() ? __('crm.primary_stage_badge') : __('crm.additional_stage_badge') }}</small>
        </a>
    @endforeach
</section>
