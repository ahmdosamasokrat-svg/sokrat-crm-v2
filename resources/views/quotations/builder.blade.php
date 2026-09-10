@php
    $isSaved = isset($quotation);
    $isLegacyRecord = $isLegacy ?? false;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ $isSaved ? ($quotation->quotation_no . ' - ' . $quotation->client_name) : __('crm.create_quotation') }} | CRM v2</title>
  <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0" />
  <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/crm-module.css') }}?v=mpc-v4-c1" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/mpc-v4/styles.css') }}?v=mpc-v4-c1" />
  <style>
    .crm-topbar-menu-btn, .menu-button {
      display: none;
      width: 44px;
      height: 44px;
      min-height: 44px;
      min-width: 44px;
      border-radius: 10px;
      border: 1px solid #e4e8ee;
      background: #fff;
      color: #20283a;
      font-size: 20px;
      cursor: pointer;
      align-items: center;
      justify-content: center;
      touch-action: manipulation;
    }
    @media (max-width: 980px) {
      .crm-topbar-menu-btn, .menu-button { display: inline-flex; }
      .crm-quote-shell { display: block; }
    }
    @media (max-width: 768px) {
      .builder-head { flex-direction: column; align-items: stretch; gap: 12px; }
      .head-actions { width: 100%; justify-content: flex-start; gap: 8px; }
      .head-actions .btn { min-height: 44px; flex: 1 1 auto; }
    }
    [data-theme="dark"] .crm-topbar-menu-btn {
      background: #1e293b;
      border-color: #334155;
      color: #f1f5f9;
    }
  </style>
</head>
<body>
@include('partials.page-loader')

