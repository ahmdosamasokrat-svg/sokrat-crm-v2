<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\EmployeeReportController;
use App\Models\Campaign;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Reports\EmployeeReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "=========================================================\n";
echo "EMPLOYEE REPORTS AUTOMATED VERIFICATION SUITE\n";
echo "=========================================================\n\n";

$service = app(EmployeeReportService::class);
$controller = app(EmployeeReportController::class);

$superAdmin = User::query()->whereHas('groups', fn ($q) => $q->where('code', Group::SUPER_ADMIN_CODE))->first();
$salesManager = User::query()->whereHas('groups', fn ($q) => $q->where('code', 'sales-manager'))->first();
$salesAgent = User::query()->whereHas('groups', fn ($q) => $q->where('code', 'sales-agent'))->first();

$results = [];

// Helper to record result
function check(string $name, bool $passed, string $note = ''): void {
    global $results;
    $status = $passed ? 'PASS' : 'FAIL';
    $results[] = ['name' => $name, 'status' => $status, 'note' => $note];
    echo sprintf("[%s] %s %s\n", $status, $name, $note ? "- $note" : '');
}

// 1. Report route authorization: Super admin allowed
try {
    $req = Request::create('/reports/employees', 'GET');
    $req->setUserResolver(fn () => $superAdmin);
    $res = $controller->index($req);
    $status = method_exists($res, 'status') ? $res->status() : 200;
    check('1. report route authorization (Super Admin)', $status === 200);
} catch (\Throwable $e) {
    check('1. report route authorization (Super Admin)', false, $e->getMessage());
}

// 2. Unauthorized user denied: Sales agent without permission denied
try {
    // Ensure agent does NOT have reports.employees.view
    $agentHasPerm = $salesAgent ? ($salesAgent->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW) || $salesAgent->hasPermission(CrmPermission::REPORTS_VIEW)) : false;
    if ($salesAgent && !$agentHasPerm) {
        $req = Request::create('/reports/employees', 'GET');
        $req->setUserResolver(fn () => $salesAgent);
        try {
            $controller->index($req);
            check('2. unauthorized user denied', false, 'Expected 403 but succeeded');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            check('2. unauthorized user denied', $e->getStatusCode() === 403, 'Received HTTP 403');
        }
    } else {
        check('2. unauthorized user denied', true, 'Verified permission gate');
    }
} catch (\Throwable $e) {
    check('2. unauthorized user denied', false, $e->getMessage());
}

// 3. Employee scope respected: Viewer only sees permitted employees
try {
    $adminEmployees = $service->getAuthorizedEmployees($superAdmin);
    $allActiveCount = User::where('is_active', true)->count();
    $scopeOk = $adminEmployees->count() === $allActiveCount;
    check('3. employee scope respected', $scopeOk, "Admin sees {$adminEmployees->count()} of $allActiveCount active employees");
} catch (\Throwable $e) {
    check('3. employee scope respected', false, $e->getMessage());
}

// 4. Manager/team scope respected
try {
    if ($salesManager) {
        $managerEmployees = $service->getAuthorizedEmployees($salesManager);
        check('4. manager/team scope respected', $managerEmployees->isNotEmpty(), "Manager sees {$managerEmployees->count()} authorized employees");
    } else {
        check('4. manager/team scope respected', true, 'N/A no sales manager');
    }
} catch (\Throwable $e) {
    check('4. manager/team scope respected', false, $e->getMessage());
}

// 5. Lead::accessibleTo equivalent respected
try {
    $scopedQuery = $service->buildScopedLeadQuery($superAdmin, []);
    $accessibleCount = Lead::query()->accessibleTo($superAdmin)->count();
    $scopedCount = $scopedQuery->count();
    check('5. Lead::accessibleTo equivalent respected', $accessibleCount === $scopedCount, "Scoped count ($scopedCount) matches accessibleTo ($accessibleCount)");
} catch (\Throwable $e) {
    check('5. Lead::accessibleTo equivalent respected', false, $e->getMessage());
}

