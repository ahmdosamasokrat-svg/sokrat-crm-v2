@php
    $crmQuotationReadOnly = isset($quotation);
    $crmQuotationData = $crmQuotationReadOnly ? $quotation->payload : null;
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{ $crmQuotationReadOnly ? 'عرض سعر محفوظ' : 'إنشاء عرض سعر' }} | CRM v2</title>
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/styles.css') }}" />
  <link rel="stylesheet" href="{{ asset('quotation-generator/crm-module.css') }}?v=crm-module-no-sidebar-v5" />
</head>
<body>
@include('partials.page-loader')
  <!-- CRM QUOTATION SHARED SIDEBAR V1 START -->
  <div class="crm-quote-shell">
   @include('partials.crm-sidebar')
   <div class="crm-quote-main">
  <!-- CRM QUOTATION SHARED SIDEBAR V1 END -->
  <div class="app-shell">
    <aside class="builder no-print">
      <div class="builder-head">
        <div>
          <span class="eyebrow">Sokrat Pro Tech</span>
          <h1>مولد عروض الأسعار</h1>
          <p>أدخل البيانات المتغيرة فقط، وسيظل تصميم عرض السعر وهوية الشركة ثابتين.</p>
        </div>
        <div class="head-actions">
          <button type="button" id="loadDemoBtn" class="btn ghost">تحميل نموذج</button>
          <button type="button" id="resetBtn" class="btn ghost danger-text">إعادة تعيين</button>
        </div>
      </div>

      <form id="quoteForm" autocomplete="off">
        <section class="panel">
          <h2>بيانات العميل</h2>
          <div class="grid two">
            <label>
              <span>اسم العميل / الشركة</span>
              <input id="clientName" type="text" placeholder="مثال: الحشاش للأثاث" />
            </label>
            <label>
              <span>الموقع</span>
              <input id="location" type="text" placeholder="مثال: المقطم" />
            </label>
            <label>
              <span>مقدم العرض</span>
              <input id="preparedBy" type="text" value="احمد حمدي" />
            </label>
            <label>
              <span>رقم عرض السعر</span>
              <input id="quotationNo" type="text" placeholder="مثال: 78695455" />
            </label>
            <label>
              <span>التاريخ</span>
              <input id="quoteDate" type="date" />
            </label>
            <label>
              <span>عنوان العرض / النظام</span>
              <input id="systemTitle" type="text" value="Call Center System" dir="ltr" />
            </label>
          </div>
          <label>
            <span>وصف العرض</span>
            <textarea id="proposalDescription" rows="3">اليكم المقايسة الفنية و المالية لتوريد و تشغيل برنامج أدارة الكول سنتر
