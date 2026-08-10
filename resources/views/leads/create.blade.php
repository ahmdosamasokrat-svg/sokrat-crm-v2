<!doctype html>
<html lang="ar" dir="rtl">
<head>
 <meta charset="utf-8">
 <meta name="viewport" content="width=device-width,initial-scale=1">
 <title>إضافة عميل جديد | SokratCRM</title>

 <style>
  :root{
   --red:#dc2637;
   --dark:#182033;
   --muted:#818b9c;
   --line:#e5e8ee;
   --bg:#f5f6f9;
   --card:#fff;
   --shadow:0 15px 42px #17203310
  }

  *{box-sizing:border-box}

  body{
   margin:0;
   min-width:320px;
   background:
    radial-gradient(circle at 8% 0,#dc26370d,transparent 28rem),
    var(--bg);
   color:var(--dark);
   font-family:Tahoma,Arial,sans-serif;
   font-size:15px
  }

  button,input,select,textarea{font:inherit}
  a{color:inherit}

  .crm-app{
   display:flex;
   min-height:100vh;
   direction:rtl
  }

  .crm-side{
   position:sticky;
   top:0;
   flex:0 0 288px;
   width:288px;
   height:100vh;
   overflow:auto;
   padding:24px 17px;
   border-left:1px solid var(--line);
   background:#fff;
   z-index:80
  }

  .crm-side-brand{
   display:flex;
   align-items:center;
   gap:11px;
   padding:4px 8px 20px;
   margin-bottom:17px;
   border-bottom:1px solid var(--line);
   text-decoration:none
  }

  .crm-side-brand .logo{
   width:58px;
   height:58px;
   flex:0 0 58px;
   object-fit:contain
  }

  .crm-side-brand strong{
   display:block;
   color:var(--red);
   font:900 22px Arial
  }

  .crm-side-brand small{
   display:block;
   margin-top:5px;
   color:var(--muted);
   font-size:12px
  }

  .crm-side-caption{
   margin:0 10px 10px;
   color:#9aa2b0;
   font-size:13px;
   font-weight:bold
  }

  .crm-side-nav{
   display:grid;
   gap:6px
  }

  .crm-link,
  .crm-toggle{
   width:100%;
   min-height:49px;
   display:flex;
   align-items:center;
   gap:10px;
   padding:8px 10px;
   border:1px solid transparent;
   border-radius:13px;
   background:transparent;
   color:#566175;
   text-decoration:none;
   text-align:right;
   cursor:pointer;
   transition:.2s
  }

  .crm-link:hover,
  .crm-toggle:hover{
   color:var(--red);
   background:#fff5f6;
   transform:translateX(-2px)
  }

  .crm-link.active,
  .crm-toggle.active{
   color:#fff;
   background:linear-gradient(135deg,#e83243,#c91d2e);
   box-shadow:0 11px 25px #dc263737
  }

  .crm-ico{
   width:32px;
   height:32px;
   flex:0 0 32px;
   display:grid;
   place-items:center;
   border-radius:10px;
   background:#f0f2f6;
   font-size:17px
  }

  .crm-link.active .crm-ico,
  .crm-toggle.active .crm-ico{
   background:#ffffff2b
  }

  .crm-label{
   flex:1;
   font-size:16px;
   font-weight:800
  }

  .crm-count{
   min-width:24px;
   height:24px;
   display:grid;
   place-items:center;
   padding:0 6px;
   border-radius:99px;
   background:#eef0f4;
   color:#7e8796;
   font:800 11px Arial
  }

  .crm-toggle.active .crm-count{
   color:#fff;
   background:#ffffff2b
  }

  .crm-arrow{
   color:#a2a9b5;
   transition:.2s
  }

  .crm-toggle[aria-expanded=true] .crm-arrow{
   transform:rotate(180deg)
  }

  .crm-sub{
   display:grid;
   grid-template-rows:0fr;
   transition:.22s
  }

  .crm-sub.open{grid-template-rows:1fr}

  .crm-sub-inner{
   min-height:0;
   overflow:hidden
  }

  .crm-sub nav{
   display:grid;
   gap:2px;
   margin:3px 28px 7px 0;
   padding-right:14px;
   border-right:1px solid var(--line)
  }

  .crm-sub a{
   padding:8px 10px;
   border-radius:8px;
   color:#788294;
   text-decoration:none;
   font-size:14px;
   font-weight:bold
  }

  .crm-sub a:hover,
  .crm-sub a.active{
   color:var(--red);
   background:#fff2f4
  }

  .crm-main{
   min-width:0;
   flex:1;
   width:calc(100% - 288px)
  }

  .shell{
   width:calc(100% - 28px);
   max-width:1500px;
   margin:0 auto;
   padding:16px 0 48px
  }

  .topbar{
   min-height:72px;
   display:flex;
   align-items:center;
   gap:13px;
   padding:12px 15px;
   margin-bottom:16px;
   border:1px solid var(--line);
   border-radius:17px;
   background:#ffffffed;
   box-shadow:var(--shadow)
  }

  .menu-button{
   display:none;
   width:42px;
   height:42px;
   border:1px solid var(--line);
   border-radius:11px;
   background:#fff;
   cursor:pointer;
   font-size:20px
  }

  .optional{
   margin-right:5px;
   color:var(--muted);
   font-size:11px;
   font-weight:normal
  }

  .page-title{flex:1}

  .page-title h1{
   margin:0;
   font-size:24px
  }

  .page-title p{
   margin:6px 0 0;
   color:var(--muted);
   font-size:13px
  }

  .user-menu{
   position:relative;
   flex:0 0 auto
  }

  .user-chip{
   display:flex;
   align-items:center;
   gap:8px;
   padding:5px 7px;
   border:1px solid transparent;
   border-radius:13px;
   background:transparent;
   color:inherit;
   text-align:right;
   cursor:pointer;
   transition:.2s
  }

  .user-chip:hover,
  .user-chip[aria-expanded=true]{
   border-color:var(--line);
   background:#fff
  }

  .avatar{
   width:42px;
   height:42px;
   display:grid;
   place-items:center;
   flex:0 0 42px;
   border-radius:12px;
   background:var(--dark);
   color:#fff;
   font-weight:900
  }

  .user-details{
   display:block;
   min-width:90px
  }

  .user-chip strong{
   display:block;
   font-size:13px
  }

  .user-chip small{
   display:block;
   margin-top:3px;
   color:var(--muted);
   font-size:11px
  }

  .user-dropdown{
   position:absolute;
   top:calc(100% + 8px);
   left:0;
   z-index:120;
   width:190px;
   padding:7px;
   border:1px solid var(--line);
   border-radius:13px;
   background:#fff;
   box-shadow:0 18px 45px #17203325
  }

  .user-dropdown[hidden]{
   display:none
  }

  .user-dropdown form{
   margin:0
  }

  .user-dropdown-action{
   width:100%;
   min-height:42px;
   display:flex;
   align-items:center;
   justify-content:center;
   padding:8px 12px;
   border:0;
   border-radius:9px;
   background:#fff0f2;
   color:var(--red);
   font-weight:900;
   cursor:pointer
  }

  .user-dropdown-action:hover{
   background:#ffe5e8
  }

  .form-card{
   overflow:hidden;
   border:1px solid var(--line);
   border-radius:20px;
   background:var(--card);
   box-shadow:var(--shadow)
  }

  .form-hero{
   padding:26px;
   background:linear-gradient(120deg,#171f31,#30394c);
   color:#fff
  }

  .form-hero-head{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:24px
  }

  .form-hero-copy{
   min-width:0;
   flex:1
  }

  .form-hero-back{
   min-height:48px;
   flex:0 0 auto;
   padding-inline:18px;
   border-color:#ffffff55;
   background:#fff;
   color:var(--dark);
   box-shadow:none;
   font-size:14px;
   white-space:nowrap
  }

  .form-hero-back:hover{
   border-color:#fff;
   background:#fff5f6;
   color:var(--red)
  }

  .form-hero small{
   color:#f2a3ac;
   font-weight:bold
  }

  .form-hero h2{
   margin:9px 0 6px;
   font-size:26px
  }

  .form-hero p{
   max-width:760px;
   margin:0;
   color:#cbd0da;
   line-height:1.8;
   font-size:13px
  }

  .form-body{
   display:grid;
   gap:18px;
   padding:20px
  }

  .form-section{
   padding:18px;
   border:1px solid var(--line);
   border-radius:16px;
   background:#fff
  }

  .section-head{
   margin-bottom:15px
  }

  .section-head h3{
   margin:0;
   font-size:18px
  }

  .section-head p{
   margin:5px 0 0;
   color:var(--muted);
   font-size:12px
  }

  .fields{
   display:grid;
   grid-template-columns:repeat(3,minmax(0,1fr));
   gap:13px
  }

  .field{min-width:0}
  .field.full{grid-column:1/-1}

  .field label{
   display:block;
   margin:0 3px 7px;
   color:#667184;
   font-size:13px;
   font-weight:bold
  }

  .required{color:var(--red)}

  .field input,
  .field select,
  .field textarea{
   width:100%;
   border:1px solid #dfe3ea;
   border-radius:11px;
   background:#fafbfc;
   color:#404b5e;
   outline:none
  }

  .field input,
  .field select{
   height:46px;
   padding:0 12px
  }

  .field textarea{
   min-height:110px;
   padding:12px;
   resize:vertical;
   line-height:1.7
  }

  .field input:focus,
  .field select:focus,
  .field textarea:focus{
   border-color:#e97d88;
   background:#fff;
   box-shadow:0 0 0 4px #dc263710
  }

  .field input[readonly]{
   background:#f0f2f5;
   cursor:not-allowed
  }

  .help{
   display:block;
   margin-top:6px;
   color:#8a93a2;
   font-size:11px;
   line-height:1.6
  }

  .conditional{
   border-color:#f0c8cd;
   background:#fffafb
  }

  .reveal-panel:not(.is-hidden){
   animation:
    crmPanelReveal
    .3s
    cubic-bezier(.22,.8,.3,1)
    both
  }

  @keyframes crmPanelReveal{
   from{
    opacity:0;
    transform:translateY(-10px) scale(.993)
   }

   to{
    opacity:1;
    transform:translateY(0) scale(1)
   }
  }

  .quotation-card{
   position:relative;
   overflow:hidden;
   border-color:#edb3bb;
   background:
    radial-gradient(
     circle at 8% 5%,
     #dc263714,
     transparent 18rem
    ),
    linear-gradient(145deg,#fff,#fff8f9);
   box-shadow:0 18px 42px #dc263710
  }

  .quotation-card::before{
   content:"";
   position:absolute;
   top:0;
   right:0;
   left:0;
   height:4px;
   background:
    linear-gradient(
     90deg,
     #bd1728,
     #f05b6a,
     #bd1728
    )
  }

  .quotation-section-head{
   position:relative;
   padding:15px 64px 15px 15px;
   border:1px solid #f0d1d6;
   border-radius:14px;
   background:#fff
  }

  .quotation-section-head::before{
   content:"▣";
   position:absolute;
   top:50%;
   right:15px;
   width:38px;
   height:38px;
   display:grid;
   place-items:center;
   border-radius:11px;
   background:
    linear-gradient(135deg,#e83243,#bd1728);
   color:#fff;
   box-shadow:0 9px 20px #dc26372d;
   transform:translateY(-50%);
   font-size:18px
  }

  .quotation-section-head h3{
   color:#9f2331
  }

  .quotation-select{
   border-color:#e7bcc2!important;
   background:#fff!important;
   font-weight:800
  }

  .quotation-file{
   height:auto!important;
   min-height:66px;
   padding:8px!important;
   border:1px dashed #dfa2aa!important;
   background:#fff8f9!important;
   cursor:pointer
  }

  .quotation-file:hover,
  .quotation-file:focus{
   border-color:var(--red)!important;
   background:#fff1f3!important
  }

  .quotation-file::file-selector-button{
   min-height:43px;
   margin-left:12px;
   padding:8px 15px;
   border:0;
   border-radius:9px;
   background:
    linear-gradient(135deg,#e83243,#bd1728);
   color:#fff;
   font-weight:900;
   cursor:pointer
  }

  .quotation-file-help{
   min-height:34px;
   display:flex;
   align-items:center;
   margin-top:8px;
   padding:7px 10px;
   border-radius:9px;
   background:#fff;
   color:#7b8595
  }

  .quotation-file-help.has-file{
   background:#edf9f2;
   color:#28784c;
   font-weight:800
  }

  .quotation-detail-panel{
   margin-top:14px!important;
   padding:16px;
   border:1px solid #eadcdf;
   border-radius:14px;
   background:#fff;
   box-shadow:0 8px 22px #17203308
  }

  @media(prefers-reduced-motion:reduce){
   .reveal-panel:not(.is-hidden){
    animation:none
   }
  }

  .future-action{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:12px;
   padding:15px;
   border:1px dashed #e7aab2;
   border-radius:13px;
   background:#fff7f8
  }

  .future-action strong{
   display:block;
   margin-bottom:5px
  }

  .future-action span{
   color:var(--muted);
   font-size:12px
  }

  .btn{
   min-height:44px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:8px 17px;
   border:1px solid transparent;
   border-radius:11px;
   background:linear-gradient(135deg,#e83243,#c91d2e);
   color:#fff;
   text-decoration:none;
   font-size:13px;
   font-weight:900;
   cursor:pointer;
   box-shadow:0 10px 24px #dc26372c
  }

  .btn.secondary{
   border-color:var(--line);
   background:#f6f7f9;
   color:#657083;
   box-shadow:none
  }

  .btn.future{
   border-color:#efb8bf;
   background:#fff;
   color:var(--red);
   box-shadow:none
  }

  .form-actions{
   display:flex;
   justify-content:flex-end;
   gap:9px
  }

  .error-summary{
   padding:14px 17px;
   border:1px solid #efb6bd;
   border-radius:13px;
   background:#fff3f4;
   color:#9b2431
  }

  .error-summary strong{
   display:block;
   margin-bottom:7px
  }

  .error-summary ul{
   margin:0;
   padding-right:20px;
   line-height:1.8
  }

  .is-hidden{display:none!important}

  .crm-overlay{
   display:none;
   position:fixed;
   inset:0;
   border:0;
   background:#11182770;
   z-index:70
  }

  @media(max-width:1000px){
   .crm-app{display:block}

   .crm-side{
    position:fixed;
    right:0;
    width:min(288px,calc(100vw - 45px));
    transform:translateX(105%);
    transition:.25s;
    box-shadow:-20px 0 55px #17203325
   }

   body.crm-side-open{overflow:hidden}
   body.crm-side-open .crm-side{transform:none}
   body.crm-side-open .crm-overlay{display:block}

   .crm-main{width:100%}
   .menu-button{display:block}

   .fields{
    grid-template-columns:repeat(2,minmax(0,1fr))
   }
  }

  @media(max-width:650px){
   .shell{width:calc(100% - 18px)}

   .page-title p,
   .user-details{
    display:none
   }

   .user-chip{
    padding:3px
   }

   .user-dropdown{
    width:170px
   }

   .form-hero-head{
    align-items:stretch;
    flex-direction:column
   }

   .form-hero-back{
    width:100%
   }

   .fields{grid-template-columns:1fr}
   .field.full{grid-column:auto}

   .form-hero,
   .form-body,
   .form-section{
    padding:16px
   }

   .future-action{
    align-items:stretch;
    flex-direction:column
   }

   .form-actions{
    flex-direction:column
   }

   .form-actions .btn{
    width:100%
   }
  }
 </style>
</head>

<body>
<button
 class="crm-overlay"
 id="crmSidebarOverlay"
 type="button"
 aria-label="إغلاق القائمة"
></button>

<div class="crm-app">
 @include('partials.crm-sidebar')

 <main class="crm-main">
  <div class="shell">
   <header class="topbar">
    <button
     class="menu-button"
     id="crmMenuButton"
     type="button"
     aria-controls="crmSidebar"
     aria-expanded="false"
    >
     ☰
    </button>

    <div class="page-title">
     <h1>إضافة عميل جديد</h1>
     <p>
      أدخل البيانات الأساسية ثم اختر حالة العميل.
     </p>
    </div>

    <div class="user-menu">
     <button
      class="user-chip"
      id="userMenuButton"
      type="button"
      aria-controls="userMenuDropdown"
      aria-expanded="false"
     >
      <span class="avatar">
       {{ mb_substr((string) session('crm_v2_user', 'A'), 0, 1) }}
      </span>

      <span class="user-details">
       <strong>{{ session('crm_v2_user', 'admin') }}</strong>
       <small>الموظف المسؤول</small>
      </span>
     </button>

     <div
      class="user-dropdown"
      id="userMenuDropdown"
      hidden
     >
      <form method="POST" action="{{ route('logout') }}">
       @csrf

       <button
        class="user-dropdown-action"
        type="submit"
       >
        تسجيل الخروج
       </button>
      </form>
     </div>
    </div>
   </header>

   <article class="form-card">
    <div class="form-hero">
     <div class="form-hero-head">
      <div class="form-hero-copy">
       <small>سجل عميل جديد</small>
       <h2>بيانات العميل</h2>
       <p>
        الحقول الإضافية تظهر تلقائيًا حسب حالة العميل،
        ويتم التحقق منها مرة أخرى عند الحفظ.
       </p>
      </div>

      <a
       class="btn form-hero-back"
       href="{{ route('v2.leads') }}"
      >
       ← رجوع لصفحة عرض العملاء
      </a>
     </div>
    </div>

    <form
     class="form-body"
     method="POST"
     action="{{ route('v2.leads.store') }}"
     enctype="multipart/form-data"
    >
     @csrf

     @if ($errors->any())
      <div class="error-summary">
       <strong>يرجى مراجعة البيانات التالية:</strong>

       <ul>
        @foreach ($errors->all() as $error)
         <li>{{ $error }}</li>
        @endforeach
       </ul>
      </div>
     @endif

     <section class="form-section">
      <div class="section-head">
       <h3>البيانات الأساسية</h3>
       <p>هذه البيانات مطلوبة لكل العملاء.</p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="firstName">
         اسم العميل الأول
         <span class="required">*</span>
        </label>

        <input
         id="firstName"
         type="text"
         name="first_name"
         value="{{ old('first_name') }}"
         maxlength="75"
         required
        >
       </div>

       <div class="field">
        <label for="lastName">
         اسم العميل الأخير
         <span class="optional">(اختياري)</span>
        </label>

        <input
         id="lastName"
         type="text"
         name="last_name"
         value="{{ old('last_name') }}"
         maxlength="75"
        >
       </div>

       <div class="field">
        <label for="phone">
         رقم الهاتف
         <span class="required">*</span>
        </label>

        <input
         id="phone"
         type="tel"
         name="phone"
         value="{{ old('phone') }}"
         maxlength="50"
         required
        >
       </div>

       <div class="field">
        <label for="source">
         المصدر
         <span class="required">*</span>
        </label>

        <input
         id="source"
         type="text"
         name="source"
         value="{{ old('source') }}"
         maxlength="100"
         list="sourceOptions"
         required
        >

        <datalist id="sourceOptions">
         @foreach ($sources as $source)
          <option value="{{ $source }}"></option>
         @endforeach
        </datalist>
       </div>

       <div class="field">
        <label for="assignedEmployee">
         الموظف المسؤول
         <span class="required">*</span>
        </label>

        <input
         id="assignedEmployee"
         type="text"
         value="{{ $assignedEmployee }}"
         readonly
        >

        <span class="help">
         يتم تسجيله تلقائيًا من جلسة الدخول.
        </span>
       </div>

       <div class="field">
        <label for="leadStatus">
         حالة العميل
         <span class="required">*</span>
        </label>

        <select
         id="leadStatus"
         name="lead_status_id"
         required
        >
         <option value="">اختر حالة العميل</option>

         @foreach ($statuses as $status)
          <option
           value="{{ $status->id }}"
           data-code="{{ $status->code }}"
           @selected((string) old('lead_status_id') === (string) $status->id)
          >
           {{ $status->name_ar }}
          </option>
         @endforeach
        </select>
       </div>
      </div>
     </section>

          <!-- CRM CREATE REQUIRED NEXT DATE V3 START -->
     <section
      class="form-section conditional is-hidden"
      id="createNextFollowupSection"
     >
      <div class="section-head">
       <h3>المتابعة القادمة</h3>

       <p>
        حدد موعد التواصل القادم مع العميل.
       </p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="createNextFollowupAt">
         موعد المتابعة القادمة
         <span class="required">*</span>
        </label>

        <input
         id="createNextFollowupAt"
         type="datetime-local"
         name="next_follow_up_at"
         value="{{ old('next_follow_up_at') }}"
        >

        <small>
         مطلوب لكل الحالات ما عدا
         لم يرد وغير مهتم.
        </small>
       </div>
      </div>
     </section>
     <!-- CRM CREATE REQUIRED NEXT DATE V3 END -->

<section
      class="form-section conditional reveal-panel is-hidden"
      id="businessDetailsSection"
     >
      <div class="section-head">
       <h3>بيانات الشركة والعميل</h3>
       <p>
        تظهر في حالات مهتم، لم يرد، مقابلة، عرض سعر،
        مناقشة، تقفيل عقد وتنفيذ.
       </p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="companyName">اسم الشركة</label>
        <input
         id="companyName"
         type="text"
         name="company_name"
         value="{{ old('company_name') }}"
         maxlength="150"
        >
       </div>

       <div class="field">
        <label for="activity">النشاط</label>
        <input
         id="activity"
         type="text"
         name="activity"
         value="{{ old('activity') }}"
         maxlength="150"
        >
       </div>

       <div class="field">
        <label for="governorate">المحافظة</label>
        <input
         id="governorate"
         type="text"
         name="governorate"
         value="{{ old('governorate') }}"
         maxlength="100"
        >
       </div>

       <div class="field full">
        <label for="address">العنوان</label>
        <input
         id="address"
         type="text"
         name="address"
         value="{{ old('address') }}"
         maxlength="255"
        >
       </div>

       <div class="field">
        <label for="usersCount">عدد المستخدمين</label>
        <input
         id="usersCount"
         type="number"
         name="users_count"
         value="{{ old('users_count') }}"
         min="0"
         max="1000000"
        >
       </div>

       <div class="field">
        <label for="branchesCount">عدد الفروع</label>
        <input
         id="branchesCount"
         type="number"
         name="branches_count"
         value="{{ old('branches_count') }}"
         min="0"
         max="1000000"
        >
       </div>

       <div class="field">
        <label for="jobTitle">المنصب</label>
        <input
         id="jobTitle"
         type="text"
         name="job_title"
         value="{{ old('job_title') }}"
         maxlength="150"
        >
       </div>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel is-hidden"
      id="noAnswerSection"
     >
      <div class="future-action">
       <div>
        <strong>تسجيل متابعة</strong>
        <span>
         سيتم حفظ بيانات العميل أولًا ثم نقلك مباشرة إلى شاشة تسجيل المتابعة.
        </span>
       </div>

       <button
         class="btn"
         id="followUpButton"
         name="after_save"
         type="submit"
         value="followup"
        >
         ✓ حفظ العميل وتسجيل متابعة
        </button>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel is-hidden"
      id="notInterestedSection"
     >
      <div class="section-head">
       <h3>سبب عدم الاهتمام</h3>
       <p>يجب تسجيل السبب عند اختيار غير مهتم.</p>
      </div>

      <div class="field">
       <label for="disinterestReason">
        سبب عدم الاهتمام
        <span class="required">*</span>
       </label>

       <textarea
        id="disinterestReason"
        name="disinterest_reason"
        maxlength="5000"
       >{{ old('disinterest_reason') }}</textarea>
      </div>
     </section>

     <section
      class="form-section conditional reveal-panel quotation-card is-hidden"
      id="quotationSection"
     >
      <div class="section-head quotation-section-head">
       <h3>بيانات عرض السعر</h3>
       <p>
        تظهر هذه البيانات في مراحل عرض سعر، مناقشة،
        تقفيل عقد وتنفيذ.
       </p>
      </div>

      <div class="fields">
       <div class="field">
        <label for="solutionType">
         نوع النظام
         <span class="required">*</span>
        </label>

        <select
         class="quotation-select"
         id="solutionType"
         name="solution_type"
        >
         <option value="">اختر نوع النظام</option>

         <option
          value="call_center"
          @selected(old('solution_type') === 'call_center')
         >
          Call Center
         </option>

         <option
          value="erp"
          @selected(old('solution_type') === 'erp')
         >
          ERP
         </option>
        </select>
       </div>

       <div class="field">
        <label for="quotationFile">
         ملف عرض السعر
         <span class="required">*</span>
        </label>

        <input
         class="quotation-file"
         id="quotationFile"
         type="file"
         name="quotation_file"
         accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
        >

        <span
         class="help quotation-file-help"
         id="quotationFileHelp"
         aria-live="polite"
        >
         اختر ملف عرض السعر — الحد الأقصى 2MB.
        </span>
       </div>
      </div>

      <div
       class="fields quotation-detail-panel reveal-panel is-hidden"
       id="callCenterFields"
      >
       <div class="field">
        <label for="linesCount">
         عدد الخطوط
         <span class="required">*</span>
        </label>

        <input
         id="linesCount"
         type="number"
         name="lines_count"
         value="{{ old('lines_count') }}"
         min="1"
         max="1000000"
        >
       </div>

       <div class="field full">
        <label for="extensions">
         الملحقات
         <span class="required">*</span>
        </label>

        <textarea
         id="extensions"
         name="extensions"
         maxlength="5000"
         placeholder="اكتب تفاصيل الملحقات"
        >{{ old('extensions') }}</textarea>
       </div>
      </div>

      <div
       class="fields quotation-detail-panel reveal-panel is-hidden"
       id="erpFields"
      >
       <div class="field full">
        <label for="departments">
         الأقسام
         <span class="required">*</span>
        </label>

        <textarea
         id="departments"
         name="departments"
         maxlength="5000"
         placeholder="اكتب الأقسام المطلوبة"
        >{{ old('departments') }}</textarea>
       </div>
      </div>
     </section>

     <div class="form-actions">
      <a
       class="btn secondary"
       href="{{ route('v2.leads') }}"
      >
       إلغاء
      </a>

      <button class="btn" type="submit">
       حفظ العميل
      </button>
     </div>
    </form>
   </article>
  </div>
 </main>
</div>

<script>
 (() => {
  const body = document.body;
  const menuButton =
   document.getElementById('crmMenuButton');

  const overlay =
   document.getElementById('crmSidebarOverlay');

  const userMenuButton =
   document.getElementById('userMenuButton');

  const userMenuDropdown =
   document.getElementById('userMenuDropdown');

  const setUserMenuOpen = (open) => {
   userMenuButton?.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );

   if (userMenuDropdown) {
    userMenuDropdown.hidden = !open;
   }
  };

  const setSidebarOpen = (open) => {
   body.classList.toggle('crm-side-open', open);

   menuButton?.setAttribute(
    'aria-expanded',
    open ? 'true' : 'false'
   );
  };

  menuButton?.addEventListener('click', () => {
   setSidebarOpen(
    !body.classList.contains('crm-side-open')
   );
  });

  overlay?.addEventListener('click', () => {
   setSidebarOpen(false);
  });

  userMenuButton?.addEventListener(
   'click',
   (event) => {
    event.stopPropagation();

    const open =
     userMenuButton.getAttribute('aria-expanded')
     !== 'true';

    setUserMenuOpen(open);
   }
  );

  userMenuDropdown?.addEventListener(
   'click',
   (event) => {
    event.stopPropagation();
   }
  );

  document.addEventListener('click', () => {
   setUserMenuOpen(false);
  });

  document.addEventListener('keydown', (event) => {
   if (event.key === 'Escape') {
    setSidebarOpen(false);
    setUserMenuOpen(false);
   }
  });

  document
   .querySelectorAll('.crm-toggle')
   .forEach((button) => {
    button.addEventListener('click', () => {
     const menu = document.getElementById(
      button.dataset.crmMenu
     );

     if (!menu) {
      return;
     }

     const open =
      button.getAttribute('aria-expanded') !== 'true';

     button.setAttribute(
      'aria-expanded',
      open ? 'true' : 'false'
     );

     menu.classList.toggle('open', open);
    });
   });

  const statusSelect =
   document.getElementById('leadStatus');

  const businessSection =
   document.getElementById('businessDetailsSection');

  const noAnswerSection =
   document.getElementById('noAnswerSection');

  const notInterestedSection =
   document.getElementById('notInterestedSection');

  const quotationSection =
   document.getElementById('quotationSection');

  const solutionType =
   document.getElementById('solutionType');

  const callCenterFields =
   document.getElementById('callCenterFields');

  const erpFields =
   document.getElementById('erpFields');

  const disinterestReason =
   document.getElementById('disinterestReason');

  const quotationFile =
   document.getElementById('quotationFile');

  const linesCount =
   document.getElementById('linesCount');

  const extensions =
   document.getElementById('extensions');

  const departments =
   document.getElementById('departments');

  const quotationStageCodes = [
   'quotation',
   'discussion',
   'contract_closed',
   'execution'
  ];

  const selectedStatusCode = () => {
   const option =
    statusSelect.options[statusSelect.selectedIndex];

   return option?.dataset?.code || '';
  };

  const updateSolutionFields = () => {
   const quotationActive =
    quotationStageCodes.includes(
     selectedStatusCode()
    );

   const type = quotationActive
    ? solutionType.value
    : '';

   const callCenterActive =
    type === 'call_center';

   const erpActive =
    type === 'erp';

   callCenterFields.classList.toggle(
    'is-hidden',
    !callCenterActive
   );

   erpFields.classList.toggle(
    'is-hidden',
    !erpActive
   );

   linesCount.required = callCenterActive;
   extensions.required = callCenterActive;
   departments.required = erpActive;
  };

  const updateStatusSections = () => {
   const code = selectedStatusCode();

   const businessActive = [
    'interested',
    'no_answer',
    'meeting',
    'quotation',
    'discussion',
    'contract_closed',
    'execution'
   ].includes(code);

   const noAnswerActive =
    code === 'no_answer';

   const notInterestedActive =
    code === 'not_interested';

   const quotationActive =
    quotationStageCodes.includes(code);

   businessSection.classList.toggle(
    'is-hidden',
    !businessActive
   );

   noAnswerSection.classList.toggle(
    'is-hidden',
    !noAnswerActive
   );

   notInterestedSection.classList.toggle(
    'is-hidden',
    !notInterestedActive
   );

   quotationSection.classList.toggle(
    'is-hidden',
    !quotationActive
   );

   disinterestReason.required =
    notInterestedActive;

   solutionType.required =
    quotationActive;

   quotationFile.required =
    quotationActive;

   updateSolutionFields();
  };

  statusSelect.addEventListener(
   'change',
   updateStatusSections
  );

  solutionType.addEventListener(
   'change',
   updateSolutionFields
  );

  const quotationFileHelp =
   document.getElementById('quotationFileHelp');

  quotationFile?.addEventListener(
   'change',
   () => {
    const file = quotationFile.files?.[0];

    quotationFileHelp?.classList.toggle(
     'has-file',
     Boolean(file)
    );

    if (quotationFileHelp) {
     quotationFileHelp.textContent = file
      ? 'تم اختيار الملف: ' + file.name
      : 'اختر ملف عرض السعر — الحد الأقصى 2MB.';
    }
   }
  );

   document
    .getElementById('followUpButton')
    ?.addEventListener(
     'click',
     (event) => {
      const confirmed = window.confirm(
       'سيتم حفظ بيانات العميل أولًا، '
       + 'وبعد نجاح الحفظ سيتم نقلك '
       + 'مباشرة إلى شاشة تسجيل المتابعة. '
       + 'هل تريد المتابعة؟'
      );

      if (!confirmed) {
       event.preventDefault();
      }
     }
    );

  updateStatusSections();
 })();
</script>

<!-- CRM CREATE REQUIRED NEXT DATE V3 JS START -->
<script>
(() => {
 const statusSelect =
  document.getElementById(
   'leadStatus'
  );

 const section =
  document.getElementById(
   'createNextFollowupSection'
  );

 const field =
  document.getElementById(
   'createNextFollowupAt'
  );

 if (
  !statusSelect
  || !section
  || !field
 ) {
  return;
 }

 const excluded = new Set([
  'new',
  'no_answer',
  'not_interested',
  'execution'
 ]);

 const getStatusCode = () => {
  const option =
   statusSelect.options[
    statusSelect.selectedIndex
   ];

  return (
   option?.dataset?.code
   || ''
  );
 };

 const sync = () => {
  const code =
   getStatusCode();

  const required =
   code !== ''
   && !excluded.has(code);

  section.classList.toggle(
   'is-hidden',
   !required
  );

  field.required =
   required;

  field.disabled =
   !required;

  if (required) {
   field.setAttribute(
    'required',
    'required'
   );

   field.removeAttribute(
    'disabled'
   );

   field.setAttribute(
    'aria-required',
    'true'
   );
  } else {
   field.removeAttribute(
    'required'
   );

   field.setAttribute(
    'disabled',
    'disabled'
   );

   field.setAttribute(
    'aria-required',
    'false'
   );
  }
 };

 statusSelect.addEventListener(
  'change',
  sync
 );

 sync();
})();
</script>
<!-- CRM CREATE REQUIRED NEXT DATE V3 JS END -->
<!-- CRM NEW EXECUTION NO FOLLOWUP V7 -->

</body>
</html>
