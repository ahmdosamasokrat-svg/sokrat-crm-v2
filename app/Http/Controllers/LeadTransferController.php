<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\PipelineStageField;
use App\Models\LeadStageFieldValue;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use App\Support\CrmDatabaseGuard;
use App\Services\LeadDistributionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use XMLWriter;
use ZipArchive;

class LeadTransferController extends Controller
{
    private const MAX_IMPORT_ROWS = 1000;

    private const MAX_EXPORT_ROWS = 5000;

    private const PREVIEW_LIFETIME_SECONDS = 7200;

    public function importIndex(
        Request $request
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $campaign = $this->campaignFromRequest(
            $request,
            $request->query('campaign')
        );

        $stages = $this->stages();
        $statuses = $this->statuses($stages);

        $actor = $request->user() ?? auth()->user();
        $assignableUsers = LeadDistributionService::getAssignableUsers($actor, $campaign);
        $activeLeadCounts = LeadDistributionService::getActiveLeadCounts($assignableUsers);
        $distributionStrategies = LeadDistributionService::strategies();

        return view(
            'leads.import',
            [
                'statuses' => $statuses,
                'defaultStatus' => $statuses->first(),
                'preview' => null,
                'campaign' => $campaign,
                'assignableUsers' => $assignableUsers,
                'activeLeadCounts' => $activeLeadCounts,
                'distributionStrategies' => $distributionStrategies,
            ]
        );
    }

    public function importTemplate(Request $request): BinaryFileResponse|RedirectResponse
    {
        $this->assertCrmV2Database();
        $this->assertXlsxSupport();

        $stageId = $request->integer('stage');
        if ($stageId <= 0) {
            $stageId = null;
        }

        return $this->downloadWorkbook(
            array_values(
                $this->importColumns($stageId)
            ),
            [],
            'crm-v2-leads-import-template.xlsx'
        );
    }

    public function importPreview(
        Request $request
    ): View|RedirectResponse {

        $this->assertCrmV2Database();

        $validator = Validator::make(
            $request->all(),
            [
                'import_file' => [
                    'required',
                    'file',
                    'max:5120',
                ],
                'campaign_id' => [
                    'nullable',
                    'integer',
                    'exists:campaigns,id',
                ],
                'distribution_strategy' => [
                    'nullable',
                    'string',
                    Rule::in(array_keys(LeadDistributionService::strategies())),
                ],
                'distribution_users' => [
                    'nullable',
                    'array',
                ],
                'distribution_users.*' => [
                    'integer',
                ],
                'distribution_single_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_fallback_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_weights' => [
                    'nullable',
                    'array',
                ],
            ],
            [
                'import_file.required' => 'اختر ملف العملاء أولًا.',
                'import_file.file' => 'ملف الاستيراد غير صحيح.',
                'import_file.max' => 'الحد الأقصى لملف الاستيراد 5MB.',
                'distribution_strategy.in' => 'طريقة التوزيع المختارة غير صحيحة.',
            ]
        );

        if ($validator->fails()) {
            return $this->importPageRedirect($request)
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $campaign = $this->campaignFromRequest(
            $request,
            $validated['campaign_id'] ?? null
        );

        $file = $request->file(
            'import_file'
        );

        if ($file === null) {
            return $this->importPageRedirect($request)->withErrors(
                [
                    'import_file' => 'تعذر قراءة ملف الاستيراد.',
                ]
            );
        }

        $extension = mb_strtolower(
            trim(
                (string)
                    $file
                        ->getClientOriginalExtension()
            )
        );

        if (
            ! in_array(
                $extension,
                [
                    'xlsx',
                    'csv',
                ],
                true
            )
        ) {
            return $this->importPageRedirect($request)->withErrors(
                [
                    'import_file' => 'الملفات المدعومة هي XLSX و CSV فقط.',
                ]
            );
        }

        $actor = $request->user() ?? auth()->user();
        $assignableUsers = LeadDistributionService::getAssignableUsers($actor, $campaign);
        $activeLeadCounts = LeadDistributionService::getActiveLeadCounts($assignableUsers);
        $distributionStrategies = LeadDistributionService::strategies();

        $distributionStrategy = $request->input('distribution_strategy');
        if ($distributionStrategy === null || $distributionStrategy === '') {
            $distributionStrategy = $request->filled('distribution_users')
                ? LeadDistributionService::STRATEGY_EQUAL
                : LeadDistributionService::STRATEGY_FROM_FILE;
        }

        $distributionConfig = [
            'strategy' => $distributionStrategy,
            'user_ids' => $request->input('distribution_users', []),
            'single_user_id' => $request->input('distribution_single_user_id'),
            'fallback_user_id' => $request->input('distribution_fallback_user_id'),
            'weights' => $request->input('distribution_weights', []),
        ];

        try {
            $rows = $this->parseImportFile(
                $file->getPathname(),
                $extension
            );

            $preview =
                $this->buildImportPreview(
                    $rows,
                    $campaign?->id,
                    $distributionConfig
                );
        } catch (\Throwable $exception) {
            return $this->importPageRedirect($request)
                ->withErrors(
                    [
                        'import_file' => $exception->getMessage(),
                    ]
                );
        }

        $stages = $this->stages();
        $statuses = $this->statuses($stages);

        return view(
            'leads.import',
            [
                'statuses' => $statuses,
                'defaultStatus' => $statuses->first(),
                'preview' => $preview,
                'campaign' => $campaign,
                'assignableUsers' => $assignableUsers,
                'activeLeadCounts' => $activeLeadCounts,
                'distributionStrategies' => $distributionStrategies,
                'distributionConfig' => $distributionConfig,
            ]
        );
    }

    public function importRedistribute(
        Request $request
    ): View|RedirectResponse {
        $this->assertCrmV2Database();

        $validated = $request->validate(
            [
                'preview_token' => [
                    'required',
                    'string',
                    'regex:/^[a-f0-9]{40}$/',
                ],
                'distribution_strategy' => [
                    'required',
                    'string',
                    Rule::in(array_keys(LeadDistributionService::strategies())),
                ],
                'distribution_users' => [
                    'nullable',
                    'array',
                ],
                'distribution_users.*' => [
                    'integer',
                ],
                'distribution_single_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_fallback_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_weights' => [
                    'nullable',
                    'array',
                ],
            ],
            [
                'preview_token.required' => 'جلسة المعاينة غير موجودة.',
                'preview_token.regex' => 'جلسة المعاينة غير صحيحة.',
                'distribution_strategy.required' => 'اختر طريقة التوزيع.',
                'distribution_strategy.in' => 'طريقة التوزيع المختارة غير صحيحة.',
            ]
        );

        $token = (string) $validated['preview_token'];
        $path = $this->previewPath($token);

        if (! is_file($path)) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(['import_file' => 'انتهت جلسة المعاينة. ارفع الملف من جديد.']);
        }

        try {
            $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(['import_file' => 'تعذر قراءة جلسة المعاينة. ارفع الملف من جديد.']);
        }

        if (
            ! is_array($payload)
            || ($payload['version'] ?? null) !== 1
            || (
                ($payload['session_id'] ?? '') !== session()->getId()
                && (int) ($payload['user_id'] ?? 0) !== (int) $request->user()->id
            )
        ) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(['import_file' => 'جلسة المعاينة لا تخص جلسة المستخدم الحالية.']);
        }

        $campaign = $this->campaignFromRequest($request, $payload['campaign_id'] ?? null);
        $actor = $request->user();
        $assignableUsers = LeadDistributionService::getAssignableUsers($actor, $campaign);
        $activeLeadCounts = LeadDistributionService::getActiveLeadCounts($assignableUsers);
        $distributionStrategies = LeadDistributionService::strategies();

        $distributionConfig = [
            'strategy' => $validated['distribution_strategy'],
            'user_ids' => $validated['distribution_users'] ?? [],
            'single_user_id' => $validated['distribution_single_user_id'] ?? null,
            'fallback_user_id' => $validated['distribution_fallback_user_id'] ?? null,
            'weights' => $validated['distribution_weights'] ?? [],
        ];

        $validPayloadRows = $payload['rows'] ?? [];
        $previewRows = $payload['preview_rows'] ?? [];

        $distributionService = app(LeadDistributionService::class);
        $distResult = $distributionService->distribute(
            $validPayloadRows,
            $previewRows,
            $distributionConfig,
            $assignableUsers,
            $actor
        );

        $payload['rows'] = $distResult['valid_rows'];
        $payload['preview_rows'] = $distResult['preview_rows'];
        $payload['distribution'] = $distResult['summary'];
        $payload['distribution_config'] = $distributionConfig;

        File::put($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $preview = [
            'token' => $token,
            'rows' => $distResult['preview_rows'],
            'total_count' => (int) ($payload['total_count'] ?? count($distResult['preview_rows'])),
            'valid_count' => (int) ($payload['valid_count'] ?? count($distResult['valid_rows'])),
            'error_count' => (int) ($payload['error_count'] ?? 0),
            'duplicate_count' => (int) ($payload['duplicate_count'] ?? 0),
            'ignored_headers' => $payload['ignored_headers'] ?? [],
            'distribution' => $distResult['summary'],
            'distribution_config' => $distributionConfig,
        ];

        $stages = $this->stages();
        $statuses = $this->statuses($stages);

        return view(
            'leads.import',
            [
                'statuses' => $statuses,
                'defaultStatus' => $statuses->first(),
                'preview' => $preview,
                'campaign' => $campaign,
                'assignableUsers' => $assignableUsers,
                'activeLeadCounts' => $activeLeadCounts,
                'distributionStrategies' => $distributionStrategies,
                'distributionConfig' => $distributionConfig,
            ]
        );
    }

