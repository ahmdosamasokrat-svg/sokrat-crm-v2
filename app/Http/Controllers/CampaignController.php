<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 150),
            'state' => (string) $request->query('state', ''),
            'user_id' => $request->integer('user_id'),
        ];

        if (! in_array($filters['state'], ['', 'active', 'upcoming', 'ended'], true)) {
            $filters['state'] = '';
        }

        $query = Campaign::query()
            ->with([
                'creator:id,name',
                'users:id,name',
            ])
            ->withCount([
                'leads as user_leads_count' => static function (Builder $leadQuery) use ($user): void {
                    $leadQuery->where('leads.assigned_user_id', $user->id)
                        ->accessibleTo($user)
                        ->select(DB::raw('count(distinct leads.id)'));
                },
                'leads as total_leads_count' => static function (Builder $leadQuery) use ($user): void {
                    $leadQuery->accessibleTo($user)
                        ->select(DB::raw('count(distinct leads.id)'));
                },
            ]);
        if (! $user->isSuperAdmin()) {
            $query->where(function ($campaignQuery) use ($user): void {
                $campaignQuery->whereHas(
                    'users',
                    static fn ($userQuery) => $userQuery->whereKey($user->id),
                );

                if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)) {
                    $campaignQuery->orWhere(
                        'created_by_user_id',
                        $user->id,
                    );
                }
            });
        }

        if ($filters['q'] !== '') {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        match ($filters['state']) {
            'active' => $query
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()),
            'upcoming' => $query->where('starts_at', '>', now()),
            'ended' => $query->where('ends_at', '<', now()),
            default => null,
        };

        if ($filters['user_id'] > 0) {
            $query->whereHas(
                'users',
                static fn ($userQuery) => $userQuery->whereKey(
                    $filters['user_id'],
                ),
            );
        }

        $filterUsers = User::query()
            ->where('is_active', true)
            ->whereHas('campaigns', function ($campaignQuery) use ($user): void {
                if (! $user->isSuperAdmin()) {
                    $campaignQuery->where(function ($visibleQuery) use ($user): void {
                        $visibleQuery->whereHas(
                            'users',
                            static fn ($memberQuery) => $memberQuery->whereKey($user->id),
                        );

                        if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)) {
                            $visibleQuery->orWhere('created_by_user_id', $user->id);
                        }
                    });
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $campaigns = $query
            ->orderByDesc('starts_at')
            ->paginate(20);

        $campaigns->getCollection()->each(
            function (Campaign $campaign) use ($user): void {
                $canManage = $this->canManageCampaign($user, $campaign);
                $campaign->setAttribute('can_manage', $canManage);
                $count = $canManage
                    ? (int) ($campaign->total_leads_count ?? $campaign->user_leads_count ?? 0)
                    : (int) ($campaign->user_leads_count ?? 0);
                $campaign->setAttribute('leads_count', $count);
            },
        );
        return view('campaigns.index', compact(
            'campaigns',
            'filters',
            'filterUsers',
        ));
    }

    public function create(): View
    {
        $users = User::query()
            ->where('is_active', true)
            ->with('groups:id,name')
            ->orderBy('name')
            ->get();

        return view('campaigns.create', compact('users'));
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = $request->file('image')?->store(
            'campaign-images',
            'public',
        );

        if ($request->hasFile('image') && ! $imagePath) {
            throw new \RuntimeException('Campaign image could not be stored.');
        }

        try {
            $campaign = DB::transaction(function () use (
                $imagePath,
                $request,
                $validated,
            ): Campaign {
                $campaign = Campaign::query()->create([
                    'name' => trim($validated['name']),
                    'image_path' => $imagePath,
                    'cost' => $validated['cost'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                    'created_by_user_id' => $request->user()->id,
                ]);

                $campaign->users()->sync($validated['user_ids']);

                return $campaign;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('v2.campaigns.index')
            ->with(
                'success',
                'تم إنشاء حملة "'.$campaign->name.'" وتعيين المستخدمين بنجاح.',
            );
    }

    public function edit(Request $request, Campaign $campaign): View
    {
        $this->ensureCanManageCampaign($request->user(), $campaign);

        $campaign->load('users:id');
        $users = User::query()
            ->where('is_active', true)
            ->with('groups:id,name')
            ->orderBy('name')
            ->get();

        return view('campaigns.create', [
            'campaign' => $campaign,
            'users' => $users,
            'editing' => true,
        ]);
    }

    public function update(
        StoreCampaignRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->ensureCanManageCampaign($request->user(), $campaign);
        $validated = $request->validated();
        $currentImagePath = $campaign->image_path;
        $newImagePath = $request->file('image')?->store(
            'campaign-images',
            'public',
        );

        if ($request->hasFile('image') && ! $newImagePath) {
            throw new \RuntimeException('Campaign image could not be stored.');
        }

        try {
            DB::transaction(function () use (
                $campaign,
                $newImagePath,
                $validated,
            ): void {
                $campaign->update([
                    'name' => trim($validated['name']),
                    'image_path' => $newImagePath ?? $campaign->image_path,
                    'cost' => $validated['cost'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                ]);
                $campaign->users()->sync($validated['user_ids']);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }

        if ($newImagePath !== null && $currentImagePath !== null) {
            Storage::disk('public')->delete($currentImagePath);
        }

        return redirect()
            ->route('v2.campaigns.show', $campaign)
            ->with('success', 'تم تحديث بيانات الحملة بنجاح.');
    }

    public function destroy(
        Request $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->ensureCanManageCampaign($request->user(), $campaign);
        $campaignName = $campaign->name;
        $imagePath = $campaign->image_path;

        $campaign->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('v2.campaigns.index')
            ->with('success', 'تم حذف حملة "'.$campaignName.'" بنجاح.');
    }

    public function show(Request $request, Campaign $campaign): View
    {
        $user = $request->user();
        $this->ensureCanViewCampaign($user, $campaign);

        $campaign->load([
            'creator:id,name',
            'users' => static fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name'),
        ]);

        $canManage = $this->canManageCampaign($user, $campaign);
        $allowedStatusIds = LeadStatus::query()
            ->visibleTo($user)
            ->select('lead_statuses.id');
        $restrictStages = static function ($query) use ($user, $allowedStatusIds): void {
            if ($user->hasRestrictedPipelineStageAccess()) {
                $query->whereIn('leads.lead_status_id', clone $allowedStatusIds);
            }
        };

        $totalCampaignLeadsQuery = $campaign->leads()->distinct();
        $restrictStages($totalCampaignLeadsQuery);
        $totalCampaignLeads = $totalCampaignLeadsQuery->count('leads.id');

        $pipelineStages = PipelineStage::query()
            ->visibleTo($user)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->load('statuses:id,pipeline_stage_id,name_ar,code');

        $statusParam = trim((string) $request->query('status', ''));
        $stageParam = trim((string) $request->query('stage', ''));

        $selectedStage = $stageParam !== ''
            ? ($pipelineStages->firstWhere('code', $stageParam) ?? $pipelineStages->firstWhere('id', (int) $stageParam))
            : null;

        $selectedStatus = null;
        if ($statusParam !== '') {
            $allStatuses = $pipelineStages->pluck('statuses')->flatten();
            $selectedStatus = $allStatuses->firstWhere('code', $statusParam) ?? $allStatuses->firstWhere('id', (int) $statusParam);
        }

        // 1. Overall assignment counts for the entire campaign (excluding soft-deleted leads)
        $overallAssignmentCounts = DB::table('campaign_lead')
            ->join('leads', 'leads.id', '=', 'campaign_lead.lead_id')
            ->where('campaign_lead.campaign_id', $campaign->id)
            ->whereNull('leads.deleted_at');
        $restrictStages($overallAssignmentCounts);
        $overallAssignmentCounts = $overallAssignmentCounts
            ->selectRaw('leads.assigned_user_id, COUNT(DISTINCT leads.id) as lead_count')
            ->groupBy('leads.assigned_user_id')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                $row->assigned_user_id === null
                    ? 'unassigned'
                    : (string) $row->assigned_user_id => (int) $row->lead_count,
            ]);

        // 2. Filtered context query and counts (reflecting active stage/status filter)
        $contextQuery = DB::table('campaign_lead')
            ->join('leads', 'leads.id', '=', 'campaign_lead.lead_id')
            ->where('campaign_lead.campaign_id', $campaign->id)
            ->whereNull('leads.deleted_at');
        $restrictStages($contextQuery);

        if ($selectedStage !== null) {
            $stageStatusIds = $selectedStage->statuses->pluck('id')->all();
            $contextQuery->whereIn('leads.lead_status_id', $stageStatusIds);
        } elseif ($selectedStatus !== null) {
            $contextQuery->where('leads.lead_status_id', $selectedStatus->id);
        }

        $contextAssignmentCounts = (clone $contextQuery)
            ->selectRaw('leads.assigned_user_id, COUNT(DISTINCT leads.id) as lead_count')
            ->groupBy('leads.assigned_user_id')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                $row->assigned_user_id === null
                    ? 'unassigned'
                    : (string) $row->assigned_user_id => (int) $row->lead_count,
            ]);

        $contextTotal = (clone $contextQuery)->distinct()->count('leads.id');
        $assignmentCounts = $contextAssignmentCounts;

        $filterUsers = collect([$user]);
        $selectedAssignee = $user;
        $showUnassigned = false;
        $showAllAssignees = false;

        if ($canManage) {
            $candidateUserIds = $overallAssignmentCounts->keys()
                ->filter(fn ($k) => $k !== 'unassigned')
                ->map(fn ($id) => (int) $id)
                ->push((int) $user->id)
                ->unique()
                ->all();

            $filterUsers = User::query()
                ->whereIn('id', $candidateUserIds)
                ->where('is_active', true)
                ->with('groups:id,name')
                ->orderBy('name')
                ->get();

            $requestedAssignee = (string) $request->query(
                'assigned_user_id',
                'all',
            );

            if ($requestedAssignee === 'all' || $requestedAssignee === '') {
                $selectedAssignee = null;
                $showAllAssignees = true;
                $showUnassigned = false;
            } elseif ($requestedAssignee === 'unassigned') {
                $selectedAssignee = null;
                $showAllAssignees = false;
                $showUnassigned = true;
            } elseif (ctype_digit($requestedAssignee)) {
                $targetId = (int) $requestedAssignee;
                $selectedAssignee = User::query()->where('is_active', true)->find($targetId);
                if ($selectedAssignee !== null) {
                    $showAllAssignees = false;
                    $showUnassigned = false;
                    if (! $filterUsers->contains('id', $selectedAssignee->id)) {
                        $filterUsers->push($selectedAssignee);
                    }
                } else {
                    $selectedAssignee = null;
                    $showAllAssignees = true;
                    $showUnassigned = false;
                }
            } else {
                $selectedAssignee = null;
                $showAllAssignees = true;
                $showUnassigned = false;
            }
        }

        $baseCampaignLeadsQuery = DB::table('campaign_lead')
            ->join('leads', 'leads.id', '=', 'campaign_lead.lead_id')
            ->where('campaign_lead.campaign_id', $campaign->id)
            ->whereNull('leads.deleted_at');
        $restrictStages($baseCampaignLeadsQuery);

        if ($showUnassigned) {
            $baseCampaignLeadsQuery->where(function ($q) {
                $q->whereNull('leads.assigned_user_id')
                    ->where(function ($eq) {
                        $eq->whereNull('leads.assigned_employee')
                            ->orWhere('leads.assigned_employee', '');
                    });
            });
        } elseif (! $showAllAssignees && $selectedAssignee !== null) {
            $baseCampaignLeadsQuery->where(function ($q) use ($selectedAssignee) {
                $q->where('leads.assigned_user_id', $selectedAssignee->id)
                    ->orWhere(function ($sq) use ($selectedAssignee) {
                        $sq->whereNull('leads.assigned_user_id')
                            ->where('leads.assigned_employee', $selectedAssignee->name);
                    });
            });
        }

        $stageLeadCounts = (clone $baseCampaignLeadsQuery)
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(DISTINCT leads.id) as aggregate')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('aggregate', 'pipeline_stage_id')
            ->all();

        $stageDistributionTotal = 0;
        foreach ($pipelineStages as $stage) {
            $stageCount = (int) ($stageLeadCounts[$stage->id] ?? 0);
            $stage->campaign_leads_count = $stageCount;
            $stageDistributionTotal += $stageCount;
        }

        $operationalCampaignLeads = $stageDistributionTotal > 0
            ? $stageDistributionTotal
            : (int) (clone $baseCampaignLeadsQuery)->distinct()->count('leads.id');

        $statuses = LeadStatus::query()
            ->visibleTo($user)
            ->withCount([
                'leads as campaign_leads_count' => static function (
                    $query,
                ) use (
                    $campaign,
                    $selectedAssignee,
                    $showUnassigned,
                    $showAllAssignees,
                ): void {
                    $query->whereHas(
                        'campaigns',
                        static fn ($campaignQuery) => $campaignQuery
                            ->whereKey($campaign->id),
                    );

                    if ($showUnassigned) {
                        $query->whereNull('assigned_user_id');
                    } elseif (! $showAllAssignees && $selectedAssignee !== null) {
                        $query->where(
                            'assigned_user_id',
                            $selectedAssignee->id,
                        );
                    }
                },
            ])
            ->with('stage:id,name_ar')
            ->orderBy('position')
            ->get(['id', 'pipeline_stage_id', 'code', 'name_ar', 'color']);
        $leadsQuery = $campaign->leads()
            ->with([
                'status.stage:id,name_ar',
                'assignedUser:id,name',
            ])
            ->orderByDesc('leads.id');
        $restrictStages($leadsQuery);

        if ($showUnassigned) {
            $leadsQuery->where(function ($q) {
                $q->whereNull('leads.assigned_user_id')
                    ->where(function ($eq) {
                        $eq->whereNull('leads.assigned_employee')
                            ->orWhere('leads.assigned_employee', '');
                    });
            });
        } elseif (! $showAllAssignees && $selectedAssignee !== null) {
            $leadsQuery->where(function ($q) use ($selectedAssignee) {
                $q->where('leads.assigned_user_id', $selectedAssignee->id)
                    ->orWhere(function ($sq) use ($selectedAssignee) {
                        $sq->whereNull('leads.assigned_user_id')
                            ->where('leads.assigned_employee', $selectedAssignee->name);
                    });
            });
        }
        if ($selectedStage !== null) {
            $leadsQuery->whereHas('status', function (Builder $sq) use ($selectedStage): void {
                $sq->where('pipeline_stage_id', $selectedStage->id);
            });
        } elseif ($selectedStatus !== null) {
            $leadsQuery->where('lead_status_id', $selectedStatus->id);
        }

        $searchQuery = trim((string) $request->query('q', ''));
        if ($searchQuery !== '') {
            $search = '%' . $searchQuery . '%';
            $leadsQuery->where(function ($q) use ($search) {
                $q->where('leads.name', 'like', $search)
                    ->orWhere('leads.company_name', 'like', $search)
                    ->orWhere('leads.phone', 'like', $search)
                    ->orWhere('leads.email', 'like', $search);
            });
        }

        $leads = $leadsQuery
            ->paginate(30)
            ->withQueryString();

        $assignableUsers = $canManage
            ? LeadAssignment::assignableUsers($user)->loadMissing('groups:id,name')
            : collect();

        $totalFilteredLeads = (clone $contextQuery)->distinct()->count('leads.id');
        return view('campaigns.show', compact(
            'campaign',
            'totalCampaignLeads',
            'operationalCampaignLeads',
            'totalFilteredLeads',
            'leads',
            'canManage',
            'assignableUsers',
            'filterUsers',
            'selectedAssignee',
            'showUnassigned',
            'showAllAssignees',
            'assignmentCounts',
            'overallAssignmentCounts',
            'contextTotal',
            'pipelineStages',
            'selectedStage',
            'statuses',
            'selectedStatus',
        ));
    }

    public function assignLeads(
        Request $request,
        Campaign $campaign,
    ): RedirectResponse {
        $actor = $request->user();
        $this->ensureCanManageCampaign($actor, $campaign);

        $validated = $request->validate(
            [
                'lead_ids' => ['required', 'array', 'min:1', 'max:1000'],
                'lead_ids.*' => [
                    'integer',
                    'distinct',
                    Rule::exists('campaign_lead', 'lead_id')
                        ->where('campaign_id', $campaign->id),
                ],
                'target_user_id' => [
                    'required',
                    'integer',
                    Rule::exists('users', 'id')->where('is_active', true),
                ],
            ],
            [
                'lead_ids.required' => 'اختر عميلًا واحدًا على الأقل.',
                'target_user_id.required' => 'اختر المستخدم المسؤول.',
            ],
        );

        $target = User::query()->findOrFail(
            (int) $validated['target_user_id'],
        );

        if (! LeadAssignment::canAssignTo($actor, $target)) {
            throw ValidationException::withMessages([
                'target_user_id' => 'لا تملك صلاحية إسناد العملاء إلى هذا المستخدم.',
            ]);
        }

        $leadIds = array_map('intval', $validated['lead_ids']);

        $assignableLeadIds = Lead::query()
            ->whereIn('id', $leadIds)
            ->whereHas(
                'campaigns',
                static fn ($query) => $query->whereKey($campaign->id),
            )
            ->when(
                $actor->hasRestrictedPipelineStageAccess(),
                static fn (Builder $query): Builder => $query->whereHas(
                    'status',
                    static fn (Builder $statuses): Builder => $statuses->visibleTo($actor),
                ),
            )
            ->when(
                $target->hasRestrictedPipelineStageAccess(),
                static fn (Builder $query): Builder => $query->whereHas(
                    'status',
                    static fn (Builder $statuses): Builder => $statuses->visibleTo($target),
                ),
            )
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        if (count($assignableLeadIds) !== count($leadIds)) {
            throw ValidationException::withMessages([
                'lead_ids' => 'بعض العملاء في مراحل غير مسموحة لك أو للمستخدم المختار.',
            ]);
        }

        $updated = DB::transaction(function () use (
            $campaign,
            $assignableLeadIds,
            $target,
        ): int {
            $campaign->users()->syncWithoutDetaching([$target->id]);
            return Lead::query()
                ->whereIn('id', $assignableLeadIds)
                ->whereHas(
                    'campaigns',
                    static fn ($query) => $query->whereKey($campaign->id),
                )
                ->update([
                    'assigned_user_id' => $target->id,
                    'assigned_employee' => $target->name,
                ]);
        });

        return back()->with(
            'success',
            'تم إسناد '.number_format($updated).' عميل إلى '.$target->name.'.',
        );
    }

    private function ensureCanViewCampaign(
        User $user,
        Campaign $campaign,
    ): void {
        abort_unless(
            $this->canManageCampaign($user, $campaign)
            || $campaign->users()->whereKey($user->id)->exists(),
            403,
        );
    }

    private function ensureCanManageCampaign(
        User $user,
        Campaign $campaign,
    ): void {
        abort_unless($this->canManageCampaign($user, $campaign), 403);
    }

    private function canManageCampaign(
        User $user,
        Campaign $campaign,
    ): bool {
        return $user->isSuperAdmin()
            || (
                (int) $campaign->created_by_user_id === (int) $user->id
                && $user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
            );
    }
}