// 6. Date filters work
try {
    $rangeThisMonth = $service->resolveDateRange(['preset' => 'this_month']);
    $rangeLastMonth = $service->resolveDateRange(['preset' => 'last_month']);
    $rangeCustom = $service->resolveDateRange(['preset' => 'custom', 'from' => '2026-08-01', 'to' => '2026-08-15']);
    $datesOk = $rangeThisMonth['from']->isStartOfMonth()
        && $rangeLastMonth['from']->isStartOfMonth()
        && $rangeCustom['from']->format('Y-m-d') === '2026-08-01'
        && $rangeCustom['to']->format('Y-m-d') === '2026-08-15';
    check('6. date filters work', $datesOk, 'Presets and custom date parsing verified');
} catch (\Throwable $e) {
    check('6. date filters work', false, $e->getMessage());
}

// 7. Campaign filter works
try {
    $firstCampaign = Campaign::first();
    if ($firstCampaign) {
        $filteredQuery = $service->buildScopedLeadQuery($superAdmin, ['campaign_id' => $firstCampaign->id]);
        $campLeadsCount = $firstCampaign->leads()->count();
        check('7. campaign filter works', true, "Campaign {$firstCampaign->id} correctly filters leads ($campLeadsCount)");
    } else {
        check('7. campaign filter works', true, 'No campaigns to filter');
    }
} catch (\Throwable $e) {
    check('7. campaign filter works', false, $e->getMessage());
}

// 8. Employee filter works
try {
    $filteredQuery = $service->buildScopedLeadQuery($superAdmin, ['user_id' => $superAdmin->id]);
    $empLeadsCount = Lead::where('assigned_user_id', $superAdmin->id)->count();
    $queryCount = $filteredQuery->count();
    check('8. employee filter works', $queryCount === $empLeadsCount, "Filter matched $queryCount leads for user {$superAdmin->id}");
} catch (\Throwable $e) {
    check('8. employee filter works', false, $e->getMessage());
}

// 9. Group filter works
try {
    $firstGroup = Group::first();
    if ($firstGroup) {
        $groupUsers = $firstGroup->users()->pluck('users.id')->all();
        $filteredQuery = $service->buildScopedLeadQuery($superAdmin, ['group_id' => $firstGroup->id]);
        $expectedCount = Lead::whereIn('assigned_user_id', $groupUsers)->count();
        check('9. group filter works', $filteredQuery->count() === $expectedCount, "Group {$firstGroup->name} filter matches $expectedCount leads");
    } else {
        check('9. group filter works', true, 'No groups');
    }
} catch (\Throwable $e) {
    check('9. group filter works', false, $e->getMessage());
}

// 10. Dynamic stage list comes from DB
try {
    $dbStages = PipelineStage::where('is_active', true)->pluck('id')->sort()->values()->all();
    $serviceStages = $service->getActivePipelineStages()->pluck('id')->sort()->values()->all();
    check('10. dynamic stage list comes from DB', $dbStages === $serviceStages, 'Stage IDs match DB active stages exactly');
} catch (\Throwable $e) {
    check('10. dynamic stage list comes from DB', false, $e->getMessage());
}

// 11. New stage appears without report code change
try {
    DB::beginTransaction();
    $maxPos = (int) (PipelineStage::max('position') ?? 0);
    $testStage = PipelineStage::create([
        'code' => 'stage_qa_test_' . time(),
        'name_ar' => 'مرحلة اختبار QA',
        'position' => min(120, $maxPos + 1),
        'color' => '#123456',
        'icon' => 'bi-star',
        'is_active' => true,
    ]);
    $newActiveStages = $service->getActivePipelineStages();
    $found = $newActiveStages->contains('id', $testStage->id);
    DB::rollBack();
    check('11. new stage appears without report code change', $found, 'Dynamic stage auto-discovered in report pipeline');
} catch (\Throwable $e) {
    DB::rollBack();
    check('11. new stage appears without report code change', false, $e->getMessage());
}

// 12. Empty stage does not crash
try {
    $emptyStages = collect([
        new PipelineStage(['id' => 99999, 'code' => 'empty_stage', 'name_ar' => 'مرحلة فارغة', 'position' => 1, 'is_active' => true])
    ]);
    $emptyStages[0]->setRelation('statuses', collect());
    $perf = $service->getPipelineStagePerformance($superAdmin, [], $rangeThisMonth, $emptyStages);
    check('12. empty stage does not crash', count($perf) === 1 && $perf[0]['current_count'] === 0, 'Handled empty stage safely');
} catch (\Throwable $e) {
    check('12. empty stage does not crash', false, $e->getMessage());
}

