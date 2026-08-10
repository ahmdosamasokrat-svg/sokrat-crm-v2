<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\TaskStatusController;
use App\Http\Controllers\LeadTransferController;
use App\Http\Controllers\LeadFollowupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    if (session('crm_v2_logged_in')) {
        return redirect()->route('dashboard');
    }

    return view('login');
})->name('login');

Route::post('/login', function (Request $request) {
    $username = env('CRM_V2_ADMIN_USER', 'admin');
    $password = env('CRM_V2_ADMIN_PASSWORD', 'admin123');

    if ($request->input('username') === $username && $request->input('password') === $password) {
        session(['crm_v2_logged_in' => true, 'crm_v2_user' => $username]);
        return redirect()->route('dashboard');
    }

    return back()->withErrors(['login' => 'invalid'])->withInput();
})->name('login.post');

Route::get(
    '/dashboard',
    [
        \App\Http\Controllers\DashboardController::class,
        'index',
    ]
)->name('dashboard');

Route::post('/logout', function () {
    session()->forget(['crm_v2_logged_in', 'crm_v2_user']);
    return redirect()->route('login');
})->name('logout');


// CODEX V2 PLACEHOLDER ROUTES START
Route::get('/leads', [LeadController::class, 'index'])->name('v2.leads');
Route::get('/leads/create', [LeadController::class, 'create'])
    ->name('v2.leads.create');

Route::post('/leads', [LeadController::class, 'store'])
    ->name('v2.leads.store');

Route::get(
    '/leads/{lead}/followups',
    [LeadFollowupController::class, 'index']
)
    ->whereNumber('lead')
    ->name('v2.leads.followups.index');

Route::post(
    '/leads/{lead}/followups',
    [LeadFollowupController::class, 'store']
)
    ->whereNumber('lead')
    ->name('v2.leads.followups.store');

Route::get('/leads/{lead}', [LeadController::class, 'show'])
    ->whereNumber('lead')
    ->name('v2.leads.show');

Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])
    ->whereNumber('lead')
    ->name('v2.leads.edit');

Route::get(
    '/leads/{lead}/quotation-preview',
    [LeadController::class, 'quotationPreview']
)
    ->whereNumber('lead')
    ->name('v2.leads.quotation.preview');

Route::patch('/leads/{lead}', [LeadController::class, 'update'])
    ->whereNumber('lead')
    ->name('v2.leads.update');

Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])
    ->whereNumber('lead')
    ->name('v2.leads.destroy');
Route::post(
    '/leads/export-selected',
    [LeadController::class, 'exportSelected']
)->name('v2.leads.export-selected');

Route::get(
    '/leads/import',
    [LeadTransferController::class, 'importIndex']
)->name('v2.leads.import');

Route::get(
    '/leads/import/template',
    [LeadTransferController::class, 'importTemplate']
)->name('v2.leads.import.template');

Route::post(
    '/leads/import/preview',
    [LeadTransferController::class, 'importPreview']
)->name('v2.leads.import.preview');

Route::post(
    '/leads/import/confirm',
    [LeadTransferController::class, 'importConfirm']
)->name('v2.leads.import.confirm');
Route::get(
    '/leads/export',
    [LeadTransferController::class, 'exportIndex']
)->name('v2.leads.export');

Route::post(
    '/leads/export/download',
    [LeadTransferController::class, 'exportDownload']
)->name('v2.leads.export.download');
Route::get(
    '/leads/kanban',
    [
        \App\Http\Controllers\DashboardController::class,
        'kanban',
    ]
)->name('v2.leads.kanban');
Route::get('/followups', fn () => view('placeholder', ['title' => 'كل المتابعات']))->name('v2.followups');

Route::get('/tasks/upcoming', fn () => view('placeholder', ['title' => 'المتابعات القادمة']))->name('v2.tasks.upcoming');
Route::get('/tasks/meetings', fn () => view('placeholder', ['title' => 'المقابلات']))->name('v2.tasks.meetings');
Route::get('/tasks/vip', fn () => view('placeholder', ['title' => 'عملاء VIP']))->name('v2.tasks.vip');
Route::get('/tasks/daily', fn () => view('placeholder', ['title' => 'المهام اليومية']))->name('v2.tasks.daily');

Route::get(
    '/tasks/status/{status}',
    [TaskStatusController::class, 'show']
)->where(
    'status',
    'new|no-answer|interested|not-interested|meeting|quotation|discussion|contract-closing|execution'
)->name('v2.tasks.status');

Route::get('/tasks/followups/{scope}', function (string $scope) {
    $scopes = [
        'today' => 'يجب التواصل معهم اليوم',
        'upcoming' => 'المتابعات القادمة',
        'overdue' => 'المتابعات المتأخرة',
    ];

    abort_unless(array_key_exists($scope, $scopes), 404);

    return view('placeholder', [
        'title' => $scopes[$scope],
    ]);
})->where(
    'scope',
    'today|upcoming|overdue'
)->name('v2.followups.scope');

Route::get('/tasks/meetings/{scope}', function (string $scope) {
    $scopes = [
        'today' => 'مقابلات اليوم',
        'upcoming' => 'المقابلات القادمة',
        'overdue' => 'المقابلات المتأخرة',
    ];

    abort_unless(array_key_exists($scope, $scopes), 404);

    return view('placeholder', [
        'title' => $scopes[$scope],
    ]);
})->where(
    'scope',
    'today|upcoming|overdue'
)->name('v2.meetings.scope');

Route::get('/reports/leads', fn () => view('placeholder', ['title' => 'تقرير العملاء']))->name('v2.reports.leads');
Route::get('/reports/tasks', fn () => view('placeholder', ['title' => 'تقرير المهام']))->name('v2.reports.tasks');
Route::get('/reports/employees', fn () => view('placeholder', ['title' => 'أداء الموظفين']))->name('v2.reports.employees');


// CRM CAMPAIGNS ROUTES V2 START

Route::get(
    '/campaigns',
    fn () => view(
        'placeholder',
        [
            'title' => 'عرض الحملات',
        ]
    )
)->name('v2.campaigns.index');

Route::get(
    '/campaigns/create',
    fn () => view(
        'placeholder',
        [
            'title' => 'إضافة حملة',
        ]
    )
)->name('v2.campaigns.create');

Route::get(
    '/campaigns/reports',
    fn () => view(
        'placeholder',
        [
            'title' => 'تقارير الحملة',
        ]
    )
)->name('v2.campaigns.reports');

// CRM CAMPAIGNS ROUTES V2 END


// CRM QUOTATIONS ROUTES V2 START

Route::get(
    '/quotations',
    [
        \App\Http\Controllers\QuotationController::class,
        'index',
    ]
)->name('v2.quotations.index');

Route::get(
    '/quotations/create',
    [
        \App\Http\Controllers\QuotationController::class,
        'create',
    ]
)->name('v2.quotations.create');

Route::post(
    '/quotations',
    [
        \App\Http\Controllers\QuotationController::class,
        'store',
    ]
)->name('v2.quotations.store');

Route::get(
    '/quotations/{quotation}',
    [
        \App\Http\Controllers\QuotationController::class,
        'show',
    ]
)
    ->whereNumber('quotation')
    ->name('v2.quotations.show');

// CRM QUOTATIONS ROUTES V2 END

Route::get('/settings', fn () => view('placeholder', ['title' => 'الإعدادات']))->name('v2.settings');
// CODEX V2 PLACEHOLDER ROUTES END
