@php
    $yieldPageTitle = trim($__env->yieldContent('page-title'));
    $yieldHeading = trim($__env->yieldContent('heading'));
    $yieldTitle = trim($__env->yieldContent('title'));
    $finalTitle = $title ?? $pageTitle ?? ($yieldPageTitle ?: ($yieldHeading ?: ($yieldTitle ?: null)));

    $yieldPageTitleAr = trim($__env->yieldContent('page-title-ar'));
    $yieldHeadingAr = trim($__env->yieldContent('heading-ar'));
    $finalTitleAr = $titleAr ?? $pageTitleAr ?? ($yieldPageTitleAr ?: ($yieldHeadingAr ?: null));

    $yieldPageDesc = trim($__env->yieldContent('page-description'));
    $yieldSubheading = trim($__env->yieldContent('subheading'));
    $finalSubtitle = $subtitle ?? $pageDescription ?? ($yieldPageDesc ?: ($yieldSubheading ?: null));

    $yieldBackUrl = trim($__env->yieldContent('back-url'));
    $yieldBackRoute = trim($__env->yieldContent('back-route'));
    $finalBackUrl = $backUrl ?? $backRoute ?? ($yieldBackUrl ?: ($yieldBackRoute ?: null));

    $yieldBackTitle = trim($__env->yieldContent('back-title'));
    $finalBackTitle = $backTitle ?? ($yieldBackTitle ?: __('crm.back'));

    $yieldIcon = trim($__env->yieldContent('page-icon'));
    $finalIcon = $icon ?? $pageIcon ?? ($yieldIcon ?: null);
