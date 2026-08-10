<!doctype html>
<html lang="ar" dir="rtl">
<head>
 <meta charset="utf-8">
 <meta
  name="viewport"
  content="width=device-width,initial-scale=1"
 >
 <title>@yield('title') | CRM v2</title>

 <style>
  *{box-sizing:border-box}

  :root{
   --red:#dc2637;
   --dark:#182033;
   --muted:#7e899a;
   --line:#e4e8ef;
   --bg:#f5f6f9;
   --card:#fff;
   --shadow:0 14px 35px #1720330d;
   --blue:#3478f6;
   --green:#169a64;
   --orange:#e59b16;
   --purple:#7b61df
  }

  body{
   margin:0;
   background:var(--bg);
   color:var(--dark);
   font-family:
    Tahoma,
    Arial,
    sans-serif
  }

  button,
  input,
  select{
   font:inherit
  }

  .transfer-layout{
   min-height:100vh;
   display:flex;
   direction:rtl
  }

  .transfer-sidebar{
   width:265px;
   min-width:265px;
   min-height:100vh;
   background:#fff;
   border-left:1px solid var(--line);
   z-index:40
  }

  .transfer-sidebar>*
  {
   min-height:100vh
  }

  .transfer-main{
   min-width:0;
   flex:1
  }

  .transfer-topbar{
   min-height:82px;
   display:flex;
   align-items:center;
   gap:16px;
   padding:15px 27px;
   border-bottom:1px solid var(--line);
   background:#fff
  }

  .transfer-menu{
   width:42px;
   height:42px;
   display:none;
   align-items:center;
   justify-content:center;
   border:1px solid var(--line);
   border-radius:10px;
   background:#fff;
   cursor:pointer;
   font-size:20px
  }

  .transfer-page-title{
   min-width:0;
   flex:1
  }

  .transfer-page-title h1{
   margin:0;
   font-size:24px
  }

  .transfer-page-title p{
   margin:6px 0 0;
   color:var(--muted);
   font-size:12px;
   line-height:1.8
  }

  .transfer-top-actions{
   display:flex;
   align-items:center;
   flex-wrap:wrap;
   gap:8px
  }

  .transfer-content{
   width:min(1320px,calc(100% - 36px));
   margin:24px auto 45px
  }

  .transfer-card{
   overflow:hidden;
   margin-bottom:20px;
   border:1px solid var(--line);
   border-radius:18px;
   background:var(--card);
   box-shadow:var(--shadow)
  }

  .transfer-hero{
   padding:25px;
   background:
    linear-gradient(
     120deg,
     #171f31,
     #30394c
    );
   color:#fff
  }

  .transfer-hero small{
   color:#f2a3ac;
   font-weight:900
  }

  .transfer-hero h2{
   margin:8px 0 7px;
   font-size:25px
  }

  .transfer-hero p{
   max-width:850px;
   margin:0;
   color:#cbd0da;
   font-size:13px;
   line-height:1.9
  }

  .card-head{
   display:flex;
   align-items:center;
   justify-content:space-between;
   gap:15px;
   padding:18px 21px;
   border-bottom:1px solid var(--line)
  }

  .card-head h3{
   margin:0;
   font-size:17px
  }

  .card-head p{
   margin:5px 0 0;
   color:var(--muted);
   font-size:11px;
   line-height:1.7
  }

  .card-body{
   padding:21px
  }

  .grid{
   display:grid;
   grid-template-columns:
    repeat(2,minmax(0,1fr));
   gap:15px
  }

  .grid.three{
   grid-template-columns:
    repeat(3,minmax(0,1fr))
  }

  .field{
   display:grid;
   gap:7px
  }

  .field.full{
   grid-column:1/-1
  }

  .field label{
   font-size:12px;
   font-weight:900
  }

  .control{
   width:100%;
   min-height:44px;
   padding:10px 12px;
   border:1px solid #d8dee8;
   border-radius:10px;
   background:#fff;
   color:var(--dark);
   outline:none
  }

  .control:focus{
   border-color:#8badde;
   box-shadow:0 0 0 3px #3478f617
  }

  .help{
   color:var(--muted);
   font-size:10px;
   line-height:1.8
  }

  .btn{
   min-height:42px;
   display:inline-flex;
   align-items:center;
   justify-content:center;
   gap:7px;
   padding:8px 14px;
   border:1px solid #d8dee8;
   border-radius:10px;
   background:#fff;
   color:#243247;
   text-decoration:none;
   font-weight:900;
   font-size:12px;
   cursor:pointer;
   transition:.16s
  }

  .btn:hover{
   transform:translateY(-1px);
   box-shadow:0 7px 17px #17203312
  }

  .btn.primary{
   border-color:#2f70c8;
   background:#3478f6;
   color:#fff
  }

  .btn.success{
   border-color:#168957;
   background:#169a64;
   color:#fff
  }

  .btn.soft{
   background:#f8fafc
  }

  .actions{
   display:flex;
   justify-content:flex-end;
   flex-wrap:wrap;
   gap:9px;
   margin-top:18px
  }

  .notice{
   margin-bottom:17px;
   padding:13px 15px;
   border-radius:11px;
   font-size:12px;
   line-height:1.8;
   font-weight:800
  }

  .notice.success{
   border:1px solid #a9d9bb;
   background:#eefaf2;
   color:#146238
  }

  .notice.error{
   border:1px solid #efbcbc;
   background:#fff2f2;
   color:#a62d2d
  }

  .notice.info{
   border:1px solid #bcd2ef;
   background:#f1f7ff;
   color:#2b5e9d
  }

  .error-list{
   margin:7px 0 0;
   padding-right:20px
  }

  .stat-grid{
   display:grid;
   grid-template-columns:
    repeat(4,minmax(0,1fr));
   gap:12px;
   margin-bottom:18px
  }

  .stat{
   padding:15px;
   border:1px solid var(--line);
   border-radius:13px;
   background:#fff
  }

  .stat span{
   display:block;
   color:var(--muted);
   font-size:10px;
   font-weight:900
  }

  .stat strong{
   display:block;
   margin-top:6px;
   font-size:23px
  }

  .status-guide{
   display:grid;
   grid-template-columns:
    repeat(3,minmax(0,1fr));
   gap:9px
  }

  .status-guide-item{
   padding:10px 12px;
   border:1px solid var(--line);
   border-radius:10px;
   background:#fafbfc
  }

  .status-guide-item strong{
   display:block;
   font-size:12px
  }

  .status-guide-item small{
   display:block;
   margin-top:4px;
   color:var(--muted);
   font-size:10px
  }

  .table-wrap{
   overflow:auto;
   border:1px solid var(--line);
   border-radius:13px
  }

  table{
   width:100%;
   min-width:920px;
   border-collapse:collapse;
   background:#fff
  }

  th,
  td{
   padding:11px 10px;
   border-bottom:1px solid #edf0f4;
   text-align:right;
   vertical-align:top;
   font-size:11px
  }

  th{
   position:sticky;
   top:0;
   z-index:2;
   background:#f7f9fc;
   color:#526076;
   font-weight:900
  }

  .row-errors,
  .row-warnings{
   margin:0;
   padding-right:17px;
   line-height:1.8
  }

  .row-errors{color:#a62d2d}
  .row-warnings{color:#9a6700}

  .badge{
   display:inline-flex;
   align-items:center;
   justify-content:center;
   padding:5px 8px;
   border-radius:999px;
   font-size:10px;
   font-weight:900
  }

  .badge.valid{
   background:#eaf8ef;
   color:#126837
  }

  .badge.error{
   background:#fff0f0;
   color:#b42c2c
  }

  .badge.duplicate{
   background:#fff7e5;
   color:#946300
  }

  .checkbox-grid{
   display:grid;
   grid-template-columns:
    repeat(4,minmax(0,1fr));
   gap:9px
  }

  .check-item{
   min-height:42px;
   display:flex;
   align-items:center;
   gap:8px;
   padding:9px 11px;
   border:1px solid var(--line);
   border-radius:9px;
   background:#fafbfc;
   font-size:11px;
   font-weight:800
  }

  .check-item input{
   width:16px;
   height:16px;
   accent-color:#3478f6
  }

  .transfer-overlay{
   display:none
  }

  @media(max-width:1050px){
   .grid.three{
    grid-template-columns:
     repeat(2,minmax(0,1fr))
   }

   .checkbox-grid{
    grid-template-columns:
     repeat(3,minmax(0,1fr))
   }
  }

  @media(max-width:900px){
   .transfer-sidebar{
    position:fixed;
    top:0;
    right:0;
    bottom:0;
    transform:translateX(105%);
    transition:.2s;
    box-shadow:-18px 0 42px #17203325
   }

   body.transfer-side-open
   .transfer-sidebar{
    transform:translateX(0)
   }

   .transfer-menu{
    display:inline-flex
   }

   .transfer-overlay{
    position:fixed;
    inset:0;
    z-index:30;
    background:#11182755
   }

   body.transfer-side-open
   .transfer-overlay{
    display:block
   }
  }

  @media(max-width:700px){
   .transfer-content{
    width:calc(100% - 18px);
    margin-top:12px
   }

   .transfer-topbar{
    padding:12px
   }

   .transfer-page-title p{
    display:none
   }

   .grid,
   .grid.three,
   .stat-grid,
   .status-guide,
   .checkbox-grid{
    grid-template-columns:1fr
   }

   .field.full{
    grid-column:auto
   }

   .transfer-top-actions{
    display:none
   }

   .card-body,
   .transfer-hero{
    padding:16px
   }
  }
 

  /* CRM TRANSFER SIDEBAR SYNC START */

  /*
   * The shared CRM sidebar partial already
   * renders <aside class="crm-side">.
   * The old transfer wrapper must not create
   * another sidebar-sized box around it.
   */
  .transfer-sidebar{
   display:contents!important
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
   color:inherit;
   text-decoration:none
  }

  .crm-side-brand .logo{
   width:58px;
   height:58px;
   flex:0 0 58px;
   display:block;
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
   gap:5px
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
   font-size:16px;
   font-weight:bold;
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
   border-color:#cf2031;
   background:
    linear-gradient(
     135deg,
     #e83243,
     #c91d2e
    );
   color:#fff;
   box-shadow:
    0 12px 27px #dc26372c
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
   min-width:0;
   flex:1
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
   font-size:11px;
   transition:.2s
  }

  .crm-toggle.active .crm-arrow{
   color:#fff
  }

  .crm-toggle[
   aria-expanded="true"
  ] .crm-arrow{
   transform:rotate(180deg)
  }

  .crm-sub{
   display:grid;
   grid-template-rows:0fr;
   transition:.22s
  }

  .crm-sub.open{
   grid-template-rows:1fr
  }

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

  .crm-sub a:hover{
   color:var(--red);
   background:#fff2f4
  }

  .crm-sub a.active{
   color:var(--red);
   background:#fff0f2
  }

  /*
   * Keep the transfer content as the flexible
   * main area beside the real 288px CRM sidebar.
   */
  .transfer-layout{
   display:flex;
   min-height:100vh;
   direction:rtl
  }

  .transfer-main{
   min-width:0;
   flex:1 1 auto;
   width:calc(100% - 288px);
   direction:rtl
  }

  @media(max-width:900px){
   .transfer-layout{
    display:block
   }

   .crm-side{
    position:fixed;
    right:0;
    top:0;
    bottom:0;
    width:min(
     288px,
     calc(100vw - 45px)
    );
    max-width:none;
    transform:translateX(105%);
    transition:.25s;
    box-shadow:
     -20px 0 55px #17203325
   }

   .transfer-main{
    width:100%;
    min-height:100vh
   }

   body.crm-side-open{
    overflow:hidden
   }

   body.crm-side-open .crm-side{
    transform:none
   }

   body.crm-side-open
   .transfer-overlay{
    display:block
   }

   .transfer-overlay{
    position:fixed;
    inset:0;
    z-index:70;
    display:none;
    background:#12182788;
    backdrop-filter:blur(2px)
   }

   .transfer-menu{
    display:inline-flex
   }
  }

  @media(max-width:520px){
   .crm-side-brand small,
   .crm-side-caption{
    font-size:12px
   }

   .crm-sub a{
    font-size:13px
   }
  }

  /* CRM TRANSFER SIDEBAR SYNC END */

</style>

 @stack('styles')
</head>
<body>

 {{-- CRM CONDITIONAL PAGE LOADER START --}}
@unless (
 trim(
  $__env->yieldContent(
   'disable-page-loader'
  )
 ) === '1'
)
 @include('partials.page-loader')
@endunless
{{-- CRM CONDITIONAL PAGE LOADER END --}}

 <div class="transfer-layout">
  <div
   class="transfer-sidebar"
   id="transferSidebar"
  >
   @include('partials.crm-sidebar')
  </div>

  <div
   class="transfer-overlay"
   id="transferOverlay"
  ></div>

  <main class="transfer-main">
   <header class="transfer-topbar">
    <button
     class="transfer-menu"
     id="transferMenuButton"
     type="button"
     aria-label="فتح القائمة"
    >
     ☰
    </button>

    <div class="transfer-page-title">
     <h1>@yield('page-title')</h1>
     <p>@yield('page-description')</p>
    </div>

    <div class="transfer-top-actions">
     @yield('top-actions')
    </div>
   </header>

   <div class="transfer-content">
    @if (session('success'))
     <div class="notice success">
      {{ session('success') }}
     </div>
    @endif

    @if ($errors->any())
     <div class="notice error">
      <strong>
       يرجى مراجعة البيانات التالية:
      </strong>

      <ul class="error-list">
       @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
       @endforeach
      </ul>
     </div>
    @endif

    @yield('content')
   </div>
  </main>
 </div>

 <script>
  (() => {
   const body = document.body;

   const menu =
    document.getElementById(
     'transferMenuButton'
    );

   const overlay =
    document.getElementById(
     'transferOverlay'
    );

   const setOpen = (open) => {
    body.classList.toggle(
     'crm-side-open',
     open
    );

    menu?.setAttribute(
     'aria-expanded',
     open ? 'true' : 'false'
    );
   };

   menu?.addEventListener(
    'click',
    () => {
     setOpen(
      !body.classList.contains(
       'crm-side-open'
      )
     );
    }
   );

   overlay?.addEventListener(
    'click',
    () => setOpen(false)
   );

   /*
    * crmSidebarMenuToggleHandler
    * Same submenu behaviour used by
    * the working leads screens.
    */
   document
    .querySelectorAll(
     '.crm-toggle'
    )
    .forEach((button) => {
     button.addEventListener(
      'click',
      () => {
       const submenu =
        document.getElementById(
         button.dataset.crmMenu
         || button.dataset.menu
         || ''
        );

       if (!submenu) {
        return;
       }

       const open =
        button.getAttribute(
         'aria-expanded'
        ) !== 'true';

       button.setAttribute(
        'aria-expanded',
        open ? 'true' : 'false'
       );

       submenu.classList.toggle(
        'open',
        open
       );
      }
     );
    });

   document
    .querySelectorAll(
     '.crm-side a'
    )
    .forEach((link) => {
     link.addEventListener(
      'click',
      () => {
       if (
        window.innerWidth <= 900
       ) {
        setOpen(false);
       }
      }
     );
    });

   document.addEventListener(
    'keydown',
    (event) => {
     if (event.key === 'Escape') {
      setOpen(false);
     }
    }
   );
  })();
 </script>

 @stack('scripts')
</body>
</html>
