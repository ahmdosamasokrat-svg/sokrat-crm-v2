<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >
 <title>{{ __('crm.quotations') }} | CRM v2</title>
 <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
 <link rel="stylesheet" href="{{ asset('css/tajawal.css') }}?v=1.0.0">
 <link rel="stylesheet" href="{{ asset('crm-sidebar-shared.css') }}?v=crm-sidebar-collapse-v2">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <link
  rel="stylesheet"
  href="{{ asset('quotation-generator/crm-module.css') }}?v=crm-module-no-sidebar-v6"
 >
 <style>
  .crm-list-head, .crm-topbar, .topbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:20px; flex-wrap:wrap; min-height:56px; }
  .crm-topbar-left, .topbar-left { display:flex; align-items:center; gap:12px; min-width:0; }
  .crm-topbar-title h1, .topbar h1 { margin:0; font-size:26px; font-weight:900; display:flex; align-items:center; gap:8px; }
  .crm-topbar-title p, .topbar p { margin:4px 0 0; color:#7d8798; font-size:13px; }
  .crm-topbar-right, .top-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .crm-topbar-actions { display:inline-flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .crm-topbar-menu-btn, .menu-button { display:none; width:44px; height:44px; min-height:44px; min-width:44px; border-radius:10px; border:1px solid #e4e8ee; background:#fff; color:#20283a; font-size:20px; cursor:pointer; align-items:center; justify-content:center; touch-action:manipulation; }

  @media(max-width:900px){
   .crm-topbar-menu-btn, .menu-button { display:inline-flex; }
  }
  @media(max-width:768px){
   .crm-list-head, .crm-topbar, .topbar { flex-direction:column; align-items:stretch; gap:14px; margin-bottom:16px; }
   .crm-topbar-left, .topbar-left { width:100%; justify-content:flex-start; }
   .crm-topbar-right, .top-actions { width:100%; justify-content:space-between; gap:8px; }
   .crm-topbar-actions { display:flex; flex:1 1 auto; gap:8px; flex-wrap:wrap; }
   .crm-qbtn { flex:1 1 auto; min-height:44px; justify-content:center; }
  }
 </style>
<body>
@include('partials.page-loader')
 <!-- CRM QUOTATION SHARED SIDEBAR V1 START -->
 <div class="crm-list-layout">

  @include('partials.crm-sidebar')
  <button class="crm-overlay" id="crmSidebarOverlay" type="button" aria-label="{{ __('crm.close_menu') }}"></button>

  <main class="crm-list-main">
 <!-- CRM QUOTATION SHARED SIDEBAR V1 END -->

  @php
    ob_start();
  @endphp
    @can('quotations.create')
    <a
      class="btn primary crm-qbtn"
      href="{{ route('v2.quotations.create') }}"
    >
      <i class="bi bi-plus-lg" aria-hidden="true" style="margin-inline-end:6px"></i>
      <span>{{ __('crm.create_quotation') }}</span>
    </a>
    @endcan
  @php
    $quoteActions = ob_get_clean();
  @endphp
  @include('partials.topbar', [
    'title' => __('crm.quotations'),
    'subtitle' => __('crm.saved_quotations_subtitle'),
    'icon' => 'bi-receipt-cutoff',
    'actions' => $quoteActions
  ])

   <form
    class="crm-qsearch"
    method="get"
    action="{{ route('v2.quotations.index') }}"
   >
    <input
     type="search"
     name="q"
     value="{{ $term }}"
     placeholder="{{ __('crm.quotation_search_placeholder') }}"
    >

    <button
     class="btn soft crm-qbtn light"
     type="submit"
    >
     {{ __('crm.search') }}
    </button>

    @if ($term !== '')
     <a
      class="btn soft crm-qbtn light"
      href="{{ route('v2.quotations.index') }}"
     >
      {{ __('crm.cancel') }}
     </a>
    @endif
   </form>

   <section class="crm-qcard">

    @if ($quotations->count())

     <table>
      <thead>
       <tr>
        <th>#</th>
        <th>{{ __('crm.quotation_number') }}</th>
        <th>{{ __('crm.client') }}</th>
        <th>{{ __('crm.date') }}</th>
        <th>{{ __('crm.prepared_by') }}</th>
        <th>{{ __('crm.system_title') }}</th>
        <th>{{ __('crm.total') }}</th>
        <th>{{ __('crm.saved_at') }}</th>
        <th>{{ __('crm.action') }}</th>
       </tr>
      </thead>

      <tbody>

       @foreach ($quotations as $quotation)

        <tr>

         <td>
          {{ $quotation->id }}
         </td>

         <td>
          <strong>
           {{ $quotation->quotation_no }}
          </strong>
         </td>

         <td>
          {{ $quotation->client_name }}
         </td>

         <td>
          {{
           $quotation->quote_date
            ? $quotation->quote_date->format('Y-m-d')
            : '—'
          }}
         </td>

         <td>
          {{ $quotation->prepared_by ?: '—' }}
         </td>

         <td>
          {{ $quotation->system_title ?: '—' }}
         </td>

         <td class="crm-qmoney">
          {{
           number_format(
            (float)
            $quotation->grand_total,
            2
           )
          }}
          جنيه
         </td>

         <td>
          {{
           optional(
            $quotation->created_at
           )->format(
            'Y-m-d H:i'
           )
          }}
         </td>

         <td>
          <a
           class="btn small soft crm-qbtn light"
           href="{{
            route(
             'v2.quotations.show',
             $quotation
            )
           }}"
          >
           {{ __('crm.open_print') }}
          </a>

        </tr>

       @endforeach

      </tbody>
     </table>

    @else

     <div class="crm-qempty">
      {{ __('crm.no_saved_quotations') }}
     </div>

    @endif

   </section>

   @if ($quotations->hasPages())

    <div class="crm-qpager">

     <span>
      صفحة
      {{ $quotations->currentPage() }}
      من
      {{ $quotations->lastPage() }}
     </span>

     <div class="crm-qpager-actions">

      @if (!$quotations->onFirstPage())
       <a
        class="crm-qbtn light"
        href="{{ $quotations->previousPageUrl() }}"
       >
        {{ __('crm.previous') }}
       </a>
      @endif

      @if ($quotations->hasMorePages())
       <a
        class="crm-qbtn light"
        href="{{ $quotations->nextPageUrl() }}"
       >
        {{ __('crm.next') }}
       </a>
      @endif

     </div>

    </div>

   @endif

  </main>
 </div>

 <script
  src="{{ asset('quotation-generator/crm-sidebar.js') }}"
 ></script>
</body>
</html>