@endphp
@once
<style>
/* Standardized Topbar Scoped Rules */
.crm-topbar,
.topbar.crm-topbar,
header.crm-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    min-height: 72px;
    padding: 14px 20px;
    background: var(--card, var(--bg-card, #ffffff));
    border: 1px solid var(--line, var(--border-color, #e5e7eb));
    border-radius: 16px;
    box-shadow: var(--shadow, var(--shadow-card, 0 10px 30px rgba(17, 24, 39, .04)));
    margin-bottom: 20px;
    position: relative;
    z-index: 50;
    box-sizing: border-box;
    width: 100%;
}

.crm-topbar-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    flex: 1 1 auto;
}

.crm-topbar-menu-btn {
    display: none;
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    border-radius: 12px;
    border: 1px solid var(--line, var(--border-color, #e5e7eb));
    background: var(--card, var(--bg-card, #ffffff));
    color: var(--dark, var(--text-main, #182033));
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    box-sizing: border-box;
    align-items: center;
    justify-content: center;
}

.crm-topbar-menu-btn:hover,
.crm-topbar-menu-btn:focus-visible {
    background: #fff5f6;
    border-color: rgba(220, 38, 55, 0.35);
    color: var(--red, #dc2637);
    transform: translateY(-1px);
}

.crm-topbar-back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border-radius: 12px;
    border: 1px solid var(--line, var(--border-color, #e5e7eb));
    background: var(--card, var(--bg-card, #ffffff));
    color: var(--dark, var(--text-main, #182033));
    text-decoration: none;
    font-size: 16px;
    cursor: pointer;
    transition: background .2s ease, border-color .2s ease, color .2s ease, transform .2s ease;
    box-sizing: border-box;
}

.crm-topbar-back-btn:hover,
.crm-topbar-back-btn:focus-visible {
    background: #fff5f6;
    border-color: rgba(220, 38, 55, 0.35);
    color: var(--red, #dc2637);
    transform: translateY(-1px);
}

.crm-topbar-title {
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.crm-topbar-title h1 {
    margin: 0;
    font-size: 20px;
    font-weight: 800;
    color: var(--dark, var(--text-main, #182033));
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.crm-topbar-title h1 i {
    color: var(--red, #dc2637);
    font-size: 20px;
    flex-shrink: 0;
}

.crm-topbar-title p {
    margin: 3px 0 0;
    color: var(--text-secondary, var(--text-muted, #6b7280));
    font-size: 13px;
    font-weight: 500;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.crm-topbar-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 0 0 auto;
}

.crm-topbar-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.crm-topbar-actions .btn,
.crm-topbar-actions .btn-primary,
.crm-topbar-actions .btn-soft,
.crm-topbar-actions .btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 42px;
    padding: 8px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .2s ease;
    white-space: nowrap;
    box-sizing: border-box;
}

.crm-topbar-actions .btn.primary,
.crm-topbar-actions .btn-primary {
    background: linear-gradient(135deg, #e83243, #c91d2e);
    color: #fff;
    border-color: #c91d2e;
    box-shadow: 0 4px 14px rgba(220, 38, 55, 0.25);
}

.crm-topbar-actions .btn.primary:hover,
.crm-topbar-actions .btn-primary:hover {
    background: linear-gradient(135deg, #f03e4f, #d62537);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(220, 38, 55, 0.35);
}

.crm-topbar-actions .btn.soft,
.crm-topbar-actions .btn-soft {
    background: #f4f6f9;
    color: var(--dark, #182033);
    border-color: var(--line, #e5e7eb);
}

.crm-topbar-actions .btn.soft:hover,
.crm-topbar-actions .btn-soft:hover {
    background: #fff5f6;
    border-color: rgba(220, 38, 55, 0.35);
    color: var(--red, #dc2637);
    transform: translateY(-1px);
}

.crm-topbar-actions .btn.ghost,
.crm-topbar-actions .btn-ghost {
    background: transparent;
    color: var(--text-secondary, var(--text-muted, #6b7280));
    border-color: transparent;
}

.crm-topbar-actions .btn.ghost:hover,
.crm-topbar-actions .btn-ghost:hover {
    background: #fff5f6;
    border-color: rgba(220, 38, 55, 0.25);
    color: var(--red, #dc2637);
    transform: translateY(-1px);
}

[dir="rtl"] .rtl-flip {
    transform: scaleX(1);
}
[dir="ltr"] .rtl-flip,
html:not([dir="rtl"]) .rtl-flip {
    transform: scaleX(-1);
}

@media (max-width: 900px) {
    .crm-topbar-menu-btn {
        display: inline-grid;
        place-items: center;
    }
}

@media (max-width: 768px) {
    .crm-topbar,
    .topbar.crm-topbar,
    header.crm-topbar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
        padding: 12px 14px;
        min-height: auto;
        border-radius: 14px;
        margin-bottom: 16px;
    }

    .crm-topbar-left {
        width: 100%;
        justify-content: flex-start;
        gap: 10px;
    }

    .crm-topbar-title h1 {
        font-size: 17px;
    }

    .crm-topbar-title p {
        font-size: 12px;
    }

    .crm-topbar-right {
        width: 100%;
        justify-content: space-between;
        gap: 8px;
        flex-wrap: wrap;
    }

    .crm-topbar-actions {
        flex: 1 1 auto;
        gap: 6px;
    }

    .crm-topbar-actions .btn {
        flex: 1 1 auto;
        min-height: 40px;
        justify-content: center;
        padding: 6px 12px;
        font-size: 13px;
    }

    .crm-topbar-user {
        flex: 0 0 auto;
        margin-inline-start: auto;
    }
}

@media (max-width: 430px) {
    .crm-topbar-title h1 {
        font-size: 16px;
    }

    .crm-topbar-actions .btn {
        font-size: 12px;
        padding: 6px 10px;
    }
}

html.dark-mode .crm-topbar,
html.dark-mode .topbar.crm-topbar,
html.dark-mode header.crm-topbar {
    background: var(--bg-card, rgba(24, 24, 27, .75)) !important;
    border-color: var(--border-color, rgba(255, 255, 255, .08)) !important;
    box-shadow: var(--shadow-card, 0 10px 30px rgba(0, 0, 0, .5)) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}

html.dark-mode .crm-topbar-title h1 {
    color: var(--text-primary, #f4f4f5) !important;
}

html.dark-mode .crm-topbar-title p {
    color: var(--text-secondary, #a1a1aa) !important;
}

html.dark-mode .crm-topbar-menu-btn,
html.dark-mode .crm-topbar-back-btn {
    background: rgba(255, 255, 255, .04) !important;
    border-color: rgba(255, 255, 255, .1) !important;
    color: var(--text-primary, #f4f4f5) !important;
}

html.dark-mode .crm-topbar-menu-btn:hover,
html.dark-mode .crm-topbar-menu-btn:focus-visible,
html.dark-mode .crm-topbar-back-btn:hover,
html.dark-mode .crm-topbar-back-btn:focus-visible {
    background: rgba(239, 68, 68, .18) !important;
    border-color: rgba(239, 68, 68, .45) !important;
    color: #f87171 !important;
}

html.dark-mode .crm-topbar-actions .btn.soft,
html.dark-mode .crm-topbar-actions .btn-soft {
    background: rgba(255, 255, 255, .06) !important;
    border-color: rgba(255, 255, 255, .1) !important;
    color: var(--text-primary, #f4f4f5) !important;
}

html.dark-mode .crm-topbar-actions .btn.soft:hover,
html.dark-mode .crm-topbar-actions .btn-soft:hover {
    background: rgba(239, 68, 68, .18) !important;
    border-color: rgba(239, 68, 68, .45) !important;
    color: #f87171 !important;
}

html.dark-mode .crm-topbar-actions .btn.ghost,
html.dark-mode .crm-topbar-actions .btn-ghost {
    background: transparent !important;
    border-color: transparent !important;
    color: var(--text-secondary, #a1a1aa) !important;
}

html.dark-mode .crm-topbar-actions .btn.ghost:hover,
html.dark-mode .crm-topbar-actions .btn-ghost:hover {
    background: rgba(239, 68, 68, .18) !important;
    border-color: rgba(239, 68, 68, .45) !important;
    color: #f87171 !important;
}
</style>
@endonce

<header class="crm-topbar topbar dash-top-header" data-crm-topbar>
    <div class="crm-topbar-left dash-header-left topbar-left">
        {{-- Mobile drawer menu toggle --}}
        <button
            class="crm-topbar-menu-btn dash-menu-toggle menu-button"
            id="menu"
            type="button"
            aria-label="{{ __('crm.open_menu') ?? 'Menu' }}"
        >
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        {{-- Optional Back Button --}}
        @if ($finalBackUrl)
            <a
                href="{{ $finalBackUrl }}"
                class="crm-topbar-back-btn btn soft small"
                title="{{ $finalBackTitle }}"
                aria-label="{{ $finalBackTitle }}"
            >
                <i class="bi bi-arrow-right rtl-flip" aria-hidden="true"></i>
            </a>
        @endif

        {{-- Title and Subtitle --}}
        <div class="crm-topbar-title dash-header-title">
            @if ($finalTitle)
                <h1 @if($finalTitleAr) data-ar-label="{{ $finalTitleAr }}" @endif>
                    @if ($finalIcon)
                        <i class="bi {{ $finalIcon }}" aria-hidden="true"></i>
                    @endif
                    <span>{!! $finalTitle !!}</span>
                </h1>
            @endif
            @if ($finalSubtitle)
                <p id="date">{!! $finalSubtitle !!}</p>
            @endif
        </div>
    </div>

    <div class="crm-topbar-right dash-header-actions top-actions settings-user-tools">
        {{-- Page Action Buttons --}}
        @if (isset($actions) && $actions)
            <div class="crm-topbar-actions">
                {!! $actions !!}
            </div>
        @elseif (trim($__env->yieldContent('top-actions')))
            <div class="crm-topbar-actions">
                @yield('top-actions')
            </div>
        @elseif (isset($slot) && trim($slot))
            <div class="crm-topbar-actions">
                {{ $slot }}
            </div>
        @endif

        {{-- Profile Dropdown & Notification Bell --}}
        @include('partials.profile-dropdown')
    </div>
</header>

@once
<script>
(() => {
    const bindMenuToggle = () => {
        const menuButtons = document.querySelectorAll('#menu, #crmMenuButton, .crm-topbar-menu-btn, .dash-menu-toggle, .menu-button');
        
        // Ensure a backdrop overlay element exists in DOM
        let overlay = document.querySelector('#crmSidebarOverlay, .crm-overlay, .overlay, #overlay');
        if (!overlay) {
            overlay = document.createElement('button');
            overlay.id = 'crmSidebarOverlay';
            overlay.type = 'button';
            overlay.className = 'crm-overlay overlay';
            overlay.setAttribute('aria-label', 'Close Menu');
            document.body.appendChild(overlay);
        }

        const closeDrawer = () => {
            document.body.classList.remove('side-open', 'crm-side-open', 'transfer-side-open');
        };

        const toggleDrawer = (event) => {
            event.stopPropagation();
            const isOpen = document.body.classList.contains('crm-side-open') || document.body.classList.contains('side-open');
            if (isOpen) {
                closeDrawer();
            } else {
                document.body.classList.add('crm-side-open', 'side-open', 'transfer-side-open');
            }
        };

        menuButtons.forEach((btn) => {
            if (btn.dataset.topbarBound === '1') return;
            btn.dataset.topbarBound = '1';
            btn.addEventListener('click', toggleDrawer);
        });

        document.querySelectorAll('#overlay, #crmSidebarOverlay, .crm-overlay, .overlay').forEach((ol) => {
            if (ol.dataset.topbarBound === '1') return;
            ol.dataset.topbarBound = '1';
            ol.addEventListener('click', closeDrawer);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDrawer();
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth > 900) closeDrawer();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindMenuToggle);
    } else {
        bindMenuToggle();
    }
})();
</script>
@endonce