توصلنا الى ان متطلبات العمل لديكم فيما يخص الكول سنتر تتركز فى اعمال أستقبال المكالمات و الاتصال بالعملاء و التواصل معهم من داخل مقركم او من خارج مقركم
تم اختيار استخدام اجهزة الكمبيوتر المتوفرة لديكم لاستقبال و الاتصال بالعملاء</textarea>
          </label>
        </section>

        <section class="panel">
          <div class="panel-title-row">
            <h2>المنتجات / البنود</h2>
            <button type="button" id="addItemBtn" class="btn small">+ إضافة بند</button>
          </div>
          <p class="helper">اختر منتجًا من القائمة أو اختر «بند مخصص». يتم حساب الكمية × سعر الوحدة تلقائيًا.</p>
          <div class="items-head desktop-only">
            <span>البند</span><span>الكمية</span><span>سعر الوحدة</span><span>الوحدة</span><span>الإجمالي</span><span>صفحة المنتج</span><span></span>
          </div>
          <div id="itemsEditor" class="items-editor"></div>
          <div class="grand-total-card">
            <span>الإجمالي العام</span>
            <strong><span id="formGrandTotal">0</span> جنيه</strong>
          </div>
        </section>

        <section class="panel typography-panel">
          <div class="panel-title-row">
            <h2>تنسيق الخط داخل عرض السعر</h2>
            <button type="button" id="resetTypographyBtn" class="btn ghost small">استعادة الافتراضي</button>
          </div>
          <p class="helper">التحكم هنا يطبق على محتوى عرض السعر والـ PDF، مع بقاء شعار وهوية الهيدر والفوتر ثابتين.</p>
          <div class="grid two typography-grid">
            <label>
              <span>نوع الخط</span>
              <select id="quoteFontFamily">
                <option value="arial">Arial</option>
                <option value="tahoma">Tahoma</option>
                <option value="traditional">Traditional Arabic</option>
                <option value="simplified">Simplified Arabic</option>
                <option value="times">Times New Roman</option>
                <option value="verdana">Verdana</option>
                <option value="georgia">Georgia</option>
              </select>
            </label>
            <label>
              <span>حجم الخط: <strong id="quoteFontSizeValue">100%</strong></span>
              <input id="quoteFontSize" class="font-size-range" type="range" min="85" max="130" step="5" value="100" />
            </label>
            <div class="color-control">
              <span class="field-label">لون النصوص</span>
              <div class="color-row">
                <input id="quoteTextColor" type="color" value="#111111" disabled aria-label="لون النصوص" />
                <label class="inline-check"><input id="enableTextColor" type="checkbox" /> <span>تفعيل لون مخصص</span></label>
              </div>
            </div>
            <div class="color-control">
              <span class="field-label">لون العناوين</span>
              <div class="color-row">
                <input id="quoteAccentColor" type="color" value="#e10d0d" disabled aria-label="لون العناوين" />
                <label class="inline-check"><input id="enableAccentColor" type="checkbox" /> <span>تفعيل لون مخصص</span></label>
              </div>
            </div>
          </div>
        </section>

        <section class="panel">
          <h2>الأقسام الاختيارية</h2>
          <div class="switch-grid">
            <label class="checkline"><input id="includeProducts" type="checkbox" checked /> <span>صفحات المنتجات / الملحقات</span></label>
            <label class="checkline"><input id="includeTechnical" type="checkbox" checked /> <span>المقايسة الفنية</span></label>
            <label class="checkline"><input id="includeTerms" type="checkbox" checked /> <span>الشروط والاتفاقات</span></label>
            <label class="checkline"><input id="includeFeatures" type="checkbox" checked /> <span>صفحة مميزات نظام الكول سنتر</span></label>
          </div>
        </section>

        <section class="panel collapsible open">
          <button class="collapse-trigger" type="button" data-target="technicalEditor">
            <span>المقايسة الفنية</span><span>⌄</span>
          </button>
          <div id="technicalEditor" class="collapse-body">
            <textarea id="technicalScope" rows="12">1- نقوم بكافة اعمال تركيب و تشغيل نظام الكول سنتر و التاكد من عمله بمنتهى الكفاءة و تسليمة للمسؤل لديكم
2- نقوم بتدريب الموظفين المختصين كل فى حدود صلاحياته و التدريب لدينا عدد 2 زيارة ميدانية و الدعم الفنى online عن طريق الهاتف او الواتس او الايميل
3- الدعم الفنى online خلال مواعيد العمل الرسمية لدينا من التاسعه صباحا الى الخامسة مساء كل يوم ماعدا الجمعه و السبت و ما عدا الاجازات الرسمية
4- الزيارات الميدانية تتم بموعد مسبق خلال 48 ساعه من الاتفاق عليها بتكلفة تحدد وقتها خلال أيام العمل
5- الدعم الفنى online لمدة سنة من تاريخ تسليمكم الكول سنتر مجانا و حال رغبتكم التجديد يتم احتساب التجديد ب 30% من قيمة التعاقد
6- يقوم العميل بتوفير جهاز كمبيوتر ليعمل ك سيرفر او نقوم بالتوريد و الاتفاق على السعر حسب المواصفة التى تناسب طبيعة العمل
7- لكل خط تم توريده عدد 1 مشتركى VPN يتم عمل التجديد السنوى لل VPN اما بتجديد اشتراك الدعم الفنى 30% من قيمة التعاقد او الاشتراك على باقة من باقات VPN حسب السعر</textarea>
          </div>
        </section>

        <section class="panel collapsible open">
          <button class="collapse-trigger" type="button" data-target="termsEditor">
            <span>الشروط والاتفاقات</span><span>⌄</span>
          </button>
          <div id="termsEditor" class="collapse-body">
            <textarea id="terms" rows="5">- الاسعار لا تشمل ضريبة القيمة المضافه .
