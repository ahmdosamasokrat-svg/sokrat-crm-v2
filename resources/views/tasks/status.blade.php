@extends('leads.transfer-layout')

@section('disable-page-loader')
{{ request()->query('no_loader') === '1'
    ? '1'
    : '0' }}
@endsection

@section(
 'title',
 $statusRecord->name_ar
 .' - '
 .$activeScopeLabel
)

@section(
 'page-title',
 'متابعات '
 .$statusRecord->name_ar
)

@section(
 'page-description',
 'اختار نوع المتابعة لعرض العملاء المطلوب متابعتهم في هذه الحالة.'
)

@section('top-actions')
 <a
  class="btn soft"
  href="{{ route('v2.leads') }}"
 >
  عرض العملاء
 </a>
@endsection

@push('styles')
<style>
 .task-status-page{
  --current-status-color:
   {{ $statusColor }};
  --current-stage-color:
   {{ $stageColor }}
 }

 .task-status-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-wrap:wrap;
  gap:12px;
  margin-bottom:16px;
  padding:16px 18px;
  border:1px solid var(--line);
  border-radius:15px;
  background:#fff;
  box-shadow:var(--shadow)
 }

 .task-status-badges{
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  gap:8px
 }

 .task-pill{
  min-height:30px;
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:5px 10px;
  border-radius:999px;
  background:#fff;
  font-size:11px;
  font-weight:900
 }

 .task-pill.status{
  border:1px solid
   var(--current-status-color);
  color:var(--current-status-color)
 }

 .task-pill.stage{
  border:1px solid
   var(--current-stage-color);
  color:var(--current-stage-color)
 }

 .task-pill i{
  width:8px;
  height:8px;
  display:block;
  border-radius:50%;
  background:currentColor
 }

 .task-work-date{
  color:var(--muted);
  font-size:11px;
  font-weight:900
 }

 .task-status-nav{
  display:flex;
  flex-wrap:wrap;
  gap:7px;
  margin-bottom:14px;
  padding:11px;
  border:1px solid var(--line);
  border-radius:14px;
  background:#fff;
  box-shadow:var(--shadow)
 }

 .task-status-nav a{
  min-height:34px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:6px 11px;
  border:1px solid var(--line);
  border-radius:9px;
  background:#fafbfc;
  color:#657186;
  text-decoration:none;
  font-size:11px;
  font-weight:900
 }

 .task-status-nav a.active{
  border-color:
   var(--current-status-color);
  color:var(--current-status-color);
  background:#fff
 }

 .task-scope-box{
  margin-bottom:18px;
  padding:15px;
  border:1px solid var(--line);
  border-radius:15px;
  background:#fff;
  box-shadow:var(--shadow)
 }

 .task-scope-title{
  margin-bottom:10px;
  color:#28354a;
  font-size:12px;
  font-weight:900
 }

 .task-scope-buttons{
  display:grid;
  grid-template-columns:
   repeat(3,minmax(0,1fr));
  gap:9px
 }

 .task-scope-btn{
  min-height:58px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:9px;
  padding:11px 13px;
  border:1px solid var(--line);
  border-radius:11px;
  background:#fafbfc;
  color:#596579;
  text-decoration:none;
  font-size:11px;
  font-weight:900;
  transition:.18s
 }

 .task-scope-btn:hover{
  background:#fff
 }

 .task-scope-btn strong{
  min-width:28px;
  height:28px;
  display:grid;
  place-items:center;
  padding:0 6px;
  border-radius:999px;
  background:#eef1f6;
  font-family:Arial;
  font-size:11px
 }

 .task-scope-btn.today{
  border-top:3px solid #3478f6
 }

 .task-scope-btn.overdue{
  border-top:3px solid #dc2637
 }

 .task-scope-btn.upcoming{
  border-top:3px solid #7b61df
 }

 .task-scope-btn.today.active{
  border-color:#3478f6;
  color:#3478f6;
  background:#f7faff
 }

 .task-scope-btn.overdue.active{
  border-color:#dc2637;
  color:#c82333;
  background:#fff8f8
 }

 .task-scope-btn.upcoming.active{
  border-color:#7b61df;
  color:#6551c8;
  background:#faf9ff
 }

 .task-status-meta{
  display:grid;
  grid-template-columns:
   repeat(2,minmax(0,1fr));
  gap:9px;
  margin-top:10px
 }

 .task-meta{
  padding:10px 12px;
  border-radius:10px;
  background:#f7f8fa;
  color:var(--muted);
  font-size:10px;
  font-weight:900
 }

 .task-meta strong{
  color:#28354a
 }

 .task-section{
  overflow:hidden;
  margin-bottom:19px;
  border:1px solid var(--line);
  border-radius:17px;
  background:#fff;
  box-shadow:var(--shadow)
 }

 .task-section-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  padding:16px 19px;
  border-bottom:1px solid var(--line)
 }

 .task-title-line{
  display:flex;
  align-items:center;
  gap:9px
 }

 .task-title-line i{
  width:10px;
  height:10px;
  display:block;
  flex:0 0 10px;
  border-radius:50%
 }

 .task-section.today
 .task-title-line i{
  background:#3478f6
 }

 .task-section.overdue
 .task-title-line i{
  background:#dc2637
 }

 .task-section.upcoming
 .task-title-line i{
  background:#7b61df
 }

 .task-section-head h2{
  margin:0;
  font-size:17px
 }

 .task-section-head p{
  margin:5px 0 0;
  color:var(--muted);
  font-size:10px
 }

 .task-section-count{
  min-width:34px;
  height:30px;
  display:grid;
  place-items:center;
  padding:0 8px;
  border-radius:999px;
  background:#f1f3f7;
  color:#596579;
  font:900 11px Arial
 }

 .task-empty{
  padding:32px 20px;
  text-align:center;
  color:var(--muted);
  font-size:11px
 }

 .task-empty strong{
  display:block;
  margin-bottom:7px;
  color:#596579;
  font-size:14px
 }

 .task-customer{
  display:flex;
  align-items:center;
  gap:9px;
  color:#253146;
  text-decoration:none
 }

 .task-avatar{
  width:38px;
  height:38px;
  flex:0 0 38px;
  display:grid;
  place-items:center;
  border-radius:11px;
  border:1px solid
   var(--current-status-color);
  color:var(--current-status-color);
  background:#fff;
  font-weight:900
 }

 .task-customer strong{
  display:block
 }

 .task-customer small{
  display:block;
  margin-top:4px;
  color:var(--muted);
  font-size:10px
 }

 .task-phone{
  direction:ltr;
  display:inline-block;
  color:#356cb2;
  text-decoration:none;
  font-family:Arial
 }

 .task-time{
  white-space:nowrap;
  font-weight:900
 }

 .task-time.today{
  color:#3478f6
 }

 .task-time.overdue{
  color:#c82333
 }

 .task-time.upcoming{
  color:#6551c8
 }

 .task-actions{
  display:flex;
  align-items:center;
  flex-wrap:wrap;
  gap:6px
 }

 .task-follow-btn{
  border-color:#2f70c8!important;
  background:#3478f6!important;
  color:#fff!important
 }

 .task-pagination{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:13px 16px;
  border-top:1px solid var(--line)
 }

 .task-pagination a,
 .task-pagination span{
  min-height:32px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding:5px 10px;
  border:1px solid var(--line);
  border-radius:8px;
  background:#fff;
  color:#596579;
  text-decoration:none;
  font-size:10px;
  font-weight:900
 }

 .task-pagination .disabled{
  opacity:.45
 }

 @media(max-width:800px){
  .task-scope-buttons{
   grid-template-columns:1fr
  }
 }

 @media(max-width:650px){
  .task-status-nav{
   overflow:auto;
   flex-wrap:nowrap
  }

  .task-status-nav a{
   flex:0 0 auto
  }

  .task-status-meta{
   grid-template-columns:1fr
  }
 }


 /* CRM TASK STATUS UX TUNE START */

 /*
  * Fill all available main-column width.
  * Only small page gutters remain.
  */
 body .transfer-content{
  width:auto!important;
  max-width:none!important;
  margin:18px 18px 42px!important
 }

 /*
  * No visual animation when changing
  * follow-up type or status on this page.
  */
 .task-status-page,
 .task-status-page *,
 .task-status-page *::before,
 .task-status-page *::after{
  transition:none!important;
  animation:none!important
 }

 .task-pill{
  min-height:36px;
  padding:7px 13px;
  font-size:14px
 }

 .task-work-date{
  font-size:14px
 }

 .task-status-nav{
  gap:10px;
  padding:13px
 }

 .task-status-nav a{
  min-height:41px;
  padding:8px 14px;
  font-size:14px
 }

 .task-scope-box{
  padding:18px
 }

 .task-scope-title{
  margin-bottom:13px;
  font-size:16px
 }

 .task-scope-buttons{
  gap:12px
 }

 .task-scope-btn{
  min-height:68px;
  padding:14px 16px;
  font-size:15px;
  transition:none!important
 }

 .task-scope-btn strong{
  min-width:34px;
  height:34px;
  padding:0 8px;
  font-size:14px
 }

 .task-status-meta{
  gap:11px;
  margin-top:12px
 }

 .task-meta{
  padding:12px 14px;
  font-size:13px
 }

 .task-section-head{
  padding:18px 21px
 }

 .task-section-head h2{
  font-size:20px
 }

 .task-section-head p{
  font-size:13px;
  line-height:1.7
 }

 .task-section-count{
  min-width:40px;
  height:35px;
  padding:0 10px;
  font-size:14px
 }

 .task-status-page table th,
 .task-status-page table td{
  padding:14px 12px;
  font-size:14px;
  line-height:1.65
 }

 .task-customer{
  gap:11px
 }

 .task-avatar{
  width:42px;
  height:42px;
  flex-basis:42px;
  font-size:15px
 }

 .task-customer strong{
  font-size:14px
 }

 .task-customer small{
  margin-top:5px;
  font-size:12px
 }

 .task-phone,
 .task-time{
  font-size:14px
 }

 .task-status-page .help{
  margin-top:5px;
  font-size:12px
 }

 .task-status-page .btn{
  min-height:42px;
  padding:9px 14px;
  font-size:13px
 }

 .task-empty{
  padding:36px 22px;
  font-size:14px;
  line-height:1.8
 }

 .task-empty strong{
  margin-bottom:8px;
  font-size:17px
 }

 .task-pagination a,
 .task-pagination span{
  min-height:37px;
  padding:7px 12px;
  font-size:13px
 }

 @media(max-width:700px){
  body .transfer-content{
   width:auto!important;
   margin:10px 9px 28px!important
  }

  .task-pill,
  .task-work-date{
   font-size:13px
  }

  .task-status-nav a{
   font-size:13px
  }

  .task-scope-btn{
   font-size:14px
  }

  .task-status-page table th,
  .task-status-page table td{
   font-size:13px
  }
 }



 /* CRM TASK CUSTOMER CARDS DESIGN START */

 .task-cards{
  display:grid;
  grid-template-columns:
   repeat(
    auto-fit,
    minmax(340px,1fr)
   );
  gap:16px;
  padding:18px
 }

 .task-customer-card{
  min-width:0;
  display:flex;
  flex-direction:column;
  gap:16px;
  padding:18px;
  border:1px solid #e1e5ec;
  border-radius:17px;
  background:#fff;
  box-shadow:
   0 7px 23px #1720330d;
  transition:none!important;
  animation:none!important
 }

 .task-card-top{
  min-width:0;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  padding-bottom:14px;
  border-bottom:1px solid #edf0f4
 }

 .task-card-customer{
  min-width:0;
  display:flex;
  align-items:center;
  gap:11px;
  color:#253146;
  text-decoration:none
 }

 .task-card-avatar{
  width:48px;
  height:48px;
  flex:0 0 48px;
  display:grid;
  place-items:center;
  border:1px solid
   var(--current-status-color);
  border-radius:14px;
  background:#fff;
  color:
   var(--current-status-color);
  font-size:18px;
  font-weight:900
 }

 .task-card-name{
  min-width:0
 }

 .task-card-name strong{
  display:block;
  overflow:hidden;
  color:#253146;
  font-size:16px;
  font-weight:900;
  text-overflow:ellipsis;
  white-space:nowrap
 }

 .task-card-name small{
  display:block;
  overflow:hidden;
  margin-top:5px;
  color:var(--muted);
  font-size:12px;
  text-overflow:ellipsis;
  white-space:nowrap
 }

 .task-card-status{
  flex:0 0 auto;
  padding:6px 10px;
  border:1px solid;
  border-radius:999px;
  background:#fff;
  font-size:12px;
  font-weight:900
 }

 .task-card-details{
  display:grid;
  grid-template-columns:
   repeat(2,minmax(0,1fr));
  gap:10px
 }

 .task-card-detail{
  min-width:0;
  min-height:72px;
  padding:11px 12px;
  border:1px solid #edf0f4;
  border-radius:11px;
  background:#fafbfc
 }

 .task-card-detail-label{
  display:block;
  margin-bottom:7px;
  color:#8993a3;
  font-size:12px;
  font-weight:900
 }

 .task-card-detail strong{
  display:block;
  overflow-wrap:anywhere;
  color:#2c384c;
  font-size:14px;
  line-height:1.65
 }

 .task-card-phone{
  direction:ltr;
  display:inline-block;
  color:#356cb2;
  text-decoration:none;
  font-family:Arial;
  font-size:14px
 }

 .task-card-follow-date{
  grid-column:1/-1;
  min-height:auto
 }

 .task-card-time{
  font-size:14px!important
 }

 .task-card-time.today{
  color:#3478f6!important
 }

 .task-card-time.overdue{
  color:#c82333!important
 }

 .task-card-time.upcoming{
  color:#6551c8!important
 }

 .task-card-actions{
  display:grid;
  grid-template-columns:
   1fr 1fr;
  gap:8px;
  margin-top:auto;
  padding-top:2px
 }

 .task-card-actions .btn{
  width:100%;
  min-height:45px
 }

 @media(min-width:1500px){
  .task-cards{
   grid-template-columns:
    repeat(3,minmax(0,1fr))
  }
 }

 @media(max-width:900px){
  .task-cards{
   grid-template-columns:
    repeat(2,minmax(0,1fr))
  }
 }

 @media(max-width:680px){
  .task-cards{
   grid-template-columns:1fr;
   gap:11px;
   padding:11px
  }

  .task-customer-card{
   padding:14px
  }

  .task-card-details{
   grid-template-columns:1fr
  }

  .task-card-follow-date{
   grid-column:auto
  }

  .task-card-actions{
   grid-template-columns:1fr
  }

  .task-card-name strong{
   font-size:15px
  }
 }

 /* CRM TASK CUSTOMER CARDS DESIGN END */

 /* CRM TASK STATUS UX TUNE END */



 /* CRM TASK CARD STAGE COLOR CALL START */

 /*
  * Current status/phase at the top:
  * always clearly highlighted in CRM red.
  */
 .task-status-nav a.active{
  border-color:#dc2637!important;
  background:#dc2637!important;
  color:#fff!important;
  box-shadow:
   0 7px 18px #dc26372b!important
 }

 /*
  * Customer card color follows
  * the Pipeline stage color.
  */
 .task-customer-card{
  border:
   1px solid
   {{ $stageColor }}66!important;
  border-top:
   5px solid
   {{ $stageColor }}!important;
  background:
   linear-gradient(
    180deg,
    {{ $stageColor }}18 0,
    {{ $stageColor }}0a 74px,
    #fff 145px
   )!important;
  box-shadow:
   0 10px 30px
   {{ $stageColor }}1f!important;
  transition:none!important;
  animation:none!important
 }

 .task-customer-card:hover{
  transform:none!important
 }

 /*
  * Small stage accent inside details.
  */
 .task-card-detail{
  border-color:
   {{ $stageColor }}24!important
 }

 .task-card-follow-date{
  border-right:
   4px solid
   {{ $stageColor }}!important
 }

 /*
  * Call button opens the existing
  * follow-up page for that customer.
  */
 .task-call-btn{
  border-color:
   {{ $stageColor }}!important;
  background:
   {{ $stageColor }}!important;
  color:#fff!important;
  font-weight:900!important;
  transition:none!important
 }

 .task-call-btn:hover,
 .task-call-btn:focus{
  border-color:
   {{ $stageColor }}!important;
  background:
   {{ $stageColor }}!important;
  color:#fff!important;
  transform:none!important
 }

 .task-card-actions{
  grid-template-columns:
   repeat(3,minmax(0,1fr))!important
 }

 @media(max-width:760px){
  .task-card-actions{
   grid-template-columns:
    repeat(2,minmax(0,1fr))!important
  }

  .task-call-btn{
   grid-column:1/-1
  }
 }

 @media(max-width:520px){
  .task-card-actions{
   grid-template-columns:
    1fr!important
  }

  .task-call-btn{
   grid-column:auto
  }
 }



 .task-call-btn-disabled{
  border-color:#c9ced8!important;
  background:#e7e9ee!important;
  color:#8a93a2!important;
  cursor:not-allowed!important;
  box-shadow:none!important
 }

 /* CRM TASK CARD STAGE COLOR CALL END */

