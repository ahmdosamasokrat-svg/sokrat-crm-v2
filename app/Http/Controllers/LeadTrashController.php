<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\LeadTrashService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LeadTrashController extends Controller
{
    public function index(Request $request, LeadTrashService $trashService): View
    {
        CrmDatabaseGuard::ensureConnected();

        $actor = $request->user();
        abort_unless($actor !== null, 401);
        abort_unless($actor->hasPermission(CrmPermission::LEADS_TRASH_VIEW), 403, 'غير مصرح لك بعرض سلة المهملات.');

        $filters = [
            'search' => $request->query('search'),
            'stage_id' => $request->query('stage_id'),
            'deleted_by' => $request->query('deleted_by'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $leads = $trashService->getTrashQuery($actor, $filters)
            ->paginate(20)
            ->withQueryString();

        $trashCount = $trashService->getTrashCount($actor);

        // Stages for restore dropdown and filter
        $activeStages = PipelineStage::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        // All stages including deleted for filtering by source stage
        $allStages = PipelineStage::withTrashed()
            ->orderBy('name_ar')
            ->get();

        // Users who deleted leads (for filter)
        $deleterUserIds = Lead::onlyTrashed()
            ->whereNotNull('deleted_by_user_id')
            ->distinct()
            ->pluck('deleted_by_user_id');

        $deleters = User::query()
            ->whereIn('id', $deleterUserIds)
            ->select(['id', 'name'])
            ->get();

        return view('leads.trash.index', [
            'leads' => $leads,
            'trashCount' => $trashCount,
            'activeStages' => $activeStages,
            'allStages' => $allStages,
            'deleters' => $deleters,
            'filters' => $filters,
        ]);
    }

    public function restore(
        Request $request,
        string $lead,
        LeadTrashService $trashService
    ): RedirectResponse|JsonResponse {
        CrmDatabaseGuard::ensureConnected();

        $actor = $request->user();
        abort_unless($actor !== null, 401);
        abort_unless($actor->hasPermission(CrmPermission::LEADS_TRASH_RESTORE), 403, 'غير مصرح لك باستعادة العملاء.');

        $leadRecord = Lead::onlyTrashed()->findOrFail((int) $lead);
        abort_unless($leadRecord->isAccessibleTo($actor), 403, 'غير مصرح لك بالوصول لهذا العميل.');

        $destinationStageId = $request->filled('destination_stage_id')
            ? (int) $request->input('destination_stage_id')
            : null;

        $destinationStatusId = $request->filled('destination_status_id')
            ? (int) $request->input('destination_status_id')
            : null;

        $restoredLead = $trashService->restoreLead(
            $leadRecord,
            $actor,
            $destinationStageId,
            $destinationStatusId
        );

        $msg = "تمت استعادة العميل '{$restoredLead->name}' بنجاح.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'lead_id' => $restoredLead->id,
            ]);
        }

        return redirect()
            ->route('v2.leads.trash.index')
            ->with('success', $msg);
    }

    public function forceDelete(
        Request $request,
        string $lead,
        LeadTrashService $trashService
    ): RedirectResponse|JsonResponse {
        CrmDatabaseGuard::ensureConnected();

        $actor = $request->user();
        abort_unless($actor !== null, 401);
        abort_unless($actor->hasPermission(CrmPermission::LEADS_TRASH_FORCE_DELETE), 403, 'غير مصرح لك بالحذف النهائي للعملاء.');

        $leadRecord = Lead::onlyTrashed()->findOrFail((int) $lead);
        abort_unless($leadRecord->isAccessibleTo($actor), 403, 'غير مصرح لك بالوصول لهذا العميل.');

        $leadName = $leadRecord->name;
        $trashService->forceDeleteLead($leadRecord, $actor);

        $msg = "تم الحذف النهائي للعميل '{$leadName}' بنجاح.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()
            ->route('v2.leads.trash.index')
            ->with('success', $msg);
    }

    public function bulkRestore(
        Request $request,
        LeadTrashService $trashService
    ): RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $actor = $request->user();
        abort_unless($actor !== null, 401);
        abort_unless($actor->hasPermission(CrmPermission::LEADS_TRASH_RESTORE), 403, 'غير مصرح لك باستعادة العملاء.');

        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:500'],
            'lead_ids.*' => ['required', 'integer'],
            'destination_stage_id' => ['nullable', 'integer', Rule::exists('pipeline_stages', 'id')->whereNull('deleted_at')],
        ]);

        $destinationStageId = ! empty($validated['destination_stage_id']) ? (int) $validated['destination_stage_id'] : null;

        $leads = Lead::onlyTrashed()
            ->whereIn('id', $validated['lead_ids'])
            ->accessibleTo($actor)
            ->get();

        $restoredCount = 0;
        foreach ($leads as $leadRecord) {
            $trashService->restoreLead($leadRecord, $actor, $destinationStageId);
            $restoredCount++;
        }

        return redirect()
            ->route('v2.leads.trash.index')
            ->with('success', "تمت استعادة {$restoredCount} عميل بنجاح.");
    }

    public function bulkForceDelete(
        Request $request,
        LeadTrashService $trashService
    ): RedirectResponse {
        CrmDatabaseGuard::ensureConnected();

        $actor = $request->user();
        abort_unless($actor !== null, 401);
        abort_unless($actor->hasPermission(CrmPermission::LEADS_TRASH_FORCE_DELETE), 403, 'غير مصرح لك بالحذف النهائي للعملاء.');

        $validated = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1', 'max:500'],
            'lead_ids.*' => ['required', 'integer'],
        ]);

        $leads = Lead::onlyTrashed()
            ->whereIn('id', $validated['lead_ids'])
            ->accessibleTo($actor)
            ->get();

        $deletedCount = 0;
        foreach ($leads as $leadRecord) {
            $trashService->forceDeleteLead($leadRecord, $actor);
            $deletedCount++;
        }

        return redirect()
            ->route('v2.leads.trash.index')
            ->with('success', "تم الحذف النهائي لـ {$deletedCount} عميل بنجاح.");
    }
}