- يتم دفع 50% عند الاتفاق و يتم توريد و تركيب النظام خلال 48 ساعه (( أيام عمل )) و يتم تحصيل المتبقى عند التسليم .</textarea>
          </div>
        </section>

        <section class="panel collapsible">
          <button class="collapse-trigger" type="button" data-target="featuresEditor">
            <span>مميزات نظام الكول سنتر</span><span>⌄</span>
          </button>
          <div id="featuresEditor" class="collapse-body">
            <textarea id="features" rows="12">- الرسالة المسجلة التفاعلية.
- تسجيل ومراقبة المكالمات.
- تقارير تفصيلية للمكالمات.
- توزيع المكالمات بشكل اوتوماتيكي.
- تقارير كاملة عن سجل المكالمات.
- التحكم في المكالمات خارج مواعيد العمل.
- تحويل المكالمات بين الفروع.
- تطبيق موبايل لاستقبال وارسال المكالمات خارج العمل.
- يدعم الهوت لاين.
- التواصل مع الموظفين داخل وخارج مقر العمل.
- توجية المكالمات للموظفين علي smartphones في اي مكان.
- تدعيم كامل للعمل خارج نطاق الشركة.</textarea>
          </div>
        </section>
      </form>

      <div class="sticky-actions">
        @if (!$crmQuotationReadOnly)
         <button type="button" id="crmSaveQuotationBtn" class="btn primary">حفظ عرض السعر</button>
        @endif
        <span id="crmQuotationSaveStatus" aria-live="polite"></span>
        <button type="button" id="previewBtn" class="btn secondary">معاينة عرض السعر</button>
        <button type="button" id="printBtn" class="btn primary">إنشاء / طباعة / حفظ PDF</button>
      </div>
    </aside>

    <main class="preview-stage">
      <div class="preview-toolbar no-print">
        <div>
          <strong>معاينة عرض السعر</strong>
          <span id="pageCount">عدد الصفحات: 0</span>
        </div>
        <button type="button" id="printTopBtn" class="btn primary small">طباعة / حفظ PDF</button>
      </div>
      <div id="quotePreview" class="quote-preview"></div>
    </main>
  </div>

  <template id="itemRowTemplate">
    <div class="item-row">
      <div class="item-main">
        <label class="mobile-label">البند</label>
        <select class="item-product"></select>
        <input class="item-custom-name" type="text" placeholder="اسم البند المخصص" hidden />
        <input class="item-description" type="text" placeholder="وصف مختصر (اختياري)" />
        <label class="image-upload-label">
          <span>صورة مخصصة</span>
          <input class="item-image" type="file" accept="image/*" />
        </label>
      </div>
      <div><label class="mobile-label">الكمية</label><input class="item-qty" type="number" min="0" step="1" value="1" /></div>
      <div><label class="mobile-label">سعر الوحدة</label><input class="item-price" type="number" min="0" step="0.01" value="0" /></div>
      <div><label class="mobile-label">الوحدة</label><input class="item-unit" type="text" value="قطعه" /></div>
      <div class="item-total-cell"><label class="mobile-label">الإجمالي</label><strong class="item-total">0</strong></div>
      <div class="product-page-cell"><label class="mobile-label">صفحة المنتج</label><input class="item-show-product" type="checkbox" /></div>
      <div><button type="button" class="remove-item" title="حذف البند">×</button></div>
    </div>
  </template>

  <script>
   window.SOKRAT_QUOTE_ASSET_BASE = @json(asset('quotation-generator/assets'));
   window.CRM_QUOTATION_READ_ONLY = @json($crmQuotationReadOnly);
   window.CRM_QUOTATION_DATA = @json($crmQuotationData);
   window.CRM_QUOTATION_STORE_URL = @json(route('v2.quotations.store'));
  </script>
  <script src="{{ asset('quotation-generator/script.js') }}"></script>
  <script src="{{ asset('quotation-generator/crm-sidebar.js') }}"></script>
  <script src="{{ asset('quotation-generator/crm-integration.js') }}"></script>
   </div>
  </div>
</body>
</html>