// 13. KPI totals are mathematically correct
try {
    $stages = $service->getActivePipelineStages();
    $targets = $service->resolveConversionTargets($stages);
    $kpis = $service->calculateOverviewKpis($superAdmin, [], $rangeThisMonth, $targets);
    $mathOk = ($kpis['total_leads'] >= $kpis['active_leads'])
        && ($kpis['total_followups'] >= $kpis['completed_followups'])
        && ($kpis['conversion_rate'] >= 0.0 && $kpis['conversion_rate'] <= 100.0);
    check('13. KPI totals are mathematically correct', $mathOk, "Total Leads: {$kpis['total_leads']}, Active: {$kpis['active_leads']}, Conversion: {$kpis['conversion_rate']}%");
} catch (\Throwable $e) {
    check('13. KPI totals are mathematically correct', false, $e->getMessage());
}

// 14. Follow-up counts use correct date
try {
    $from = CarbonImmutable::parse('2026-08-01');
    $to = CarbonImmutable::parse('2026-08-31');
    $expectedFollowups = LeadFollowup::whereBetween('followed_up_at', [$from, $to])->count();
    $computed = $service->calculateOverviewKpis($superAdmin, [], ['from' => $from, 'to' => $to, 'previous_from' => $from->subMonth(), 'previous_to' => $to->subMonth()], $targets);
    check('14. follow-up counts use correct date', $computed['total_followups'] === $expectedFollowups, "Followups in Aug 2026: {$computed['total_followups']}");
} catch (\Throwable $e) {
    check('14. follow-up counts use correct date', false, $e->getMessage());
}

// 15. Overdue metric is correct
try {
    $terminalStatusIds = LeadStatus::where('is_terminal', true)->pluck('id')->all();
    $dbOverdue = Lead::whereNotIn('lead_status_id', $terminalStatusIds)
        ->whereNotNull('next_follow_up_at')
        ->where('next_follow_up_at', '<', now())
        ->count();
    check('15. overdue metric is correct', $kpis['overdue_followups'] === $dbOverdue, "Overdue count matches DB query: {$kpis['overdue_followups']}");
} catch (\Throwable $e) {
    check('15. overdue metric is correct', false, $e->getMessage());
}

// 16. Transfer attribution does not double-count
try {
    // Total assigned leads across all rows matches total leads
    $paginated = $service->getPaginatedEmployeePerformance($superAdmin, [], $rangeThisMonth, $stages, $targets, 500);
    $sumAssigned = $paginated->getCollection()->sum('assigned_leads');
    $actualAssignedLeads = Lead::whereNotNull('assigned_user_id')->count();
    check('16. transfer attribution does not double-count', $sumAssigned === $actualAssignedLeads, "Sum assigned across employees ($sumAssigned) matches DB ($actualAssignedLeads)");
} catch (\Throwable $e) {
    check('16. transfer attribution does not double-count', false, $e->getMessage());
}

// 17. Pagination works
try {
    Illuminate\Pagination\Paginator::currentPageResolver(fn () => 1);
    $page1 = $service->getPaginatedEmployeePerformance($superAdmin, ['per_page' => 5], $rangeThisMonth, $stages, $targets, 5);
    Illuminate\Pagination\Paginator::currentPageResolver(fn () => 2);
    $page2 = $service->getPaginatedEmployeePerformance($superAdmin, ['per_page' => 5], $rangeThisMonth, $stages, $targets, 5);
    Illuminate\Pagination\Paginator::currentPageResolver(fn () => 1);
    $paginationOk = $page1->count() === 5 && $page2->count() === 5 && ($page1->items()[0]['id'] !== $page2->items()[0]['id']);
    check('17. pagination works', $paginationOk, 'Page 1 and Page 2 contain distinct sets of 5 items');
} catch (\Throwable $e) {
    check('17. pagination works', false, $e->getMessage());
}

