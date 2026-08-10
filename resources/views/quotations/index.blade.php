<!doctype html>
<html lang="ar" dir="rtl">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >
 <title>عروض الأسعار | CRM v2</title>

 <link
  rel="stylesheet"
  href="{{ asset('quotation-generator/crm-module.css') }}?v=crm-module-no-sidebar-v5"
 >
</head>
<body>
@include('partials.page-loader')
 <!-- CRM QUOTATION SHARED SIDEBAR V1 START -->
 <div class="crm-list-layout">

  @include('partials.crm-sidebar')

  <main class="crm-list-main">
 <!-- CRM QUOTATION SHARED SIDEBAR V1 END -->

   <header class="crm-list-head">
    <div>
     <h1>عروض الأسعار</h1>
     <p>
      جميع عروض الأسعار المحفوظة داخل CRM.
     </p>
    </div>

    <a
     class="crm-qbtn"
     href="{{ route('v2.quotations.create') }}"
    >
     ＋ إنشاء عرض سعر
    </a>
   </header>

   <form
    class="crm-qsearch"
    method="get"
    action="{{ route('v2.quotations.index') }}"
   >
    <input
     type="search"
     name="q"
     value="{{ $term }}"
     placeholder="بحث برقم العرض أو العميل أو مقدم العرض..."
    >

    <button
     class="crm-qbtn light"
     type="submit"
    >
     بحث
    </button>

    @if ($term !== '')
     <a
      class="crm-qbtn light"
      href="{{ route('v2.quotations.index') }}"
     >
      إلغاء
     </a>
    @endif
   </form>

   <section class="crm-qcard">

    @if ($quotations->count())

     <table>
      <thead>
       <tr>
        <th>#</th>
        <th>رقم العرض</th>
        <th>العميل</th>
        <th>التاريخ</th>
        <th>مقدم العرض</th>
        <th>النظام / العنوان</th>
        <th>الإجمالي</th>
        <th>تاريخ الحفظ</th>
        <th>الإجراء</th>
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
           class="crm-qbtn light"
           href="{{
            route(
             'v2.quotations.show',
             $quotation
            )
           }}"
          >
           فتح / طباعة
          </a>
         </td>

        </tr>

       @endforeach

      </tbody>
     </table>

    @else

     <div class="crm-qempty">
      لا توجد عروض أسعار محفوظة حتى الآن.
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
        السابق
       </a>
      @endif

      @if ($quotations->hasMorePages())
       <a
        class="crm-qbtn light"
        href="{{ $quotations->nextPageUrl() }}"
       >
        التالي
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
