@extends('leads.transfer-layout')

@section('title', 'تصدير العملاء')
@section('page-title', 'تصدير العملاء')
@section(
 'page-description',
 'حدد نطاق العملاء والأعمدة ثم نزّل ملف Excel.'
)

@section('top-actions')
 <a
  class="btn soft"
  href="{{ route('v2.leads.import') }}"
 >
  ↑ استيراد العملاء
 </a>

 <a
  class="btn soft"
  href="{{ route('v2.leads') }}"
 >
  عرض العملاء
 </a>
@endsection

@section('content')

 <article class="transfer-card">
  <div class="transfer-hero">
   <small>تصدير Excel</small>

   <h2>
    تجهيز ملف العملاء
   </h2>

   <p>
    اختر الفلاتر المطلوبة والأعمدة التي تريد
    ظهورها في الملف. تصدير العملاء المحددين
    من شاشة العملاء سيظل يعمل كما هو.
   </p>
  </div>

  <div class="card-body">
   <div class="notice info">
    إجمالي العملاء حاليًا:
    <strong>{{ $totalLeads }}</strong>.
    الحد الأقصى للتصدير من هذه الشاشة
    هو 5000 عميل في العملية الواحدة.
   </div>

   @if ($errors->has('export'))
    <div class="notice error">
     {{ $errors->first('export') }}
    </div>
   @endif

   <form
    id="leadExportForm"
    method="POST"
    action="{{ route(
     'v2.leads.export.download'
    ) }}"
   >
    @csrf

    <div class="grid three">
     <div class="field">
      <label for="stageId">
       المرحلة
      </label>

      <select
       class="control"
       id="stageId"
       name="stage_id"
      >
       <option value="">
        كل المراحل
       </option>

       @foreach ($stages as $stage)
        <option
         value="{{ $stage->id }}"
         @selected(
          (string) old('stage_id')
          === (string) $stage->id
         )
        >
         {{ $stage->name_ar }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="statusId">
       الحالة
      </label>

      <select
       class="control"
       id="statusId"
       name="status_id"
      >
       <option value="">
        كل الحالات
       </option>

       @foreach ($statuses as $status)
        <option
         value="{{ $status->id }}"
         @selected(
          (string) old('status_id')
          === (string) $status->id
         )
        >
         {{
          $status->stage?->name_ar
          ?? '----'
         }}
         —
         {{ $status->name_ar }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="employee">
       الموظف المسؤول
      </label>

      <select
       class="control"
       id="employee"
       name="employee"
      >
       <option value="">
        كل الموظفين
       </option>

       @foreach (
        $employees
        as $employee
       )
        <option
         value="{{ $employee }}"
         @selected(
          old('employee')
          === $employee
         )
        >
         {{ $employee }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="source">
       المصدر
      </label>

      <select
       class="control"
       id="source"
       name="source"
      >
       <option value="">
        كل المصادر
       </option>

       @foreach ($sources as $source)
        <option
         value="{{ $source }}"
         @selected(
          old('source')
          === $source
         )
        >
         {{ $source }}
        </option>
       @endforeach
      </select>
     </div>

     <div class="field">
      <label for="dateFrom">
       تاريخ الإضافة من
      </label>

      <input
       class="control"
       id="dateFrom"
       type="date"
       name="date_from"
       value="{{ old('date_from') }}"
      >
     </div>

     <div class="field">
      <label for="dateTo">
       تاريخ الإضافة إلى
      </label>

      <input
       class="control"
       id="dateTo"
       type="date"
       name="date_to"
       value="{{ old('date_to') }}"
      >
     </div>
    </div>

    <div
     style="
      margin-top:22px;
      padding-top:20px;
      border-top:1px solid #e4e8ef
     "
    >
     <div class="card-head"
          style="padding:0 0 14px;border:0">
      <div>
       <h3>
        أعمدة ملف Excel
       </h3>

       <p>
        اختر البيانات التي تريد ظهورها.
       </p>
      </div>

      <div style="display:flex;gap:7px">
       <button
        class="btn soft"
        id="selectAllColumns"
        type="button"
       >
        تحديد الكل
       </button>

       <button
        class="btn soft"
        id="clearColumns"
        type="button"
       >
        إلغاء الكل
       </button>
      </div>
     </div>

     <div class="checkbox-grid">
      @foreach (
       $columns
       as $key => $label
      )
       <label class="check-item">
        <input
         class="export-column-checkbox"
         type="checkbox"
         name="columns[]"
         value="{{ $key }}"
         @checked(
          old('columns') === null
          || in_array(
           $key,
           old('columns', []),
           true
          )
         )
        >
        <span>{{ $label }}</span>
       </label>
      @endforeach
     </div>
    </div>

    <div class="actions">
     <button
      class="btn success"
      type="submit"
     >
      ↓ تنزيل ملف Excel
     </button>
    </div>
   </form>
  </div>
 </article>

@endsection

@push('scripts')
<script>
 (() => {
  const checkboxes = Array.from(
   document.querySelectorAll(
    '.export-column-checkbox'
   )
  );

  document
   .getElementById(
    'selectAllColumns'
   )
   ?.addEventListener(
    'click',
    () => {
     checkboxes.forEach(
      (checkbox) => {
       checkbox.checked = true;
      }
     );
    }
   );

  document
   .getElementById(
    'clearColumns'
   )
   ?.addEventListener(
    'click',
    () => {
     checkboxes.forEach(
      (checkbox) => {
       checkbox.checked = false;
      }
     );
    }
   );

  document
   .getElementById(
    'leadExportForm'
   )
   ?.addEventListener(
    'submit',
    (event) => {
     if (
      !checkboxes.some(
       (checkbox) => checkbox.checked
      )
     ) {
      event.preventDefault();

      window.alert(
       'اختر عمودًا واحدًا على الأقل.'
      );
     }
    }
   );
 })();
</script>
@endpush