</style>
@endpush

@section('content')
 <div
  class="task-status-page"
  data-task-scope="{{ $activeScope }}"
 >
  <div class="task-status-head">
   <div class="task-status-badges">
    <span class="task-pill status">
     <i></i>
     الحالة:
     {{ $statusRecord->name_ar }}
    </span>

    <span class="task-pill stage">
     <i></i>
     المرحلة:
     {{
      $statusRecord->stage?->name_ar
      ?? 'بدون مرحلة'
     }}
    </span>
   </div>

   <div class="task-work-date">
    تاريخ العمل:
    {{ $todayLabel }}
   </div>
  </div>

  <nav
   class="task-status-nav"
   aria-label="حالات العملاء"
  >
   @foreach (
    $statusLinks
    as $slug => $label
   )
    <a
     class="{{
      $statusSlug === $slug
       ? 'active'
       : ''
     }}"
     href="{{ route(
      'v2.tasks.status',
      [
       'status' => $slug,
       'scope' => $activeScope,
      ]
     ) }}"
    >
     {{ $label }}
    </a>
   @endforeach
  </nav>

  <div class="task-scope-box">
   <div class="task-scope-title">
    اختار نوع المتابعة
   </div>

   <div class="task-scope-buttons">
    @foreach (
     $scopeLinks
     as $scope => $label
    )
     <a
      class="task-scope-btn {{ $scope }} {{
       $activeScope === $scope
        ? 'active'
        : ''
      }}"
      href="{{ route(
       'v2.tasks.status',
       [
        'status' => $statusSlug,
        'scope' => $scope,
        'no_loader' => 1,
       ]
      ) }}"
     >
      <span>
       {{ $label }}
      </span>

      <strong>
       {{
        number_format(
         $scopeCounts[$scope]
        )
       }}
      </strong>
     </a>
    @endforeach
   </div>

   <div class="task-status-meta">
    <div class="task-meta">
     إجمالي العملاء في الحالة:
     <strong>
      {{
       number_format(
        $totalStatusLeads
       )
      }}
     </strong>
    </div>

    <div class="task-meta">
     بدون موعد متابعة:
     <strong>
      {{
       number_format(
        $withoutFollowUpCount
       )
      }}
     </strong>
    </div>
   </div>
  </div>

  <section
   class="task-section {{ $activeScope }}"
  >
   <header class="task-section-head">
    <div class="task-title-line">
     <i></i>

     <div>
      <h2>
       {{ $activeScopeLabel }}
      </h2>

      <p>
       {{ $activeScopeDescription }}
      </p>
     </div>
    </div>

    <span class="task-section-count">
     {{
      number_format(
       $activeLeads->total()
      )
     }}
    </span>
   </header>

   @if ($activeLeads->isEmpty())
    <div class="task-empty">
     <strong>
      لا يوجد عملاء في هذه المتابعة
     </strong>

     لا يوجد حاليًا عميل في حالة
     {{ $statusRecord->name_ar }}
     مطابق لنوع المتابعة المختار.
    </div>
   @else
    <!-- CRM TASK CUSTOMER CARDS START -->
    <div class="task-cards">
     @foreach (
      $activeLeads
      as $lead
     )
      <article
       class="task-customer-card"
       data-lead-card="{{ $lead->id }}"
      >
       <div class="task-card-top">
        <a
         class="task-card-customer"
         href="{{ route(
          'v2.leads.show',
          $lead
         ) }}"
        >
         <span class="task-card-avatar">
          {{
           mb_substr(
            (string) $lead->name,
            0,
            1
           )
          }}
         </span>

         <span class="task-card-name">
          <strong>
           {{ $lead->name }}
          </strong>

          <small>
           {{
            $lead->email
            ?: 'لا يوجد بريد إلكتروني'
           }}
          </small>
         </span>
        </a>

        <span
         class="task-card-status"
         style="
          border-color:
           {{ $statusColor }};
          color:
           {{ $statusColor }};
         "
        >
         {{ $statusRecord->name_ar }}
        </span>
       </div>

       <div class="task-card-details">
        <div class="task-card-detail">
         <span class="task-card-detail-label">
          الهاتف
         </span>

         <strong>
          @if ($lead->phone)
           <a
            class="task-card-phone"
            href="tel:{{ $lead->phone }}"
           >
            {{ $lead->phone }}
           </a>
          @else
           غير مسجل
          @endif
         </strong>
        </div>

        <div class="task-card-detail">
         <span class="task-card-detail-label">
          الشركة
         </span>

         <strong>
          {{
           $lead->company_name
           ?: 'بدون شركة'
          }}
         </strong>
        </div>

        <div class="task-card-detail">
         <span class="task-card-detail-label">
          المصدر
         </span>

         <strong>
          {{
           $lead->source
           ?: 'غير محدد'
          }}
         </strong>
        </div>

        <div class="task-card-detail">
         <span class="task-card-detail-label">
          الموظف المسؤول
         </span>

         <strong>
          {{
           $lead->assigned_employee
           ?: 'غير مسند'
          }}
         </strong>
        </div>

        <div class="task-card-detail task-card-follow-date">
         <span class="task-card-detail-label">
          موعد المتابعة
         </span>

         <strong
          class="task-card-time {{ $activeScope }}"
         >
          ◷
          {{
           $lead
            ->next_follow_up_at
            ?->format(
             'd/m/Y - h:i A'
            )
           ?? 'غير محدد'
          }}
         </strong>
        </div>
       </div>

       <div class="task-card-actions">
        @if ($lead->phone)
         <a
          class="btn task-call-btn"
          data-call-and-followup="1"
          data-followup-url="{{ route(
           'v2.leads.followups.index',
           $lead
          ) }}"
          href="tel:{{
           preg_replace(
            '/[^0-9+]/',
            '',
            (string) $lead->phone
           )
          }}"
          onclick="
           window.open(
            this.dataset.followupUrl,
            '_blank',
            'noopener,noreferrer'
           );
          "
          title="اتصال بالعميل وفتح تسجيل المتابعة"
          aria-label="اتصال بالعميل {{ $lead->name }} وفتح تسجيل المتابعة"
         >
          ☎ اتصال
         </a>
        @else
         <span
          class="btn task-call-btn task-call-btn-disabled"
          aria-disabled="true"
          title="لا يوجد رقم هاتف مسجل"
         >
          ☎ لا يوجد هاتف
         </span>
        @endif
        <a
         class="btn task-follow-btn"
         href="{{ route(
          'v2.leads.followups.index',
          $lead
         ) }}"
        >
         ◷ تسجيل متابعة
        </a>

        <a
         class="btn soft"
         href="{{ route(
          'v2.leads.show',
          $lead
         ) }}"
        >
         عرض العميل
        </a>
       </div>
      </article>
     @endforeach
    </div>
    <!-- CRM TASK CUSTOMER CARDS END -->

    @if ($activeLeads->hasPages())
     <nav class="task-pagination">
      @if ($activeLeads->onFirstPage())
       <span class="disabled">
        السابق
       </span>
      @else
       <a
        href="{{
         $activeLeads
          ->previousPageUrl()
        }}"
       >
        السابق
       </a>
      @endif

      <span>
       صفحة
       {{ $activeLeads->currentPage() }}
       من
       {{ $activeLeads->lastPage() }}
      </span>

      @if ($activeLeads->hasMorePages())
       <a
        href="{{
         $activeLeads
          ->nextPageUrl()
        }}"
       >
        التالي
       </a>
      @else
       <span class="disabled">
        التالي
       </span>
      @endif
     </nav>
    @endif
   @endif
  </section>
 </div>
@endsection