    private function importPageRedirect(
        Request $request
    ): RedirectResponse {
        $campaignId = filter_var(
            $request->input('campaign_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        return redirect()->route(
            'v2.leads.import',
            $campaignId === false
                ? []
                : ['campaign' => $campaignId],
        );
    }

    public function importConfirm(
        Request $request
    ): RedirectResponse {

        $this->assertCrmV2Database();

        $validated = $request->validate(
            [
                'preview_token' => [
                    'required',
                    'string',
                    'regex:/^[a-f0-9]{40}$/',
                ],
                'distribution_strategy' => [
                    'nullable',
                    'string',
                    Rule::in(array_keys(LeadDistributionService::strategies())),
                ],
                'distribution_users' => [
                    'nullable',
                    'array',
                ],
                'distribution_users.*' => [
                    'integer',
                ],
                'distribution_single_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_fallback_user_id' => [
                    'nullable',
                    'integer',
                ],
                'distribution_weights' => [
                    'nullable',
                    'array',
                ],
            ],
            [
                'preview_token.required' => 'جلسة المعاينة غير موجودة.',
                'preview_token.regex' => 'جلسة المعاينة غير صحيحة.',
            ]
        );

        $token = (string)
            $validated['preview_token'];

        $path = $this->previewPath(
            $token
        );

        if (! is_file($path)) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'انتهت جلسة المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        try {
            $payload = json_decode(
                File::get($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'تعذر قراءة جلسة المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        if (
            ! is_array($payload)
            || ($payload['version'] ?? null) !== 1
            || (
                ($payload['session_id'] ?? '')
                    !== session()->getId()
                && (int) ($payload['user_id'] ?? 0)
                    !== (int) $request->user()->id
            )
        ) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'جلسة المعاينة لا تخص '
                            .'جلسة المستخدم الحالية.',
                    ]
                );
        }

        $createdAt = (int)
            ($payload['created_at'] ?? 0);