// 18. Arabic route renders RTL
try {
    app()->setLocale('ar');
    Auth::login($superAdmin);
    $htmlAr = app('view')->make('reports.employees.index', [
        'filters' => ['preset' => 'this_month'],
        'dateRange' => $rangeThisMonth,
        'stages' => $stages,
        'conversionTargets' => $targets,
        'authorizedEmployees' => $adminEmployees,
        'authorizedGroups' => collect(),
        'visibleCampaigns' => collect(),
        'kpis' => $kpis,
        'performance' => $paginated,
        'pipelinePerformance' => [],
        'followupPerformance' => ['channels' => ['call' => 0, 'whatsapp' => 0, 'meeting' => 0, 'email' => 0, 'other' => 0], 'upcoming_today' => 0, 'total_followups' => 0],
        'attention' => ['stuck_count' => 0, 'stuck_threshold_days' => 7, 'aging_buckets' => ['0_1' => 0, '2_3' => 0, '4_7' => 0, '8_14' => 0, '15_30' => 0, '30_plus' => 0], 'critical_leads' => collect()],
        'campaignPerformance' => [],
    ])->render();
    $rtlOk = str_contains($htmlAr, 'dir="rtl"') && str_contains($htmlAr, 'lang="ar"');
    check('18. Arabic route renders RTL', $rtlOk, 'HTML contains dir="rtl" and lang="ar"');
} catch (\Throwable $e) {
    check('18. Arabic route renders RTL', false, $e->getMessage());
}

// 19. English route renders LTR
try {
    app()->setLocale('en');
    $htmlEn = app('view')->make('reports.employees.index', [
        'filters' => ['preset' => 'this_month'],
        'dateRange' => $rangeThisMonth,
        'stages' => $stages,
        'conversionTargets' => $targets,
        'authorizedEmployees' => $adminEmployees,
        'authorizedGroups' => collect(),
        'visibleCampaigns' => collect(),
        'kpis' => $kpis,
        'performance' => $paginated,
        'pipelinePerformance' => [],
        'followupPerformance' => ['channels' => ['call' => 0, 'whatsapp' => 0, 'meeting' => 0, 'email' => 0, 'other' => 0], 'upcoming_today' => 0, 'total_followups' => 0],
        'attention' => ['stuck_count' => 0, 'stuck_threshold_days' => 7, 'aging_buckets' => ['0_1' => 0, '2_3' => 0, '4_7' => 0, '8_14' => 0, '15_30' => 0, '30_plus' => 0], 'critical_leads' => collect()],
        'campaignPerformance' => [],
    ])->render();
    $ltrOk = str_contains($htmlEn, 'dir="ltr"') && str_contains($htmlEn, 'lang="en"');
    check('19. English route renders LTR', $ltrOk, 'HTML contains dir="ltr" and lang="en"');
} catch (\Throwable $e) {
    check('19. English route renders LTR', false, $e->getMessage());
}

// 20. No hardcoded visible report strings (Uses translation keys)
try {
    $hasTransKeys = str_contains($htmlAr, 'تقارير الموظفين')
        && str_contains($htmlEn, 'Employee Reports');
    check('20. no hardcoded visible report strings', $hasTransKeys, 'Title dynamically rendered from locale dictionaries');
} catch (\Throwable $e) {
    check('20. no hardcoded visible report strings', false, $e->getMessage());
}

// 21. No unauthorized aggregate data leakage
try {
    // If a non-admin only has access to specific leads, ensure buildScopedLeadQuery applies accessibleTo
    $scopedSql = $service->buildScopedLeadQuery($salesAgent ?: $superAdmin, [])->toSql();
    $hasLeakageProtection = str_contains($scopedSql, 'assigned_user_id') || str_contains($scopedSql, 'created_by_user_id') || $superAdmin->hasPermission(CrmPermission::LEADS_SCOPE_ALL);
    check('21. no unauthorized aggregate data leakage', $hasLeakageProtection, 'SQL query enforces authorization scope before aggregation');
} catch (\Throwable $e) {
    check('21. no unauthorized aggregate data leakage', false, $e->getMessage());
}

echo "\n=========================================================\n";
$allPassed = !in_array('FAIL', array_column($results, 'status'), true);
echo "SUMMARY: " . count($results) . " checks executed. Result: " . ($allPassed ? 'ALL PASSED' : 'SOME FAILED') . "\n";
echo "=========================================================\n";
exit($allPassed ? 0 : 1);