<!-- CRM QUOTATION SHELL START -->
<div class="crm-quote-shell">
  @include('partials.crm-sidebar')
  <button class="crm-overlay" id="crmSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>

  <div class="crm-quote-main">
    @if ($isLegacyRecord)
      {{-- Graceful Historical Record Handling --}}
      <div class="legacy-quotation-wrap">
        <div class="legacy-quotation-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
            <div style="display:flex;align-items:center;gap:12px">
              <button class="crm-topbar-menu-btn menu-button" id="menu" type="button" aria-label="{{ __('crm.open_menu') }}">
                <i class="bi bi-list"></i>
              </button>
              <div>
                <span class="eyebrow" style="color:var(--mpc-blue);font-weight:900;font-size:12px">{{ __('crm.legacy_quotation_title') }}</span>
                <h1 style="margin:2px 0 0;font-size:22px;font-weight:900">#{{ $quotation->quotation_no }} — {{ $quotation->client_name }}</h1>
              </div>
            </div>
            @include('partials.profile-dropdown')
          </div>

          <div class="legacy-notice-box" role="alert">
            <div class="legacy-notice-ar">{{ __('crm.legacy_quotation_notice_ar') }}</div>
            <div class="legacy-notice-en">{{ __('crm.legacy_quotation_notice_en') }}</div>
          </div>

          <div class="legacy-meta-grid">
            <div class="legacy-meta-item">
              <strong>{{ __('crm.quotation_number') }}</strong>
              <span>{{ $quotation->quotation_no ?: '—' }}</span>
            </div>
            <div class="legacy-meta-item">
              <strong>{{ __('crm.client') }}</strong>
              <span>{{ $quotation->client_name ?: '—' }}</span>
            </div>
            <div class="legacy-meta-item">
              <strong>{{ __('crm.date') }}</strong>
              <span>{{ $quotation->quote_date?->format('Y-m-d') ?: '—' }}</span>
            </div>
            <div class="legacy-meta-item">
              <strong>{{ __('crm.total') }}</strong>
              <span style="color:#0b4e92">{{ number_format($quotation->grand_total, 2) }} {{ __('crm.pound') }}</span>
            </div>
            <div class="legacy-meta-item">
              <strong>{{ __('crm.prepared_by') }}</strong>
              <span>{{ $quotation->created_by ?: ($quotation->prepared_by ?: '—') }}</span>
            </div>
            <div class="legacy-meta-item">
              <strong>{{ __('crm.saved_at') }}</strong>
              <span>{{ $quotation->created_at?->format('Y-m-d H:i') ?: '—' }}</span>
            </div>
          </div>

          <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap">
            <a href="{{ route('v2.quotations.index') }}" class="btn secondary" style="display:inline-flex;align-items:center;gap:6px">
              <i class="bi bi-arrow-right"></i>
              <span>{{ __('crm.quotations') }}</span>
            </a>
            @can('quotations.create')
            <a href="{{ route('v2.quotations.create') }}" class="btn primary" style="display:inline-flex;align-items:center;gap:6px">
              <i class="bi bi-plus-lg"></i>
              <span>{{ __('crm.create_quotation') }}</span>
            </a>
            @endcan
          </div>
        </div>
      </div>
    @else
      {{-- Official MPC Quotation Generator v4 --}}
      <div class="app-shell">
        <aside class="builder no-print">
          <div class="builder-scroll">
            <div class="builder-head">
              <div style="display:flex;align-items:center;gap:12px">
                <button class="crm-topbar-menu-btn menu-button" id="menu" type="button" aria-label="{{ __('crm.open_menu') }}">
                  <i class="bi bi-list"></i>
                </button>
                <div>
                  <span class="eyebrow">{{ __('crm.mpc_eyebrow') }}</span>
                  <h1>{{ __('crm.mpc_offer_generator') }}</h1>
                </div>
              </div>
              <div class="head-actions">
                <button type="button" id="loadDemoBtn" class="btn ghost">{{ __('crm.load_demo') }}</button>
                <button type="button" id="resetBtn" class="btn ghost danger-text">{{ __('crm.reset') }}</button>
                <button type="button" id="resetLayoutBtn" class="btn ghost" title="{{ __('crm.reset_panel_width_title') }}">{{ __('crm.default_size') }}</button>
                @include('partials.profile-dropdown')
              </div>
            </div>

            <form id="quoteForm" autocomplete="off">
              <section class="panel">
                <h2>{{ __('crm.offer_type_and_basics') }}</h2>
                <div class="grid two">
                  <label class="span-two">
                    <span>{{ __('crm.offer_type') }}</span>
                    <select id="offerType">
                      <option value="other">{{ __('crm.offer_type_other') }}</option>
                      <option value="iso">{{ __('crm.offer_type_iso') }}</option>
                      <option value="inspection">{{ __('crm.offer_type_inspection') }}</option>
                    </select>
                  </label>
                  <label>
                    <span>{{ __('crm.client_or_entity_name') }}</span>
                    <input id="clientName" type="text" placeholder="{{ __('crm.client_name_placeholder_mpc') }}" />
                  </label>
                  <label>
                    <span>{{ __('crm.quotation_number') }}</span>
                    <input id="quotationNo" type="text" placeholder="{{ __('crm.quotation_no_placeholder_mpc') }}" />
                  </label>
                  <label>
                    <span>{{ __('crm.date') }}</span>
                    <input id="quoteDate" type="date" />
                  </label>
                  <label>
                    <span>{{ __('crm.vat_percent') }}</span>
                    <input id="vatRate" type="number" min="0" step="0.01" value="0" />
                  </label>
                </div>
                <label>
                  <span>{{ __('crm.offer_title') }}</span>
                  <input id="offerTitle" type="text" dir="ltr" />
                </label>
                <label>
                  <span>{{ __('crm.offer_intro') }}</span>
                  <textarea id="introText" rows="4" dir="ltr"></textarea>
                </label>
                <label>
                  <span>{{ __('crm.lead_in_text') }}</span>
                  <input id="leadText" type="text" dir="ltr" />
                </label>
              </section>

              <section class="panel">
                <div class="panel-title-row">
                  <div>
                    <h2>{{ __('crm.financial_table_title') }}</h2>
                    <p class="helper" id="tableHelp">{{ __('crm.financial_table_help') }}</p>
                  </div>
                  <button type="button" id="addRowBtn" class="btn small">{{ __('crm.add_row') }}</button>
                </div>
                <div id="tableEditor" class="table-editor"></div>
                <div class="switch-row">
                  <label class="checkline"><input id="showVatRow" type="checkbox" checked /><span>{{ __('crm.show_vat_row') }}</span></label>
                  <label class="checkline"><input id="formatNumbers" type="checkbox" checked /><span>{{ __('crm.format_numbers') }}</span></label>
                </div>
                <div class="grand-total-card">
                  <span>{{ __('crm.aggregate_grand_total') }}</span>
                  <strong id="aggregateTotal">0</strong>
                </div>
              </section>

              <section id="haccpPanel" class="panel" hidden>
                <div class="panel-title-row">
                  <div>
                    <h2>{{ __('crm.haccp_table_title') }}</h2>
                    <p class="helper">{{ __('crm.haccp_table_help') }}</p>
                  </div>
                </div>
                <div id="haccpEditor" class="haccp-editor"></div>
              </section>

              <section class="panel">
                <div class="panel-title-row">
                  <div>
                    <h2>{{ __('crm.payment_method_title') }}</h2>
                    <p class="helper">{{ __('crm.payment_method_help') }}</p>
                  </div>
                  <button type="button" id="addPaymentBtn" class="btn small">{{ __('crm.add_payment_row') }}</button>
                </div>
                <div class="grid two">
                  <label>
                    <span>{{ __('crm.payment_times_label') }}</span>
                    <input id="paymentTimes" type="text" value="02" dir="ltr" />
                  </label>
                  <label id="paymentYearWrap">
                    <span>{{ __('crm.payment_year_label') }}</span>
                    <input id="paymentYear" type="text" placeholder="{{ __('crm.payment_year_placeholder') }}" dir="ltr" />
                  </label>
                </div>
                <label class="checkline compact-check">
                  <input id="autoPaymentAmounts" type="checkbox" />
                  <span>{{ __('crm.auto_payment_amounts') }}</span>
                </label>
                <div id="paymentEditor" class="payment-editor"></div>
              </section>

              <section class="panel">
                <h2>{{ __('crm.closing_texts_title') }}</h2>
                <label>
                  <span>{{ __('crm.transport_note_label') }}</span>
                  <textarea id="transportNote" rows="2" dir="ltr">Transportation is not included in the offer, and accommodation is not included if required.</textarea>
                </label>
                <label>
                  <span>{{ __('crm.closing_text_label') }}</span>
                  <input id="closingText" type="text" dir="ltr" value="Thanks, and regards" />
                </label>
                <div class="switch-row">
                  <label class="checkline"><input id="showTransportNote" type="checkbox" checked /><span>{{ __('crm.show_transport_note') }}</span></label>
                  <label class="checkline"><input id="showClosing" type="checkbox" checked /><span>{{ __('crm.show_closing_text') }}</span></label>
                </div>
              </section>

              <section class="panel typography-panel">
                <div class="panel-title-row">
                  <h2>{{ __('crm.quote_typography_title') }}</h2>
                  <button type="button" id="resetTypographyBtn" class="btn ghost small">{{ __('crm.restore_docx_design') }}</button>
                </div>
                <p class="helper">{{ __('crm.typography_docx_help') }}</p>
                <div class="grid two typography-grid">
                  <label>
                    <span>{{ __('crm.font_type') }}</span>
                    <select id="quoteFontFamily">
                      <option value="times">Times New Roman</option>
                      <option value="arial">Arial</option>
                      <option value="tahoma">Tahoma</option>
                      <option value="georgia">Georgia</option>
                    </select>
                  </label>
                  <label>
                    <span>{{ __('crm.font_size') }}: <strong id="quoteFontSizeValue">100%</strong></span>
                    <input id="quoteFontSize" class="font-size-range" type="range" min="85" max="115" step="5" value="100" />
                  </label>
                  <div class="color-control">
                    <span class="field-label">{{ __('crm.blue_headings_color') }}</span>
                    <div class="color-row">
                      <input id="quoteAccentColor" type="color" value="#4472c4" aria-label="{{ __('crm.blue_headings_color') }}" />
                      <label class="inline-check"><input id="enableAccentColor" type="checkbox" /> <span>{{ __('crm.customize_color') }}</span></label>
                    </div>
                  </div>
                  <div class="color-control">
                    <span class="field-label">{{ __('crm.body_text_color') }}</span>
                    <div class="color-row">
                      <input id="quoteTextColor" type="color" value="#000000" aria-label="{{ __('crm.body_text_color') }}" />
                      <label class="inline-check"><input id="enableTextColor" type="checkbox" /> <span>{{ __('crm.customize_color') }}</span></label>
                    </div>
                  </div>
                </div>
              </section>
            </form>
          </div>

          <div class="sticky-actions">
            <button type="button" id="crmSaveQuotationBtn" class="btn primary">{{ $isSaved ? __('crm.update_quotation') : __('crm.save_quotation') }}</button>
            <button type="button" id="previewBtn" class="btn secondary">{{ __('crm.preview_quotation') }}</button>
            <button type="button" id="wordBtn" class="btn word">{{ __('crm.export_word') }}</button>
            <button type="button" id="printBtn" class="btn primary">{{ __('crm.create_print_save_pdf') }}</button>
            <span id="crmQuotationSaveStatus" aria-live="polite"></span>
          </div>
        </aside>

        <div id="builderResizer" class="builder-resizer no-print" role="separator" aria-orientation="vertical" aria-label="{{ __('crm.resize_input_panel') }}" title="{{ __('crm.resize_input_hint') }}">
          <span class="resizer-grip" aria-hidden="true"></span>
        </div>

        <main class="preview-stage">
          <div class="preview-toolbar no-print">
            <div>
              <strong>{{ __('crm.a4_preview_title') }}</strong>
              <span id="pageCount">{{ __('crm.page_count_label', ['count' => 1]) }}</span>
            </div>
            <div class="preview-toolbar-actions">
              <button type="button" id="wordTopBtn" class="btn word small">{{ __('crm.export_word') }}</button>
              <button type="button" id="printTopBtn" class="btn primary small">{{ __('crm.create_print_save_pdf') }}</button>
            </div>
          </div>
          <div id="quotePreview" class="quote-preview"></div>
        </main>
      </div>

      <template id="serviceRowTemplate">
        <div class="service-row-editor">
          <div class="service-row-fields"></div>
          <button type="button" class="remove-service-row" title="{{ __('crm.delete_service_row') }}">×</button>
        </div>
      </template>

      <template id="paymentRowTemplate">
        <div class="payment-row-editor">
          <label><span>{{ __('crm.payment_percent_label') }}</span><input class="payment-percent" type="number" min="0" step="0.01" /></label>
          <label><span>{{ __('crm.payment_amount_label') }}</span><input class="payment-amount" type="text" inputmode="decimal" dir="ltr" /></label>
          <label class="payment-due-wrap"><span>{{ __('crm.payment_due_label') }}</span><input class="payment-due" type="text" dir="ltr" /></label>
          <button type="button" class="remove-payment-row" title="{{ __('crm.delete_payment_row') }}">×</button>
        </div>
      </template>

      <script>
        window.MPC_ASSET_BASE = @json(asset('quotation-generator/mpc-v4/assets'));
        window.CRM_QUOTATION_ID = @json(isset($quotation) ? $quotation->id : null);
        window.CRM_QUOTATION_DATA = @json(isset($quotation) ? $quotation->payload : null);
        window.CRM_QUOTATION_PREFILL = @json($prefill ?? null);
        window.CRM_QUOTATION_STORE_URL = @json(route('v2.quotations.store'));
        window.CRM_QUOTATION_UPDATE_URL = @json(isset($quotation) ? route('v2.quotations.update', $quotation) : null);
        window.CRM_I18N = {
          services: @json(app()->getLocale() === 'ar' ? 'الخدمة' : 'Services'),
          required_1: 'Required 1',
          required_2: 'Required 2',
          required_3: 'Required 3',
          remarks: @json(app()->getLocale() === 'ar' ? 'ملاحظات' : 'Remarks'),
          standard_item: 'International Standard / Item',
          certification: 'Certification',
          surveillance_1: 'Surveillance 1',
          surveillance_2: 'Surveillance 2',
          recertification: 'Recertification',
          inspection_service: 'Inspection service',
          initial: 'Initial',
          routen: 'Routen',
          year_1: '1st year',
          year_2: '2nd year',
          year_3: '3rd year',
          delete_service_row: @json(__('crm.delete_service_row')),
          delete_payment_row: @json(__('crm.delete_payment_row')),
          payment_percent_label: @json(__('crm.payment_percent_label')),
          payment_amount_label: @json(__('crm.payment_amount_label')),
          payment_due_label: @json(__('crm.payment_due_label')),
          page_count: @json(__('crm.page_count_label', ['count' => 1])),
          saving: @json(__('crm.saving')),
          save_quotation: @json(__('crm.save_quotation')),
          update_quotation: @json(__('crm.update_quotation')),
          client_name_required: @json(app()->getLocale() === 'ar' ? 'يرجى إدخال اسم العميل / الجهة قبل الحفظ.' : 'Please enter client/entity name before saving.'),
          quotation_no_required: @json(app()->getLocale() === 'ar' ? 'يرجى إدخال رقم عرض السعر قبل الحفظ.' : 'Please enter quotation number before saving.')
        };
      </script>
      <script src="{{ asset('quotation-generator/mpc-v4/assets-data.js') }}?v=mpc-v4-c3"></script>
      <script src="{{ asset('quotation-generator/mpc-v4/script.js') }}?v=mpc-v4-c3"></script>
    @endif
    <script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
  </div>
</div>
<!-- CRM QUOTATION SHELL END -->
</body>
</html>
