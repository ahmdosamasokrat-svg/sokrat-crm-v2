<?php

namespace App\Http\Controllers;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use App\Support\CrmDatabaseGuard;
use App\Support\FollowupCustomerFieldSchema;
use App\Support\StageFieldSchema;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeadFollowupController extends Controller
{
    public function index(
        Request $request,
        string $lead
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $leadRecord = Lead::query()
            ->with([
                'status.stage',
                'assignedUser:id,name',
                'campaigns:id,name',
            ])
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('viewFollowups', $leadRecord);

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        /*
         * Kanban drag/drop chooses only the
         * proposed destination status.
         * This GET request never changes the lead.
         */
        $requestedStatusId = (int)
            $request->query(
                'target_status_id',
                0
            );

        $defaultStatusId =
            $statuses->contains(
                static fn (
                    LeadStatus $status
                ): bool => (int) $status->id
                    === $requestedStatusId
            )
                ? $requestedStatusId
                : (int)
                    $leadRecord
                        ->lead_status_id;

        $statusGroups = $statuses->groupBy(
            static fn (
                LeadStatus $status
            ): string => trim(
                (string)
                    $status->stage?->name_ar
            ) !== ''
                    ? (string)
                        $status->stage?->name_ar
                    : 'بدون مرحلة'
        );

        $communicationTypes =
            $this->communicationTypes();

        $requestedType = trim(
            (string)
                $request->query(
                    'channel',
                    'other'
                )
        );

        $defaultCommunicationType =
            array_key_exists(
                $requestedType,
                $communicationTypes
            )
                ? $requestedType
                : 'other';

        $followups = LeadFollowup::query()
            ->with([
                'fromStatus.stage',
                'toStatus.stage',
                'user:id,name',
            ])
            ->where(
                'lead_id',
                $leadRecord->id
            )
            ->orderByDesc('followed_up_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $currentEmployee =
            $this->currentEmployeeName();

        $callPhone = $this->callPhone(
            (string) $leadRecord->phone
        );

        $quotationPath = trim(
            (string)
                $leadRecord->quotation_file_path
        );

        $hasQuotationFile = (
            $quotationPath !== ''
            && Storage::disk('local')->exists(
                $quotationPath
            )
        );

        $quotationFileName =
            $hasQuotationFile
                ? basename($quotationPath)
                : null;

        $quotationFileHelpText =
            $hasQuotationFile
                ? 'يوجد ملف عرض سعر حالي. '
                    .'اختر ملفًا جديدًا فقط '
                    .'لاستبداله.'
                : 'الملفات المدعومة: PDF, Word, '
                    .'Excel والصور. الحد الأقصى 2MB.';
        $totalLeads = Lead::query()
            ->accessibleTo($request->user())
            ->count();
        $actor = $request->user();
        $manageableCampaigns = Campaign::query()
            ->with([
                'users' => static fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
            ])
            ->when(
                ! $actor->isSuperAdmin(),
                static fn ($query) => $actor->hasPermission(
                    CrmPermission::CAMPAIGNS_CREATE,
                )
                    ? $query->where('created_by_user_id', $actor->id)
                    : $query->whereRaw('1 = 0'),
            )
            ->orderBy('name')
            ->get()
            ->each(function (Campaign $campaign) use ($actor): void {
                $campaign->setRelation(
                    'users',
                    $campaign->users
                        ->filter(
                            static fn (User $target): bool => LeadAssignment::canAssignTo(
                                $actor,
                                $target,
                            ),
                        )
                        ->values(),
                );
            });
        $campaignAssignees = $manageableCampaigns
            ->flatMap->users
            ->unique('id')
            ->sortBy('name')
            ->values();
        $currentCampaign = $leadRecord->campaigns->first(
            static fn (Campaign $campaign): bool => $manageableCampaigns
                ->contains('id', $campaign->id),
        );

        $activeStages = PipelineStage::query()
            ->where('is_active', true)
            ->with(['activeFields', 'statuses'])
            ->orderBy('position')
            ->get();
        $customerFields = FollowupCustomerFieldSchema::fields();

        return view(
            'leads.followups.index',
            [
                'lead' => $leadRecord,
                'statuses' => $statuses,
                'statusGroups' => $statusGroups,
                'communicationTypes' => $communicationTypes,
                'defaultCommunicationType' => $defaultCommunicationType,
                'followups' => $followups,
                'currentEmployee' => $currentEmployee,
                'callPhone' => $callPhone,
                'hasQuotationFile' => $hasQuotationFile,
                'quotationFileName' => $quotationFileName,
                'quotationFileHelpText' => $quotationFileHelpText,
                'defaultStatusId' => $defaultStatusId,
                'totalLeads' => $totalLeads,
                'manageableCampaigns' => $manageableCampaigns,
                'campaignAssignees' => $campaignAssignees,
                'currentCampaign' => $currentCampaign,
                'activeStages' => $activeStages,
                'customerFields' => $customerFields,
                'customerFieldValues' => FollowupCustomerFieldSchema::currentValues($leadRecord, $customerFields),
            ]
        );
    }

    public function store(
        Request $request,
        string $lead
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $communicationTypes =
            $this->communicationTypes();

        $leadRecord = Lead::query()
            ->findOrFail(
                (int) $lead
            );
        Gate::authorize('createFollowup', $leadRecord);
        $campaign = null;
        $targetUser = null;

        $currentLeadCampaignId = (int) ($leadRecord->campaigns()->value('campaigns.id') ?? 0);
        $submittedCampaignId = $request->integer('campaign_id');
        $isChangingCampaign = $request->filled('campaign_id') && $submittedCampaignId > 0 && $submittedCampaignId !== $currentLeadCampaignId;

        if ($isChangingCampaign) {
            $campaign = Campaign::query()->findOrFail($submittedCampaignId);
            $actor = $request->user();

            abort_unless(
                $actor->isSuperAdmin()
                || (
                    (int) $campaign->created_by_user_id === (int) $actor->id
                    && $actor->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
                ),
                403,
            );

            if ($request->filled('assigned_user_id')) {
                $targetUser = User::query()->find(
                    $request->integer('assigned_user_id'),
                );

                if ($targetUser === null || ! $campaign->users()->whereKey($targetUser->id)->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'assigned_user_id' => 'الموظف المختار غير مسجل في هذه الحملة.',
                    ]);
                }

                if (! LeadAssignment::canAssignTo($actor, $targetUser)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'assigned_user_id' => 'ليس لديك صلاحية إسناد العميل إلى هذا الموظف.',
                    ]);
                }
            }
        } elseif ($request->filled('assigned_user_id') && $request->integer('assigned_user_id') > 0 && (int) $leadRecord->assigned_user_id !== $request->integer('assigned_user_id')) {
            $targetUser = User::query()->find(
                $request->integer('assigned_user_id'),
            );
            if ($targetUser && ! LeadAssignment::canAssignTo($request->user(), $targetUser)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'assigned_user_id' => 'ليس لديك صلاحية إسناد العميل إلى هذا الموظف.',
                ]);
            }
        }

        $statusInput = $request->validate(
            [
                'lead_status_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],
            ],
            [
                'lead_status_id.required' => 'اختر الحالة التي تمت عليها المتابعة.',
                'lead_status_id.exists' => 'الحالة المختارة غير موجودة.',
            ]
        );

        $status = LeadStatus::query()
            ->findOrFail(
                (int)
                    $statusInput[
                        'lead_status_id'
                    ]
            );

        $stageFields = $status->stage ? StageFieldSchema::getFieldsForStage($status->stage, true) : collect();
        $stageFieldKeys = $stageFields->pluck('key')->all();

        $hasSolutionTypeField = $stageFields->contains(static fn ($f) => $f->key === 'solution_type' || $f->binding_target === 'solution_type');
        $hasQuotationFileField = $stageFields->contains(static fn ($f) => $f->key === 'quotation_file_path' || $f->binding_target === 'quotation_file_path');
        $hasReasonField = $stageFields->contains(static fn ($f) => $f->key === 'reason' || $f->binding_target === 'disinterest_reason');

        // A transition only requires quotation fields if the target stage explicitly defines them!
        $isQuotationStage = $hasSolutionTypeField || $hasQuotationFileField;

        $currentQuotationPath = trim(
            (string)
                $leadRecord->quotation_file_path
        );

        $hasCurrentQuotationFile = (
            $currentQuotationPath !== ''
            && Storage::disk('local')->exists(
                $currentQuotationPath
            )
        );
        $rawStageInputs = array_replace_recursive(
            (array) $request->input('stage_fields', []),
            (array) $request->file('stage_fields', [])
        );

        // Normalize solution_type from stage_fields if present
        if (! $request->filled('solution_type') && ! empty($rawStageInputs['solution_type'])) {
            $request->merge(['solution_type' => $rawStageInputs['solution_type']]);
        }

        $uploadedQuotationFile = $request->file('quotation_file')
            ?? $request->file('stage_fields.quotation_file_path')
            ?? $request->file('stage_fields.quotation_file');

        $hasQuotationUpload = $uploadedQuotationFile !== null && $uploadedQuotationFile->isValid();

        // Normalize lines_count from stage_fields (including q_cdljek or lines_count)
        if (! $request->filled('lines_count')) {
            $linesVal = $rawStageInputs['lines_count'] ?? ($rawStageInputs['q_cdljek'] ?? null);
            if ($linesVal !== null && $linesVal !== '') {
                $request->merge(['lines_count' => $linesVal]);
            }
        }

        // Normalize extensions and departments from stage_fields
        if (! $request->filled('extensions') && ! empty($rawStageInputs['extensions'])) {
            $request->merge(['extensions' => $rawStageInputs['extensions']]);
        }
        if (! $request->filled('departments') && ! empty($rawStageInputs['departments'])) {
            $request->merge(['departments' => $rawStageInputs['departments']]);
        }

        // Normalize disinterest_reason from stage_fields (including reason or disinterest_reason)
        if (! $request->filled('disinterest_reason')) {
            $reasonVal = $rawStageInputs['reason'] ?? ($rawStageInputs['disinterest_reason'] ?? null);
            if ($reasonVal !== null && $reasonVal !== '') {
                $request->merge(['disinterest_reason' => $reasonVal]);
            }
        }

        $solutionTypeInput = trim((string) $request->input('solution_type', ''));

        $hasLinesCountQuestion = in_array('lines_count', $stageFieldKeys, true) || in_array('q_cdljek', $stageFieldKeys, true);
        $hasExtensionsQuestion = in_array('extensions', $stageFieldKeys, true);
        $hasDepartmentsQuestion = in_array('departments', $stageFieldKeys, true);
        /*
         * CRM FOLLOWUP CONDITIONAL DATE V2 START
         *
         * A next follow-up date is mandatory
         * for all statuses except not_interested.
         */
        $followupStatusCode = (string)
            LeadStatus::query()
                ->whereKey(
                    (int)
                        $statusInput[
                            'lead_status_id'
                        ]
                )
                ->value('code');

        if ($followupStatusCode === '') {
            abort(422);
        }

        /*
         * CRM NEW EXECUTION NO FOLLOWUP V7
         *
         * No future appointment while the
         * destination status is:
         * new, not_interested or execution.
         */

        $hasStageScheduling = ! empty($rawStageInputs['callback_at'])
            || ($status->stage && StageFieldSchema::getFieldsForStage($status->stage, true)->contains(static fn ($f) => $f->key === 'callback_at' || $f->binding_target === 'next_follow_up_at'));

        $requiresNextFollowUp =
            ! $hasStageScheduling
            && ! in_array(
                $followupStatusCode,
                [
                    'new',
                    'not_interested',
                    'execution',
                ],
                true
            );

        /* CRM FOLLOWUP CONDITIONAL DATE V2 END */

        $validated = $request->validate(
            [
                'lead_status_id' => [
                    'required',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],

                'communication_type' => [
                    'required',
                    'string',
                    Rule::in(
                        array_keys(
                            $communicationTypes
                        )
                    ),
                ],

                'outcome' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'next_follow_up_at' => [
                    $requiresNextFollowUp
                        ? 'required'
                        : 'nullable',
                    'date',
                ],

                'disinterest_reason' => [
                    Rule::requiredIf(
                        $status->code === 'not_interested' && ! $hasReasonField
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'solution_type' => [
                    Rule::requiredIf(
                        $hasSolutionTypeField
                    ),
                    'nullable',
                    Rule::in([
                        'call_center',
                        'erp',
                    ]),
                ],

                'lines_count' => [
                    Rule::requiredIf(
                        $hasSolutionTypeField
                        && $hasLinesCountQuestion
                        && $solutionTypeInput === 'call_center'
                    ),
                    'nullable',
                    'integer',
                    'min:1',
                    'max:1000000',
                ],

                'extensions' => [
                    Rule::requiredIf(
                        $hasSolutionTypeField
                        && $hasExtensionsQuestion
                        && $solutionTypeInput === 'call_center'
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],

                'departments' => [
                    Rule::requiredIf(
                        $hasSolutionTypeField
                        && $hasDepartmentsQuestion
                        && $solutionTypeInput === 'erp'
                    ),
                    'nullable',
                    'string',
                    'max:5000',
                ],
                'quotation_file' => [
                    Rule::requiredIf(
                        $hasQuotationFileField
                        && ! $hasCurrentQuotationFile
                        && ! $hasQuotationUpload
                    ),
                    'nullable',
                    'file',
                    'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                    'max:10240',
                ],
                'campaign_id' => [
                    'nullable',
                    'integer',
                    'exists:campaigns,id',
                ],
                'assigned_user_id' => [
                    Rule::requiredIf($campaign !== null),
                    'nullable',
                    'integer',
                    'exists:users,id',
                ],
            ],
            [
                'lead_status_id.required' => 'اختر الحالة التي تمت عليها المتابعة.',
                'lead_status_id.exists' => 'الحالة المختارة غير موجودة.',

                'communication_type.required' => 'اختر نوع التواصل.',
                'communication_type.in' => 'نوع التواصل المختار غير صحيح.',
                'outcome.max' => 'نتيجة المتابعة لا يمكن أن '
                    .'تتجاوز 5000 حرف.',

                'next_follow_up_at.required' => 'حدد موعد المتابعة القادمة. '
                    .'الموعد اختياري فقط في حالة '
                    .'غير مهتم.',
                'next_follow_up_at.date_format' => 'موعد المتابعة القادمة غير صحيح.',

                'disinterest_reason.required' => 'سبب عدم الاهتمام مطلوب.',

                'solution_type.required' => 'نوع النظام مطلوب.',

                'lines_count.required' => 'عدد الخطوط مطلوب.',

                'extensions.required' => 'تفاصيل الملحقات مطلوبة.',

                'departments.required' => 'الأقسام المطلوبة مطلوبة.',

                'quotation_file.required' => 'ملف عرض السعر مطلوب.',

                'quotation_file.max' => 'الحد الأقصى لملف عرض '
                    .'السعر 2MB.',

                'quotation_file.mimes' => 'صيغة ملف عرض السعر '
                    .'غير مدعومة.',
            ]
        );
        $targetStage = $status->stage;
        if ($uploadedQuotationFile !== null) {
            $fileValidator = \Illuminate\Support\Facades\Validator::make(
                ['quotation_file' => $uploadedQuotationFile],
                ['quotation_file' => ['file', 'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg', 'max:10240']],
                [
                    'quotation_file.mimes' => 'صيغة ملف عرض السعر غير مدعومة.',
                    'quotation_file.max' => 'الحد الأقصى لملف عرض السعر 10MB.',
                ]
            );
            if ($fileValidator->fails()) {
                throw new ValidationException($fileValidator);
            }
        }

        $normalizedStageValues = [];
        if ($targetStage !== null) {
            $rawStageInputs = array_replace_recursive(
                (array) $request->input('stage_fields', []),
                (array) $request->file('stage_fields', [])
            );
            $stageKeys = \App\Support\StageFieldSchema::getFieldsForStage($targetStage, true)->pluck('key')->all();
            if (in_array('callback_at', $stageKeys, true) && empty($rawStageInputs['callback_at']) && $request->filled('next_follow_up_at')) {
                $rawStageInputs['callback_at'] = (string) $request->input('next_follow_up_at');
            }
            if (in_array('reason', $stageKeys, true) && empty($rawStageInputs['reason']) && $request->filled('disinterest_reason')) {
                $rawStageInputs['reason'] = (string) $request->input('disinterest_reason');
            }
            $normalizedStageValues = \App\Support\StageFieldSchema::validateAndExtract($targetStage, $rawStageInputs, $request->user());
        }
        $normalizedCustomerFields = FollowupCustomerFieldSchema::validateAndExtract($request->all());


        $nullableText = static function (
            mixed $value
        ): ?string {
            if (! is_string($value)) {
                return null;
            }

            $value = trim($value);

            return $value === ''
                ? null
                : $value;
        };

        $employeeName =
            $this->currentEmployeeName();
        $currentUserId = (int) $request->user()->id;

        $outcome = trim(
            (string) (
                $validated['outcome']
                ?? ''
            )
        );

        $communicationType =
            (string)
                $validated[
                    'communication_type'
                ];

        $nextFollowUpAt = null;

        if (
            isset(
                $validated[
                    'next_follow_up_at'
                ]
            )
            && trim(
                (string)
                    $validated[
                        'next_follow_up_at'
                    ]
            ) !== ''
        ) {
            try {
                $nextFollowUpAt = Carbon::parse(
                    (string) $validated['next_follow_up_at'],
                    (string) config('app.timezone', 'UTC')
                );
            } catch (\Throwable) {
                $nextFollowUpAt = Carbon::createFromFormat(
                    'Y-m-d\TH:i',
                    (string) $validated['next_follow_up_at'],
                    (string) config('app.timezone', 'UTC')
                );
            }
        }

        /*
         * CRM NEW EXECUTION NO FOLLOWUP V7
         * SERVER ENFORCEMENT
         */
        if (! $requiresNextFollowUp) {
            $nextFollowUpAt = null;
        }

        $newQuotationPath = null;
        $uploadedQuotationName = null;
        if (isset($normalizedStageValues['quotation_file_path']) && is_array($normalizedStageValues['quotation_file_path']) && ($normalizedStageValues['quotation_file_path']['type'] ?? '') === 'file') {
            $newQuotationPath = $normalizedStageValues['quotation_file_path']['path'];
            $uploadedQuotationName = $normalizedStageValues['quotation_file_path']['original_name'] ?? null;
        } elseif (
            $uploadedQuotationFile !== null
            && $uploadedQuotationFile->isValid()
        ) {
            $uploadedQuotationName =
                mb_substr(
                    trim(
                        (string)
                            $uploadedQuotationFile->getClientOriginalName()
                    ),
                    0,
                    255
                );

            $storedPath = $uploadedQuotationFile->store('crm-v2/quotation-files', 'local');

            if (! is_string($storedPath) || trim($storedPath) === '') {
                throw new \RuntimeException('Quotation file storage failed.');
            }

            $newQuotationPath = $storedPath;
            \App\Models\LeadDocument::query()->create([
                'lead_id' => $leadRecord->id,
                'pipeline_stage_id' => $status->pipeline_stage_id,
                'pipeline_stage_field_id' => null,
                'lead_status_history_id' => null,
                'category' => \App\Models\LeadDocument::CATEGORY_QUOTATION,
                'original_name' => $uploadedQuotationName ?: 'عرض سعر.pdf',
                'stored_name' => basename($newQuotationPath),
                'disk' => 'local',
                'path' => $newQuotationPath,
                'mime_type' => $uploadedQuotationFile->getClientMimeType() ?: 'application/pdf',
                'size' => (int) $uploadedQuotationFile->getSize(),
                'created_by_user_id' => $request->user()?->id,
            ]);
        }

        $stageData = [
            'disinterest_reason' => $status->code
                    === 'not_interested'
                        ? $nullableText(
                            $validated[
                                'disinterest_reason'
                            ] ?? null
                        )
                        : null,

            'solution_type' => $isQuotationStage
                    ? $nullableText(
                        $validated[
                            'solution_type'
                        ] ?? null
                    )
                    : null,

            'lines_count' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'call_center'
                && isset($validated['lines_count'])
                && is_numeric($validated['lines_count'])
            )
                ? (int) $validated['lines_count']
                : null,

            'extensions' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'call_center'
            )
                ? $nullableText(
                    $validated[
                        'extensions'
                    ] ?? null
                )
                : null,

            'departments' => (
                $isQuotationStage
                && (
                    $validated[
                        'solution_type'
                    ] ?? null
                ) === 'erp'
            )
                ? $nullableText(
                    $validated[
                        'departments'
                    ] ?? null
                )
                : null,

            'quotation_sent' => $isQuotationStage,

            'quotation_file_path' => $newQuotationPath
                    ?? (
                        $hasCurrentQuotationFile
                            ? $currentQuotationPath
                            : null
                    ),
        ];

        $fieldLabels = [
            'disinterest_reason' => 'سبب عدم الاهتمام',
            'solution_type' => 'نوع النظام',
            'lines_count' => 'عدد الخطوط',
            'extensions' => 'الملحقات',
            'departments' => 'الأقسام',
            'quotation_file_path' => 'ملف عرض السعر',
        ];

        $formatChangeValue =
            static function (
                string $field,
                mixed $value
            ): string {
                if (
                    $value === null
                    || $value === ''
                ) {
                    return '----';
                }

                if (
                    $field
                    === 'solution_type'
                ) {
                    return match (
                        (string) $value
                    ) {
                        'call_center' => 'Call Center',
                        'erp' => 'ERP',
                        default => (string) $value,
                    };
                }

                if (
                    $field
                    === 'quotation_file_path'
                ) {
                    return basename(
                        (string) $value
                    );
                }

                return trim(
                    (string) $value
                );
            };

        // Prepare legacy field changes before transition
        $fieldChanges = [];
        $customerFieldUpdate = FollowupCustomerFieldSchema::prepareUpdates(
            $leadRecord,
            $normalizedCustomerFields,
        );

        if ($campaign !== null && $targetUser !== null) {
            $oldCampaignName = $leadRecord->campaigns()->value('campaigns.name') ?? 'بدون حملة';
            $oldAssigneeName = $leadRecord->assignedUser?->name ?? $leadRecord->assigned_employee ?? 'غير مسند';

            if ($oldCampaignName !== $campaign->name) {
                $fieldChanges[] = [
                    'field' => 'campaign_id',
                    'label' => 'الحملة',
                    'old' => $oldCampaignName,
                    'new' => $campaign->name,
                ];
            }

            if ((int) $leadRecord->assigned_user_id !== (int) $targetUser->id) {
                $fieldChanges[] = [
                    'field' => 'assigned_user_id',
                    'label' => 'الموظف المسؤول',
                    'old' => $oldAssigneeName,
                    'new' => $targetUser->name,
                ];
            }
        }

        foreach ($fieldLabels as $field => $label) {
            $oldRaw = $leadRecord->getAttribute($field);
            $newRaw = $stageData[$field] ?? null;

            $oldComparable = $oldRaw === null ? null : (string) $oldRaw;
            $newComparable = $newRaw === null ? null : (string) $newRaw;

            if ($oldComparable === $newComparable) {
                continue;
            }

            $oldValue = $formatChangeValue($field, $oldRaw);
            $newValue = $formatChangeValue($field, $newRaw);

            if ($field === 'quotation_file_path' && $uploadedQuotationName !== null && $uploadedQuotationName !== '') {
                $newValue = $uploadedQuotationName;
            }

            $fieldChanges[] = [
                'field' => $field,
                'label' => $label,
                'old' => $oldValue,
                'new' => $newValue,
            ];
        }

        $allFieldChanges = array_merge($fieldChanges, $customerFieldUpdate['changes']);
        $legacyAttributes = array_merge($stageData, $customerFieldUpdate['attributes']);

        try {
            /** @var \App\Services\LeadTransitionService $transitionService */
            $transitionService = app(\App\Services\LeadTransitionService::class);
            $transitionResult = $transitionService->transition(
                $leadRecord,
                $status,
                $request->user(),
                [
                    'stage_fields' => $normalizedStageValues,
                    'record_followup' => true,
                    'communication_type' => $communicationType,
                    'outcome' => $outcome,
                    'next_follow_up_at' => $nextFollowUpAt,
                    'followed_up_at' => now(),
                    'employee_name' => $employeeName,
                    'assigned_user_id' => $targetUser?->id ?? $leadRecord->assigned_user_id,
                    'assigned_employee' => $targetUser?->name ?? $leadRecord->assigned_employee,
                    'campaign' => $campaign,
                    'campaign_id' => $campaign?->id,
                    'lead_attributes' => $legacyAttributes,
                    'field_changes' => $allFieldChanges,
                    'history_note' => 'متابعة - ' . ($communicationTypes[$communicationType] ?? $communicationType) . ': ' . $outcome,
                    'disinterest_reason' => $validated['disinterest_reason'] ?? ($normalizedStageValues['reason'] ?? null),
                    'notes' => $stageData['notes'] ?? null,
                ]
            );

            $leadRecord = $transitionResult['lead'];
        } catch (\Throwable $exception) {
            if ($newQuotationPath !== null) {
                Storage::disk('local')->delete($newQuotationPath);
            }

            throw $exception;
        }

        if (
            $newQuotationPath !== null
            && $currentQuotationPath !== ''
            && $currentQuotationPath
                !== $newQuotationPath
            && Storage::disk('local')->exists(
                $currentQuotationPath
            )
        ) {
            Storage::disk('local')->delete(
                $currentQuotationPath
            );
        }

        if ($request->boolean('kanban_popup')) {
            return redirect()
                ->route(
                    'v2.leads.followups.index',
                    [
                        'lead' => $leadRecord->id,
                        'kanban_popup' => 1,
                        'saved' => 1,
                    ]
                )
                ->with(
                    'success',
                    'تم حفظ بيانات المرحلة وتسجيل المتابعة بنجاح باسم ' . $employeeName . '.'
                );
        }

        // If user remains authorized to view this lead, return to followups index
        if ($leadRecord->isAccessibleTo($request->user())) {
            return redirect()
                ->route(
                    'v2.leads.followups.index',
                    $leadRecord
                )
                ->with(
                    'success',
                    'تم حفظ بيانات المرحلة وتسجيل المتابعة بنجاح باسم ' . $employeeName . '.'
                );
        }

        // If transition/reassignment transferred lead outside user scope, safely return to leads index
        return redirect()
            ->route('v2.leads')
            ->with(
                'success',
                'تم حفظ بيانات المرحلة وتحديث إسناد العميل بنجاح باسم ' . $employeeName . '.'
            );
    }
    private function communicationTypes(): array
    {
        return [
            'call' => 'اتصال هاتفي',
            'whatsapp' => 'واتساب',
            'meeting' => 'مقابلة',
            'email' => 'بريد إلكتروني',
            'other' => 'متابعة عامة',
        ];
    }

    private function currentEmployeeName(): string
    {
        $employeeName = mb_substr(
            trim(
                (string)
                    auth()->user()->name
            ),
            0,
            150
        );

        return $employeeName !== ''
            ? $employeeName
            : 'المستخدم';
    }

    private function callPhone(
        string $phone
    ): ?string {
        $digits = preg_replace(
            '/\D+/',
            '',
            trim($phone)
        ) ?? '';

        return preg_match(
            '/^[0-9]{2,20}$/',
            $digits
        ) === 1
            ? $digits
            : null;
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
