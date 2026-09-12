<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Reports\EmployeeReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeReportController extends Controller
{
    public function __construct(
        private readonly EmployeeReportService $reportService,
    ) {
    }

    /**
     * Display the dynamic Employee Reports center.
     */
    public function index(Request $request): View|JsonResponse
    {
        $viewer = $request->user();
        abort_unless(
            $viewer !== null && (
                $viewer->isSuperAdmin()
                || $viewer->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW)
                || $viewer->hasPermission(CrmPermission::REPORTS_VIEW)
            ),
            403,
            __('crm.unauthorized')
        );

        $filters = $this->extractFilters($request);
        $dateRange = $this->reportService->resolveDateRange($filters);
        $stages = $this->reportService->getActivePipelineStages($viewer);
        $conversionTargets = $this->reportService->resolveConversionTargets($stages);

        $authorizedEmployees = $this->reportService->getAuthorizedEmployees($viewer);
        $authorizedGroups = $this->reportService->getAuthorizedGroups($viewer);
        $visibleCampaigns = $this->reportService->getVisibleCampaigns($viewer);

        $kpis = $this->reportService->calculateOverviewKpis($viewer, $filters, $dateRange, $conversionTargets);
        $performance = $this->reportService->getPaginatedEmployeePerformance(
            $viewer,
            $filters,
            $dateRange,
            $stages,
            $conversionTargets,
            (int) ($filters['per_page'] ?? 15)
        );

        $pipelinePerformance = $this->reportService->getPipelineStagePerformance($viewer, $filters, $dateRange, $stages);
        $followupPerformance = $this->reportService->getFollowupPerformance($viewer, $filters, $dateRange);
        $attention = $this->reportService->getAttentionAndAging($viewer, $filters, (int) ($filters['stuck_threshold_days'] ?? 7));
        $campaignPerformance = $this->reportService->getCampaignPerformance($viewer, $filters, $dateRange);
        $performanceTrend = $this->reportService->getPerformanceTrend($viewer, $filters, $dateRange, $stages);
        $upcomingFollowups = $this->reportService->getUpcomingFollowups($viewer, $filters, 6);
        $customerDistribution = $this->reportService->getCustomerDistribution($viewer, $filters);

        if ($request->filled('followups_type')) {
            $type = (string) $request->query('followups_type');
            $list = $this->reportService->getFollowupsListByType($viewer, $filters, $type, 60);
            return response()->json([
                'success' => true,
                'type' => $type,
                'items' => $list->map(fn ($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'company_name' => $l->company_name,
                    'phone' => $l->phone,
                    'assigned_user' => $l->assignedUser?->name,
                    'stage_name' => $l->status?->stage?->localizedName() ?: ($l->status?->name_ar ?: '—'),
                    'stage_color' => $l->status?->stage?->color ?: '#3b82f6',
                    'next_follow_up_at' => $l->next_follow_up_at?->format('Y-m-d H:i'),
                    'time_diff' => $l->next_follow_up_at ? $l->next_follow_up_at->diffForHumans() : '',
                    'lead_url' => url('/leads/' . $l->id),
                ]),
            ]);
        }

        if ($request->ajax() || $request->wantsJson() || $request->query('ajax') === '1') {
            return response()->json([
                'success' => true,
                'filters' => $filters,
                'date_range' => [
                    'from' => $dateRange['from']->format('Y-m-d'),
                    'to' => $dateRange['to']->format('Y-m-d'),
                    'preset' => $dateRange['preset'],
                ],
                'kpis' => $kpis,
                'pipeline_performance' => $pipelinePerformance,
                'followup_performance' => $followupPerformance,
                'attention' => $attention,
                'campaign_performance' => $campaignPerformance,
                'performance_trend' => $performanceTrend,
                'upcoming_followups' => $upcomingFollowups,
                'customer_distribution' => $customerDistribution,
                'pagination' => [
                    'current_page' => $performance->currentPage(),
                    'last_page' => $performance->lastPage(),
                    'total' => $performance->total(),
                    'per_page' => $performance->perPage(),
                ],
                'rows' => $performance->items(),
            ]);
        }

        return view('reports.employees.index', [
            'filters' => $filters,
            'dateRange' => $dateRange,
            'stages' => $stages,
            'conversionTargets' => $conversionTargets,
            'authorizedEmployees' => $authorizedEmployees,
            'authorizedGroups' => $authorizedGroups,
            'visibleCampaigns' => $visibleCampaigns,
            'kpis' => $kpis,
            'performance' => $performance,
            'pipelinePerformance' => $pipelinePerformance,
            'followupPerformance' => $followupPerformance,
            'attention' => $attention,
            'campaignPerformance' => $campaignPerformance,
            'performanceTrend' => $performanceTrend,
            'upcomingFollowups' => $upcomingFollowups,
            'customerDistribution' => $customerDistribution,
        ]);
    }

    /**
     * Detailed Employee Drilldown.
     */
    public function show(Request $request, User $user): View|JsonResponse
    {
        $viewer = $request->user();
        abort_unless(
            $viewer !== null && (
                $viewer->isSuperAdmin()
                || $viewer->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW)
                || $viewer->hasPermission(CrmPermission::REPORTS_VIEW)
            ),
            403,
            __('crm.unauthorized')
        );

        // Enforce employee visibility scope
        $authorizedEmployeeIds = $this->reportService->getAuthorizedEmployees($viewer)->pluck('id')->all();
        abort_unless(
            in_array($user->id, $authorizedEmployeeIds, true),
            403,
            __('crm.unauthorized')
        );

        $filters = $this->extractFilters($request);
        $dateRange = $this->reportService->resolveDateRange($filters);
        $stages = $this->reportService->getActivePipelineStages($viewer);
        $conversionTargets = $this->reportService->resolveConversionTargets($stages);

        $drilldown = $this->reportService->getEmployeeDrilldown(
            $user,
            $viewer,
            $filters,
            $dateRange,
            $stages,
            $conversionTargets
        );

        if ($request->ajax() || $request->wantsJson() || $request->query('ajax') === '1') {
            return response()->json([
                'success' => true,
                'drilldown' => $drilldown,
            ]);
        }

        return view('reports.employees.show', [
            'employee' => $user,
            'drilldown' => $drilldown,
            'filters' => $filters,
            'dateRange' => $dateRange,
            'stages' => $stages,
        ]);
    }

    /**
     * Export currently filtered employee performance table to CSV with UTF-8 BOM.
     */
    public function export(Request $request): StreamedResponse
    {
        $viewer = $request->user();
        abort_unless(
            $viewer !== null && (
                $viewer->isSuperAdmin()
                || $viewer->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW)
                || $viewer->hasPermission(CrmPermission::REPORTS_VIEW)
            ),
            403,
            __('crm.unauthorized')
        );

        $filters = $this->extractFilters($request);
        $dateRange = $this->reportService->resolveDateRange($filters);
        $stages = $this->reportService->getActivePipelineStages($viewer);
        $conversionTargets = $this->reportService->resolveConversionTargets($stages);

        // Fetch all matching rows up to 1000 for export
        $performance = $this->reportService->getPaginatedEmployeePerformance(
            $viewer,
            $filters,
            $dateRange,
            $stages,
            $conversionTargets,
            1000
        );

        $filename = 'employee-reports-' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($performance, $stages): void {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Arabic support in Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Dynamic header columns
            $csvHeaders = [
                __('crm.employee'),
                __('crm.team'),
                __('crm.assigned_leads'),
                __('crm.kpi_new_leads'),
                __('crm.kpi_total_followups'),
                __('crm.kpi_completed_followups'),
                __('crm.kpi_overdue_followups'),
                __('crm.kpi_without_followup'),
                __('crm.kpi_conversion_rate') . ' (%)',
            ];

            foreach ($stages as $stage) {
                $csvHeaders[] = $stage->localizedName();
            }

            fputcsv($handle, $csvHeaders);

            foreach ($performance->items() as $row) {
                $csvRow = [
                    $row['name'],
                    implode(', ', $row['groups']),
                    $row['assigned_leads'],
                    $row['new_leads'],
                    $row['total_followups'],
                    $row['completed_followups'],
                    $row['overdue_followups'],
                    $row['without_followup'],
                    $row['conversion_rate'] . '%',
                ];

                foreach ($stages as $stage) {
                    $csvRow[] = $row['stage_counts'][$stage->id] ?? 0;
                }

                fputcsv($handle, $csvRow);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractFilters(Request $request): array
    {
        return [
            'preset' => $request->query('preset', 'this_month'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'user_id' => $request->query('user_id'),
            'group_id' => $request->query('group_id'),
            'campaign_id' => $request->query('campaign_id'),
            'stage_id' => $request->query('stage_id'),
            'compare' => $request->boolean('compare'),
            'search' => $request->query('search'),
            'sort' => $request->query('sort', 'name'),
            'direction' => $request->query('direction', 'asc'),
            'per_page' => $request->query('per_page', 15),
            'stuck_threshold_days' => $request->query('stuck_threshold_days', 7),
        ];
    }
}