        if (
            $createdAt <= 0
            || (
                time() - $createdAt
            ) > self::PREVIEW_LIFETIME_SECONDS
        ) {
            @unlink($path);

            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'انتهت صلاحية المعاينة. '
                            .'ارفع الملف من جديد.',
                    ]
                );
        }

        $campaign = $this->campaignFromRequest(
            $request,
            $payload['campaign_id'] ?? null
        );

        $rows = $payload['rows'] ?? [];

        if (
            ! is_array($rows)
            || $rows === []
        ) {
            return redirect()
                ->route('v2.leads.import')
                ->withErrors(
                    [
                        'import_file' => 'لا توجد صفوف صالحة للاستيراد.',
                    ]
                );
        }

        $actor = $request->user();
        if ($request->filled('distribution_strategy')) {
            $campaign = $this->campaignFromRequest($request, $payload['campaign_id'] ?? null);
            $assignableUsers = LeadDistributionService::getAssignableUsers($actor, $campaign);
            $distConfig = [
                'strategy' => (string) $request->input('distribution_strategy'),
                'user_ids' => $request->input('distribution_users', []),
                'single_user_id' => $request->input('distribution_single_user_id'),
                'fallback_user_id' => $request->input('distribution_fallback_user_id'),
                'weights' => $request->input('distribution_weights', []),
            ];
            $distService = app(LeadDistributionService::class);
            $distResult = $distService->distribute(
                $rows,
                [],
                $distConfig,
                $assignableUsers,
                $actor
            );
            $rows = $distResult['valid_rows'];
        }
        $result = DB::transaction(
            function () use (
                $rows,
                $actor,
                $campaign
            ): array {
                $statusIds = LeadStatus::query()
                    ->whereHas(
                        'stage',
                        static fn ($query) => $query->where('is_active', true)
                    )
                    ->pluck('id')
                    ->map(
                        static fn ($id): int => (int) $id
                    )
                    ->flip();

                $existingPhones = [];

                $existingLeads = Lead::query()
                    ->lockForUpdate()
                    ->get([
                        'id',
                        'phone',
                    ]);

                foreach (
                    $existingLeads as $existingLead
                ) {
                    $phone = $this
                        ->normalizePhone(
                            (string)
                                $existingLead->phone
                        );

                    if ($phone !== '') {
                        $existingPhones[
                            $phone
                        ] = true;
                    }
                }

                $imported = 0;
                $skipped = 0;

                foreach ($rows as $row) {
                    if (
                        ! is_array($row)
                        || ! isset($row['data'])
                        || ! is_array($row['data'])
                    ) {
                        continue;
                    }

                    $data = $row['data'];

                    $statusId = (int)
                        ($data['lead_status_id'] ?? 0);

                    if (
                        $statusId <= 0
                        || ! $statusIds->has(
                            $statusId
                        )
                    ) {
                        throw new \RuntimeException(
                            'إحدى الحالات الموجودة '
                            .'في المعاينة لم تعد متاحة.'
                        );
                    }
                    $assigneeId = (int) (
                        $data['assigned_user_id'] ?? 0
                    );
                    $assignee = User::query()
                        ->with('groups:id')
                        ->find($assigneeId);

                    if (
                        $assignee === null
                        || ! LeadAssignment::canAssignTo($actor, $assignee)
                    ) {
                        throw new \RuntimeException(
                            'لم تعد تملك صلاحية إسناد أحد العملاء إلى الموظف المحدد.'
                        );
                    }

                    $data['assigned_user_id'] = $assignee->id;
                    $data['assigned_employee'] = $assignee->name;
                    $data['created_by_user_id'] = $actor->id;
                    $data['created_by'] = $actor->name;

                    $phone = $this
                        ->normalizePhone(
                            (string)
                                ($data['phone'] ?? '')
                        );

                    if (
                        $phone === ''
                        || isset(
                            $existingPhones[
                                $phone
                            ]
                        )
                    ) {
                        $skipped++;

                        continue;
                    }

                    $lead = Lead::query()->create(
                        $data
                    );
                    if (! empty($data['custom_fields']) && is_array($data['custom_fields'])) {
                        foreach ($data['custom_fields'] as $fKey => $fVal) {
                            $stageField = PipelineStageField::query()
                                ->where('key', $fKey)
                                ->where('is_active', true)
                                ->first();

                            if ($stageField !== null) {
                                LeadStageFieldValue::query()->create([
                                    'lead_id' => $lead->id,
                                    'pipeline_stage_id' => $stageField->pipeline_stage_id,
                                    'pipeline_stage_field_id' => $stageField->id,
                                    'field_key' => $fKey,
                                    'field_type' => $stageField->type,
                                    'value' => (string) $fVal,
                                    'created_by_user_id' => $actor->id,
                                ]);
                            }
                        }
                    }

                    if ($campaign !== null) {
                        $campaign->leads()->attach(
                            $lead->id
                        );
                    }

                    $existingPhones[
                        $phone
                    ] = true;

                    $imported++;
                }

                return [
                    'imported' => $imported,
                    'skipped' => $skipped,
                ];
            }
        );

        @unlink($path);

        $message =
            'تم استيراد '
            .$result['imported']
            .' عميل بنجاح.';

        if ($result['skipped'] > 0) {
            $message .=
                ' وتم تخطي '
                .$result['skipped']
                .' عميل لأن رقم الهاتف '
                .'أصبح موجودًا بالفعل.';
        }

        $redirect = $campaign !== null
            ? route('v2.campaigns.show', $campaign)
            : route('v2.leads.import');

        return redirect($redirect)
            ->with(
                'success',
                $message
            );
    }

    public function exportIndex(Request $request): View|RedirectResponse
    {

        $this->assertCrmV2Database();
        $user = $request->user();

        $sources = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('source')
            ->where('source', '<>', '')
            ->distinct()
            ->orderBy('source')
            ->pluck('source');

        $visibleAssignedUserIds = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');

        $employees = User::query()
            ->whereIn('id', $visibleAssignedUserIds)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Lead::query()
                    ->accessibleTo($user)
                    ->whereNull('assigned_user_id')
                    ->whereNotNull('assigned_employee')
                    ->where('assigned_employee', '<>', '')
                    ->pluck('assigned_employee')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $stages = $this->stages();

        return view(
            'leads.export',
            [
                'statuses' => $this->statuses($stages),
                'stages' => $stages,
                'sources' => $sources,
                'employees' => $employees,
                'columns' => $this->exportColumns(),
                'totalLeads' => Lead::query()
                    ->accessibleTo($user)
                    ->count(),
            ]
        );
    }

    public function exportDownload(
        Request $request
    ): BinaryFileResponse|RedirectResponse {

        $this->assertCrmV2Database();
        $this->assertXlsxSupport();

        $columns =
            $this->exportColumns();

        $validated = $request->validate(
            [
                'status_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'lead_statuses',
                        'id'
                    ),
                ],

                'stage_id' => [
                    'nullable',
                    'integer',
                    Rule::exists(
                        'pipeline_stages',
                        'id'
                    ),
                ],

                'employee' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'source' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'date_from' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'date_to' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'from_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'to_date' => [
                    'nullable',
                    'date_format:Y-m-d',
                ],
                'q' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
                'follow_up' => [
                    'nullable',
                    'string',
                    Rule::in(['today', 'upcoming', 'overdue', 'none', '']),
                ],
                'sort' => [
                    'nullable',
                    'string',
                    Rule::in(['latest', 'oldest', 'name', 'followup', '']),
                ],

                'columns' => [
                    'required',
                    'array',
                    'min:1',
                    'max:40',
                ],

                'columns.*' => [
                    'required',
                    'string',
                    'distinct',
                    Rule::in(
                        array_keys($columns)
                    ),
                ],
            ],
            [
                'columns.required' => 'اختر عمودًا واحدًا على الأقل.',
                'columns.min' => 'اختر عمودًا واحدًا على الأقل.',
                'columns.*.in' => 'أحد أعمدة التصدير غير صحيح.',
                'date_from.date_format' => 'تاريخ البداية غير صحيح.',
                'date_to.date_format' => 'تاريخ النهاية غير صحيح.',
                'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يكون '
                    .'بعد أو مساويًا لتاريخ البداية.',
            ]
        );

        $query = Lead::query()
            ->accessibleTo($request->user())
            ->with([
                'status.stage',
                'assignedUser:id,name',
                'stageValues:id,lead_id,field_key,value',
            ]);

        if (
            ! empty(
                $validated['status_id']
            )
        ) {
            $query->where(
                'lead_status_id',
                (int)
                    $validated['status_id']
            );
        }

        if (
            ! empty(
                $validated['stage_id']
            )
        ) {
            $stageId = (int)
                $validated['stage_id'];

            $query->whereHas(
                'status',
                static function (
                    $statusQuery
                ) use ($stageId): void {
                    $statusQuery->where(
                        'pipeline_stage_id',
                        $stageId
                    );
                }
            );
        }

        if (
            ! empty(
                $validated['employee']
            )
        ) {
            $employee = trim(
                (string) $validated['employee']
            );

            $query->where(
                function ($employeeQuery) use ($employee): void {
                    $employeeQuery
                        ->whereHas(
                            'assignedUser',
                            static fn ($userQuery) => $userQuery->where('name', $employee)
                        )
                        ->orWhere(
                            static fn ($legacyQuery) => $legacyQuery
                                ->whereNull('assigned_user_id')
                                ->where('assigned_employee', $employee)
                        );
                }
            );
        }

        if (
            ! empty(
                $validated['source']
            )
        ) {
            $query->where(
                'source',
                trim(
                    (string)
                        $validated[
                            'source'
                        ]
                )
            );
        }

        $dateFrom = $validated['from_date'] ?? $validated['date_from'] ?? null;
        $dateTo = $validated['to_date'] ?? $validated['date_to'] ?? null;

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            return back()->withInput()->withErrors([
                'date_to' => 'تاريخ النهاية يجب أن يكون بعد أو مساويًا لتاريخ البداية.',
            ]);
        }

        if (! empty($dateFrom)) {
            $query->whereDate('created_at', '>=', (string) $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('created_at', '<=', (string) $dateTo);
        }

        if (! empty($validated['q'])) {
            $search = '%' . trim((string) $validated['q']) . '%';
            $query->where(function ($qQuery) use ($search): void {
                $qQuery->where('name', 'like', $search)
                    ->orWhere('company_name', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('source', 'like', $search);
            });
        }

        if (! empty($validated['follow_up'])) {
            match ($validated['follow_up']) {
                'today' => $query->whereBetween('next_follow_up_at', [now()->startOfDay(), now()->endOfDay()]),
                'upcoming' => $query->where('next_follow_up_at', '>', now()->endOfDay()),
                'overdue' => $query->where('next_follow_up_at', '<', now()->startOfDay()),
                'none' => $query->whereNull('next_follow_up_at'),
                default => null,
            };
        }

        $sort = $validated['sort'] ?? 'latest';
        match ($sort) {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'name' => $query->orderBy('name')->orderBy('id'),
            'followup' => $query->orderByRaw('next_follow_up_at IS NULL')->orderBy('next_follow_up_at')->orderByDesc('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        $count = (clone $query)
            ->count();

        if ($count === 0) {
            return back()
                ->withInput()
                ->withErrors(
                    [
                        'export' => 'لا توجد عملاء مطابقون '
                            .'للفلاتر المختارة.',
                    ]
                );
        }

        if (
            $count
            > self::MAX_EXPORT_ROWS
        ) {
            return back()
                ->withInput()
                ->withErrors(
                    [
                        'export' => 'عدد العملاء المطابقين '
                            .'أكبر من '
                            .self::MAX_EXPORT_ROWS
                            .'. قلل النطاق باستخدام '
                            .'الفلاتر.',
                    ]
                );
        }

        $selectedColumns =
            array_values(
                $validated['columns']
            );

        $headers = array_map(
            static fn (string $key): string => $columns[$key],
            $selectedColumns
        );

        $leads = $query
            ->orderBy('id')
            ->get();

        $rows = [];

        foreach ($leads as $lead) {
            $row = [];

            foreach (
                $selectedColumns as $column
            ) {
                $row[] =
                    $this->exportValue(
                        $lead,
                        $column
                    );
            }

            $rows[] = $row;
        }

        return $this->downloadWorkbook(
            $headers,
            $rows,
            'crm-v2-leads-'
                .now()->format(
                    'Ymd-His'
                )
                .'.xlsx'
        );
    }

    private function stages()
    {
        return PipelineStage::query()
            ->where('is_active', true)
            ->with([
                'statuses' => static fn ($query) => $query
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    private function statuses($stages = null)
    {
        return ($stages ?? $this->stages())
            ->flatMap(
                static fn (PipelineStage $stage) => $stage->statuses
            )
            ->values();
    }

    private function importColumns(?int $stageId = null): array
    {
        $columns = [
            'first_name' => 'اسم العميل الأول',
            'last_name' => 'اسم العميل الأخير',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'company_name' => 'اسم الشركة',
            'activity' => 'النشاط',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'users_count' => 'عدد المستخدمين',
            'branches_count' => 'عدد الفروع',
            'job_title' => 'المنصب',
            'source' => 'المصدر',
            'status' => 'الحالة',
            'stage' => 'المرحلة',
            'solution_type' => 'نوع النظام',
            'lines_count' => 'عدد الخطوط',
            'extensions' => 'الملحقات',
            'departments' => 'الأقسام',
            'disinterest_reason' => 'سبب عدم الاهتمام',
            'assigned_employee' => 'الموظف المسؤول',
            'notes' => 'ملاحظات',
        ];

        try {
            $customQuery = PipelineStageField::query()
                ->where('is_active', true)
                ->where('binding_type', 'custom')
                ->with('stage:id,name_ar,code')
                ->orderBy('position');

            if ($stageId !== null && $stageId > 0) {
                $customQuery->where('pipeline_stage_id', $stageId);
            }

            $customFields = $customQuery->get();

            $existingLabelsNormalized = [];
            foreach ($columns as $k => $lbl) {
                $existingLabelsNormalized[$this->normalizeHeader((string) $lbl)] = $k;
            }

            foreach ($customFields as $field) {
                $rawLabel = trim((string) ($field->label_ar ?: ($field->label_en ?: $field->key)));
                $normLabel = $this->normalizeHeader($rawLabel);

                // If this label or concept is already covered by canonical columns, skip adding duplicate column
                if (
                    isset($existingLabelsNormalized[$normLabel])
                    || in_array($field->key, ['reason', 'disinterest_reason', 'lines_count'], true)
                    || str_starts_with($field->key, 'notes')
                    || str_contains($normLabel, 'عدمالاهتمام')
                    || str_contains($normLabel, 'عددالخطوط')
                    || str_contains($normLabel, 'ملاحظات')
                ) {
                    continue;
                }

                $colKey = 'custom_' . $field->key;
                if (! isset($columns[$colKey])) {
                    $columns[$colKey] = $rawLabel;
                    $existingLabelsNormalized[$normLabel] = $colKey;
                }
            }
        } catch (\Throwable) {
        }

        return $columns;
    }

    private function importHeaderAliases(): array
    {
        $aliases = [
            'first_name' => [
                'اسم العميل الأول',
                'الاسم الأول',
                'first_name',
                'first name',
            ],

            'last_name' => [
                'اسم العميل الأخير',
                'الاسم الأخير',
                'last_name',
                'last name',
            ],

            'phone' => [
                'الهاتف',
                'رقم الهاتف',
                'phone',
                'mobile',
            ],

            'email' => [
                'البريد الإلكتروني',
                'البريد الالكتروني',
                'email',
            ],

            'company_name' => [
                'اسم الشركة',
                'الشركة',
                'company_name',
                'company',
            ],

            'activity' => [
                'النشاط',
                'activity',
            ],

            'governorate' => [
                'المحافظة',
                'governorate',
            ],

            'address' => [
                'العنوان',
                'address',
            ],

            'users_count' => [
                'عدد المستخدمين',
                'users_count',
                'users count',
            ],

            'branches_count' => [
                'عدد الفروع',
                'branches_count',
                'branches count',
            ],

            'job_title' => [
                'المنصب',
                'المسمى الوظيفي',
                'job_title',
                'job title',
            ],

            'source' => [
                'المصدر',
                'source',
            ],

            'status' => [
                'الحالة',
                'حالة العميل',
                'status',
                'status_code',
                'status code',
            ],

            'stage' => [
                'المرحلة',
                'مرحلة العميل',
                'stage',
                'stage_code',
                'stage code',
            ],

            'solution_type' => [
                'نوع النظام',
                'solution_type',
                'solution type',
            ],

            'lines_count' => [
                'عدد الخطوط',
                'lines_count',
                'lines count',
            ],

            'extensions' => [
                'الملحقات',
                'extensions',
            ],

            'departments' => [
                'الأقسام',
                'الاقسام',
                'departments',
            ],

            'disinterest_reason' => [
                'سبب عدم الاهتمام',
                'disinterest_reason',
                'disinterest reason',
            ],

            'assigned_employee' => [
                'الموظف المسؤول',
                'assigned_employee',
                'assigned employee',
            ],

            'notes' => [
                'ملاحظات',
                'notes',
            ],
        ];

        $map = [];

        foreach (
            $aliases as $key => $values
        ) {
            foreach ($values as $value) {
                $map[
                    $this->normalizeHeader(
                        $value
                    )
                ] = $key;
            }
        }
        try {
            $customFields = PipelineStageField::query()
                ->where('is_active', true)
                ->where('binding_type', 'custom')
                ->with('stage:id,name_ar,code')
                ->get();
            foreach ($customFields as $field) {
                $rawLabel = trim((string) ($field->label_ar ?: ($field->label_en ?: $field->key)));
                $normLabel = $this->normalizeHeader($rawLabel);

                if (
                    in_array($field->key, ['reason', 'disinterest_reason'], true)
                    || str_contains($normLabel, 'عدمالاهتمام')
                ) {
                    $map[$this->normalizeHeader((string) $field->key)] = 'disinterest_reason';
                    if ($field->label_ar) {
                        $map[$this->normalizeHeader((string) $field->label_ar)] = 'disinterest_reason';
                    }
                    continue;
                }

                if (
                    in_array($field->key, ['lines_count'], true)
                    || str_contains($normLabel, 'عددالخطوط')
                ) {
                    $map[$this->normalizeHeader((string) $field->key)] = 'lines_count';
                    if ($field->label_ar) {
                        $map[$this->normalizeHeader((string) $field->label_ar)] = 'lines_count';
                    }
                    continue;
                }

                if (
                    str_starts_with($field->key, 'notes')
                    || str_contains($normLabel, 'ملاحظات')
                ) {
                    $map[$this->normalizeHeader((string) $field->key)] = 'notes';
                    if ($field->label_ar) {
                        $map[$this->normalizeHeader((string) $field->label_ar)] = 'notes';
                    }
                    continue;
                }

                $key = 'custom_' . $field->key;
                $map[$this->normalizeHeader((string) $field->key)] = $key;
                if ($field->label_ar) {
                    $map[$this->normalizeHeader((string) $field->label_ar)] = $key;
                }
                if ($field->label_en) {
                    $map[$this->normalizeHeader((string) $field->label_en)] = $key;
                }
            }
        } catch (\Throwable) {
        }
        return $map;
    }

    private function buildImportPreview(
        array $rows,
        ?int $campaignId = null,
        array $distributionConfig = []
    ): array {
        if ($rows === []) {
            throw new \RuntimeException(
                'ملف الاستيراد فارغ.'
            );
        }

        $headerIndex = null;

        foreach ($rows as $index => $row) {
            if (
                $this->rowHasContent(
                    $row
                )
            ) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException(
                'ملف الاستيراد لا يحتوي '
                .'على بيانات.'
            );
        }

        $headerRow =
            $rows[$headerIndex];

        $aliasMap =
            $this->importHeaderAliases();

        $indexes = [];
        $ignoredHeaders = [];

        foreach (
            $headerRow as $columnIndex => $header
        ) {
            $headerText = trim(
                (string) $header
            );

            if ($headerText === '') {
                continue;
            }

            $normalized =
                $this->normalizeHeader(
                    $headerText
                );

            if (
                ! isset(
                    $aliasMap[
                        $normalized
                    ]
                )
            ) {
                $ignoredHeaders[] =
                    $headerText;

                continue;
            }

            $key = $aliasMap[
                $normalized
            ];

            if (isset($indexes[$key])) {
                throw new \RuntimeException(
                    'يوجد عمود مكرر للبيان: '
                    .$this->importColumns()[
                        $key
                    ]
                );
            }

            $indexes[$key] =
                $columnIndex;
        }

        foreach (
            [
                'first_name',
                'phone',
                'source',
            ] as $requiredHeader
        ) {
            if (
                ! isset(
                    $indexes[
                        $requiredHeader
                    ]
                )
            ) {
                throw new \RuntimeException(
                    'العمود المطلوب غير موجود: '
                    .$this->importColumns()[
                        $requiredHeader
                    ]
                );
            }
        }

        $dataRows = array_slice(
            $rows,
            $headerIndex + 1
        );

        if (
            count($dataRows)
            > self::MAX_IMPORT_ROWS
        ) {
            throw new \RuntimeException(
                'الحد الأقصى للاستيراد هو '
                .self::MAX_IMPORT_ROWS
                .' صف في العملية الواحدة.'
            );
        }

        $stages = $this->stages();
        $statuses = $this->statuses($stages);

        if ($statuses->isEmpty()) {
            throw new \RuntimeException(
                'لا توجد مراحل نشطة متاحة للاستيراد.'
            );
        }

        $statusMap = [];

        foreach ($statuses as $status) {
            $statusMap[
                $this->normalizeToken(
                    (string) $status->code
                )
            ] = $status;

            $statusMap[
                $this->normalizeToken(
                    (string) $status->name_ar
                )
            ] = $status;
        }

        $stageMap = [];

        foreach ($stages as $stage) {
            $stageMap[
                $this->normalizeToken(
                    (string) $stage->code
                )
            ] = $stage;

            $stageMap[
                $this->normalizeToken(
                    (string) $stage->name_ar
                )
            ] = $stage;
        }

        $defaultStatus = $statuses->first();

        $actor = auth()->user();
        $userIdMap = [];

        $assignableUsers =
            LeadAssignment::assignableUsers($actor);

        if ($campaignId !== null) {
            $campaignUserIds = Campaign::query()
                ->findOrFail($campaignId)
                ->users()
                ->pluck('users.id')
                ->push($actor->id)
                ->map(
                    static fn ($id): int => (int) $id
                )
                ->unique();

            $assignableUsers = $assignableUsers
                ->whereIn('id', $campaignUserIds);
        }

        foreach (
            $assignableUsers as $user
        ) {
            foreach (
                [
                    $user->name,
                    $user->username,
                ] as $identity
            ) {
                $token = $this->normalizeToken(
                    (string) $identity
                );

                if (
                    $token !== ''
                    && ! isset($userIdMap[$token])
                ) {
                    $userIdMap[$token] =
                        (int) $user->id;
                }
            }
        }

        $existingPhones = [];

        foreach (
            Lead::query()
                ->pluck('phone') as $phone
        ) {
            $normalized =
                $this->normalizePhone(
                    (string) $phone
                );

            if ($normalized !== '') {
                $existingPhones[
                    $normalized
                ] = true;
            }
        }

        $filePhones = [];

        $previewRows = [];
        $validPayloadRows = [];

        $validCount = 0;
        $errorCount = 0;
        $duplicateCount = 0;

        foreach (
            $dataRows as $offset => $row
        ) {
            if (
                ! $this->rowHasContent(
                    $row
                )
            ) {
                continue;
            }

            $excelRowNumber =
                $headerIndex
                + $offset
                + 2;

            $value = function (
                string $key
            ) use (
                $row,
                $indexes
            ): string {
                if (
                    ! isset(
                        $indexes[$key]
                    )
                ) {
                    return '';
                }

                return trim(
                    (string) (
                        $row[
                            $indexes[$key]
                        ] ?? ''
                    )
                );
            };

            $errors = [];
            $warnings = [];

            $firstName =
                $value('first_name');

            $lastName =
                $this->nullableText(
                    $value('last_name')
                );

            $phone =
                $value('phone');

            $source =
                $value('source');

            if ($firstName === '') {
                $errors[] =
                    'اسم العميل الأول مطلوب.';
            }

            $this->validateLength(
                $firstName,
                75,
                'اسم العميل الأول',
                $errors
            );

            if ($phone === '') {
                $errors[] =
                    'رقم الهاتف مطلوب.';
            }

            $this->validateLength(
                $phone,
                50,
                'رقم الهاتف',
                $errors
            );

            if ($source === '') {
                $errors[] =
                    'المصدر مطلوب.';
            }

            $this->validateLength(
                $source,
                100,
                'المصدر',
                $errors
            );

            if ($lastName !== null) {
                $this->validateLength(
                    $lastName,
                    75,
                    'اسم العميل الأخير',
                    $errors
                );
            }

            $email =
                $this->nullableText(
                    $value('email')
                );

            if (
                $email !== null
                && (
                    mb_strlen($email) > 190
                    || filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    ) === false
                )
            ) {
                $errors[] =
                    'البريد الإلكتروني غير صحيح.';
            }

            $statusInput =
                $value('status');

            $stageInput =
                $value('stage');

            $stage = null;

            if ($stageInput !== '') {
                $stage = $stageMap[
                    $this->normalizeToken(
                        $stageInput
                    )
                ] ?? null;

                if ($stage === null) {
                    $errors[] =
                        'المرحلة "'
                        .$stageInput
                        .'" غير موجودة أو غير نشطة.';
                }
            }

            $status = null;

            if ($statusInput !== '') {
                $normalizedStatusInput =
                    $this->normalizeToken(
                        $statusInput
                    );

                $status = $stage !== null
                    ? $stage->statuses->first(
                        fn (LeadStatus $candidate): bool => in_array(
                            $normalizedStatusInput,
                            [
                                $this->normalizeToken((string) $candidate->code),
                                $this->normalizeToken((string) $candidate->name_ar),
                            ],
                            true
                        )
                    )
                    : ($statusMap[$normalizedStatusInput] ?? null);

                if ($status === null) {
                    $errors[] =
                        'الحالة "'
                        .$statusInput
                        .'" غير موجودة في مرحلة نشطة.';
                }
            } elseif ($stage !== null) {
                $status = $stage->statuses->first();

                if ($status === null) {
                    $errors[] =
                        'المرحلة "'
                        .$stageInput
                        .'" لا تحتوي على حالة متاحة.';
                }
            } elseif ($stageInput === '') {
                $status = $defaultStatus;
            }

            if (
                $status !== null
                && $stage !== null
                && (int) $status->pipeline_stage_id
                    !== (int) $stage->id
            ) {
                $errors[] =
                    'الحالة المحددة لا تتبع المرحلة المحددة.';
            }

            $usersCount =
                $this->parseUnsignedInteger(
                    $value(
                        'users_count'
                    ),
                    0,
                    1000000,
                    'عدد المستخدمين',
                    $errors
                );

            $branchesCount =
                $this->parseUnsignedInteger(
                    $value(
                        'branches_count'
                    ),
                    0,
                    1000000,
                    'عدد الفروع',
                    $errors
                );

            $linesCount =
                $this->parseUnsignedInteger(
                    $value(
                        'lines_count'
                    ),
                    1,
                    1000000,
                    'عدد الخطوط',
                    $errors
                );

            $solutionType =
                $this->parseSolutionType(
                    $value(
                        'solution_type'
                    ),
                    $errors
                );

            $companyName =
                $this->nullableText(
                    $value(
                        'company_name'
                    )
                );

            $activity =
                $this->nullableText(
                    $value('activity')
                );

            $governorate =
                $this->nullableText(
                    $value(
                        'governorate'
                    )
                );

            $address =
                $this->nullableText(
                    $value('address')
                );

            $jobTitle =
                $this->nullableText(
                    $value(
                        'job_title'
                    )
                );

            $extensions =
                $this->nullableText(
                    $value(
                        'extensions'
                    )
                );

            $departments =
                $this->nullableText(
                    $value(
                        'departments'
                    )
                );

            $disinterestReason =
                $this->nullableText(
                    $value(
                        'disinterest_reason'
                    )
                );
            $strategy = $distributionConfig['strategy'] ?? LeadDistributionService::STRATEGY_FROM_FILE;
            $rawAssignedEmployee = $this->nullableText($value('assigned_employee'));
            $assignedEmployee = $rawAssignedEmployee;
            $assignedUserId = null;

            if ($assignedEmployee !== null) {
                $assignedUserId = $userIdMap[$this->normalizeToken($assignedEmployee)] ?? null;
            }

            if ($strategy === LeadDistributionService::STRATEGY_FROM_FILE) {
                if ($assignedUserId === null) {
                    $fallbackId = isset($distributionConfig['fallback_user_id'])
                        ? (int) $distributionConfig['fallback_user_id']
                        : null;

                    if ($fallbackId !== null && in_array($fallbackId, $userIdMap, true)) {
                        $assignedUserId = $fallbackId;
                        $assignedEmployee = $assignableUsers->firstWhere('id', $fallbackId)?->name ?? $this->currentEmployeeName();
                    } elseif ($rawAssignedEmployee === null) {
                        $assignedUserId = (int) $actor->id;
                        $assignedEmployee = $this->currentEmployeeName();
                    } else {
                        $errors[] = 'الموظف المسؤول المذكور بالملف غير موجود أو لا تملك صلاحية الإسناد إليه.';
                    }
                }
            } else {
                if ($assignedUserId === null) {
                    $assignedUserId = (int) $actor->id;
                    $assignedEmployee = $this->currentEmployeeName();
                }
            }

            $notes =
                $this->nullableText(
                    $value('notes')
                );

            foreach (
                [
                    [
                        $companyName,
                        150,
                        'اسم الشركة',
                    ],
                    [
                        $activity,
                        150,
                        'النشاط',
                    ],
                    [
                        $governorate,
                        100,
                        'المحافظة',
                    ],
                    [
                        $address,
                        255,
                        'العنوان',
                    ],
                    [
                        $jobTitle,
                        150,
                        'المنصب',
                    ],
                    [
                        $assignedEmployee,
                        150,
                        'الموظف المسؤول',
                    ],
                    [
                        $extensions,
                        5000,
                        'الملحقات',
                    ],
                    [
                        $departments,
                        5000,
                        'الأقسام',
                    ],
                    [
                        $disinterestReason,
                        5000,
                        'سبب عدم الاهتمام',
                    ],
                    [
                        $notes,
                        5000,
                        'الملاحظات',
                    ],
                ] as [
                    $text,
                    $max,
                    $label,
                ]
            ) {
                if ($text !== null) {
                    $this->validateLength(
                        $text,
                        $max,
                        $label,
                        $errors
                    );
                }
            }

            if (
                $status !== null
                && $status->code
                    === 'not_interested'
                && $disinterestReason
                    === null
            ) {
                $warnings[] =
                    'الحالة غير مهتم بدون سبب؛ '
                    .'يمكن استكمال السبب لاحقًا.';
            }

            $quotationCodes = [
                'quotation',
                'discussion',
                'contract_closed',
                'execution',
            ];

            if (
                $status !== null
                && in_array(
                    $status->code,
                    $quotationCodes,
                    true
                )
                && $solutionType === null
            ) {
                $warnings[] =
                    'الحالة ضمن مراحل عرض السعر '
                    .'ولا يوجد نوع نظام؛ يمكن '
                    .'استكماله من المتابعة لاحقًا.';
            }

            $normalizedPhone =
                $this->normalizePhone(
                    $phone
                );

            if (
                $phone !== ''
                && $normalizedPhone === ''
            ) {
                $errors[] =
                    'رقم الهاتف لا يحتوي '
                    .'على أرقام صحيحة.';
            }

            $duplicateReason = null;

            if (
                $normalizedPhone !== ''
                && isset(
                    $existingPhones[
                        $normalizedPhone
                    ]
                )
            ) {
                $duplicateReason =
                    'رقم الهاتف موجود بالفعل '
                    .'في CRM.';
            } elseif (
                $normalizedPhone !== ''
                && isset(
                    $filePhones[
                        $normalizedPhone
                    ]
                )
            ) {
                $duplicateReason =
                    'رقم الهاتف مكرر داخل الملف.';
            }

            if ($normalizedPhone !== '') {
                $filePhones[
                    $normalizedPhone
                ] = true;
            }

            $fullName = trim(
                $firstName
                .' '
                .($lastName ?? '')
            );

            $state = 'valid';

            if ($errors !== []) {
                $state = 'error';
                $errorCount++;
            } elseif (
                $duplicateReason !== null
            ) {
                $state = 'duplicate';
                $duplicateCount++;
            } else {
                $validCount++;
            }

            $leadData = null;

            if ($state === 'valid') {
                $leadData = [
                    'lead_status_id' => (int) $status->id,

                    'name' => $fullName,

                    'first_name' => $firstName,

                    'last_name' => $lastName,

                    'company_name' => $companyName,

                    'activity' => $activity,

                    'governorate' => $governorate,

                    'address' => $address,

                    'users_count' => $usersCount,

                    'branches_count' => $branchesCount,

                    'job_title' => $jobTitle,

                    'disinterest_reason' => $disinterestReason,

                    'solution_type' => $solutionType,

                    'lines_count' => $solutionType
                            === 'call_center'
                            ? $linesCount
                            : null,

                    'extensions' => $solutionType
                            === 'call_center'
                            ? $extensions
                            : null,

                    'departments' => $solutionType
                            === 'erp'
                            ? $departments
                            : null,

                    'quotation_file_path' => null,

                    'phone' => $phone,

                    'email' => $email,

                    'source' => $source,

                    'quotation_sent' => false,

                    'assigned_employee' => $assignedEmployee,

                    'assigned_user_id' => $assignedUserId,

                    'created_by' => $actor->name,

                    'created_by_user_id' => (int) $actor->id,

                    'notes' => $notes,
                ];
                $customFieldValues = [];
                foreach ($indexes as $colKey => $colIdx) {
                    if (str_starts_with($colKey, 'custom_')) {
                        $fieldKey = substr($colKey, 7);
                        $val = trim((string) ($row[$colIdx] ?? ''));
                        if ($val !== '') {
                            $customFieldValues[$fieldKey] = $val;
                        }
                    }
                }
                if ($customFieldValues !== []) {
                    $leadData['custom_fields'] = $customFieldValues;
                }

                $validPayloadRows[] = [
                    'row_number' => $excelRowNumber,
                    'data' => $leadData,
                ];
            }

            $previewRows[] = [
                'row_number' => $excelRowNumber,

                'name' => $fullName !== ''
                        ? $fullName
                        : '----',

                'phone' => $phone !== ''
                        ? $phone
                        : '----',

                'status' => $status?->name_ar
                    ?? ($statusInput !== '' ? $statusInput : '----'),

                'stage' => $status?->stage
                    ?->name_ar
                    ?? ($stageInput !== '' ? $stageInput : '----'),

                'state' => $state,

                'duplicate_reason' => $duplicateReason,

                'errors' => $errors,

                'warnings' => $warnings,
            ];
        }

        if ($previewRows === []) {
            throw new \RuntimeException(
                'لا توجد صفوف بيانات بعد '
                .'صف العناوين.'
            );
        }

        $distributionSummary = null;

        if ($validPayloadRows !== []) {
            $distributionService = app(LeadDistributionService::class);
            $distResult = $distributionService->distribute(
                $validPayloadRows,
                $previewRows,
                $distributionConfig,
                $assignableUsers,
                $actor
            );

            $validPayloadRows = $distResult['valid_rows'];
            $previewRows = $distResult['preview_rows'];
            $distributionSummary = $distResult['summary'];
        }

        $token = null;

        if ($validPayloadRows !== []) {
            $token =
                $this->writePreviewPayload(
                    $validPayloadRows,
                    $campaignId,
                    [
                        'preview_rows' => $previewRows,
                        'distribution' => $distributionSummary,
                        'distribution_config' => $distributionConfig,
                        'total_count' => count($previewRows),
                        'valid_count' => $validCount,
                        'error_count' => $errorCount,
                        'duplicate_count' => $duplicateCount,
                        'ignored_headers' => array_values(
                            array_unique($ignoredHeaders)
                        ),
                    ]
                );
        }

        return [
            'token' => $token,
            'rows' => $previewRows,
            'total_count' => count($previewRows),
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'duplicate_count' => $duplicateCount,
            'ignored_headers' => array_values(
                array_unique(
                    $ignoredHeaders
                )
            ),
            'distribution' => $distributionSummary,
            'distribution_config' => $distributionConfig,
        ];
    }

    private function parseImportFile(
        string $path,
        string $extension
    ): array {
        return match ($extension) {
            'csv' => $this->parseCsv($path),

            'xlsx' => $this->parseXlsx($path),

            default => throw new \RuntimeException(
                'صيغة الملف غير مدعومة.'
            ),
        };
    }

    private function parseCsv(
        string $path
    ): array {
        $content = File::get($path);

        if (
            str_starts_with(
                $content,
                "\xEF\xBB\xBF"
            )
        ) {
            $content = substr(
                $content,
                3
            );
        } elseif (
            str_starts_with(
                $content,
                "\xFF\xFE"
            )
        ) {
            $content =
                mb_convert_encoding(
                    substr($content, 2),
                    'UTF-8',
                    'UTF-16LE'
                );
        } elseif (
            str_starts_with(
                $content,
                "\xFE\xFF"
            )
        ) {
            $content =
                mb_convert_encoding(
                    substr($content, 2),
                    'UTF-8',
                    'UTF-16BE'
                );
        }

        $firstLine = '';

        foreach (
            preg_split(
                '/\R/u',
                $content
            ) ?: [] as $line
        ) {
            if (trim($line) !== '') {
                $firstLine = $line;
                break;
            }
        }

        if ($firstLine === '') {
            return [];
        }

        $delimiter = ',';
        $bestCount = 0;

        foreach (
            [
                ',',
                ';',
                "\t",
            ] as $candidate
        ) {
            $count = count(
                str_getcsv(
                    $firstLine,
                    $candidate
                )
            );

            if ($count > $bestCount) {
                $bestCount = $count;
                $delimiter = $candidate;
            }
        }

        $stream = fopen(
            'php://temp',
            'r+'
        );

        if ($stream === false) {
            throw new \RuntimeException(
                'تعذر تجهيز ملف CSV.'
            );
        }

        fwrite(
            $stream,
            $content
        );

        rewind($stream);

        $rows = [];

        while (
            (
                $row = fgetcsv(
                    $stream,
                    0,
                    $delimiter
                )
            ) !== false
        ) {
            $rows[] = array_map(
                static fn ($value): string => trim(
                    (string) $value
                ),
                $row
            );

            if (
                count($rows)
                > self::MAX_IMPORT_ROWS
                    + 20
            ) {
                break;
            }
        }

        fclose($stream);

        return $rows;
    }

    private function parseXlsx(
        string $path
    ): array {
        $this->assertXlsxSupport();

        $zip = new ZipArchive;

        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new \RuntimeException(
                'تعذر فتح ملف Excel.'
            );
        }

        try {
            $sharedStrings =
                $this->xlsxSharedStrings(
                    $zip
                );

            $sheetPath =
                'xl/worksheets/sheet1.xml';

            if (
                $zip->locateName(
                    $sheetPath
                ) === false
            ) {
                $sheetPath = '';

                for (
                    $index = 0;
                    $index < $zip->numFiles;
                    $index++
                ) {
                    $name = (string)
                        $zip->getNameIndex(
                            $index
                        );

                    if (
                        preg_match(
                            '#^xl/worksheets/'
                            .'sheet\d+\.xml$#',
                            $name
                        )
                    ) {
                        $sheetPath = $name;
                        break;
                    }
                }

                if ($sheetPath === '') {
                    throw new \RuntimeException(
                        'لا توجد ورقة بيانات '
                        .'صالحة داخل ملف Excel.'
                    );
                }
            }

            $sheetXml =
                $zip->getFromName(
                    $sheetPath
                );

            if (
                ! is_string($sheetXml)
                || $sheetXml === ''
            ) {
                throw new \RuntimeException(
                    'تعذر قراءة ورقة Excel.'
                );
            }

            $xml = simplexml_load_string(
                $sheetXml,
                \SimpleXMLElement::class,
                LIBXML_NONET
                | LIBXML_COMPACT
            );

            if ($xml === false) {
                throw new \RuntimeException(
                    'بيانات Excel الداخلية '
                    .'غير صحيحة.'
                );
            }

            $namespaces =
                $xml->getNamespaces(true);

            $mainNamespace =
                $namespaces[''] ?? '';

            $main = $mainNamespace !== ''
                ? $xml->children(
                    $mainNamespace
                )
                : $xml;

            $rows = [];

            foreach (
                $main->sheetData->row as $rowNode
            ) {
                $cells = [];
                $maxColumn = -1;

                foreach (
                    $rowNode->children(
                        $mainNamespace
                    )->c as $cell
                ) {
                    $attributes =
                        $cell->attributes();

                    $reference = (string)
                        ($attributes['r'] ?? '');

                    if (
                        ! preg_match(
                            '/^([A-Z]+)\d+$/',
                            $reference,
                            $match
                        )
                    ) {
                        continue;
                    }

                    $columnIndex =
                        $this
                            ->xlsxColumnIndex(
                                $match[1]
                            );

                    $type = (string)
                        ($attributes['t'] ?? '');

                    $cellMain =
                        $mainNamespace !== ''
                            ? $cell->children(
                                $mainNamespace
                            )
                            : $cell;

                    $cellValue = '';

                    if ($type === 's') {
                        $sharedIndex = (int)
                            ($cellMain->v ?? 0);

                        $cellValue =
                            $sharedStrings[
                                $sharedIndex
                            ] ?? '';
                    } elseif (
                        $type === 'inlineStr'
                    ) {
                        $cellValue =
                            isset(
                                $cellMain->is
                            )
                                ? $this
                                    ->xlsxNodeText(
                                        $cellMain->is,
                                        $mainNamespace
                                    )
                                : '';
                    } else {
                        $cellValue =
                            isset(
                                $cellMain->v
                            )
                                ? (string)
                                    $cellMain->v
                                : '';
                    }

                    $cells[
                        $columnIndex
                    ] = trim(
                        $cellValue
                    );

                    $maxColumn = max(
                        $maxColumn,
                        $columnIndex
                    );
                }

                if ($maxColumn < 0) {
                    $rows[] = [];

                    continue;
                }

                $row = [];

                for (
                    $column = 0;
                    $column <= $maxColumn;
                    $column++
                ) {
                    $row[] =
                        $cells[$column]
                        ?? '';
                }

                $rows[] = $row;

                if (
                    count($rows)
                    > self::MAX_IMPORT_ROWS
                        + 20
                ) {
                    break;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function xlsxSharedStrings(
        ZipArchive $zip
    ): array {
        $xmlContent =
            $zip->getFromName(
                'xl/sharedStrings.xml'
            );

        if (
            ! is_string($xmlContent)
            || $xmlContent === ''
        ) {
            return [];
        }

        $xml = simplexml_load_string(
            $xmlContent,
            \SimpleXMLElement::class,
            LIBXML_NONET
            | LIBXML_COMPACT
        );

        if ($xml === false) {
            return [];
        }

        $namespaces =
            $xml->getNamespaces(true);

        $mainNamespace =
            $namespaces[''] ?? '';

        $main = $mainNamespace !== ''
            ? $xml->children(
                $mainNamespace
            )
            : $xml;

        $values = [];

        foreach ($main->si as $item) {
            $values[] =
                $this->xlsxNodeText(
                    $item,
                    $mainNamespace
                );
        }

        return $values;
    }

    private function xlsxNodeText(
        \SimpleXMLElement $node,
        string $namespace
    ): string {
        $children = $namespace !== ''
            ? $node->children($namespace)
            : $node;

        if (isset($children->t)) {
            return (string)
                $children->t;
        }

        $text = '';

        foreach ($children->r as $run) {
            $runChildren =
                $namespace !== ''
                    ? $run->children(
                        $namespace
                    )
                    : $run;

            $text .= (string)
                ($runChildren->t ?? '');
        }

        return $text;
    }

    private function writePreviewPayload(
        array $rows,
        ?int $campaignId = null,
        array $extra = []
    ): string {
        $directory =
            $this->previewDirectory();

        File::ensureDirectoryExists(
            $directory
        );

        foreach (
            File::files($directory) as $file
        ) {
            if (
                $file->getMTime()
                < time() - 86400
            ) {
                @unlink(
                    $file->getPathname()
                );
            }
        }

        $token = bin2hex(
            random_bytes(20)
        );

        $payload = array_merge([
            'version' => 1,
            'created_at' => time(),
            'session_id' => session()->getId(),
            'user_id' => auth()->id(),
            'employee' => $this
                ->currentEmployeeName(),
            'campaign_id' => $campaignId,
            'rows' => $rows,
        ], $extra);

        File::put(
            $this->previewPath(
                $token
            ),
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_THROW_ON_ERROR
            )
        );

        return $token;
    }

    private function campaignFromRequest(
        Request $request,
        mixed $campaignId
    ): ?Campaign {
        $campaignId = (int) $campaignId;

        if ($campaignId <= 0) {
            return null;
        }

        $campaign = Campaign::query()
            ->findOrFail($campaignId);
        $actor = $request->user();

        abort_unless(
            $actor->isSuperAdmin()
            || (
                (int) $campaign->created_by_user_id === (int) $actor->id
                && $actor->hasPermission(
                    CrmPermission::CAMPAIGNS_CREATE
                )
            ),
            403
        );

        return $campaign;
    }

    private function previewDirectory(): string
    {
        return storage_path(
            'app/crm-v2/import-previews'
        );
    }

    private function previewPath(
        string $token
    ): string {
        return $this->previewDirectory()
            .DIRECTORY_SEPARATOR
            .$token
            .'.json';
    }

    private function parseUnsignedInteger(
        string $value,
        int $minimum,
        int $maximum,
        string $label,
        array &$errors
    ): ?int {
        $value = trim(
            $this->normalizeDigits(
                $value
            )
        );

        if ($value === '') {
            return null;
        }

        if (
            ! preg_match(
                '/^\d+$/',
                $value
            )
        ) {
            $errors[] =
                $label
                .' يجب أن يكون رقمًا صحيحًا.';

            return null;
        }

        $number = (int) $value;

        if (
            $number < $minimum
            || $number > $maximum
        ) {
            $errors[] =
                $label
                .' يجب أن يكون بين '
                .$minimum
                .' و '
                .$maximum
                .'.';

            return null;
        }

        return $number;
    }

    private function parseSolutionType(
        string $value,
        array &$errors
    ): ?string {
        $value =
            $this->normalizeToken(
                $value
            );

        if ($value === '') {
            return null;
        }

        $map = [
            'call_center' => 'call_center',
            'call center' => 'call_center',
            'callcenter' => 'call_center',
            'كول سنتر' => 'call_center',
            'erp' => 'erp',
        ];

        if (! isset($map[$value])) {
            $errors[] =
                'نوع النظام يجب أن يكون '
                .'Call Center أو ERP.';

            return null;
        }

        return $map[$value];
    }

    private function nullableText(
        string $value
    ): ?string {
        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }

    private function validateLength(
        string $value,
        int $maximum,
        string $label,
        array &$errors
    ): void {
        if (
            mb_strlen($value)
            > $maximum
        ) {
            $errors[] =
                $label
                .' يتجاوز الحد الأقصى '
                .$maximum
                .' حرف.';
        }
    }

    private function rowHasContent(
        array $row
    ): bool {
        foreach ($row as $value) {
            if (
                trim(
                    (string) $value
                ) !== ''
            ) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(
        string $value
    ): string {
        $value = $this
            ->normalizeToken(
                $value
            );

        $value = str_replace(
            [
                '_',
                '-',
            ],
            ' ',
            $value
        );

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            ) ?? $value
        );
    }

    private function normalizeToken(
        string $value
    ): string {
        $value = trim(
            mb_strtolower(
                $value
            )
        );

        $value = preg_replace(
            '/[\x{200E}\x{200F}\x{061C}]/u',
            '',
            $value
        ) ?? $value;

        return trim(
            preg_replace(
                '/\s+/u',
                ' ',
                $value
            ) ?? $value
        );
    }

    private function normalizePhone(
        string $value
    ): string {
        $value =
            $this->normalizeDigits(
                $value
            );

        return preg_replace(
            '/\D+/u',
            '',
            $value
        ) ?? '';
    }

    private function normalizeDigits(
        string $value
    ): string {
        return strtr(
            $value,
            [
                '٠' => '0',
                '١' => '1',
                '٢' => '2',
                '٣' => '3',
                '٤' => '4',
                '٥' => '5',
                '٦' => '6',
                '٧' => '7',
                '٨' => '8',
                '٩' => '9',
                '۰' => '0',
                '۱' => '1',
                '۲' => '2',
                '۳' => '3',
                '۴' => '4',
                '۵' => '5',
                '۶' => '6',
                '۷' => '7',
                '۸' => '8',
                '۹' => '9',
            ]
        );
    }

    private function exportColumns(): array
    {
        $columns = [
            'id' => 'رقم العميل',
            'name' => 'اسم العميل',
            'first_name' => 'الاسم الأول',
            'last_name' => 'الاسم الأخير',
            'phone' => 'الهاتف',
            'email' => 'البريد الإلكتروني',
            'company_name' => 'الشركة',
            'activity' => 'النشاط',
            'governorate' => 'المحافظة',
            'address' => 'العنوان',
            'users_count' => 'عدد المستخدمين',
            'branches_count' => 'عدد الفروع',
            'job_title' => 'المنصب',
            'source' => 'المصدر',
            'status' => 'الحالة',
            'stage' => 'المرحلة',
            'assigned_employee' => 'الموظف المسؤول',
            'next_follow_up_at' => 'المتابعة القادمة',
            'solution_type' => 'نوع النظام',
            'lines_count' => 'عدد الخطوط',
            'extensions' => 'الملحقات',
            'departments' => 'الأقسام',
            'quotation_sent' => 'عرض السعر مرسل',
            'quotation_file' => 'ملف عرض السعر',
            'disinterest_reason' => 'سبب عدم الاهتمام',
            'notes' => 'ملاحظات',
            'created_by' => 'أنشأ بواسطة',
            'created_at' => 'تاريخ الإضافة',
            'updated_at' => 'آخر تحديث',
        ];
        try {
            $customFields = PipelineStageField::query()
                ->where('is_active', true)
                ->where('binding_type', 'custom')
                ->with('stage:id,name_ar,code')
                ->orderBy('position')
                ->get();

            $existingLabelsNormalized = [];
            foreach ($columns as $k => $lbl) {
                $existingLabelsNormalized[$this->normalizeHeader((string) $lbl)] = $k;
            }

            foreach ($customFields as $field) {
                $rawLabel = trim((string) ($field->label_ar ?: ($field->label_en ?: $field->key)));
                $normLabel = $this->normalizeHeader($rawLabel);

                // If this label or concept is already covered by canonical columns, skip adding duplicate column
                if (
                    isset($existingLabelsNormalized[$normLabel])
                    || in_array($field->key, ['reason', 'disinterest_reason', 'lines_count'], true)
                    || str_starts_with($field->key, 'notes')
                    || str_contains($normLabel, 'عدمالاهتمام')
                    || str_contains($normLabel, 'عددالخطوط')
                    || str_contains($normLabel, 'ملاحظات')
                ) {
                    continue;
                }

                $colKey = 'custom_' . $field->key;
                if (! isset($columns[$colKey])) {
                    $columns[$colKey] = $rawLabel;
                    $existingLabelsNormalized[$normLabel] = $colKey;
                }
            }
        } catch (\Throwable) {
        }
        return $columns;
    }

    private function exportValue(
        Lead $lead,
        string $column
    ): string {
        if (str_starts_with($column, 'custom_')) {
            $fieldKey = substr($column, 7);

            $stageVal = $lead->relationLoaded('stageValues')
                ? $lead->stageValues->firstWhere('field_key', $fieldKey)
                : $lead->stageValues()->where('field_key', $fieldKey)->latest('created_at')->first();

            if ($stageVal !== null && $stageVal->value !== null && $stageVal->value !== '') {
                return (string) $stageVal->value;
            }

            $customFields = (array) ($lead->custom_fields ?? []);
            if (isset($customFields[$fieldKey]) && $customFields[$fieldKey] !== null && $customFields[$fieldKey] !== '') {
                return is_array($customFields[$fieldKey])
                    ? implode(', ', $customFields[$fieldKey])
                    : (string) $customFields[$fieldKey];
            }

            return '';
        }
        return match ($column) {

            'id' => (string) $lead->id,

            'name' => (string) $lead->name,

            'first_name' => (string)
                    $lead->first_name,

            'last_name' => (string)
                    $lead->last_name,

            'phone' => (string) $lead->phone,

            'email' => (string) $lead->email,

            'company_name' => (string)
                    $lead->company_name,

            'activity' => (string)
                    $lead->activity,

            'governorate' => (string)
                    $lead->governorate,

            'address' => (string)
                    $lead->address,

            'users_count' => $lead->users_count === null
                    ? ''
                    : (string)
                        $lead->users_count,

            'branches_count' => $lead->branches_count === null
                    ? ''
                    : (string)
                        $lead->branches_count,

            'job_title' => (string)
                    $lead->job_title,

            'source' => (string)
                    $lead->source,

            'status' => (string) (
                $lead->status
                    ?->name_ar
                ?? ''
            ),

            'stage' => (string) (
                $lead->status
                    ?->stage
                    ?->name_ar
                ?? ''
            ),

            'assigned_employee' => (string) (
                $lead->assignedUser?->name
                ?? $lead->assigned_employee
            ),

            'next_follow_up_at' => $this->formatDate(
                $lead
                    ->next_follow_up_at
            ),

            'solution_type' => match (
                (string)
                    $lead->solution_type
            ) {
                'call_center' => 'Call Center',
                'erp' => 'ERP',
                default => (string)
                        $lead
                            ->solution_type,
            },

            'lines_count' => $lead->lines_count === null
                    ? ''
                    : (string)
                        $lead->lines_count,

            'extensions' => (string)
                    $lead->extensions,

            'departments' => (string)
                    $lead->departments,

            'quotation_sent' => $lead->quotation_sent
                    ? 'نعم'
                    : 'لا',

            'quotation_file' => $this
                ->quotationFileLabel(
                    $lead
                ),

            'disinterest_reason' => (string)
                    $lead
                        ->disinterest_reason,

            'notes' => (string) $lead->notes,

            'created_by' => (string)
                    $lead->created_by,

            'created_at' => $this->formatDate(
                $lead->created_at
            ),

            'updated_at' => $this->formatDate(
                $lead->updated_at
            ),

            default => '',
        };
    }

    private function quotationFileLabel(
        Lead $lead
    ): string {
        $path = trim(
            (string)
                $lead->quotation_file_path
        );

        if ($path === '') {
            return 'غير مرفوع';
        }

        try {
            if (
                Storage::disk('local')
                    ->exists($path)
            ) {
                return basename($path);
            }

            return 'المسار مسجل والملف غير موجود';
        } catch (\Throwable) {
            return 'تعذر التحقق من الملف';
        }
    }

    private function formatDate(
        mixed $value
    ): string {
        if (
            $value
            instanceof \DateTimeInterface
        ) {
            return $value->format(
                'Y-m-d H:i'
            );
        }

        return is_string($value)
            ? trim($value)
            : '';
    }

    private function downloadWorkbook(
        array $headers,
        array $rows,
        string $downloadName
    ): BinaryFileResponse {
        $this->assertXlsxSupport();

        $temporaryFile = tempnam(
            sys_get_temp_dir(),
            'crm-v2-transfer-'
        );

        if (
            ! is_string($temporaryFile)
            || $temporaryFile === ''
        ) {
            throw new \RuntimeException(
                'تعذر إنشاء ملف Excel المؤقت.'
            );
        }

        try {
            $this->writeWorkbook(
                $temporaryFile,
                $headers,
                $rows
            );

            return response()
                ->download(
                    $temporaryFile,
                    $downloadName,
                    [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                        'Cache-Control' => 'private, no-store, max-age=0',

                        'Pragma' => 'no-cache',

                        'X-Content-Type-Options' => 'nosniff',
                    ]
                )
                ->deleteFileAfterSend(
                    true
                );
        } catch (\Throwable $exception) {
            if (is_file($temporaryFile)) {
                @unlink($temporaryFile);
            }

            throw $exception;
        }
    }

    private function writeWorkbook(
        string $path,
        array $headers,
        array $rows
    ): void {
        if ($headers === []) {
            throw new \RuntimeException(
                'لا توجد أعمدة لملف Excel.'
            );
        }

        $zip = new ZipArchive;

        $result = $zip->open(
            $path,
            ZipArchive::CREATE
            | ZipArchive::OVERWRITE
        );

        if ($result !== true) {
            throw new \RuntimeException(
                'تعذر إنشاء ملف Excel.'
            );
        }

        try {
            $zip->addFromString(
                '[Content_Types].xml',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
 <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
 <Default Extension="xml" ContentType="application/xml"/>
 <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
 <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML
            );

            $zip->addFromString(
                '_rels/.rels',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML
            );

            $zip->addFromString(
                'xl/workbook.xml',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
 <sheets>
  <sheet name="Leads" sheetId="1" r:id="rId1"/>
 </sheets>
</workbook>
XML
            );

            $zip->addFromString(
                'xl/_rels/workbook.xml.rels',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
 <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML
            );

            $writer = new XMLWriter;

            $writer->openMemory();

            $writer->startDocument(
                '1.0',
                'UTF-8',
                'yes'
            );

            $writer->startElement(
                'worksheet'
            );

            $writer->writeAttribute(
                'xmlns',
                'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
            );

            $writer->startElement(
                'sheetViews'
            );

            $writer->startElement(
                'sheetView'
            );

            $writer->writeAttribute(
                'workbookViewId',
                '0'
            );

            $writer->writeAttribute(
                'rightToLeft',
                '1'
            );

            $writer->endElement();
            $writer->endElement();

            $writer->startElement(
                'sheetData'
            );

            $allRows = array_merge(
                [
                    $headers,
                ],
                $rows
            );

            foreach (
                $allRows as $rowIndex => $row
            ) {
                $excelRow =
                    $rowIndex + 1;

                $writer->startElement(
                    'row'
                );

                $writer->writeAttribute(
                    'r',
                    (string)
                        $excelRow
                );

                foreach (
                    array_values($row) as $columnIndex => $value
                ) {
                    $cellReference =
                        $this
                            ->xlsxColumnName(
                                $columnIndex
                            )
                        .$excelRow;

                    $writer->startElement(
                        'c'
                    );

                    $writer->writeAttribute(
                        'r',
                        $cellReference
                    );

                    $writer->writeAttribute(
                        't',
                        'inlineStr'
                    );

                    $writer->startElement(
                        'is'
                    );

                    $writer->startElement(
                        't'
                    );

                    $writer->text(
                        (string) $value
                    );

                    $writer->endElement();
                    $writer->endElement();
                    $writer->endElement();
                }

                $writer->endElement();
            }

            $writer->endElement();
            $writer->endElement();
            $writer->endDocument();

            $sheetXml =
                $writer->outputMemory();

            if (
                ! is_string($sheetXml)
                || $sheetXml === ''
            ) {
                throw new \RuntimeException(
                    'تعذر إنشاء ورقة Excel.'
                );
            }

            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                $sheetXml
            );
        } finally {
            $zip->close();
        }

        if (
            ! is_file($path)
            || filesize($path) === 0
        ) {
            throw new \RuntimeException(
                'ملف Excel الناتج فارغ.'
            );
        }
    }

    private function xlsxColumnName(
        int $index
    ): string {
        $index++;

        $name = '';

        while ($index > 0) {
            $remainder =
                ($index - 1) % 26;

            $name =
                chr(
                    65 + $remainder
                )
                .$name;

            $index = intdiv(
                $index - 1,
                26
            );
        }

        return $name;
    }

    private function xlsxColumnIndex(
        string $letters
    ): int {
        $letters =
            strtoupper($letters);

        $number = 0;

        foreach (
            str_split($letters) as $letter
        ) {
            $number =
                ($number * 26)
                + (
                    ord($letter)
                    - 64
                );
        }

        return max(
            0,
            $number - 1
        );
    }

    private function currentEmployeeName(): string
    {
        $name = trim(
            (string)
                auth()->user()->name
        );

        return $name !== ''
            ? $name
            : 'غير مسند';
    }

    private function assertXlsxSupport(): void
    {
        if (
            ! class_exists(
                ZipArchive::class
            )
            || ! class_exists(
                XMLWriter::class
            )
            || ! class_exists(
                \SimpleXMLElement::class
            )
        ) {
            throw new \RuntimeException(
                'دعم Excel غير متاح '
                .'على السيرفر.'
            );
        }
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
